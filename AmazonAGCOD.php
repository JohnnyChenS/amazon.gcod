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
 * $regionCode = 'us-east-1';
 * $host = 'host:agcod-v2-gamma.amazon.com';
 * $endpoint = 'host:agcod-v2-gamma.amazon.com';
 *
 * $uniqueRequestId = $partnerId.rand(0,99); //assign a unique request id for each request
 * 
 * $gcod = new AmazonGCOD($partnerId,$accessKey,$secretKey,$regionCode,$host,$endpoint,$uniqueRequestId);
 * $giftcard = $gcod->createGiftCode(5); //request for a USD$5 giftcard code
 *
 * $gcod->cancelGiftCode($giftcard['gcId']); //
 */

class AmazonGCOD{	
	private $a_PartnerId = '';
	private $a_AccessKey = '';
	private $a_SecretKey = '';
	private $a_RegionCode = '';
	private $a_ServiceName = 'AGCODService';
	private $a_Endpoint = '';
	private $a_Host = '';
	
	private $a_Timestamp_ISO8601 = '';
	private $a_Timestamp = '';
	private $a_RequestId = '';
	
    public function __construct($partnerID,$accessKey,$secretKey,$regionCode,$host,$endpoint,$requestId)
    {
        $this->a_PartnerId = $partnerID;
        $this->a_AccessKey = $accessKey;
        $this->a_SecretKey = $secretKey;
        $this->a_RegionCode = $regionCode;
        $this->a_Host      = $host;
        $this->a_Endpoint  = $endpoint;

    	$time = date('Ymd\THis\Z',time() - date('Z'));
    	$this->a_Timestamp_ISO8601 = $time;
    	$this->a_Timestamp = substr($time,0,8);
    	
    	$str = sprintf('%012s', $requestId);
    	$this->a_RequestId = $this->a_PartnerId . $str;
    }
    
    public function createGiftCode($gc_amount) {
        $op = 'CreateGiftCard';
        // step1. gen json "PAYLOAD"
        $Data = array();

        $Data['creationRequestId'] = $this->a_RequestId;
        $Data['partnerId'] = $this->a_PartnerId;
        $Data['value'] = array('currencyCode' => 'USD', 'amount' => $gc_amount);
        
        $payload = json_encode($Data);

    	$payloadHashed = $this->hashPayload($payload);

    	$CanonicalRequestHashed = $this->hashCanonicalRequest($payloadHashed,$op);

    	$signature = $this->generateSignature($CanonicalRequestHashed);

    	$return_content = $this->sendRequest($signature,$payload,$op);
    	
        // agcod 获取结果
        $gc = json_decode($return_content, true);
    	
    	// 返回数据预处理
    	$return = array(
    		'status' => 'FAILURE',
    		'code' => '',
    		'json' => $return_content,
    	);
    	
    	// 确认OK
		if($gc['status'] == 'SUCCESS') {
			$return['status'] = $gc['status'];
			$return['code'] = $gc['gcClaimCode'];
		}elseif($gc['errorCode'] == 'F300' && $gc['errorType'] == 'InsufficientFunds'){
            //insufficientFund error occur should notice me
        }else{
            //other error
        }
    	
    	return $return;
    }

    public function cancelGiftCode($amazonGcId){
        $op = 'CancelGiftCard';

        $data = array();
        $data['partnerId'] = $this->a_PartnerId;
        $data['creationRequestId'] = $this->a_RequestId;
        $data['gcId'] = $amazonGcId;

        $payload = json_encode($data);

        $payloadHashed = $this->hashPayload($payload);

        $CanonicalRequestHashed = $this->hashCanonicalRequest($payloadHashed,$op);

        $signature = $this->generateSignature($CanonicalRequestHashed);

        $return_content = $this->sendRequest($signature,$payload,$op);
        
        // agcod 获取结果
        $gc = json_decode($return_content, true);

        return $gc;
    }

    private function hashPayload($payload){
        // step2. hash $payload
        $payloadHashed = hash('sha256',$payload);

        return $payloadHashed;
    }

    private function hashCanonicalRequest($payloadHashed,$op = 'CreateGiftCard'){
        // step3. gen string "CANONICAL REQUEST" with $payloadHashed
        $CanonicalRequest = "POST\n/{$op}\n\naccept:application/json\ncontent-type:application/json\n".$this->a_Host."\nx-amz-date:".$this->a_Timestamp_ISO8601."\nx-amz-target:com.amazonaws.agcod.AGCODService.{$op}\n\naccept;content-type;host;x-amz-date;x-amz-target\n".$payloadHashed;

        // step4. hash $CanonicalRequest
        $CanonicalRequestHashed = hash('sha256',$CanonicalRequest);

        return $CanonicalRequestHashed;
    }

    private function generateSignature($CanonicalRequestHashed){
        // step5. gen string "SIGN" with $CanonicalRequestHashed
        $string2sign = "AWS4-HMAC-SHA256\n".$this->a_Timestamp_ISO8601."\n".$this->a_Timestamp."/".$this->a_RegionCode."/".$this->a_ServiceName."/aws4_request\n".$CanonicalRequestHashed;

        // step6. make "SIGNING KEY"
         
        //$kSecret = SECRET_KEY
        $kDate = hash_hmac('sha256', $this->a_Timestamp, 'AWS4'.$this->a_SecretKey, true);
        $kRegion = hash_hmac('sha256', $this->a_RegionCode, $kDate, true);
        $kService = hash_hmac('sha256', $this->a_ServiceName, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        
        // step7. "SIGNATURE" with $kSigning
        $signature = hash_hmac('sha256', $string2sign, $kSigning);

        return $signature;
    }

    private function sendRequest($signature,$payload,$op = 'CreateGiftCard'){
        $curl_head = array(
        'accept:application/json',
        'content-type:application/json',
        $this->a_Host,
        'x-amz-date:'.$this->a_Timestamp_ISO8601,
        'x-amz-target:com.amazonaws.agcod.AGCODService.'.$op,
        'Authorization:AWS4-HMAC-SHA256 Credential='.$this->a_AccessKey.'/'.$this->a_Timestamp.'/us-east-1/AGCODService/aws4_request, SignedHeaders=accept;content-type;host;x-amz-date;x-amz-target,Signature='.$signature,
        );

        // get gc
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->a_Endpoint.$op);
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_head);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $return_content = curl_exec($ch);

        curl_close($ch);

        return $return_content;
    }

}