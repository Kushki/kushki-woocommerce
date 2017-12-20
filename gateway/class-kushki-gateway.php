<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

use kushki\lib\Amount;
use kushki\lib\ExtraTaxes;
use kushki\lib\Kushki;
use kushki\lib\KushkiEnvironment;
use kushki\lib\KushkiLanguage;

/**
 * Created by PhpStorm.
 * User: zerocooljs
 * Date: 10/2/16
 * Time: 08:57
 */
class Kushki_Gateway extends WC_Payment_Gateway_CC
{

    private $environment;
    private $private_id;
    private $public_id;
    private $tax_iva;
    private $tax_ice;
    private $tax_propina;
    private $tax_tasa_aeroportuaria;
    private $tax_agencia_viaje;
    private $tax_iac;

    public function __construct()
    {
        // The global ID for this Payment method
        $this->id = "kushki";

        // The Title shown on the top of the Payment Gateways Page next to all the other Payment Gateways
        $this->method_title = __("Kushki", 'kushki-gateway');

        // The description for this Payment Gateway, shown on the actual Payment options page on the backend
        $this->method_description = __("Kushki payment gateway for WooCommerce.", 'kushki-gateway');

        // The title to be used for the vertical tabs that can be ordered top to bottom
        $this->title = __("Kushki", 'kushki-gateway');

        // If you want to show an image next to the gateway's name on the frontend, enter a URL to an image.
        $this->icon = null;

        // Bool. Can be set to true if you want payment fields to show on the checkout
        // if doing a direct integration, which we are doing in this case
        $this->has_fields = true;

        // Supports the default credit card form
        $this->supports = array('default_credit_card_form');

        // This basically defines your settings which are then loaded with init_settings()
        $this->init_form_fields();

        // After init_settings() is called, you can get the settings and load them into variables, e.g:
        // $this->title = $this->get_option( 'title' );
        $this->init_settings();

        // Turn these settings into variables we can use
        foreach ($this->settings as $setting_key => $value) {
            $this->$setting_key = $value;
        }

        // Lets check for SSL
        add_action('admin_notices', array($this, 'do_ssl_check'));

        // Save settings
        if (is_admin()) {
            // Versions over 2.0
            // Save our administration options. Since we are not going to be doing anything special
            // we have not defined 'process_admin_options' in this class so the method in the parent
            // class will be used instead
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
                $this,
                'process_admin_options'
            ));
        }
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable / Disable', 'kushki-gateway'),
                'label' => __('Enable this payment gateway', 'kushki-gateway'),
                'type' => 'checkbox',
                'default' => 'no',
            ),
            'title' => array(
                'title' => __('Title', 'kushki-gateway'),
                'type' => 'text',
                'desc_tip' => __('Payment title the customer will see during the checkout process.', 'kushki-gateway'),
                'default' => __('Kushki', 'kushki-gateway'),
            ),
            'description' => array(
                'title' => __('Description', 'kushki-gateway'),
                'type' => 'textarea',
                'desc_tip' => __('Payment description the customer will see during the checkout process.', 'kushki-gateway'),
                'default' => __('Pay securely using your credit card.', 'kushki-gateway'),
                'css' => 'max-width:350px;'
            ),
            'public_id' => array(
                'title' => __('Merchant Public ID', 'kushki-gateway'),
                'type' => 'text',
                'desc_tip' => __('This is the merchant public id provided by Kushki.', 'kushki-gateway'),
            ),
            'private_id' => array(
                'title' => __('Merchant Private ID', 'kushki-gateway'),
                'type' => 'password',
                'desc_tip' => __('This is the merchant private id provided by Kushki.', 'kushki-gateway'),
            ),
            'environment' => array(
                'title' => __('Test Mode', 'kushki-gateway'),
                'label' => __('Enable Test Mode', 'kushki-gateway'),
                'type' => 'checkbox',
                'description' => __('Place the payment gateway in test mode.', 'kushki-gateway'),
                'default' => 'yes',
            ),
            'tax_details' => array(
                'title' => __('Tax Settings', 'kushki-gateway'),
                'type' => 'title',
                'description' => __("Set the current defined name for each tax on the Woocommerce Tax settings.<br>" .
                                     "<strong>NOTE: Keep in blank the taxes unused. </strong>", 'kushki-gateway'),
            ),
            'tax_iva' => array(
                'title' => __('IVA', 'kushki-gateway'),
                'type' => 'text',
                'description' => __('Defined tax name for IVA.', 'kushki-gateway'),
                'default' => 'IVA',
                'desc_tip' => true,
                'placeholder' => __('Required', 'kushki-gateway')
            ),
            'tax_ice' => array(
                'title' => __('ICE', 'kushki-gateway'),
                'type' => 'text',
                'description' => __('Defined tax name for ICE, this is used only on Ecuador.', 'kushki-gateway'),
                'desc_tip' => true,
                'placeholder' => __('Optional, only used on Ecuador', 'kushki-gateway')
            ),
            'tax_propina' => array(
                'title' => __('Propina', 'kushki-gateway'),
                'type' => 'text',
                'description' => __('Defined tax name for Propina, this is used only on Colombia.', 'kushki-gateway'),
                'desc_tip' => true,
                'placeholder' => __('Optional, only used on Colombia', 'kushki-gateway')
            ),
            'tax_tasa_aeroportuaria' => array(
                'title' => __('Tasa Aeroportuaria', 'kushki-gateway'),
                'type' => 'text',
                'description' => __('Defined tax name for Tasa Aeroportuaria, this is used only on Colombia.', 'kushki-gateway'),
                'desc_tip' => true,
                'placeholder' => __('Optional, only used on Colombia', 'kushki-gateway')
            ),
            'tax_agencia_viaje' => array(
                'title' => __('Agencia de Viaje', 'kushki-gateway'),
                'type' => 'text',
                'description' => __('Defined tax name for Agencia de Viaje, this is used only on Colombia.', 'kushki-gateway'),
                'desc_tip' => true,
                'placeholder' => __('Optional, only used on Colombia', 'kushki-gateway')
            ),
            'tax_iac' => array(
                'title' => __('IAC', 'kushki-gateway'),
                'type' => 'text',
                'description' => __('Defined tax name for IAC, this is used only on Colombia.', 'kushki-gateway'),
                'desc_tip' => true,
                'placeholder' => __('Optional, only used on Colombia', 'kushki-gateway')
            )
        );
    }

    public function process_payment($order_id)
    {

        global $woocommerce;
        // Get this Order's information so that we know
        // who to charge and how much
        $customer_order = new WC_Order($order_id);

        $merchantId = $this->private_id;
        $language = KushkiLanguage::ES;
        $currency = $customer_order->get_currency();
        $decimals = wc_get_price_decimals();
        $environment = ($this->environment == "yes") ? KushkiEnvironment::TESTING : KushkiEnvironment::PRODUCTION;
        $dataOrder = $customer_order->get_data();

        $kushki = new Kushki($merchantId, $language, $currency, $environment);

        $token = $_POST['kushkiToken'];
        $months = intval($_POST['kushkiDeferred']);
        $subtotal = round($customer_order->get_total() - $customer_order->get_total_tax(), $decimals);
        $iva = 0;
        $ice = 0;
        $propina = null;
        $tasaAeroportuaria = null;
        $agenciaDeViaje = null;
        $iac = null;
        $ivaPercent = 0;

        foreach ($dataOrder['tax_lines'] as $tax) {
            $totalTax = floatval($tax->get_tax_total());
            if($tax->get_shipping_tax_total()) {
                $totalTax += floatval($tax->get_shipping_tax_total());
            }
            switch ($tax->get_label()) {
                case $this->tax_iva:
                    $ivaPercent = intval(str_replace('%', '', WC_Tax::get_rate_percent($tax->get_rate_id()))) / 100;
                    $iva += $totalTax;
                    break;
                case $this->tax_ice:
                    $ice += $totalTax;
                    break;
                case $this->tax_propina:
                    if (is_null($propina)) {
                        $propina = 0;
                    }
                    $propina += $totalTax;
                    break;
                case $this->tax_tasa_aeroportuaria:
                    if (is_null($tasaAeroportuaria)) {
                        $tasaAeroportuaria = 0;
                    }
                    $tasaAeroportuaria += $totalTax;
                    break;
                case $this->tax_agencia_viaje:
                    if (is_null($agenciaDeViaje)) {
                        $agenciaDeViaje = 0;
                    }
                    $agenciaDeViaje += $totalTax;
                    break;
                case $this->tax_iac:
                    if (is_null($iac)) {
                        $iac = 0;
                    }
                    $iac += $totalTax;
                    break;
            }
        }

        $subtotalIva = 0;
        if($ivaPercent > 0){
            $subtotalIva = round($iva / $ivaPercent, $decimals);
        }
        $subtotalIva0 = round($subtotal - $subtotalIva, $decimals);

        $iva = round($iva, $decimals);

        $auxTax = $ice;
        if (!is_null($propina)
            || !is_null($tasaAeroportuaria)
            || !is_null($agenciaDeViaje)
            || !is_null($iac)) {
            $auxTax = new ExtraTaxes((!is_null($propina) ? round($propina, $decimals) : 0),
                (!is_null($tasaAeroportuaria) ? round($tasaAeroportuaria, $decimals) : 0),
                (!is_null($agenciaDeViaje) ? round($agenciaDeViaje, $decimals) : 0),
                (!is_null($iac) ? round($iac, $decimals) : 0));
        }

        $amount = new Amount($subtotalIva, $iva, $subtotalIva0, $auxTax);

        $taxLines = [];
        foreach ($dataOrder['tax_lines'] as $item){
            array_push($taxLines,$item->get_data());
        }
        $dataOrder['tax_lines'] = $taxLines;

        $shippingLines = [];
        foreach ($dataOrder['shipping_lines'] as $item){
            array_push($shippingLines,$item->get_data());
        }
        $dataOrder['shipping_lines'] = $shippingLines;

        $feeLines = [];
        foreach ($dataOrder['fee_lines'] as $item){
            array_push($feeLines,$item->get_data());
        }
        $dataOrder['fee_lines'] = $feeLines;

        unset($dataOrder['coupon_lines']);
        unset($dataOrder['meta_data']);
        unset($dataOrder['line_items']);


        if ($months > 0) {
            $transaction = $kushki->deferredCharge($token, $amount, $months, $dataOrder);
            // $transaction = $kushki->deferredCharge($token, $amount, $months);
        } else {
            $transaction = $kushki->charge($token, $amount, $dataOrder);
            // $transaction = $kushki->charge($token, $amount);
        }

        if ($transaction->isSuccessful()) {
            // Payment has been successful
            $customer_order->add_order_note(__('Kushki payment completed.', 'kushki-gategay') . ' Ticket: ' . $transaction->getTicketNumber());

            // Mark order as Paid
            $customer_order->payment_complete();

            // Empty the cart (Very important step)
            $woocommerce->cart->empty_cart();

            // Redirect to thank you page
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($customer_order),
            );
        } else {
            // Transaction was not succesful
            // Add notice to the cart
            wc_add_notice("Error " . $transaction->getResponseCode() . ": " . $transaction->getResponseText(), 'error');
            // Add note to the order for your reference
            return $customer_order->add_order_note("Error " . $transaction->getResponseCode() . ": " . $transaction->getResponseText());
        }
    }

    public function validate_fields()
    {
        return true;
    }

    public function do_ssl_check()
    {
        if ($this->enabled == "yes") {
            if (get_option('woocommerce_force_ssl_checkout') == "no") {
                echo "<div class=\"error\"><p>" . sprintf(__("<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>"), $this->method_title, admin_url('admin.php?page=wc-settings&tab=checkout')) . "</p></div>";
            }
        }
    }

    public function form()
    {
        global $woocommerce;

        ?>

        <fieldset id="wc-<?php echo esc_attr($this->id); ?>-cc-form"
                  class='wc-credit-card-form wc-payment-form'>
            <?php do_action('woocommerce_credit_card_form_start', $this->id); ?>
            <div id="kushki-form">
            </div>
            <?php do_action('woocommerce_credit_card_form_end', $this->id); ?>
            <div class="clear"></div>
        </fieldset>
        <script type="text/javascript">
            var kushki = new KushkiCheckout({
                "form": "kushki-form",
                "merchant_id": '<?php echo $this->public_id ?>',
                "amount": '<?php echo number_format($woocommerce->cart->total, 2) ?>',
                "currency": '<?php echo get_woocommerce_currency() ?>',
                "is_subscription": false
            }<?php echo ($this->environment == "yes") ? "" : ", \"https://cdn.kushkipagos.com/index.html\""; ?>);
            if(jQuery('#kushki-form').closest(".payment_box.payment_method_kushki").css('display') == 'block'){
                jQuery('#place_order').hide();
            }
            jQuery('#kushki-form').closest(".payment_box.payment_method_kushki").bind("show", function() {
                jQuery('#place_order').hide();
            });

            jQuery('#kushki-form').closest(".payment_box.payment_method_kushki").bind("hide", function() {
                jQuery('#place_order').show();
            });
        </script>
        <?php
    }
}