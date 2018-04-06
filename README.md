#AGCOD means Amazon Gift Code On Demand.


Usage:
```php
//replace these configurations with your own settings in the \Amazon\Config file
$partnerId = 'YourCompanyID';
$accessKey = 'findfromYourAwsAccountManagementPage';
$secretKey = 'findYourAwsAccountManagementPage';

//US Region Amazon Service API should use these configuration
$regionCode = 'us-east-1';
$host = 'host:agcod-v2-gamma.amazon.com';
$endpoint = 'https://agcod-v2-gamma.amazon.com';


$gcService = new GCServiceDecorator($regionCode, $host, $endpint, 'USD');

$giftcard = $gcService->createGiftCode(3);//request for a USD$3 giftcard code

$gcService->cancelGiftCode($giftcard['gcId']); //cancel the card
```
