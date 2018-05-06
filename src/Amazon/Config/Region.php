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

    private static $__serviceEndpoint = [
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

    public static function getServiceConf($region, $isSandbox) {
        return self::$__serviceEndpoint[self::__getEnv($isSandbox)][$region];
    }

    private static function __getEnv($isSandbox){
        if($isSandbox)
            return 'sandbox';

        return 'prod';
    }
}