# php-apk-download
Just fill account data.
Type *#*#8255#*#* on your android device to get Device_ID

```php
$d = new GStoreApkDownload($email, $password, $device_id);
file_put_contents('com.facebook.katana.apk', $d->getPackage('com.facebook.katana'));
```
