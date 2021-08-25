<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 3/14/17
 * Time: 4:38 PM
 */

namespace kushki\lib;

use HttpException;
use HttpRequest;

class KushkiClient
{

    function callAPI($method, $url, $data)
    {
        if($method == "POST"){
            $responseRaw = \Httpful\Request::post($url)
                ->sendsJson()
                ->withStrictSSL()
                ->addHeaders(array(
                    'private-merchant-id' => $data["private-merchant-id"]
                ))
                ->body(json_encode($data["body"]))
                ->send();
            return new Transaction($responseRaw->content_type, $responseRaw->body, $responseRaw->code);
        }
        elseif ($method == "PATCH"){
            $responseRaw = \Httpful\Request::patch($url)
                ->sendsJson()
                ->withStrictSSL()
                ->addHeaders(array(
                    'private-merchant-id' => $data["private-merchant-id"]
                ))
                ->body(json_encode($data["body"]))
                ->send();
            return new Transaction($responseRaw->content_type, $responseRaw->body, $responseRaw->code);
        }
        elseif ($method == "GET"){
            $responseRaw = \Httpful\Request::get($url)
                ->sendsJson()
                ->withStrictSSL()
                ->addHeaders(array(
                    'private-merchant-id' => $data["private-merchant-id"]
                ))
                ->send();
            return new Transaction($responseRaw->content_type, $responseRaw->body, $responseRaw->code);
        }
        else
        {
            if(isset($data["body"])){
                $responseRaw = \Httpful\Request::delete($url)
                    ->sendsJson()
                    ->withStrictSSL()
                    ->addHeaders(array(
                        'private-merchant-id' => $data["private-merchant-id"]))
                    ->body(json_encode($data["body"]))
                    ->send();
            }
            else {
                $responseRaw = \Httpful\Request::delete($url)
                    ->sendsJson()
                    ->withStrictSSL()
                    ->addHeaders(array(
                        'private-merchant-id' => $data["private-merchant-id"]))
                    ->send();
            }

            return new Transaction($responseRaw->content_type, $responseRaw->body, $responseRaw->code);
        }
    }

    function callCharge($url, $data)
    {
        $url = $url . KushkiConstant::CHARGE_API_URL;
        $method = "POST";
        $requestCharge = $this->callAPI($method,$url,$data);
        return $requestCharge;
    }

    function callCreateSubscription($url, $data)
    {
        $url = $url . KushkiConstant::SUBSCRIPTION_API_URL;
        $method = "POST";
        $requestCreateSubscription = $this->callAPI($method,$url,$data);
        return $requestCreateSubscription;
    }

    function callUpdateSubscription($url, $data)
    {
        $url = $url . KushkiConstant::SUBSCRIPTION_API_URL . "/" . $data["subscriptionId"];
        $method = 'PATCH';
        $requestUpdateSubscription = $this->callAPI($method,$url,$data);
        return $requestUpdateSubscription;
    }

    function callChargeSubscription($url, $data)
    {
        $url = $url . KushkiConstant::SUBSCRIPTION_API_URL . "/" . $data["subscriptionId"] .
            KushkiConstant::CHARGE_API_URL;
        $method = 'POST';
        $requestChargeSubscription = $this->callAPI($method,$url,$data);
        return $requestChargeSubscription;
    }

    function callVoidCharge($url, $data)
    {
        $url = $url . KushkiConstant::CHARGE_API_URL . "/" . $data["ticketNumber"];
        $method = 'DELETE';
        $requestVoidCharge = $this->callAPI($method,$url,$data);
        return $requestVoidCharge;
    }

    function callCardAsyncStatus($url, $data)
    {
        $baseUrl = str_replace('plugins', 'card-async', $url);
        $urlCardAsync = $baseUrl . KushkiConstant::CARD_ASYNC_STATUS . "/" . $data["token"];
        $method = 'GET';
        return $this->callAPI($method,$urlCardAsync,$data);
    }

    function callTransferStatus($url, $data)
    {
        $baseUrl = str_replace('plugins', 'transfer', $url);
        $urlTransfer = $baseUrl . KushkiConstant::TRANSFER_STATUS . "/" . $data["token"];
        $method = 'GET';
        return $this->callAPI($method,$urlTransfer,$data);
    }

    function callPreAuth($url, $data)
    {
        $url = $url . KushkiConstant::PRE_AUTH_URL;
        $method = 'POST';
        $requestPreAuth = $this->callAPI($method,$url,$data);
        return $requestPreAuth;
    }
    function callCapture($url, $data)
    {
        $url = $url . KushkiConstant::CAPTURE_URL;
        $method = "POST";
        $requestCapture = $this->callAPI($method,$url,$data);
        return $requestCapture;
    }
    function callRefund($url, $data)
    {
        $url = $url . KushkiConstant::REFUND_API_URL . "/" . $data["ticketNumber"];
        $method = 'DELETE';
        $requestVoidCharge = $this->callAPI($method,$url,$data);
        return $requestVoidCharge;
    }
}

?>