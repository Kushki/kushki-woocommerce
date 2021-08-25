<?php

namespace kushki\lib;

class KushkiCardAsyncTrxRequest {
    private $token;
    private $merchantId;
    private $url;

    function __construct( $merchantId, $token, $baseUrl = KushkiEnvironment::PRODUCTION ) {
        $this->token = $token;
        $this->merchantId = $merchantId;
        $this->url = $baseUrl;
    }

    public function getTransaction() {
        $data = array(
            "private-merchant-id" => $this->merchantId,
            "token"               => $this->token
        );
        $response = new KushkiClient();
        return $response->callCardAsyncStatus($this->url, $data);
    }
}