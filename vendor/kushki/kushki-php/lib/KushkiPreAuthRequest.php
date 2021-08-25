<?php
/**
 * Created by PhpStorm.
 * User: dario
 * Date: 4/19/21
 * Time: 12:16 PM
 */

namespace kushki\lib;

class KushkiPreAuthRequest {
    private $url;
    private $token;
    private $orderId;
    private $amount;
    private $metadata;
    private $merchantId;
    private $currency;
    private $sift;
    private $language = KushkiLanguage::ES;

    function __construct(
        $merchantId,
        $token,
        $orderId,
        $amount,
        $siftFields,
        $metadata = false,
        $baseUrl = KushkiEnvironment::PRODUCTION,
        $currency = KushkiCurrency::USD
    )
    {
        $this->url = $baseUrl;
        $this->token = $token;
        $this->orderId = $orderId;
        $this->amount = $amount;
        $this->metadata = $metadata;
        $this->merchantId = $merchantId;
        $this->currency = $currency;
        $this->sift = $siftFields;
    }

    public function getUrl() {
        return $this->url;
    }

    public function getParameter($parameterName) {
        $params = $this->getData()["body"];
        if ( isset($params[$parameterName]) ) {
            return $params[$parameterName];
        }
        throw new KushkiException(KushkiConstant::PARAMETER_DO_NOT_EXIST);
    }

    public function getData() {
        $arrAmount = $this->amount->toHash();
        $arrAmount["currency"] = $this->currency;
        $body = array(
            "token" => $this->token,
            "orderId" => strval($this->orderId),
            "amount" => $arrAmount,
            "channel" => KushkiConstant::WOOCOMMERCE_CHANNEL
        );

        $body = array_merge($body, $this->sift);

        $metadataArray = array("metadata" => $this->metadata);
        if ( $this->metadata != false ) {
            $body = array_merge($body, $metadataArray);
        }

        $data = array(
            "private-merchant-id" => $this->merchantId,
            "body"                => $body
        );

        return $data;
    }

    public function preAuth() {
        $data = $this->getData();
        $response = new KushkiClient();
        return $response = $response->callPreAuth($this->url, $data);
    }
}