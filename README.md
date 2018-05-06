#AGCOD means Amazon Gift Code On Demand.


Usage:
```php
//replace these configurations with your own settings in the \Amazon\Config\Account.php file
$partnerId = 'YourCompanyID';
$accessKey = 'findfromYourAwsAccountManagementPage';
$secretKey = 'findYourAwsAccountManagementPage';

//US Region Amazon Service API should instantiate like this:
$gcService = new GCServiceWrapper(\Amazon\Config\Region::US,TRUE);

$giftcard = $gcService->createGiftCode(3);//request for a USD$3 giftcard code

$gcService->cancelGiftCode($giftcard['gcId']); //cancel the card
```
