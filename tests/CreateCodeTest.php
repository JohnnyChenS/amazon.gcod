<?php
include __DIR__ . '/../vendor/autoload.php';
include __DIR__ . "/../src/Amazon/Config/Account.php";
include __DIR__ . "/../src/Amazon/Config/Region.php";
include __DIR__ . "/../src/Amazon/AwsService.php";
include __DIR__ . "/../src/Amazon/GCServiceWrapper.php";

/**
 * Created by PhpStorm.
 * User: johnny
 * Date: 2016/12/15
 * Time: 15:46
 */
use Amazon\AwsService;
use Amazon\Config;

class CreateCodeTest extends PHPUnit_Framework_TestCase
{
    public function testRespondSuccess() {
        $config = Config\Region::getServiceConf(Config\Region::US, TRUE);

        $mockService = $this->getMock(AwsService::class, ['sendRequest'], [$config['regionCode'], $config['host'], $config['endpoint'], $config['currencyCode']]);
        $mockService->expects($this->once())->method('sendRequest')->will(
            $this->returnCallback(function ($signature, $payload, $op) {
                return json_encode([
                    'operation' => $op,
                    'signature' => $signature,
                    'payload'   => $payload,
                ]);
            })
        );

        $wrapper = new \Amazon\GCServiceWrapper(Config\Region::US, TRUE);

        $reflection = new ReflectionClass($wrapper);
        $property = $reflection->getProperty('__awsService');
        $property->setAccessible(true);
        $property->setValue($wrapper, $mockService);

        $result = $wrapper->createGiftCode(3);
        $this->assertEquals('CreateGiftCard', $result['operation']);

        $signature = json_decode($result['signature'], TRUE);

        $this->assertEquals("YourPartnerId", $signature['partnerId']);
        $this->assertEquals(3, $signature['value']['amount']);
        $this->assertEquals('USD', $signature['value']['currencyCode']);
    }
}