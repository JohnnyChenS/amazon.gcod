<?php
/**
 * Created by PhpStorm.
 * User: johnny
 * Date: 2018/5/6
 * Time: 11:28
 */

namespace Amazon\Config;


class Region
{
    const US = 'us';
    const EU = 'eu';

    private $__config;

    private $__serviceEndpoint = [
        'sandbox' => [
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

    public function __construct($region, $isSandbox) {
        $this->__config = $this->__serviceEndpoint[$this->__getEnv($isSandbox)][$region];
    }

    public function getRegionCode(){
        return $this->__config['regionCode'];
    }

    public function getHost(){
        return $this->__config['host'];
    }

    public function getEndPoint(){
        return $this->__config['endpoint'];
    }

    public function getCurrencyCode(){
        return $this->__config['currencyCode'];
    }

    private function __getEnv($isSandbox){
        if($isSandbox)
            return 'sandbox';

        return 'prod';
    }
}