<?php

/**
 * @author Johnny Chen <chz0321@gmail.com>
 * @desc This class is for making a request to Amazon GiftCode on Demand service API.
 *       amazon agcod official doc: https://s3.amazonaws.com/AGCOD/tech_spec/AGCODTechSpec.pdf
 *
 * sample code:
 *
 * //initialize the \Amazon\Config\Account class with with your own settings
 *
 * $__partnerId = 'YourCompanyID';
 * $__accessKey = 'findFromYourAwsAccountManagementPage';
 * $__secretKey = 'findYourAwsAccountManagementPage';
 *
 * $account = new \Amazon\Config\Account($__partnerId, $__accessKey, $__secretKey);
 * $region = new \Amazon\Config\Region(\Amazon\Config\Region::US);
 *
 * $gcService = new \Amazon\GCServiceWrapper($account, $region);
 * $giftCard = $gcService->createGiftCode(5); //request for a USD$5 gift card code
 *
 * $gcService->cancelGiftCode($giftCard['gcId']); //cancel the code by Code ID
 */
namespace Amazon;

class GCServiceWrapper
{
    const __SERVICE_NAME__ = 'AGCODService';
    const __SERVICE_TARGET__ = 'com.amazonaws.agcod';

    private $__awsService;
    private $__account;

    public function __construct(Config\Account $myAccount, Config\Region $myRegion) {
        $this->__account = $myAccount;

        $this->__awsService = new AwsService($myRegion->getRegionCode(), $myRegion->getHost(), $myRegion->getEndPoint(), $myRegion->getCurrencyCode(), self::__SERVICE_NAME__, self::__SERVICE_TARGET__, $myAccount->getSecretKey(), $myAccount->getAccessKey());
    }

    /**
     * @param $gcAmount
     * @return mixed
     */
    public function createGiftCode($gcAmount) {
        $op                       = 'CreateGiftCard';
        $currentTimestamp         = time();
        $iso8601FormattedDateTime = $this->__getIso8601TimeFormat($currentTimestamp);

        $data                      = [];
        $data['creationRequestId'] = $this->__generateRequestId();
        $data['partnerId']         = $this->__account->getPartnerId();
        $data['value']             = ['currencyCode' => $this->__awsService->getCurrency(), 'amount' => $gcAmount];

        $payload                = json_encode($data); // step1. gen json "PAYLOAD"
        $hashedPayload          = $this->__awsService->hashPayload($payload); // step2. hash $payload
        $hashedCanonicalRequest = $this->__awsService->hashCanonicalRequest($hashedPayload, $op, $iso8601FormattedDateTime);
        $signature              = $this->__awsService->generateSignature($hashedCanonicalRequest, $iso8601FormattedDateTime);

        return json_decode($this->__awsService->sendRequest($payload, $signature, $op, $iso8601FormattedDateTime), TRUE);
    }

    public function cancelGiftCode($codeId) {
        $op                       = 'CancelGiftCard';
        $currentTimestamp         = time();
        $iso8601FormattedDateTime = $this->__getIso8601TimeFormat($currentTimestamp);

        $data                      = [];
        $data['creationRequestId'] = $this->__generateRequestId();
        $data['partnerId']         = $this->__account->getPartnerId();
        $data['gcId']              = $codeId;

        $payload                = json_encode($data);
        $hashedPayload          = $this->__awsService->hashPayload($payload);
        $hashedCanonicalRequest = $this->__awsService->hashCanonicalRequest($hashedPayload, $op, $iso8601FormattedDateTime);
        $signature              = $this->__awsService->generateSignature($hashedCanonicalRequest, $iso8601FormattedDateTime);

        return json_decode($this->__awsService->sendRequest($payload, $signature, $op, $iso8601FormattedDateTime), TRUE);
    }

    private function __generateRequestId() {
        return sprintf("%012s", $this->__account->getPartnerId() . substr(microtime(TRUE) * 10000, -7));
    }

    private function __getIso8601TimeFormat($timestamp) {
        return date('Ymd\THis\Z', $timestamp - date('Z', $timestamp));
    }
}