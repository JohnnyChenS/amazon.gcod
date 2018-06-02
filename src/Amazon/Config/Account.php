<?php
/**
 * Created by PhpStorm.
 * User: johnny
 * Date: 2018/4/6
 * Time: 17:47
 */

namespace Amazon\Config;


class Account
{
    private $__partnerId = 'YourPartnerId';
    private $__accessKey = 'YourAccessKey';
    private $__secretKey = 'YourSecretKey';

    public function __construct($partnerId, $accessKey, $secretKey) {
        $this->__partnerId = $partnerId;
        $this->__accessKey = $accessKey;
        $this->__secretKey = $secretKey;
    }

    /**
     * @return string
     */
    public function getPartnerId() {
        return $this->__partnerId;
    }

    /**
     * @return string
     */
    public function getAccessKey() {
        return $this->__accessKey;
    }

    /**
     * @return string
     */
    public function getSecretKey() {
        return $this->__secretKey;
    }
}