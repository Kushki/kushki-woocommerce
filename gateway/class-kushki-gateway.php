<?php
use kushki\lib\Amount;
use kushki\lib\Kushki;
use kushki\lib\KushkiCurrency;
use kushki\lib\KushkiEnvironment;
use kushki\lib\KushkiLanguage;

/**
 * Created by PhpStorm.
 * User: zerocooljs
 * Date: 10/2/16
 * Time: 08:57
 */
class Kushki_Gateway extends WC_Payment_Gateway_CC {

	private $environment;
	private $private_id;
	private $public_id;

	public function __construct() {
		// The global ID for this Payment method
		$this->id = "kushki";

		// The Title shown on the top of the Payment Gateways Page next to all the other Payment Gateways
		$this->method_title = __( "Kushki", 'kushki-gateway' );

		// The description for this Payment Gateway, shown on the actual Payment options page on the backend
		$this->method_description = __( "Kushki payment gateway for WooCommerce.", 'kushki-gateway' );

		// The title to be used for the vertical tabs that can be ordered top to bottom
		$this->title = __( "Kushki", 'kushki-gateway' );

		// If you want to show an image next to the gateway's name on the frontend, enter a URL to an image.
		$this->icon = null;

		// Bool. Can be set to true if you want payment fields to show on the checkout
		// if doing a direct integration, which we are doing in this case
		$this->has_fields = true;

		// Supports the default credit card form
		$this->supports = array( 'default_credit_card_form' );

		// This basically defines your settings which are then loaded with init_settings()
		$this->init_form_fields();

		// After init_settings() is called, you can get the settings and load them into variables, e.g:
		// $this->title = $this->get_option( 'title' );
		$this->init_settings();

		// Turn these settings into variables we can use
		foreach ( $this->settings as $setting_key => $value ) {
			$this->$setting_key = $value;
		}

		// Lets check for SSL
		add_action( 'admin_notices', array( $this, 'do_ssl_check' ) );

		// Save settings
		if ( is_admin() ) {
			// Versions over 2.0
			// Save our administration options. Since we are not going to be doing anything special
			// we have not defined 'process_admin_options' in this class so the method in the parent
			// class will be used instead
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
				$this,
				'process_admin_options'
			) );
		}
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'     => array(
				'title'   => __( 'Enable / Disable', 'kushki-gateway' ),
				'label'   => __( 'Enable this payment gateway', 'kushki-gateway' ),
				'type'    => 'checkbox',
				'default' => 'no',
			),
			'title'       => array(
				'title'    => __( 'Title', 'kushki-gateway' ),
				'type'     => 'text',
				'desc_tip' => __( 'Payment title the customer will see during the checkout process.', 'kushki-gateway' ),
				'default'  => __( 'Kushki', 'kushki-gateway' ),
			),
			'description' => array(
				'title'    => __( 'Description', 'kushki-gateway' ),
				'type'     => 'textarea',
				'desc_tip' => __( 'Payment description the customer will see during the checkout process.', 'kushki-gateway' ),
				'default'  => __( 'Pay securely using your credit card.', 'kushki-gateway' ),
				'css'      => 'max-width:350px;'
			),
			'public_id'   => array(
				'title'    => __( 'Merchant Public ID', 'kushki-gateway' ),
				'type'     => 'text',
				'desc_tip' => __( 'This is the merchant public id provided by Kushki.', 'kushki-gateway' ),
			),
			'private_id'  => array(
				'title'    => __( 'Merchant Private ID', 'kushki-gateway' ),
				'type'     => 'password',
				'desc_tip' => __( 'This is the merchant private id provided by Kushki.', 'kushki-gateway' ),
			),
			'environment' => array(
				'title'       => __( 'Test Mode', 'kushki-gateway' ),
				'label'       => __( 'Enable Test Mode', 'kushki-gateway' ),
				'type'        => 'checkbox',
				'description' => __( 'Place the payment gateway in test mode.', 'kushki-gateway' ),
				'default'     => 'yes',
			)
		);
	}

	public function process_payment( $order_id ) {

		global $woocommerce;
		// Get this Order's information so that we know
		// who to charge and how much
		$customer_order = new WC_Order( $order_id );

		$merchantId  = $this->private_id;
		$language    = KushkiLanguage::ES;
		$currency    = KushkiCurrency::USD;
		$environment = ( $this->environment == "yes" ) ? KushkiEnvironment::PRODUCTION : KushkiEnvironment::TESTING;

		$kushki = new Kushki( $merchantId, $language, $currency, $environment );

		$token        = $_POST['kushkiToken'];
		$months       = intval( $_POST['kushkiDeferred'] );
		$total        = $customer_order->order_total;
		$subtotalIva  = round($total / 1.14,2);
		$iva          = $total - $subtotalIva;
		$subtotalIva0 = 0;
		$ice          = 0;
		$amount       = new Amount( $subtotalIva, $iva, $subtotalIva0, $ice );
		if ( $months > 0 ) {
			$transaction = $kushki->deferredCharge( $token, $amount, $months );
		} else {
			$transaction = $kushki->charge( $token, $amount );
		}

		if ( $transaction->isSuccessful() ) {
			// Payment has been successful
			$customer_order->add_order_note( __( 'Kushki payment completed.', 'kushki-gategay' ) . ' Ticket: ' . $transaction->getTicketNumber() );

			// Mark order as Paid
			$customer_order->payment_complete();

			// Empty the cart (Very important step)
			$woocommerce->cart->empty_cart();

			// Redirect to thank you page
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $customer_order ),
			);
		} else {
			// Transaction was not succesful
			// Add notice to the cart
			wc_add_notice( "Error " . $transaction->getResponseCode() . ": " . $transaction->getResponseText(), 'error' );
			// Add note to the order for your reference
			$customer_order->add_order_note( "Error " . $transaction->getResponseCode() . ": " . $transaction->getResponseText() );
		}


	}

	public function validate_fields() {
		return true;
	}

	public function do_ssl_check() {
		if ( $this->enabled == "yes" ) {
			if ( get_option( 'woocommerce_force_ssl_checkout' ) == "no" ) {
				echo "<div class=\"error\"><p>" . sprintf( __( "<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) . "</p></div>";
			}
		}
	}

	public function form() {
		global $woocommerce;

		?>

        <fieldset id="wc-<?php echo esc_attr( $this->id ); ?>-cc-form" class='wc-credit-card-form wc-payment-form'>
			<?php do_action( 'woocommerce_credit_card_form_start', $this->id ); ?>
            <div id="kushki-form">
            </div>
			<?php do_action( 'woocommerce_credit_card_form_end', $this->id ); ?>
            <div class="clear"></div>
        </fieldset>
        <script type="text/javascript">
            var kushki = new KushkiCheckout({
                "form": "kushki-form",
                "merchant_id": '<?php echo $this->public_id ?>',
                "amount": '<?php echo number_format($woocommerce->cart->total,2) ?>',
                "is_subscription": false
            }<?php echo ( !$this->environment == "yes" )? ", \"https://p1.kushkipagos.com/kushki/kushki/index.html\"":""; ?>);
            jQuery('#place_order').hide();
        </script>
		<?php
	}
}