# php-apk-download
usage:
```php
$packs = ['com.facebook.katana', 'com.instagram.android'];

$d = new GStoreApkDownload('account@gmail.com', 'password_to_account', 'android_device_id');

foreach ($packs as $p) {

	file_put_contents($p . '.apk', $d->getPackage($p));
	
}
```
