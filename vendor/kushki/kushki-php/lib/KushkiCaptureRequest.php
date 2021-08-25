<?php
/**
 * Created by PhpStorm.
 * User: andresrairan
 * Date: 4/19/21
 * Time: 10:50 AM
 */

namespace kushki\lib;


class KushkiCaptureRequest{
    private $url;
    private $orderId;
    private $ticketNumber;
    private $merchantId;

    function __construct($merchantId, $orderId, $ticketNumber, $baseUrl = KushkiEnvironment::TESTING) {
        $this->orderId = $orderId;
        $this->ticketNumber = $ticketNumber;
        $this->url = $baseUrl;
        $this->merchantId = $merchantId;
    }
    public function getUrl() {
        return $this->url;
    }
    public function getData(){
        $body = array(
            "ticketNumber" => $this->ticketNumber,
            "orderId" => strval($this->orderId),
            "channel" => KushkiConstant::WOOCOMMERCE_CHANNEL
        );

        $data = array(
            "private-merchant-id" => $this->merchantId,
            "body"                => $body
        );
        return $data;
    }
    public function capture(){
        $data = $this->getData();
        $response = new KushkiClient();
        return $response = $response->callCapture($this->url, $data);
    }
}