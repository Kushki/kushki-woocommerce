<?php

namespace kushki\lib;

class KushkiConstant {
    const VERSION = '1.0';
    const TOKENS_URL = "/tokens";
    const CHARGE_API_URL = "/charge";
    const SUBSCRIPTION_API_URL = "/subscriptions";
    const VOID_URL = "/void";
    const REFUND_API_URL = "/refund";
    const DEFERRED_URL = "/deferred";
    const CARD_ASYNC_STATUS = "/status";
    const TRANSFER_STATUS = "/status";
    const PRE_AUTH_URL = "/preAuth";
    const CAPTURE_URL = "/capture";
    const PARAMETER_MERCHANT_ID = "merchant_identifier";
    const PARAMETER_LANGUAGE = "language_indicator";
    const PARAMETER_TRANSACTION_TOKEN = "transaction_token";
    const PARAMETER_TRANSACTION_TICKET = "ticket_number";
    const PARAMETER_TRANSACTION_ID = "transaction_id";
    const PARAMETER_CURRENCY_CODE = "currency_code";
    const PARAMETER_TRANSACTION_AMOUNT = "transaction_amount";
    const PARAMETER_TICKET_NUMBER = "ticket_number";
    const PARAMETER_DO_NOT_EXIST = "Parameter does not exist";
    const PARAMETER_DEFERRED = "deferred";
    const PARAMETER_MONTHS = "months";
    const PARAMETER_INTEREST = "rate_of_interest";
    const PARAMETER_ERRORS = "error";
    const PARAMETER_ERRORS_MESSAGE = "message";
    const PARAMETER_ERRORS_CODE = "code";

    const CONTENT_TYPE = "application/json";

    const WOOCOMMERCE_CHANNEL = "WOOCOMMERCE";

    const CARD_PAYMENT_METHOD = "card";
    const CARD_ASYNC_PAYMENT_METHOD = "card_async";
    const CASH_PAYMENT_METHOD = "cash";
    const TRANSFER_PAYMENT_METHOD = "transfer";
    const PRE_AUTH_PAYMENT_METHOD = "preauth";

    // Order statuses
    const ORDER_COMPLETED = "completed";
    const ORDER_PROCESSING = "processing";
    const ORDER_ON_HOLD = "on hold";
    const ORDER_REFUNDED = "refunded";
    const ORDER_CANCELED = "canceled";
    const ORDER_FAILED = "failed";

    // Transaction status
    const APPROVED_STATUS = "approvedTransaction";
    const DECLINED_STATUS = "declinedTransaction";
    const INITIALIZED_STATUS = "initializedTransaction";
    const APPROVAL = "APPROVAL";
    const DECLINED = "DECLINED";

    const KUSHKI_PUBLIC_KEY = "-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC81t5iu5C0JxYq5/XNPiD5ol3Z
w8rw3LtFIUm7y3m8o8wv5qVnzGh6XwQ8LWypdkbBDKWZZrAUd3lybZOP7/82Nb1/
noYj8ixVRdbnYtbsSAbu9PxjB7a/7LCGKsugLkou74PJDadQweM88kzQOx/kzAyV
bS9gCCVUguHcq2vRRQIDAQAB
-----END PUBLIC KEY-----";
}

?>
