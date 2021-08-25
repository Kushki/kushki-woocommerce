<?php


namespace kushki\lib;


class KushkiTransferTrxRequest
{
    private $token;
    private $privateMerchantId;
    private $url;

    function __construct( $merchantId, $token, $baseUrl = KushkiEnvironment::PRODUCTION ) {
        $this->token = $token;
        $this->privateMerchantId = $merchantId;
        $this->url = $baseUrl;
    }

    public function getTransaction() {
        $data = array(
            "private-merchant-id" => $this->privateMerchantId,
            "token"               => $this->token
        );
        $response = new KushkiClient();
        return $response->callTransferStatus($this->url, $data);
    }

}