<?php

use kushki\lib\Amount;
use kushki\lib\ExtraTaxes;
use kushki\lib\Kushki;
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
		add_action( 'admin_notices', array( $this, 'do_ssl_check' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );

		foreach ( $this->settings as $setting_key => $value ) {
			$this->$setting_key = $value;
		}
		// Lets check for SSL
		add_action('admin_notices', array($this, 'do_ssl_check'));
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			"process_admin_options"
		) );

	}

	public function do_ssl_check()
	{
		if ($this->enabled == "yes") {
			if (get_option('woocommerce_force_ssl_checkout') == "no") {
				echo "<div class=\"error\"><p>" . sprintf(__("<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>"), $this->method_title, admin_url('admin.php?page=wc-settings&tab=checkout')) . "</p></div>";
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
		if ( ! is_null( $propina )
		     || ! is_null( $tasaAeroportuaria )
		     || ! is_null( $agenciaDeViaje )
		     || ! is_null( $iac ) ) {
			$auxTax = new ExtraTaxes( ( ! is_null( $propina ) ? round( $propina, $decimals ) : 0 ),
				( ! is_null( $tasaAeroportuaria ) ? round( $tasaAeroportuaria, $decimals ) : 0 ),
				( ! is_null( $agenciaDeViaje ) ? round( $agenciaDeViaje, $decimals ) : 0 ),
				( ! is_null( $iac ) ? round( $iac, $decimals ) : 0 ) );
		}

		$amount = new Amount( $subtotalIva, $iva, $subtotalIva0, $auxTax );

		$taxLines = [];
		foreach ( $dataOrder['tax_lines'] as $item ) {
			array_push( $taxLines, $item->get_data() );
		}
		$dataOrder['tax_lines'] = $taxLines;

		$shippingLines = [];
		foreach ( $dataOrder['shipping_lines'] as $item ) {
			array_push( $shippingLines, $item->get_data() );
		}
		$dataOrder['shipping_lines'] = $shippingLines;

		$feeLines = [];
		foreach ( $dataOrder['fee_lines'] as $item ) {
			array_push( $feeLines, $item->get_data() );
		}
		$dataOrder['fee_lines'] = $feeLines;
        $customer_contact_details = $this->build_contact_details($customer_order);

		unset( $dataOrder['coupon_lines'] );
		unset( $dataOrder['meta_data'] );
		unset( $dataOrder['line_items'] );

		if ( $months > 0 ) {
			$transaction = $kushki->deferredCharge( $token, $amount, $months, $dataOrder, $customer_contact_details);
			// $transaction = $kushki->deferredCharge($token, $amount, $months);
		} else {
			$transaction = $kushki->charge( $token, $amount, $dataOrder, $customer_contact_details);
			// $transaction = $kushki->charge($token, $amount);
		}

		if ( $transaction->isSuccessful() ) {
			// Payment has been successful
			$customer_order->add_order_note( __( 'Kushki payment completed.', 'kushki-gategay' ) . ' Ticket: ' . $transaction->getTicketNumber() );

			apply_filters( 'woocommerce_payment_complete_order_status', $customer_order->update_status( 'completed' ) );

			// Empty the cart (Very important step)
			wc_empty_cart();

			// Redirect to thank you page
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $customer_order ),
			);
		} else {
			// Transaction was not succesful
			// Add notice to the cart
			WC()->session->set( 'reload_checkout', true );
			wc_add_notice( "Error " . $transaction->getResponseCode() . ": " . $transaction->getResponseText(), 'error' );
			// Add note to the order for your reference
			WC()->session->set( 'reload_checkout', false );

			return $customer_order->add_order_note( "Error " . $transaction->getResponseCode() . ": " . $transaction->getResponseText() );
		}
	}

	public function build_contact_details($customer_order){

        return array("email" => $customer_order->billing_email);
    }

	public function payment_scripts() {


		if ( $this->environment === 'no' ) {
			wp_enqueue_script( 'kushki-prod-public.js', plugin_dir_url( __FILE__ ) . '/js/kushki-prod-public.js', array( 'jquery' ), null, false );
		}
		else{
			wp_enqueue_script( 'kushki-public.js', plugin_dir_url( __FILE__ ) . '/js/kushki-public.js', array( 'jquery' ), null, false );
		}

	}

	public function form() {
		global $woocommerce;

		if ( ! $this->public_id || ! $this->private_id ) {
			echo '<p> Error en la configuración </p>';
		} else {
			echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class=\'wc-credit-card-form wc-payment-form\'>';
			do_action( 'woocommerce_credit_card_form_start', $this->id );
			echo '<div id="kushki-payment-form"></div>';
			do_action( 'woocommerce_credit_card_form_end', $this->id );
			echo $this->render_cajita( $woocommerce->cart->total );
			echo '<div class="clear"></div>  </fieldset>';
		}
	}

	public function render_cajita( $total ) {

		return '<script type="text/javascript">
						var form = jQuery(\'#kushki-payment-form\');
						var place_order_button = jQuery(\'#place_order\');
					
						    var kushki = new KushkiCheckout(
		                    {
		                    "form": "kushki-payment-form",
		                    "merchant_id": \'' . $this->public_id . '\',
		                    "amount": \'' . number_format( $total, 2 ) . '\',
		                    "currency": \'' . get_woocommerce_currency() . '\',
		                    "is_subscription": false
		                    });
						
		                	if(form.closest(".payment_box.payment_method_kushki").css(\'display\') === \'block\'){
                				place_order_button.hide();
            				}
            				form.closest(".payment_box.payment_method_kushki").bind("show", function() {
                				place_order_button.hide();
            				});

            				form.closest(".payment_box.payment_method_kushki").bind("hide", function() {
                				place_order_button.show();
            				});
                			
		            </script>';

	}

	public function validate_fields()
	{
		WC()->session->set( 'reload_checkout', true);
		return true;
	}

}

?>