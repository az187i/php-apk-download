<?php
class GStoreApkDownload
{
  public $api_url = "https://android.clients.google.com/market/api/ApiRequest";
  public $auth_token = null;

  private $_user = '';
  private $_password = '';
  private $_device_id = '';

  public function __construct($user, $password, $device_id)
  {
    $this->_user = $user;
    $this->_password = $password;
    $this->_device_id = $device_id;
  }

  private function _login()
  {
    $login = curl_init("https://www.google.com/accounts/ClientLogin");
    $post = ['Email=' . $this->_user, 'Passwd=' . $this->_password, 'service=androidsecure', 'accountType=HOSTED_OR_GOOGLE'];
    $post = implode('&', $post);
    curl_setopt($login, CURLOPT_POST, 1);
    curl_setopt($login, CURLOPT_POSTFIELDS, $post);
    curl_setopt($login, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($login, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($login, CURLOPT_SSL_VERIFYHOST, 0);
    $response = curl_exec($login);
    if (!$response) echo curl_error($login);
    if (preg_match("/Auth=([a-zA-Z0-9=_\-]+)/", $response, $tokens)) {
      return $tokens[1];
    } else {
      return false;
    }
  }

  private function _getAuthToken()
  {
    if (empty($this->auth_token)) {
      return $this->_login();
    } else {
      return $this->auth_token;
    }
  }

  private static function _generateAssetRequest($options)
  {
    $FIELD_AUTHTOKEN = 0;
    $FIELD_ISSECURE = 2;
    $FIELD_SDKVERSION = 4;
    $FIELD_DEVICEID = 6;
    $FIELD_DEVICEANDSDKVERSION = 8;
    $FIELD_LOCALE = 10;
    $FIELD_COUNTRY = 12;
    $FIELD_OPERATORALPHA = 14;
    $FIELD_SIMOPERATORALPHA = 16;
    $FIELD_OPERATORNUMERIC = 18;
    $FIELD_SIMOPERATORNUMERIC = 20;
    $FIELD_PACKAGENAME_LENGTH = 22;
    $FIELD_PACKAGENAME = 24;
    $desc = [
      $FIELD_AUTHTOKEN
      , [0x10], $FIELD_ISSECURE
      , [0x18], $FIELD_SDKVERSION
      , [0x22], $FIELD_DEVICEID
      , [0x2A], $FIELD_DEVICEANDSDKVERSION
      , [0x32], $FIELD_LOCALE
      , [0x3A], $FIELD_COUNTRY
      , [0x42], $FIELD_OPERATORALPHA
      , [0x4A], $FIELD_SIMOPERATORALPHA
      , [0x52], $FIELD_OPERATORNUMERIC
      , [0x5A], $FIELD_SIMOPERATORNUMERIC
      , [0x13]
      , [0x52], $FIELD_PACKAGENAME_LENGTH
      , [0x0A], $FIELD_PACKAGENAME
      , [0x14]
    ];
    $out = [];
    $simOperatorLength = 0;
    for ($i = 0; $i < count($desc); $i++) {
      if (is_array($desc[$i])) {
        $out = array_merge($out, $desc[$i]);
        continue;
      }
      switch ($desc[$i]) {
        case $FIELD_AUTHTOKEN:
          $out = self::_serializeData($out, $options["authToken"], "string");
          break;
        case $FIELD_ISSECURE:
          $out = self::_serializeData($out, $options["isSecure"], "bool");
          break;
        case $FIELD_SDKVERSION:
          $out = self::_serializeData($out, $options["sdkVersion"], "int32");
          break;
        case $FIELD_DEVICEID:
          $out = self::_serializeData($out, $options["deviceId"], "string");
          break;
        case $FIELD_DEVICEANDSDKVERSION:
          $out = self::_serializeData($out, $options["deviceAndSdkVersion"], "string");
          break;
        case $FIELD_LOCALE:
          $out = self::_serializeData($out, $options["locale"], "string");
          break;
        case $FIELD_COUNTRY:
          $out = self::_serializeData($out, $options["country"], "string");
          break;
        case $FIELD_OPERATORALPHA:
          $out = self::_serializeData($out, $options["operatorAlpha"], "string");
          break;
        case $FIELD_SIMOPERATORALPHA:
          $out = self::_serializeData($out, $options["simOperatorAlpha"], "string");
          break;
        case $FIELD_OPERATORNUMERIC:
          $out = self::_serializeData($out, $options["operatorNumeric"], "string");
          break;
        case $FIELD_SIMOPERATORNUMERIC:
          $out = self::_serializeData($out, $options["simOperatorNumeric"], "string");
          $simOperatorLength = count($out) + 1;
          break;
        case $FIELD_PACKAGENAME_LENGTH:
          $out = array_merge($out, self::_serializeInt32(strlen($options["packageName"]) + 2));
          break;
        case $FIELD_PACKAGENAME:
          $out = self::_serializeData($out, $options["packageName"], "string");
          break;
      }
    }
    $newout = [0x0A];
    $newout = array_merge($newout, self::_serializeInt32($simOperatorLength));
    $newout = array_merge($newout, [0x0A]);
    $newout = array_merge($newout, $out);
    $out = $newout;
    $out = array_map("chr", $out);
    $binary = implode('', $out);
    return base64_encode($binary);
  }

  private static function _serializeData($arr, $value, $dataType)
  {
    $newData = [];
    switch ($dataType) {
      case 'string':
        $newData = array_merge($newData, self::_serializeInt32(strlen($value)));
        $newData = array_merge($newData, self::_stringToByteArray($value));
        break;
      case 'int32':
        $newData = array_merge($newData, self::_serializeInt32($value));
        break;
      case 'bool':
        $newData[] = $value ? 1 : 0;
        break;
    }
    return array_merge($arr, $newData);
  }

  private static function _serializeInt32($num)
  {
    $data = [];
    for ($i = 0; $i < 5; $i++) {
      $elm = $num % 128;
      if ($num >>= 7) {
        $elm+= 128;
      }
      $data[] = $elm;
      if ($num == 0) {
        break;
      }
    }
    return $data;
  }

  private static function _stringToByteArray($str)
  {
    $b = [];
    for ($pos = 0; $pos < strlen($str); ++$pos) {
      $b[] = ord($str[$pos]);
    }
    return $b;
  }

  public function getPackage($packageName)
  {
    if ($auth_token = $this->_getAuthToken()) {
      $options = ['authToken' => $auth_token, 'isSecure' => 'true', 'sdkVersion' => '2009011', 'deviceId' => $this->_device_id, 'deviceAndSdkVersion' => "hammerhead:16", 'locale' => 'en', 'country' => 'us', 'operatorAlpha' => 'T-Mobile', 'simOperatorAlpha' => 'T-Mobile', 'operatorNumeric' => '31020', 'simOperatorNumeric' => '31020', 'packageName' => $packageName];
      $post = 'version=2&request=' . self::_generateAssetRequest($options);
      $session = curl_init($this->api_url);
      curl_setopt($session, CURLOPT_POST, true);
      curl_setopt($session, CURLOPT_POSTFIELDS, $post);
      curl_setopt($session, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($session, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($session, CURLOPT_SSL_VERIFYHOST, 0);
      $response = curl_exec($session);
      preg_match("/https?:\/\/.*?downloadId=[0-9\-\_]+/i", gzdecode($response) , $links);
      preg_match("/MarketDA..(\d+)/", gzdecode($response) , $marketda);
      curl_setopt($session, CURLOPT_URL, $links[0]);
      curl_setopt($session, CURLOPT_COOKIE, 'MarketDA=' . $marketda[1] . '; path=/; domain=.android.clients.google.com; false');
      curl_setopt($session, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($session, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($session, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt($session, CURLOPT_FOLLOWLOCATION, 1);
      return curl_exec($session);
    } else {
      return false;
    }
  }
}
