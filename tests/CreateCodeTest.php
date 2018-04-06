<?php
include __DIR__ . '/../vendor/autoload.php';
include __DIR__ ."/../src/Amazon/Config.php";
include __DIR__ ."/../src/Amazon/GCService.php";
include __DIR__ ."/../src/Amazon/GCServiceDecorator.php";

/**
 * Created by PhpStorm.
 * User: johnny
 * Date: 2016/12/15
 * Time: 15:46
 */
use Amazon\GCServiceDecorator;

class CreateCodeTest extends PHPUnit_Framework_TestCase
{
    public function testRespondSuccess() {
        $mockAgcod = $this->getMock(GCServiceDecorator::class, ['sendRequest'], ['us-east-1', 'host:agcod-v2-gamma.amazon.com', 'https://agcod-v2-gamma.amazon.com', 'USD']);
        $mockAgcod->expects($this->once())->method('sendRequest')->will(
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

        $signature = json_decode($result['signature'],TRUE);

        $this->assertEquals("YourPartnerId", $signature['partnerId']);
        $this->assertEquals(3, $signature['value']['amount']);
        $this->assertEquals('USD', $signature['value']['currencyCode']);
    }
}