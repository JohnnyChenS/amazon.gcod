<?php
/**
 * Created by PhpStorm.
 * User: johnny
 * Date: 2018/4/6
 * Time: 17:47
 */

namespace Amazon;


class Config
{
    private static $__partnerId = 'YourPartnerId';
    private static $__accessKey = 'YourAccessKey';
    private static $__secretKey = 'YourSecretKey';

    /**
     * @return string
     */
    public static function getPartnerId() {
        return self::$__partnerId;
    }

    /**
     * @return string
     */
    public static function getAccessKey() {
        return self::$__accessKey;
    }

    /**
     * @return string
     */
    public static function getSecretKey() {
        return self::$__secretKey;
    }
}