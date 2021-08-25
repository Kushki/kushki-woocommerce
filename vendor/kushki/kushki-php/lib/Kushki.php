<?php

namespace kushki\lib;

class Kushki {
    private $merchantId;
    private $language;
    private $currency;
    private $environment;
    private $requestHandler;

    /**
     * @param string $merchantId
     * @param string $language
     * @param string $currency
     * @param string $environment
     */
    public function __construct($merchantId,
                                $language = KushkiLanguage::ES,
                                $currency = KushkiCurrency::USD,
                                $environment = KushkiEnvironment::PRODUCTION) {
        $this->merchantId = $merchantId;
        $this->language = $language;
        $this->currency = $currency;
        $this->environment = $environment;
        $this->requestHandler = new RequestHandler();
    }

    /**
     * @param string $token
     * @param Amount $amount
     * @return Transaction
     * @throws KushkiException
     */

    public function charge( $paymentMethod, $token, $orderId, $amount, $deferred, $storeDomain, $siftFields, $metadata = false, $contactDetails = false ) {
        $chargeRequestBuilder = new KushkiChargeRequest($this->merchantId, $paymentMethod, $token, $orderId, $amount, $deferred, $storeDomain, $months = 0, $siftFields, $metadata,
            $this->environment, $this->currency, $contactDetails);
        $request = $chargeRequestBuilder->charge();
        return $request;
    }

    public function getCardAsyncTransaction( $token ) {
        $cardAsyncTrxBuilder = new KushkiCardAsyncTrxRequest( $this->merchantId, $token, $this->environment );
        return $cardAsyncTrxBuilder->getTransaction();
    }

    public function getTransferTransaction( $token ) {
        $transferTrxBuilder = new KushkiTransferTrxRequest( $this->merchantId, $token, $this->environment );
        return $transferTrxBuilder->getTransaction();
    }

    /**
     * @param $ticketNumber
     * @return Transaction
     * @throws KushkiException
     */
    public function voidCharge($ticketNumber, $amount = false){
        $voidRequestBuilder = new KushkiVoidRequest($this->merchantId, $ticketNumber, $amount, $this->environment);
        $request = $voidRequestBuilder->voidCharge();
        return $request;
    }
    /**
     * @param $ticketNumber
     * @return Transaction
     * @throws KushkiException
     */
    public function refund($ticketNumber, $amount = false){
        $voidRequestBuilder = new KushkiRefundRequest($this->merchantId, $ticketNumber, $amount, $this->environment);
        $request = $voidRequestBuilder->refund();
        return $request;
    }

    /**
     * @param $token
     * @param $planName
     * @param $periodicity
     * @param $contactDetails
     * @param $amount
     * @param $startDate
     * @return Transaction
     * @throws KushkiException
     */
    public function createSubscription($token, $planName, $periodicity, $contactDetails, $amount, $startDate,
                                       $metadata = false){
        $subscriptionRequest = new KushkiSubscriptionRequest($this->merchantId, $token, $planName, $periodicity,
            $contactDetails, $amount, $startDate, $metadata , $this->environment,
            $this->currency);
        $subscription = $subscriptionRequest->createSubscription();
        return $subscription;
    }

    /**
     * @param $subscriptionId
     * @param $body
     * @return Transaction
     * @throws KushkiException
     */
    public function updateSubscription($subscriptionId, $body){
        $subscriptionRequest = new KushkiSubscriptionUpdateRequest($this->merchantId, $subscriptionId, $body,
            $this->environment);
        $updateSubscription = $subscriptionRequest->updateSubscription();
        return $updateSubscription;
    }

    /**
     * @param $subscriptionId
     * @return Transaction
     * @throws KushkiException
     */
    public function chargeSubscription($subscriptionId, $metadata = false){
        $subscriptionRequest = new KushkiSubscriptionChargeRequest($this->merchantId, $subscriptionId, $metadata,
            $this->environment);
        $chargeSubscription = $subscriptionRequest->chargeSubscription();
        return $chargeSubscription;
    }

    /**
     * @param $token
     * @param $orderId
     * @param $amount
     * @param bool $metadata
     * @return Transaction
     * @throws KushkiException
     */
    public function preAuth($token, $orderId, $amount, $siftFields, $metadata = false){
        $preAuthRequestBuilder = new KushkiPreAuthRequest($this->merchantId, $token, $orderId, $amount, $siftFields, $metadata,
            $this->environment, $this->currency);
        $request = $preAuthRequestBuilder->preAuth();
        return $request;
    }
    public function capture($orderId, $ticketNumber) {
        $captureRequestBuilder = new KushkiCaptureRequest($this->merchantId, $orderId, $ticketNumber);
        $request = $captureRequestBuilder->capture();
        return $request;
    }

    public function getMerchantId() {
        return $this->merchantId;
    }

    public function getLanguage() {
        return $this->language;
    }

    public function getCurrency() {
        return $this->currency;
    }
    public function getEnvironment() {
        return $this->environment;
    }
}

?>
