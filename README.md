#AGCOD means Amazon Gift Code On Demand.

Install:
```
composer require johnnychens/amazon.gcod
```

Usage:
```php
//initiate the \Amazon\Config\Account class with with your own settings:
$partnerId = 'YourCompanyID';
$accessKey = 'findfromYourAwsAccountManagementPage';
$secretKey = 'findYourAwsAccountManagementPage';

$account = new \Amazon\Config\Account($partnerId, $accessKey, $secretKey);

//initiate the \Amazon\Config\Region class:
$region = new \Amazon\Config\Region(\Amazon\Config\Region::US, TRUE);

//Amazon Service API should instantiate like this:
$gcService = new GCServiceWrapper($account, $region);

$giftcard = $gcService->createGiftCode(3);//request for a USD$3 giftcard code

$gcService->cancelGiftCode($giftcard['gcId']); //cancel the card
```
