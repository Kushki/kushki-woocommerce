<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 3/21/17
 * Time: 9:20 PM
 */

namespace kushki\lib;


class KushkiRefundRequest
{
    private $url;
    private $merchantId;
    private $ticketNumber;
    private $amount;

    function __construct($merchantId, $ticketNumber , $amount, $baseUrl = KushkiEnvironment::PRODUCTION)
    {
        $this->url = $baseUrl;
        $this->merchantId = $merchantId;
        $this->ticketNumber = $ticketNumber;
        $this->amount = $amount;
    }

    public function getUrl() {
        return $this->url;
    }

    public function getParameter($parameterName) {
        $params = $this->getData();
        if (isset($params[$parameterName])) {
            return $params[$parameterName];
        }
        throw new KushkiException(KushkiConstant::PARAMETER_DO_NOT_EXIST);
    }

    public function getData(){
        $data = array("private-merchant-id" => $this->merchantId,"ticketNumber" => $this->ticketNumber);
        if($this->amount != false){
            $body = array(
                "amount" => $this->amount->toHash());
            $data["body"] = $body;
        }
        $body = array("channel" => "WOOCOMMERCE");
        $data["body"] = $body;
        return $data;
    }

    public function refund(){
        $data = $this->getData();
        $response = new KushkiClient();
        return $response = $response->callRefund($this->url, $data);
    }
}