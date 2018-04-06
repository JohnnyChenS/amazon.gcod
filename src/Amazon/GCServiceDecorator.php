<?php

/**
 * @author Johnny Chen <chz0321@gmail.com>
 * @desc This class is for making a request to Amazon GiftCode on Demand service API.
 *       amazon agcod official doc: https://s3.amazonaws.com/AGCOD/tech_spec/AGCODTechSpec.pdf
 *
 * sample code:
 *
 * //replace these configurations with your own settings in the \Amazon\Config file
 * $__partnerId = 'YourCompanyID';
 * $__accessKey = 'findfromYourAwsAccountManagementPage';
 * $__secretKey = 'findYourAwsAccountManagementPage';
 *
 * //then instantiate the GCServiceDecorator with the specific api region:
 * $regionCode = 'us-east-1'; //your aws server region
 * $host = 'host:agcod-v2-gamma.amazon.com';
 * $endpoint = 'https://agcod-v2-gamma.amazon.com';
 *
 * $gcSerivce = new \Amazon\GCServiceDecorator($regionCode,$host,$endpoint,'USD');
 * $giftcard = $gcService->createGiftCode(5); //request for a USD$5 giftcard code
 *
 * $gcService->cancelGiftCode($giftcard['gcId']); //cancel the code by Code ID
 */
namespace Amazon;

class GCServiceDecorator extends GCService
{

    public function __construct($regionCode, $host, $endpoint, $currency) {
        parent::__construct($regionCode, $host, $endpoint, $currency);
    }

    /**
     * @param $gcAmount
     * @return mixed
     */
    public function createGiftCode($gcAmount) {
        $op                       = 'CreateGiftCard';
        $currentTimestamp         = time();
        $iso8601FormattedDateTime = $this->__getIso8601TimeFormat($currentTimestamp);

        // step1. gen json "PAYLOAD"
        $data                      = [];
        $data['creationRequestId'] = $this->__generateRequestId();
        $data['partnerId']         = Config::getPartnerId();
        $data['value']             = ['currencyCode' => $this->getCurrency(), 'amount' => $gcAmount];

        $payload                = json_encode($data);
        $hashedPayload          = $this->hashPayload($payload);
        $hashedCanonicalRequest = $this->hashCanonicalRequest($hashedPayload, $op, $iso8601FormattedDateTime);
        $signature              = $this->generateSignature($hashedCanonicalRequest, $iso8601FormattedDateTime);

        return json_decode($this->sendRequest($payload, $signature, $op, $iso8601FormattedDateTime), TRUE);
    }

    public function cancelGiftCode($codeId) {
        $op                       = 'CancelGiftCard';
        $currentTimestamp         = time();
        $iso8601FormattedDateTime = $this->__getIso8601TimeFormat($currentTimestamp);

        $data                      = [];
        $data['creationRequestId'] = $this->__generateRequestId();
        $data['partnerId']         = Config::getPartnerId();
        $data['gcId']              = $codeId;

        $payload                = json_encode($data);
        $hashedPayload          = $this->hashPayload($payload);
        $hashedCanonicalRequest = $this->hashCanonicalRequest($hashedPayload, $op, $iso8601FormattedDateTime);
        $signature              = $this->generateSignature($hashedCanonicalRequest, $iso8601FormattedDateTime);

        return json_decode($this->sendRequest($payload, $signature, $op, $iso8601FormattedDateTime), TRUE);
    }

    private function __generateRequestId() {
        return sprintf("%012s", Config::getPartnerId() . substr(microtime(TRUE) * 10000, -7));
    }

    private function __getIso8601TimeFormat($timestamp) {
        return date('Ymd\THis\Z', $timestamp - date('Z', $timestamp));
    }
}