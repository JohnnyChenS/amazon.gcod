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
        $region  = new Config\Region(Config\Region::US, TRUE);
        $account = new Config\Account("myid", "mykey", "mykey2");

        $mockService = $this->getMock(AwsService::class, ['sendRequest'], [$region->getRegionCode(), $region->getHost(), $region->getEndPoint(), $region->getCurrencyCode(), 'AGCODService', 'com.amazonaws.agcod', $account->getSecretKey(), $account->getAccessKey()]);
        $mockService->expects($this->once())->method('sendRequest')->will(
            $this->returnCallback(function ($signature, $payload, $op) {
                return json_encode([
                    'operation' => $op,
                    'signature' => $signature,
                    'payload'   => $payload,
                ]);
            })
        );

        $wrapper = new \Amazon\GCServiceWrapper($account, $region);

        $reflection = new ReflectionClass($wrapper);
        $property   = $reflection->getProperty('__awsService');
        $property->setAccessible(TRUE);
        $property->setValue($wrapper, $mockService);

        $result = $wrapper->createGiftCode(3);
        $this->assertEquals('CreateGiftCard', $result['operation']);

        $signature = json_decode($result['signature'], TRUE);

        $this->assertEquals("myid", $signature['partnerId']);
        $this->assertEquals(3, $signature['value']['amount']);
        $this->assertEquals('USD', $signature['value']['currencyCode']);
    }

    public function testRealRequest() {
        $account = new Config\Account("myPartnerId", "myAccessKey", "mySecretKey");
        $region  = new Config\Region(Config\Region::US, TRUE);

        $wrapper = new \Amazon\GCServiceWrapper($account, $region);
        $result  = $wrapper->createGiftCode(3);
        var_dump($result);
    }
}