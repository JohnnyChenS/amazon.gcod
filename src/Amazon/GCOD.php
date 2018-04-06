<?php

/**
 * @author Johnny Chen <chz0321@gmail.com>
 * @desc This class is for making a request to Amazon GiftCode on Demand service API.
 *       amazon agcod official doc: https://s3.amazonaws.com/AGCOD/tech_spec/AGCODTechSpec.pdf
 *
 * sample code:
 *
 * $partnerId = 'YourCompanyID';
 * $accessKey = 'findfromYourAwsAccountManagementPage';
 * $secretKey = 'findYourAwsAccountManagementPage';
 * $regionCode = 'us-east-1'; //your aws server region
 * $host = 'host:agcod-v2-gamma.amazon.com';
 * $endpoint = 'https://agcod-v2-gamma.amazon.com';
 *
 * $uniqueRequestId = $partnerId.rand(0,99); //assign a unique request id for each request
 *
 * $gcod = new AmazonGCOD($partnerId,$accessKey,$secretKey,$regionCode,$host,$endpoint,$uniqueRequestId);
 * $giftcard = $gcod->createGiftCode(5); //request for a USD$5 giftcard code
 *
 * $gcod->cancelGiftCode($giftcard['gcId']); //cancel the code by Code ID
 */
namespace Amazon;

class GCOD
{
    private $__service;

    public function __construct(GCService $service) {
        $this->__service = $service;
    }

    /**
     * @param $gcAmount
     * @return mixed
     */
    public function createGiftCode($gcAmount) {
        $currentTimestamp = time();
        $iso8601FormattedDateTime = $this->__getIso8601TimeFormat($currentTimestamp);

        $op = 'CreateGiftCard';
        // step1. gen json "PAYLOAD"
        $data                      = [];
        $data['creationRequestId'] = $this->__generateRequestId();
        $data['partnerId']         = Config::getPartnerId();
        $data['value']             = ['currencyCode' => $this->__service->getCurrency(), 'amount' => $gcAmount];

        $payload                = json_encode($data);
        $hashedPayload          = $this->__service->hashPayload($payload);
        $hashedCanonicalRequest = $this->__service->hashCanonicalRequest($hashedPayload, $op, $iso8601FormattedDateTime);
        $signature              = $this->__service->generateSignature($hashedCanonicalRequest, $iso8601FormattedDateTime);

        return json_decode($this->__service->sendRequest($payload, $signature, $op, $iso8601FormattedDateTime), TRUE);
    }

    public function cancelGiftCode($codeId) {
        $currentTimestamp = time();
        $iso8601FormattedDateTime = $this->__getIso8601TimeFormat($currentTimestamp);

        $op = 'CancelGiftCard';

        $data                      = [];
        $data['creationRequestId'] = $this->__generateRequestId();
        $data['partnerId']         = Config::getPartnerId();
        $data['gcId']              = $codeId;

        $payload                = json_encode($data);
        $hashedPayload          = $this->__service->hashPayload($payload);
        $hashedCanonicalRequest = $this->__service->hashCanonicalRequest($hashedPayload, $op, $iso8601FormattedDateTime);
        $signature              = $this->__service->generateSignature($hashedCanonicalRequest, $iso8601FormattedDateTime);

        return json_decode($this->__service->sendRequest($payload, $signature, $op, $iso8601FormattedDateTime), TRUE);
    }

    private function __generateRequestId() {
        return Config::getPartnerId() . sprintf("%012s", microtime(TRUE) * 1000);
    }

    private function __getIso8601TimeFormat($timestamp){
        return date('Ymd\THis\Z', $timestamp - date('Z',$timestamp));
    }
}