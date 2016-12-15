<?php
include __DIR__ . '/../vendor/autoload.php';

/**
 * Created by PhpStorm.
 * User: johnny
 * Date: 2016/12/15
 * Time: 15:46
 */
class CreateCodeTest extends PHPUnit_Framework_TestCase
{
    public function testRespondSuccess() {
        $mockAgcod = $this->getMock(AmazonGCOD::class, ['_sendRequest'], ['mycom', 'mykey', 'skey', 'us-east-1', 'host:agcod-v2-gamma.amazon.com', 'host:agcod-v2-gamma.amazon.com', 'Test-request-001']);
        $mockAgcod->expects($this->once())->method('_sendRequest')->will(
            $this->returnCallback(function ($signature, $payload, $op) {
                return json_encode([
                    'operation' => $op,
                    'signature' => $signature,
                    'payload'   => $payload,
                ]);
            })
        );

        $result = $mockAgcod->createGiftCode(3);

        $this->assertEquals('CreateGiftCard', $result['operation']);
        $this->assertEquals('{"creationRequestId":"mycomTest-request-001","partnerId":"mycom","value":{"currencyCode":"USD","amount":3}}', $result['payload']);
    }
}