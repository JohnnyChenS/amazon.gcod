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
 *
 * $gcSerivce = new \Amazon\GCServiceDecorator('us');
 * $giftcard = $gcService->createGiftCode(5); //request for a USD$5 giftcard code
 *
 * $gcService->cancelGiftCode($giftcard['gcId']); //cancel the code by Code ID
 */
namespace Amazon;

class GCServiceDecorator extends GCService
{
    private $__isSandBox = FALSE;

    private $__regionConf = [
        'sandBox' => [
            'us' => [
                'regionCode'   => 'us-east-1',
                'host'         => 'host:agcod-v2-gamma.amazon.com',
                'endpoint'     => 'https://agcod-v2-gamma.amazon.com',
                'currencyCode' => 'USD',
            ],
        ],

        'prod' => [
            'us' => [
                'regionCode'   => 'us-east-1',
                'host'         => 'host:agcod-v2.amazon.com',
                'endpoint'     => 'https://agcod-v2.amazon.com',
                'currencyCode' => 'USD',
            ],
        ],
    ];

    public function __construct($region, $useSandBox = false) {
        $this->__isSandBox = $useSandBox;
        $envConf = $this->__useSandBox();

        parent::__construct($envConf[$region]['regionCode'], $envConf[$region]['host'], $envConf[$region]['endpoint'], $envConf[$region]['currencyCode']);
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

    private function __useSandBox() {
        if ($this->__isSandBox)
            return $this->__regionConf['sandBox'];
        else
            return $this->__regionConf['prod'];
    }
}