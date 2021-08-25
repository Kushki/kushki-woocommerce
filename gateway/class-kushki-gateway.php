<?php

use kushki\lib\Amount;
use kushki\lib\ExtraTaxes;
use kushki\lib\Kushki;
use kushki\lib\KushkiConstant;
use kushki\lib\KushkiEnvironment;
use kushki\lib\KushkiLanguage;

class WC_Kushki_Gateway extends WC_Payment_Gateway_CC {

    private $environment;
    private $private_id;
    private $public_id;
    private $tax_iva;
    private $tax_ice;
    private $tax_propina;
    private $tax_tasa_aeroportuaria;
    private $tax_agencia_viaje;
    private $tax_iac;

	public function __construct() {
		$this->id                 = "kushki";
		$this->method_title       = __( "Kushki", 'kushki-gateway' );
		$this->method_description = __( "Kushki payment gateway for WooCommerce.", 'kushki-gateway' );
		$this->title              = __( "Kushki", 'kushki-gateway' );
		$this->icon               = apply_filters( 'woocommerce_kushki_icon', plugins_url( '../assets/kushki.png', __FILE__ ) );
		$this->has_fields         = true;
		$this->init_form_fields();
		$this->init_settings();
        $this->supports = array(
            'products',
            'refunds'
        );
		add_action( 'admin_notices', array( $this, 'do_ssl_check' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );

        foreach ( $this->settings as $setting_key => $value ) {
            $this->$setting_key = $value;
        }
        // Lets check for SSL
        add_action( 'admin_notices', array( $this, 'do_ssl_check' ) );
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
            $this,
            "process_admin_options"
        ) );
        // CallbackUrl to confirm payment
        add_action( 'woocommerce_api_confirm_payment', array($this, 'confirm_payment_callback_handler') );
        //Add content to the WooCommerce Thank You Page
        add_action( 'woocommerce_thankyou', array( $this, 'add_cash_detail' ), 4 );
        add_action( 'woocommerce_api_webhook_payment_complete', array( $this, 'webhook' ) );
        add_action( 'woocommerce_order_status_completed', array(&$this, 'capture_payment'), 10, 1);

    }

    public function do_ssl_check() {
        if ( $this->enabled == "yes" ) {
            if ( get_option( 'woocommerce_force_ssl_checkout' ) == "no" ) {
                echo "<div class=\"error\"><p>" . sprintf( __( "<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) . "</p></div>";
            }
        }
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled'                => array(
                'title'   => __( 'Enable / Disable', 'kushki-gateway' ),
                'label'   => __( 'Enable this payment gateway', 'kushki-gateway' ),
                'type'    => 'checkbox',
                'default' => 'no',
            ),
            'title'                  => array(
                'title'    => __( 'Title', 'kushki-gateway' ),
                'type'     => 'text',
                'desc_tip' => __( 'Payment title the customer will see during the checkout process.', 'kushki-gateway' ),
                'default'  => __( 'Kushki', 'kushki-gateway' ),
            ),
            'description'            => array(
                'title'    => __( 'Description', 'kushki-gateway' ),
                'type'     => 'textarea',
                'desc_tip' => __( 'Payment description the customer will see during the checkout process.', 'kushki-gateway' ),
                'default'  => __( 'Pay securely using your credit card.', 'kushki-gateway' ),
                'css'      => 'max-width:350px;'
            ),
            'public_id'              => array(
                'title'    => __( 'Merchant Public ID', 'kushki-gateway' ),
                'type'     => 'text',
                'desc_tip' => __( 'This is the merchant public id provided by Kushki.', 'kushki-gateway' ),
            ),
            'private_id'             => array(
                'title'    => __( 'Merchant Private ID', 'kushki-gateway' ),
                'type'     => 'password',
                'desc_tip' => __( 'This is the merchant private id provided by Kushki.', 'kushki-gateway' ),
            ),
            'environment'            => array(
                'title'       => __( 'Test Mode', 'kushki-gateway' ),
                'label'       => __( 'Enable Test Mode', 'kushki-gateway' ),
                'type'        => 'checkbox',
                'description' => __( 'Place the payment gateway in test mode.', 'kushki-gateway' ),
                'default'     => 'yes',
            ),
            'tax_details'            => array(
                'title'       => __( 'Tax Settings', 'kushki-gateway' ),
                'type'        => 'title',
                'description' => __( "Set the current defined name for each tax on the Woocommerce Tax settings.<br>" .
                    "<strong>NOTE: Keep in blank the taxes unused. </strong>", 'kushki-gateway' ),
            ),
            'tax_iva'                => array(
                'title'       => __( 'IVA', 'kushki-gateway' ),
                'type'        => 'text',
                'description' => __( 'Defined tax name for IVA.', 'kushki-gateway' ),
                'default'     => 'IVA',
                'desc_tip'    => true,
                'placeholder' => __( 'Required', 'kushki-gateway' )
            ),
            'tax_ice'                => array(
                'title'       => __( 'ICE', 'kushki-gateway' ),
                'type'        => 'text',
                'description' => __( 'Defined tax name for ICE, this is used only on Ecuador.', 'kushki-gateway' ),
                'desc_tip'    => true,
                'placeholder' => __( 'Optional, only used on Ecuador', 'kushki-gateway' )
            ),
            'tax_propina'            => array(
                'title'       => __( 'Propina', 'kushki-gateway' ),
                'type'        => 'text',
                'description' => __( 'Defined tax name for Propina, this is used only on Colombia.', 'kushki-gateway' ),
                'desc_tip'    => true,
                'placeholder' => __( 'Optional, only used on Colombia', 'kushki-gateway' )
            ),
            'tax_tasa_aeroportuaria' => array(
                'title'       => __( 'Tasa Aeroportuaria', 'kushki-gateway' ),
                'type'        => 'text',
                'description' => __( 'Defined tax name for Tasa Aeroportuaria, this is used only on Colombia.', 'kushki-gateway' ),
                'desc_tip'    => true,
                'placeholder' => __( 'Optional, only used on Colombia', 'kushki-gateway' )
            ),
            'tax_agencia_viaje'      => array(
                'title'       => __( 'Agencia de Viaje', 'kushki-gateway' ),
                'type'        => 'text',
                'description' => __( 'Defined tax name for Agencia de Viaje, this is used only on Colombia.', 'kushki-gateway' ),
                'desc_tip'    => true,
                'placeholder' => __( 'Optional, only used on Colombia', 'kushki-gateway' )
            ),
            'tax_iac'                => array(
                'title'       => __( 'IAC', 'kushki-gateway' ),
                'type'        => 'text',
                'description' => __( 'Defined tax name for IAC, this is used only on Colombia.', 'kushki-gateway' ),
                'desc_tip'    => true,
                'placeholder' => __( 'Optional, only used on Colombia', 'kushki-gateway' )
            )
        );
    }

    public function process_payment( $order_id ) {
        $customer_order = wc_get_order( $order_id );

        $merchantId  = $this->private_id;
        $language    = KushkiLanguage::ES;
        $currency    = $customer_order->get_currency();
        $decimals    = wc_get_price_decimals();
        $environment = ( $this->environment == "yes" ) ? KushkiEnvironment::TESTING : KushkiEnvironment::PRODUCTION;
        $dataOrder   = $customer_order->get_data();

        $kushki = new Kushki( $merchantId, $language, $currency, $environment );

        $token             = $_POST['kushkiToken'];
        $months            = intval( $_POST['kushkiDeferred'] );
        $monthsOfGrace     = intval( $_POST['kushkiMonthsOfGrace'] );
        $deferredType      = $_POST['kushkiDeferredType'];
        $paymentMethod     = $_POST['kushkiPaymentMethod'];
        $deferred = array(
            "months" => $months,
            "monthsOfGrace" => $monthsOfGrace,
            "deferredType" => $deferredType
        );


        $amount = $this->build_amount( $dataOrder, $customer_order, $decimals );
        $customer_contact_details = $this->build_contact_details( $customer_order );
		$metadata = $this->build_metadata( $dataOrder, $order_id );
		$storeDomain = $this->getDomainUrl();
		$siftFields = $this->map_charge_sift($order_id);

		if ( $paymentMethod == KushkiConstant::PRE_AUTH_PAYMENT_METHOD ) {
            $transaction = $kushki->preAuth( $token, $order_id, $amount, $siftFields, $metadata );
        } else {
            $transaction = $kushki->charge( $paymentMethod, $token, $order_id, $amount, $deferred, $storeDomain, $siftFields, $metadata, $customer_contact_details );
        }
        if ( $transaction->isSuccessful() ) {
            $transactionId = $transaction->getTransactionId();
            switch ( $paymentMethod ) {
                case KushkiConstant::PRE_AUTH_PAYMENT_METHOD:
                    $customer_order->set_transaction_id($transactionId);

                    if ( $transaction->isSuccessful() ) {
                        $customer_order->add_order_note( __( 'Kushki payment processing.', 'kushki-gateway' ) . ' Ticket preauthorization: ' . $transaction->getTicketNumber() );
                        $customer_order->add_meta_data("_kushki_ticketNumber", $transaction->getTicketNumber());
                        $customer_order->add_meta_data("_kushki_preauth", true);
                        $customer_order->add_meta_data("_kushki_capture_call", 0);
                        $customer_order->save_meta_data();
                        apply_filters( 'woocommerce_payment_complete_order_status', $customer_order->update_status( KushkiConstant::ORDER_PROCESSING ) );
                        // Empty the cart (Very important step)
                        wc_empty_cart();
                        // Redirect to thank you page
                        return array(
                            'result'   => 'success',
                            'redirect' => $this->get_return_url( $customer_order ),
                        );
                    } else {
                        apply_filters( 'woocommerce_payment_complete_order_status', $customer_order->update_status( KushkiConstant::ORDER_FAILED ) );
                        // Add notice to the cart
                        WC()->session->set( 'reload_checkout', true );
                        wc_add_notice( $transaction->getResponseText(), 'error' );
                        // Add note to the order for your reference
                        WC()->session->set( 'reload_checkout', false );
                        return $customer_order->add_order_note( __( 'Kushki preauth failed. ', 'kushki-gateway' ) . $transaction->getResponseText() );
                    }

                case KushkiConstant::CARD_ASYNC_PAYMENT_METHOD:
                    $customer_order->set_transaction_id($transactionId);
                    apply_filters( 'woocommerce_payment_complete_order_status', $customer_order->update_status( KushkiConstant::ORDER_PROCESSING ) );
                    $customer_order->add_order_note(__('Kushki payment on hold, ', 'kushki-gateway') . 'with debit card.');

                    return array(
                        'result' => 'success',
                        'redirect' => $transaction->getReturnUrl()
                    );

                case KushkiConstant::CASH_PAYMENT_METHOD:
                    $customer_order->set_transaction_id($transactionId);
                    apply_filters( 'woocommerce_payment_complete_order_status', $customer_order->update_status( KushkiConstant::ORDER_PROCESSING ) );
                    $customer_order->add_order_note( __( 'Kushki payment on hold,', 'kushki-gateway' ) . ' with cash. Ticket: ' . $transaction->getTicketNumber());
                    $customer_order->add_order_note( __( 'Cash order: ', 'kushki-gateway' ) . $transaction->getPdfUrl() );
                    $customer_order->add_order_note(__( 'Pin: ', 'kushki-gateway' ) . $transaction->getPin());
                    $customer_order->add_meta_data( "_pdfUrl", $transaction->getPdfUrl(), true );
                    $customer_order->add_meta_data( "_pin", $transaction->getPin(), true );
                    $customer_order->save_meta_data();
                    wc_empty_cart();
                    // Redirect to thank you page
                    return array(
                        'result' => 'success',
                        'redirect' => $this->get_return_url( $customer_order )
                    );
                case KushkiConstant::TRANSFER_PAYMENT_METHOD:
                    $customer_order->set_transaction_id($transactionId);
                    $customer_order->add_meta_data("_trazabilityCode", $transaction->getTrazabilityCode());
                    $customer_order->save_meta_data();
                    apply_filters( 'woocommerce_payment_complete_order_status', $customer_order->update_status( KushkiConstant::ORDER_PROCESSING ) );
                    // Empty the cart (Very important step)
                    wc_empty_cart();
                    // Redirect to thank you page
                    return array(
                        'result' => 'success',
                        'redirect' => $transaction->getReturnUrl()
                    );

                default: // CARD
                    $customer_order->add_order_note(__('Kushki payment completed.', 'kushki-gateway') . ' with credit card. Ticket: ' . $transaction->getTicketNumber());
                    $customer_order->add_meta_data("_kushki_ticketNumber", $transaction->getTicketNumber());
                    $customer_order->add_meta_data("_kushki_refund", true);
                    $customer_order->save_meta_data();
                    $customer_order->set_transaction_id($transactionId);
                    apply_filters('woocommerce_payment_complete_order_status', $customer_order->update_status(KushkiConstant::ORDER_COMPLETED));
                    // Empty the cart (Very important step)
                    wc_empty_cart();
                    // Redirect to thank you page
                    return array(
                        'result'   => 'success',
                        'redirect' => $this->get_return_url( $customer_order ),
                    );
            }
        } else {
            // Transaction was not succesful
            // Add notice to the cart
            WC()->session->set( 'reload_checkout', true );
            wc_add_notice( "Error " . $transaction->getResponseCode() . ": " . $transaction->getResponseText(), 'error' );
            // Add note to the order for your reference
            WC()->session->set( 'reload_checkout', false );
            apply_filters('woocommerce_payment_complete_order_status', $customer_order->update_status(KushkiConstant::ORDER_FAILED));
            return $customer_order->add_order_note( __( 'Kushki ' . $paymentMethod . ' payment failed. ', 'kushki-gateway' ) . $transaction->getResponseText() );
        }
    }

    public function build_amount( $dataOrder, $customer_order, $decimals ): Amount
    {
        $iva               = 0;
        $ice               = 0;
        $propina           = null;
        $tasaAeroportuaria = null;
        $agenciaDeViaje    = null;
        $iac               = null;

        foreach ( $dataOrder['tax_lines'] as $tax ) {
            $totalTax = round( floatval( $tax->get_tax_total() ), $decimals );
            if ( $tax->get_shipping_tax_total() ) {
                $totalTax += round( floatval( $tax->get_shipping_tax_total() ), $decimals );
            }
            switch ( $tax->get_label() ) {
                case $this->tax_iva:
                    $ivaPercent = intval( str_replace( '%', '', WC_Tax::get_rate_percent( $tax->get_rate_id() ) ) ) / 100;
                    $iva        += $totalTax;
                    break;
                case $this->tax_ice:
                    $ice += $totalTax;
                    break;
                case $this->tax_propina:
                    if ( is_null( $propina ) ) {
                        $propina = 0;
                    }
                    $propina += $totalTax;
                    break;
                case $this->tax_tasa_aeroportuaria:
                    if ( is_null( $tasaAeroportuaria ) ) {
                        $tasaAeroportuaria = 0;
                    }
                    $tasaAeroportuaria += $totalTax;
                    break;
                case $this->tax_agencia_viaje:
                    if ( is_null( $agenciaDeViaje ) ) {
                        $agenciaDeViaje = 0;
                    }
                    $agenciaDeViaje += $totalTax;
                    break;
                case $this->tax_iac:
                    if ( is_null( $iac ) ) {
                        $iac = 0;
                    }
                    $iac += $totalTax;
                    break;
            }
        }

        $subtotalIva  = 0;
        $subtotalIva0 = 0;

        foreach ( $customer_order->get_items() as $item_key => $item_values ) {
            $item_data   = $item_values->get_data();
            $product_tax = $item_data['subtotal_tax'];
            if ( $product_tax != 0 && $iva != 0 ) {
                $subtotalIva += $item_data['subtotal'];
            } else {
                $subtotalIva0 += $item_data['subtotal'];
            }
        }

        foreach ( $dataOrder['shipping_lines'] as $item_key => $item_values ) {
            $item_data = $item_values->get_data();
            if ( $item_data['total_tax'] != 0 ) {
                $shipping_value = round( floatval( $item_data['total'] ), $decimals );
                $subtotalIva    += $shipping_value;
            } else {
                $subtotalIva0 += round( floatval( $item_data['total'] ), $decimals );
            }
        }

        foreach ( $dataOrder['coupon_lines'] as $item_key => $item_values ) {
            $item_data = $item_values->get_data();
            if ( $item_data['discount_tax'] != 0 ) {
                $subtotalIva -= round( floatval( $item_data['discount'] ), $decimals );
            } else {
                $subtotalIva0 -= round( floatval( $item_data['discount'] ), $decimals );
            }
        }

        foreach ( $dataOrder['fee_lines'] as $item_key => $item_values ) {
            $item_data = $item_values->get_data();
            if ( $item_data['total_tax'] != 0 ) {
                $subtotalIva += round( floatval( $item_data['total'] ), $decimals );
            } else {
                $subtotalIva0 += round( floatval( $item_data['total'] ), $decimals );
            }
        }


        $iva = round( $iva, $decimals );

        $auxTax = $ice;

        $subtotalIva  = round( $subtotalIva, $decimals );
        $subtotalIva0 = round( $subtotalIva0, $decimals );

        if ( ! is_null( $propina )
            || ! is_null( $tasaAeroportuaria )
            || ! is_null( $agenciaDeViaje )
            || ! is_null( $iac ) ) {
            $auxTax = new ExtraTaxes( ( ! is_null( $propina ) ? round( $propina, $decimals ) : 0 ),
                ( ! is_null( $tasaAeroportuaria ) ? round( $tasaAeroportuaria, $decimals ) : 0 ),
                ( ! is_null( $agenciaDeViaje ) ? round( $agenciaDeViaje, $decimals ) : 0 ),
                ( ! is_null( $iac ) ? round( $iac, $decimals ) : 0 ) );
        }

        $auxTax = round( $auxTax, $decimals );

        return new Amount( $subtotalIva, $iva, $subtotalIva0, $auxTax );
    }

    public function build_contact_details( $customer_order ) {

        return array( "email" => $customer_order->billing_email );
    }

    public function build_metadata( $dataOrder, $orderId ) {
        $metadataOrder = $dataOrder;
        $taxLines = [];
        foreach ( $metadataOrder['tax_lines'] as $item ) {
            array_push( $taxLines, $item->get_data() );
        }
        $metadataOrder['tax_lines'] = $taxLines;

        $shippingLines = [];
        foreach ( $metadataOrder['shipping_lines'] as $item ) {
            array_push( $shippingLines, $item->get_data() );
        }
        $metadataOrder['shipping_lines'] = $shippingLines;

        $feeLines = [];
        foreach ( $metadataOrder['fee_lines'] as $item ) {
            array_push( $feeLines, $item->get_data() );
        }
        $metadataOrder['fee_lines']   = $feeLines;
        $metadataOrder['plugin'] = "woocommerce";
        $metadataOrder['city'] = $metadataOrder['billing']['city'];
        $metadataOrder['country'] = $metadataOrder['billing']['country'];
        $metadataOrder['postalCode'] = $metadataOrder['billing']['postcode'];
        $metadataOrder['billingAddressPhone'] = $metadataOrder['billing']['phone'];
        $metadataOrder['province'] = $metadataOrder['billing']['state'];
        $optionalAddress = empty($metadataOrder['billing']['address_2']) ? ', ' . $metadataOrder['billing']['address_2'] : '';
        $metadataOrder['billingAddress'] = $metadataOrder['billing']['address_1'] . $optionalAddress;
        $metadataOrder['email'] = $metadataOrder['billing']['email'];
        $metadataOrder['name'] = trim($metadataOrder['billing']['first_name']) . ' ' . trim($metadataOrder['billing']['last_name']);
        $metadataOrder['totalAmount'] = floatval($metadataOrder['total']);
        $metadataOrder['ip'] = $metadataOrder['customer_ip_address'];
        $metadataOrder['orderId'] = $orderId;

        unset( $metadataOrder['coupon_lines'] );
        unset( $metadataOrder['meta_data'] );
        unset( $metadataOrder['line_items'] );
        unset( $metadataOrder['billing'] );

        return $metadataOrder;
    }

    public function payment_scripts() {


        if ( $this->environment === 'no' ) {
            wp_enqueue_script( 'kushki-prod-public.js', plugin_dir_url( __FILE__ ) . '/js/kushki-prod-public.js', array( 'jquery' ), null, false );
        } else {
            wp_enqueue_script( 'kushki-public.js', plugin_dir_url( __FILE__ ) . '/js/kushki-public.js', array( 'jquery' ), null, false );
        }

    }

    public function map_charge_sift($order){
        $customer_order = wc_get_order( $order );
        $optionalShippingAddress = empty($customer_order->get_shipping_address_2()) ? ', ' . $customer_order->get_shipping_address_2() : '';
        $optionalBillingAddress = empty($customer_order->get_billing_address_2()) ? ', ' . $customer_order->get_billing_address_2() : '';
        $order_details = array(
            "siteDomain"=> $this->getDomainUrl(),
            "shippingDetails"=> array(
                "fisrtName" => $customer_order->get_shipping_first_name(),
                "lastName" => $customer_order->get_shipping_last_name(),
                "phone"=> $customer_order->get_billing_phone(),
                "address"=> $customer_order->get_shipping_address_1() . $optionalShippingAddress,
                "city"=> $customer_order->get_shipping_city(),
                "region"=> $customer_order->get_shipping_state(),
                "country"=> $customer_order->get_shipping_country(),
                "zipCode"=> $customer_order->get_shipping_postcode()
            ),
            "billingDetails"=> array(
                "firstName" => $customer_order->get_billing_first_name(),
                "lastName" => $customer_order->get_billing_last_name(),
                "phone"=> $customer_order->get_billing_phone(),
                "address"=> $customer_order->get_billing_address_1() . $optionalBillingAddress,
                "city"=> $customer_order->get_billing_city(),
                "region"=> $customer_order->get_billing_state(),
                "country"=>  $customer_order->get_billing_country(),
                "zipCode"=> $customer_order->get_billing_postcode()
            )
        );

        foreach ($customer_order->get_items() as $item ){
            $product = wc_get_product($item->get_product_id());
            $products [] = array(
                "id"=> $item->get_product_id(),
                "title"=> $item->get_name(),
                "price"=> $item->get_total(),
                "sku"=> $product->get_sku(),
                "quantity"=>$item->get_quantity()
            );
        }

        return array(
            "orderDetails" => $order_details,
            "productDetails" => array(
                "products" => $products
            )
        );
    }

    public function form() {
        global $woocommerce;
        $cart = $woocommerce->cart;
        $amount_values = $this->get_amount_values( $cart );
        $callback_url = $this->getDomainUrl() . "/wc-api/confirm_payment";
        $currency = get_woocommerce_currency();

        if ( ! $this->public_id || ! $this->private_id ) {
            echo '<p> Error en la configuración </p>';
        } else {
            echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class=\'wc-credit-card-form wc-payment-form\'>';
            do_action( 'woocommerce_credit_card_form_start', $this->id );
            echo '<div id="kushki-payment-form"></div>';
            do_action( 'woocommerce_credit_card_form_end', $this->id );
            echo $this->render_kform( $amount_values, $currency, $callback_url );
            echo '<div class="clear"></div>  </fieldset>';
        }
    }

    public function getDomainUrl(): string
    {
        return get_bloginfo('url');
    }

    public function get_amount_values( $cart ): array
    {
        $cart_contents = $cart->cart_contents;
        $taxes = $cart->get_tax_totals();
        $shipping = $cart->get_shipping_total();

        $subtotalIva  = 0;
        $subtotalIva0 = 0;
        $iva          = 0;
        $ice          = 0;

        foreach( $cart_contents as $item ) {
            if ( $item['line_tax'] != 0 && $item['line_subtotal_tax'] != 0 ) {
                $subtotalIva += $item['line_total'];
            } else {
                $subtotalIva0 += $item['line_total'];
            }
        }

        foreach ( $taxes as $tax => $details ) {
            if ( $details->label == $this->tax_iva ) {
                $iva = $details->amount;
            } else {
                $ice = $details->amount;
            }
        }

        if ( $shipping != 0 && ( $iva !=  0 || $ice != 0)) {
            $subtotalIva += $shipping;
        } else {
            $subtotalIva0 += $shipping;
        }

        $subtotalIva  = round( floatval( $subtotalIva ), 2 );
        $subtotalIva0 = round( floatval( $subtotalIva0 ), 2 );
        $iva          = round( floatval( $iva ), 2 );
        $ice          = round( floatval( $ice ), 2 );

        return array(
            "subtotalIva"  => $subtotalIva,
            "subtotalIva0" => $subtotalIva0,
            "iva"          => $iva,
            "ice"          => $ice
        );
    }

    public function render_kform( $amount, $currency, $callback_url ) {
        $this->showAndHideButton();
        return '<script type="text/javascript">
	                var form = jQuery(\'#kushki-payment-form\');
        			var place_order_button = jQuery(\'#place_order\');
        			var kushki = new KushkiKFormCheckout({
        		                "form": "kushki-payment-form",
        		                "kformId": "WOOCOMMERCE",
        		                "publicMerchantId": \'' . $this->public_id . '\',
        		                "subtotalIva": \'' . $amount['subtotalIva'] . '\',
        		                "subtotalIva0": \'' . $amount['subtotalIva0'] . '\',
        		                "iva": \'' . $amount['iva'] . '\',
        		                "ice": \'' . $amount['ice'] . '\',
        		                "callbackUrl": \'' . $callback_url . '\',
        		                "currency": \'' . $currency . '\',
        		                "regional": false
        		    });
        		 </script>';
    }

    public function showAndHideButton(){
        echo '<script>
		var place_order_button = jQuery("#place_order");
		var payment_input = jQuery("#payment input");
		payment_input.ready( function() {
		    var valor = jQuery("input[name=payment_method]:checked", "#payment").val();
		    if(valor === "kushki"){
		        place_order_button.hide();
		    } else{
		        place_order_button.show();
		    }
		});
		payment_input.on("change", function() {
		    var valor = jQuery("input[name=payment_method]:checked", "#payment").val();
		    if(valor === "kushki"){
		        place_order_button.hide();
		    } else{
		        place_order_button.show();
		    }
		});
		</script>';
    }

    public function validate_fields() {
        WC()->session->set( 'reload_checkout', true );

		return true;
	}

    public function capture_payment($order_id)
    {
        try {
            $order = wc_get_order( $order_id );

            if ($order->get_meta("_kushki_preauth") == 1) {
                if ($order->get_meta("_kushki_capture_call") == 0) {
                    $order->update_meta_data("_kushki_capture_call", 1);
                    $order->save_meta_data();
                    $ticketNumber = $order->get_meta("_kushki_ticketNumber");
                    $merchantId  = $this->private_id;
                    $language    = KushkiLanguage::ES;
                    $currency    = $order->get_currency();
                    $environment = ( $this->environment == "yes" ) ? KushkiEnvironment::TESTING : KushkiEnvironment::PRODUCTION;
                    $kushki = new Kushki( $merchantId, $language, $currency, $environment );
                    $transaction_capture = $kushki->capture($order_id, $ticketNumber);

                    if ($transaction_capture->isSuccessful()) {
                        $order->add_order_note( __( 'Kushki payment completed.', 'kushki-gateway' ) . ' Ticket capture: ' . $transaction_capture->getTicketNumber() );
                        $order->add_meta_data("_kushki_ticketNumber_capture", $transaction_capture->getTicketNumber());
                        $order->add_meta_data("_kushki_capture", true);
                        $order->add_meta_data("_kushki_refund", true);
                        $order->save_meta_data();
                    } else {
                        apply_filters( 'woocommerce_payment_complete_order_status', $order->update_status( KushkiConstant::ORDER_FAILED ) );
                        return $order->add_order_note( __( 'Kushki capture failed. ', 'kushki-gateway' ) . $transaction_capture->getResponseText() );
                    }
                } else {
                    return $order->add_order_note( __( 'Kushki capture can not be recall. ', 'kushki-gateway' ));
                }
            }
        } catch (Exception $error) {
            return new WP_Error($error->getMessage());
        }
    }

    public function process_refund( $order_id, $amount = NULL, $reason = '' ) {

        try {

            $customer_order = wc_get_order( $order_id );
            $order_total     = $customer_order->get_total();
            $total_refunded  = $customer_order->get_total_refunded();

            if ($total_refunded != $order_total ){
                $customer_order->add_order_note( __( 'Kushki no puede hacer devoluciones parciales, debe hacer la devolución por el total del pedido.'));
                return false;
            }

            if ($customer_order->get_meta("_kushki_refund") != 1) {
                $customer_order->add_order_note( __( 'Kushki no puede hacer devoluciones en este tipo de orden.'));
                return false;
            }

            // Do your refund here. Refund $amount for the order with ID $order_id
            $merchantId  = $this->private_id;
            $language    = KushkiLanguage::ES;
            $currency    = $customer_order->get_currency();
            $environment = ( $this->environment == "yes" ) ? KushkiEnvironment::TESTING : KushkiEnvironment::PRODUCTION;
            $dataOrder   = $customer_order->get_data();
            $decimals    = wc_get_price_decimals();
            $amount = $this->build_amount( $dataOrder, $customer_order, $decimals );
            $kushki = new Kushki( $merchantId, $language, $currency, $environment );
            $refund = $kushki->refund($customer_order->get_meta("_kushki_ticketNumber"), $amount);

            if ( ! $refund ) {
                throw new Exception( __( 'An error occurred while attempting to create the refund using the payment gateway API.', 'woocommerce' ) );
            } else {
                if ($refund->isSuccessful()) {
                    $refundBody = $refund->getBody();
                    $customer_order->add_order_note(__('Kushki payment refund.', 'kushki-gateway') . ' Ticket: ' . $refundBody->ticketNumber);
                    return true;
                } else {
                    $customer_order->add_order_note( 'Kushki refund failed. ' . $refund->getResponseText() );
                    return false;
                }
            }
        }catch ( Exception $e ) {
            return new WP_Error( 'error', $e->getMessage() );
        }

    }

	public function confirm_payment_callback_handler() {
        header( 'HTTP/1.1 200 OK' );
        $token = isset($_REQUEST['token']) ? $_REQUEST['token'] : null;
        if (is_null($token)) return;

        $environment = ( $this->environment == "yes" ) ? KushkiEnvironment::TESTING : KushkiEnvironment::PRODUCTION;
        $kushki = new Kushki( $this->private_id, "", "", $environment );
        $transaction = $kushki->getCardAsyncTransaction($token);
        $transactionTransfer = $kushki->getTransferTransaction($token);

        if($transactionTransfer->isSuccessful()) {
            $this->DrawTransferCompleteTransactionResume($transactionTransfer);
        }
        else if($transaction->isSuccessful()) {
            $this->DrawCardAsyncCompleteTransactionResume($transaction);
        }
        else {
            wp_redirect(home_url('/checkout'));
            wc_add_notice("Error " . $transaction->getResponseCode() . ": " . $transaction->getResponseText(), 'error');
            exit();
        }
    }

    /**
     * @param \kushki\lib\Transaction $transaction
     */
    private function DrawCardAsyncCompleteTransactionResume(\kushki\lib\Transaction $transaction): void
    {
            $transaction_body = $transaction->getBody();
            $order_id = $transaction_body->metadata->orderId;
            $ticket_number = $transaction_body->ticketNumber;
            $transaction_status = $transaction_body->status;
            $customer_order = wc_get_order($order_id);

            switch ($transaction_status) {
                case KushkiConstant::APPROVED_STATUS:
                    $customer_order->payment_complete();
                    wc_reduce_stock_levels($order_id);
                    apply_filters('woocommerce_payment_complete_order_status', $customer_order->update_status(KushkiConstant::ORDER_COMPLETED));
                    $customer_order->add_order_note(__('Kushki payment completed, ', 'kushki-gateway') . 'with debit card. Ticket: ' . $ticket_number);
                    break;
                case KushkiConstant::DECLINED_STATUS:
                    apply_filters('woocommerce_payment_complete_order_status', $customer_order->update_status(KushkiConstant::ORDER_FAILED));
                    $customer_order->add_order_note(__('Kushki payment failed, ', 'kushki-gateway') . 'with debit card.');
                    break;
                default:
                    apply_filters('woocommerce_payment_complete_order_status', $customer_order->update_status(KushkiConstant::ORDER_PROCESSING));
                    $customer_order->add_order_note(__('Kushki payment on hold, ', 'kushki-gateway') . 'with debit card. Ticket: ' . $ticket_number);
                    break;
            }

            wc_empty_cart();
            wp_redirect($this->get_return_url($customer_order));
            exit();
    }

    /**
     * @param \kushki\lib\Transaction $transactionTransfer
     */
    private function DrawTransferCompleteTransactionResume(\kushki\lib\Transaction $transactionTransfer): void
    {
            $transaction_body = $transactionTransfer->getBody();
            $order_id = $transaction_body->metadata->orderId;
            $ticket_number = $transaction_body->ticketNumber;
            $transaction_status = $transaction_body->status;
            $customer_order = wc_get_order($order_id);
            switch ($transaction_status) {
                case KushkiConstant::APPROVED_STATUS:
                    $customer_order->payment_complete();
                    wc_reduce_stock_levels($order_id);
                    apply_filters('woocommerce_payment_complete_order_status', $customer_order->update_status(KushkiConstant::ORDER_COMPLETED));
                    $customer_order->add_order_note(__('Kushki payment completed, ', 'kushki-gateway') . 'with transfer. Ticket: ' . $ticket_number);
                    break;
                case KushkiConstant::DECLINED_STATUS:
                    apply_filters('woocommerce_payment_complete_order_status', $customer_order->update_status(KushkiConstant::ORDER_FAILED));
                    $customer_order->add_order_note(__('Kushki payment failed, ', 'kushki-gateway') . 'with transfer.');
                    break;
                default:
                    apply_filters('woocommerce_payment_complete_order_status', $customer_order->update_status(KushkiConstant::ORDER_PROCESSING));
                    $customer_order->add_order_note(__('Kushki payment on hold, ', 'kushki-gateway') . 'with transfer. Ticket: ' . $ticket_number);
                    break;
            }

            wc_empty_cart();
            wp_redirect($this->get_return_url($customer_order));
            exit();

    }

    function add_cash_detail($order_id) {
        $customer_order = wc_get_order( $order_id );
        $pin = $customer_order->get_meta("_pin");
        $pdfUrl = $customer_order->get_meta("_pdfUrl");
        if(!empty($pin) && !empty($pdfUrl)){
            echo '<ul>
                <li>Pin: <strong>'.$pin.'</strong></li>
                <li>Orden de pago: <strong><a target="_blank" href="'.$pdfUrl.'">'.$pdfUrl.'</a></strong></li>
            </ul>';
        }
    }

    public function webhook(){

        $payload = file_get_contents("php://input");

        try{
            $data =   json_decode($payload, true);
            $order_id = $data["orderId"];
            $transaction_reference = $data["transactionReference"];
            $status = $data["status"];

            if ( ! $transaction_reference ) {
                return;
            }

            $args = array(
                'transaction_id' => $transaction_reference
            );
            $query = new WC_Order_Query( $args );
            $order = $query->get_orders()[0];

            if($order->get_status() == KushkiConstant::ORDER_PROCESSING){
                if($status == KushkiConstant::APPROVAL){
                    $order->update_status( KushkiConstant::ORDER_COMPLETED );
                    $order->payment_complete();
                    wc_reduce_stock_levels($order_id);

                } else {
                    $order->update_status( KushkiConstant::ORDER_FAILED );

                }
            };
            http_response_code(200);

        } catch (\UnexpectedValueException $e){
            // Invalid payload
            http_response_code(400);
        }

    }


}

?>