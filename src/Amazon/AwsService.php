<?php
/**
 * Created by PhpStorm.
 * User: johnny
 * Date: 2018/4/6
 * Time: 17:55
 */

namespace Amazon;


class AwsService
{
    const __SERVICE_NAME__ = 'AGCODService';

    private $__regionCode;
    private $__endpoint;
    private $__host;
    private $__currency;

    public function __construct($regionCode, $host, $endpoint, $currency) {
        $this->__regionCode = $regionCode;
        $this->__endpoint   = $endpoint;
        $this->__host       = $host;
        $this->__currency   = $currency;
    }

    /**
     * @return mixed
     */
    function getCurrency() {
        return $this->__currency;
    }


    function hashPayload($payload) {
        return hash('sha256', $payload);
    }

    function hashCanonicalRequest($hashedPayload, $op, $iso8601FormattedDateTime) {
        // step3. gen string "CANONICAL REQUEST" with $hashedPayload;
        $canonicalRequest = "POST\n" .
            "/{$op}\n" .
            "\naccept:application/json" .
            "\ncontent-type:application/json" .
            "\n" . $this->__host .
            "\nx-amz-date:" . $iso8601FormattedDateTime .
            "\nx-amz-target:com.amazonaws.agcod.AGCODService.{$op}\n" .
            "\naccept;content-type;host;x-amz-date;x-amz-target\n" .
            $hashedPayload;
        //step4. hash $CanonicalRequest
        return hash('sha256', $canonicalRequest);
    }

    function generateSignature($hashedCanonicalRequest, $iso8601FormattedDateTime) {
        // step5. gen string "SIGN" with $CanonicalRequestHashed
        $str2sign = "AWS4-HMAC-SHA256\n" .
            $iso8601FormattedDateTime . "\n" .
            $this->__convertIso8601TimeFormat2DateTime($iso8601FormattedDateTime) . "/" .
            $this->__regionCode . "/" .
            self::__SERVICE_NAME__ . "/aws4_request\n" .
            $hashedCanonicalRequest;
        // step6. make "SIGNING KEY"
        $kDate    = hash_hmac('sha256', $this->__convertIso8601TimeFormat2DateTime($iso8601FormattedDateTime), 'AWS4' . Config\Account::getSecretKey(), TRUE);
        $kRegion  = hash_hmac('sha256', $this->__regionCode, $kDate, TRUE);
        $kService = hash_hmac('sha256', self::__SERVICE_NAME__, $kRegion, TRUE);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, TRUE);
        // step7. "SIGNATURE" with $kSigning
        $signature = hash_hmac('sha256', $str2sign, $kSigning);

        return $signature;
    }

    function sendRequest($payload, $signature, $op, $iso8601FormattedDateTime) {
        // get gc
        return $this->__post($this->__endpoint . $op, $this->__generateHeaders($signature, $op, $iso8601FormattedDateTime), $payload);
    }

    private function __generateHeaders($signature, $op, $iso8601FormattedDateTime) {
        return [
            'accept:application/json',
            'content-type:application/json',
            $this->__host,
            'x-amz-date:' . $iso8601FormattedDateTime,
            'x-amz-target:com.amazonaws.agcod.AGCODService.' . $op,
            'Authorization:AWS4-HMAC-SHA256 Credential=' . Config\Account::getAccessKey() .
            '/' . $this->__convertIso8601TimeFormat2DateTime($iso8601FormattedDateTime) .
            '/us-east-1/AGCODService/aws4_request, SignedHeaders=accept;content-type;host;x-amz-date;x-amz-target,Signature=' .
            $signature,
        ];
    }

    private function __post($url, $header, $postData) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    private function __convertIso8601TimeFormat2DateTime($iso8601FormattedDateTime) {
        return substr($iso8601FormattedDateTime, 0, 8);
    }
}