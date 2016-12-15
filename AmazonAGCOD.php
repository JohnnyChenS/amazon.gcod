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
 * $endpoint = 'host:agcod-v2-gamma.amazon.com';
 *
 * $uniqueRequestId = $partnerId.rand(0,99); //assign a unique request id for each request
 *
 * $gcod = new AmazonGCOD($partnerId,$accessKey,$secretKey,$regionCode,$host,$endpoint,$uniqueRequestId);
 * $giftcard = $gcod->createGiftCode(5); //request for a USD$5 giftcard code
 *
 * $gcod->cancelGiftCode($giftcard['gcId']); //cancel the code by Code ID
 */
class AmazonGCOD
{
    protected $_partnerId = '';
    protected $_accessKey = '';
    protected $_secretKey = '';
    protected $_regionCode = '';
    protected $_service = 'AGCODService';
    protected $_endpoint = '';
    protected $_host = '';

    protected $_timestampISO8601 = '';
    protected $_timestamp = '';
    protected $_requestId = '';

    public function __construct($partnerID, $accessKey, $secretKey, $regionCode, $host, $endpoint, $requestId) {
        $this->_partnerId  = $partnerID;
        $this->_accessKey  = $accessKey;
        $this->_secretKey  = $secretKey;
        $this->_regionCode = $regionCode;
        $this->_host       = $host;
        $this->_endpoint   = $endpoint;

        $time                    = date('Ymd\THis\Z', time() - date('Z'));
        $this->_timestampISO8601 = $time;
        $this->_timestamp        = substr($time, 0, 8);

        $str              = sprintf('%012s', $requestId);
        $this->_requestId = $this->_partnerId . $str;
    }

    public function createGiftCode($gc_amount) {
        $op = 'CreateGiftCard';
        // step1. gen json "PAYLOAD"
        $Data = [];

        $Data['creationRequestId'] = $this->_requestId;
        $Data['partnerId']         = $this->_partnerId;
        $Data['value']             = ['currencyCode' => 'USD', 'amount' => $gc_amount];

        $payload                = json_encode($Data);
        $payloadHashed          = $this->_hashPayload($payload);
        $CanonicalRequestHashed = $this->_hashCanonicalRequest($payloadHashed, $op);
        $signature              = $this->_generateSignature($CanonicalRequestHashed);
        $return_content         = $this->_sendRequest($signature, $payload, $op);

        // agcod 获取结果
        $gc = json_decode($return_content, TRUE);

        // 返回数据预处理
        $return = array(
            'status' => 'FAILURE',
            'code'   => '',
            'json'   => $return_content,
        );

        // 确认OK
        if ($gc['status'] == 'SUCCESS') {
            $return['status'] = $gc['status'];
            $return['code']   = $gc['gcClaimCode'];
        } elseif ($gc['errorCode'] == 'F300' && $gc['errorType'] == 'InsufficientFunds') {
            //insufficientFund error occur should notice me
        } else {
            //other error
        }

        return $return;
    }

    public function cancelGiftCode($amazonGcId) {
        $op = 'CancelGiftCard';

        $data                      = array();
        $data['partnerId']         = $this->_partnerId;
        $data['creationRequestId'] = $this->_requestId;
        $data['gcId']              = $amazonGcId;

        $payload                = json_encode($data);
        $payloadHashed          = $this->_hashPayload($payload);
        $CanonicalRequestHashed = $this->_hashCanonicalRequest($payloadHashed, $op);
        $signature              = $this->_generateSignature($CanonicalRequestHashed);
        $return_content         = $this->_sendRequest($signature, $payload, $op);

        // agcod 获取结果
        $gc = json_decode($return_content, TRUE);

        return $gc;
    }

    protected function _hashPayload($payload) {
        // step2. hash $payload
        $payloadHashed = hash('sha256', $payload);

        return $payloadHashed;
    }

    protected function _hashCanonicalRequest($payloadHashed, $op = 'CreateGiftCard') {
        // step3. gen string "CANONICAL REQUEST" with $payloadHashed
        $CanonicalRequest = "POST\n/{$op}\n\naccept:application/json\ncontent-type:application/json\n" . $this->_host . "\nx-amz-date:" . $this->_timestampISO8601 . "\nx-amz-target:com.amazonaws.agcod.AGCODService.{$op}\n\naccept;content-type;host;x-amz-date;x-amz-target\n" . $payloadHashed;
        // step4. hash $CanonicalRequest
        $CanonicalRequestHashed = hash('sha256', $CanonicalRequest);

        return $CanonicalRequestHashed;
    }

    protected function _generateSignature($CanonicalRequestHashed) {
        // step5. gen string "SIGN" with $CanonicalRequestHashed
        $string2sign = "AWS4-HMAC-SHA256\n" . $this->_timestampISO8601 . "\n" . $this->_timestamp . "/" . $this->_regionCode . "/" . $this->_service . "/aws4_request\n" . $CanonicalRequestHashed;

        // step6. make "SIGNING KEY"

        //$kSecret = SECRET_KEY
        $kDate    = hash_hmac('sha256', $this->_timestamp, 'AWS4' . $this->_secretKey, TRUE);
        $kRegion  = hash_hmac('sha256', $this->_regionCode, $kDate, TRUE);
        $kService = hash_hmac('sha256', $this->_service, $kRegion, TRUE);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, TRUE);

        // step7. "SIGNATURE" with $kSigning
        $signature = hash_hmac('sha256', $string2sign, $kSigning);

        return $signature;
    }

    protected function _sendRequest($signature, $payload, $op = 'CreateGiftCard') {
        $curl_head = array(
            'accept:application/json',
            'content-type:application/json',
            $this->_host,
            'x-amz-date:' . $this->_timestampISO8601,
            'x-amz-target:com.amazonaws.agcod.AGCODService.' . $op,
            'Authorization:AWS4-HMAC-SHA256 Credential=' . $this->_accessKey . '/' . $this->_timestamp . '/us-east-1/AGCODService/aws4_request, SignedHeaders=accept;content-type;host;x-amz-date;x-amz-target,Signature=' . $signature,
        );

        // get gc
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->_endpoint . $op);
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_head);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        $return_content = curl_exec($ch);

        curl_close($ch);

        return $return_content;
    }

}