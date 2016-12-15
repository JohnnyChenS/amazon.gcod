#AGCOD means Amazon Gift Code On Demand.


Usage:
```php
$partnerId = 'YourCompanyID';
$accessKey = 'findfromYourAwsAccountManagementPage';
$secretKey = 'findYourAwsAccountManagementPage';
$regionCode = 'us-east-1';
$host = 'host:agcod-v2-gamma.amazon.com';
$endpoint = 'host:agcod-v2-gamma.amazon.com';

$uniqueRequestId = $partnerId.rand(0,99); //assign a unique request id for each request

$gcod = new AmazonGCOD($partnerId,$accessKey,$secretKey,$regionCode,$host,$endpoint,$uniqueRequestId);
$giftcard = $gcod->createGiftCode(5); //request for a USD$5 giftcard code

$gcod->cancelGiftCode($giftcard['gcId']); //
```