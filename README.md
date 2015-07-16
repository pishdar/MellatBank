## Mellat Bank
#### کلاس درگاه پرداخت بانک ملت برای گسترش دهندگان وب
---
##### روش نصب
* ۱- جهت استفاده از این کلاس، اول باید آن را به برنامه‌ی خود ملحق کنید، مثلا:
```php
require_once(__DIR__ .'/path-to-package/MellatBank/MellatBank.php');
```
> در صورتی که شما از یک نرم افزار مدیریت مخزن استفاده میکنید،
> معمولا این مرحله به صورت خودکار انجام میشود.

* ۲- سپس شما میتوانید با استفاده از اطلاعات هویتی خود، یک شئی از کلاس بانک ملت بسازید:
```php
/**
 * @param intiger $terminal : Bankmellat Terminal ID (int)
 * @param string $username : Bankmellat Username (string)
 * @param string $password : Bankmellat Password (string)
 */
$mellat = new MellatBank($terminal, $username, $password);
```
* ۳- پس از تنظیم اولیه، کاربر با استفاده از این روش به درگاه بانک منتقل میشود:
```php
/**
 * @param intiger $amount : مبلغ پرداخت
 * @param string $callBackUrl : آدرس برگشت بعد از پرداخت
 */
$mellat->startPayment($amount, $callBackUrl);
```
* ۴- در صفحه برگشت از بانک، شما میتوانید به این روش موفقیت یا عدم موفقیت پراخت را چک کنید:
```php
  $results = $mellat->checkPayment($_POST);
  if($results['status']=='success') {
	  # تراکنش با موفقیت انجام شده است.
	  echo $results['trans'] ; # شماره تراکنش
  }
  else {
	  # تراکنش موفق نبوده است .
	  die(var_dum($results));
  }
```