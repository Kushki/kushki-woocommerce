<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 3/15/17
 * Time: 10:50 AM
 */

namespace kushki\lib;


class KushkiChargeRequest{
    private $url;
    private $paymentMethod;
    private $token;
    private $orderId;
    private $amount;
    private $deferred;
    private $merchantId;
    private $metadata;
    private $currency;
    private $language = KushkiLanguage::ES;
    private $months;
    private $contactDetails;
    private $storeDomain;
    private $sift;


    function __construct($merchantId, $paymentMethod, $token, $orderId, $amount, $deferred, $storeDomain, $months, $siftFields, $metadata = false,
                         $baseUrl = KushkiEnvironment::PRODUCTION, $currency = KushkiCurrency::USD, $contactDetails = false) {
        $this->url = $baseUrl;
        $this->paymentMethod = $paymentMethod;
        $this->token = $token;
        $this->orderId = $orderId;
        $this->amount = $amount;
        $this->deferred = $deferred;
        $this->months = $months;
        $this->merchantId = $merchantId;
        $this->metadata = $metadata;
        $this->currency = $currency;
        $this->contactDetails = $contactDetails;
        $this->storeDomain = $storeDomain;
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
    public function getData(){
        $paymentMethod = "";
        $activationMethod = "";

        switch ( $this->paymentMethod ) {
            case KushkiConstant::CARD_PAYMENT_METHOD:
                $paymentMethod = "creditCard";
                $activationMethod = "singlePayment";
                break;
            case KushkiConstant::CARD_ASYNC_PAYMENT_METHOD:
                $paymentMethod = "debitCard";
                $activationMethod = "cardAsyncPayment";
                break;
            case KushkiConstant::CASH_PAYMENT_METHOD:
                $paymentMethod = "cash";
                $activationMethod = "cashPayment";
                break;
            case KushkiConstant::TRANSFER_PAYMENT_METHOD:
                $paymentMethod = "transfer";
                $activationMethod = "transferPayment";
                break;
        }

        $arrAmount = $this->amount->toHash();
        $arrAmount["currency"] = $this->currency;
        $body = array(
            "token" => $this->token,
            "orderId" => strval($this->orderId),
            "amount" => $arrAmount,
            "paymentMethod" => $paymentMethod,
            "activationMethod" => $activationMethod,
            "channel" => KushkiConstant::WOOCOMMERCE_CHANNEL,
            "storeDomain" => $this->storeDomain
        );

        if ($this->paymentMethod == KushkiConstant::CARD_PAYMENT_METHOD)
            $body = array_merge($body, $this->sift);

        if( $this->deferred['months'] || $this->deferred['monthsOfGrace'] || $this->deferred['deferredType'] ) {
            $body['deferred'] = [];
            if ( isset($this->deferred['months']) && $this->deferred['months'] > 0 )
                $body['deferred']['months'] = $this->deferred['months'];
            else
                $body['deferred']['months'] = 0;

            if ( isset($this->deferred['deferredType']) && !( $this->deferred['deferredType'] == 'all' || $this->deferred['deferredType'] == '' ))
                $body['deferred']['creditType'] = $this->deferred['deferredType'];
            else
                $body['deferred']['creditType'] = '000';

            if ( isset($this->deferred['monthsOfGrace']) && $this->deferred['monthsOfGrace'] != '' )
                $body['deferred']['graceMonths'] = strval($this->deferred['monthsOfGrace']);
            else
                $body['deferred']['graceMonths'] = '00';
        }

        $metadataArray = array("metadata" => $this->metadata);
        if( $this->metadata != false ){
            $body = array_merge($body, $metadataArray);
        }

        $contactDetailsArray = array("contactDetails" => $this->contactDetails);
        if( $this->contactDetails!=false ){
            $body = array_merge($body, $contactDetailsArray);
        }

        $data = array(
            "private-merchant-id" => $this->merchantId,
            "body"                => $body
        );
        return $data;
    }

    public function charge(){
        $data = $this->getData();
        $response = new KushkiClient();
        return $response = $response->callCharge($this->url, $data);
    }

}