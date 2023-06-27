<?php
/*
Plugin Name: Woocommerce - Gopay
Plugin URL: https://cleverstart.cz
Description: Přidá možnost zaplatit přes Gopay
Version: 1.0.28
Author: Pavel Janíček
Author URI: https://cleverstart.cz
*/
use GoPay\Definition\Language;
use GoPay\Definition\Payment\Currency;
use GoPay\Definition\Payment\PaymentInstrument;
use GoPay\Definition\Payment\BankSwiftCode;
use GoPay\Definition\Payment\Recurrence;
use GoPay\Definition\TokenScope;
use GoPay\Definition\Response\PaymentStatus;

require __DIR__ . '/vendor/autoload.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://plugins.cleverstart.cz/?action=get_metadata&slug=woogopay',
	__FILE__, //Full path to the main plugin file or functions.php.
	'woogopay'
);

add_action('plugins_loaded', 'woocommerce_cleverstart_gopay_init', 0);

function woocommerce_cleverstart_gopay_init(){
  if(!class_exists('WC_Payment_Gateway')) return;

  class WC_Cleverstart_Gopay extends WC_Payment_Gateway{
		public function __construct(){
      
      $this->id = 'gopay';
      $this->medthod_title = 'GoPay';
      $this->has_fields = false;

      $this->init_form_fields();
      $this->init_settings();

      $this->title = $this->settings['title'];
      $this->description = $this->settings['description'];
      $this->goid = $this->settings['goid'];
      $this->clientid = $this->settings['clientid'];
			$this->clientsecret = $this->settings['clientsecret'];
      $this->test_goid = $this->settings['test_goid'];
      $this->test_clientid = $this->settings['test_clientid'];
      $this->test_clientsecret = $this->settings['test_clientsecret'];
			$this->test_mode = $this->settings['test_mode'];
      $iso_code = explode('_', get_locale());
      $this->language =strtoupper($iso_code[0]);

      if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
             } else {
                add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
            }

   }

   function init_form_fields(){

       $this -> form_fields = array(
                'enabled' => array(
                    'title' => __('Povolit / Zakázat', 'woothepay'),
                    'type' => 'checkbox',
                    'label' => __('Povolit platby přes Gopay', 'woothepay'),
                    'default' => 'no'),
                'title' => array(
                    'title' => __('Název:', 'woothepay'),
                    'type'=> 'text',
                    'description' => __('Zde můžete změnit název brány zobrazovaný během nákupu', 'woothepay'),
                    'default' => __('GoPay', 'woothepay')),
                'description' => array(
                    'title' => __('Popis:', 'woothepay'),
                    'type' => 'textarea',
                    'description' => __('Zobrazí popis platební brány během nákupu', 'woothepay'),
                    'default' => __('Zaplaťte rychle a snadno platební kartou.', 'woothepay')),
                'test_goid' => array(
                    'title' => __('Test GoID', 'woothepay'),
                    'type' => 'text',
                    'description' => __('Test GoID:')),
                'test_clientid' => array(
                    'title' => __('Test Client ID', 'woothepay'),
                    'type' => 'text',
                    'description' => __('Test Client ID:')),
                'test_clientsecret' => array(
                    'title' => __('Test Client Secret', 'woothepay'),
                    'type' => 'text',
                    'description' => __('Test Client Secret:')),
                'test_jmeno' =>  array(
                    'title' => __('Test Uživatelské jméno', 'woothepay'),
                    'type' => 'text',
                    'description' => __('Test Uživatelské jméno:')),
                'test_heslo' =>  array(
                    'title' => __('Test heslo', 'woothepay'),
                    'type' => 'text',
                    'description' => __('Test heslo:')),
                'goid' => array(
                    'title' => __('GoID', 'woothepay'),
                    'type' => 'text',
                    'description' => __('GoID:')),
                'clientid' => array(
                    'title' => __('Client ID', 'woothepay'),
                    'type' => 'text',
                    'description' => __('Client ID:')),
                'clientsecret' => array(
                    'title' => __('Client Secret', 'woothepay'),
                    'type' => 'text',
                    'description' => __('Client Secret:')),
                 'jmeno' =>  array(
                    'title' => __('Uživatelské jméno', 'woothepay'),
                    'type' => 'text',
                    'description' => __('Uživatelské jméno:')),
                 'heslo' =>  array(
                    'title' => __('Heslo', 'woothepay'),
                    'type' => 'text',
                    'description' => __('Heslo:')),
								'test_mode' => array(
									'title' => __('Testovací mód?', 'woothepay'),
									'type' => 'checkbox',
									'label' => __('Je brána v testovacím módu?', 'woothepay'),
									'default' => 'no'
								)
            );
    }
    public function admin_options(){
        echo '<h3>'.__('Platební brána Gopay', 'woothepay').'</h3>';
        echo '<p>'.__('Gopay je rychlá a spolehlivá platební brána pro příjem plateb kartou.').'</p>';
        echo '<table class="form-table">';
        // Generate the HTML For the settings form.
        $this -> generate_settings_html();
        echo '</table>';

    }

		function payment_fields(){
        if($this -> description) echo wpautop(wptexturize($this -> description));

    }

    public function get_all_swift_codes(){
      return array(
        BankSwiftCode::CESKA_SPORITELNA,
        BankSwiftCode::KOMERCNI_BANKA,
        BankSwiftCode::RAIFFEISENBANK,
        BankSwiftCode::MBANK,
        BankSwiftCode::FIO_BANKA,
        BankSwiftCode::CSOB,
        BankSwiftCode::ERA,
        BankSwiftCode::UNICREDIT_BANK_CZ,
        BankSwiftCode::VSEOBECNA_VEROVA_BANKA_BANKA,
        BankSwiftCode::TATRA_BANKA,
        BankSwiftCode::UNICREDIT_BANK_SK,
        BankSwiftCode::SLOVENSKA_SPORITELNA,
        BankSwiftCode::POSTOVA_BANKA,
        BankSwiftCode::CSOB_SK,
        BankSwiftCode::SBERBANK_SLOVENSKO,
        BankSwiftCode::SPECIAL,
        BankSwiftCode::MBANK1,
        BankSwiftCode::CITI_HANDLOWY,
        BankSwiftCode::IKO,
        BankSwiftCode::INTELIGO,
        BankSwiftCode::PLUS_BANK,
        BankSwiftCode::BANK_BPH_SA,
        BankSwiftCode::TOYOTA_BANK,
        BankSwiftCode::VOLKSWAGEN_BANK,
        BankSwiftCode::SGB,
        BankSwiftCode::POCZTOWY_BANK,
        BankSwiftCode::BGZ_BANK,
        BankSwiftCode::IDEA,
        BankSwiftCode::BPS,
        BankSwiftCode::GETIN_ONLINE,
        BankSwiftCode::BLIK,
        BankSwiftCode::NOBLE_BANK,
        BankSwiftCode::ORANGE,
        BankSwiftCode::BZ_WBK,
        BankSwiftCode::RAIFFEISEN_BANK_POLSKA_SA,
        BankSwiftCode::POWSZECHNA_KASA_OSZCZEDNOSCI_BANK_POLSKI_SA,
        BankSwiftCode::ALIOR_BANK,
        BankSwiftCode::ING_BANK_SLASKI,
        BankSwiftCode::PEKAO_SA,
        BankSwiftCode::GETIN_ONLINE1,
        BankSwiftCode::BANK_MILLENNIUM,
        BankSwiftCode::BANK_OCHRONY_SRODOWISKA,
        BankSwiftCode::BNP_PARIBAS_POLSKA,
        BankSwiftCode::CREDIT_AGRICOLE,
        BankSwiftCode::DEUTSCHE_BANK_POLSKA_SA,
        BankSwiftCode::E_SKOK,
        BankSwiftCode::EUROBANK,
        BankSwiftCode::POLSKI_BANK_PRZEDSIEBIORCZOSCI_SPOLKA_AKCYJNA

      );
    }

    public function process_payment($order_id){
        global $woocommerce;


        $order = new WC_Order($order_id);
        $items = array();
        foreach ( $order->get_items( array( 'line_item', 'fee' ) ) as $item ) {
            array_push($items,
                      array('name' => $item['name'],
                            'count' => $item['quantity'],
                            'amount' => (int)($order->get_item_subtotal( $item, false ) * 100 * $item['quantity'])
                           )
                      );
        }
        $contact =array(
          'first_name' => $order->get_billing_first_name(),
          'last_name'=> $order->get_billing_last_name(),
          'email'=> $order->get_billing_email(),
          'phone_number'=>$order->get_billing_phone(),
          'city'=>$order->get_billing_city(),
          'street'=>$order->get_billing_address_1().$order->get_billing_address_2(),
          'postal_code' => $order->get_billing_postcode(),
          'country_code' => $order->get_billing_country()
        );
        $totalInCents = $order->get_total() * 100;
        $totalInCents = (int)$totalInCents;
        $redirect_url = $order->get_checkout_order_received_url(). "&listener=woogopay&order_id=" .$order_id;

        if ( 'no' == $this->settings['test_mode'] ) {
          $gopay = GoPay\payments([
              'goid' => $this->settings['goid'],
              'clientId' => $this->settings['clientid'],
              'clientSecret' => $this->settings['clientsecret'],
              'isProductionMode' => true,
              'language' => $this->language
            ]);
				}else {
          $gopay = GoPay\payments([
            'goid' => $this->settings['test_goid'],
            'clientId' => $this->settings['test_clientid'],
            'clientSecret' => $this->settings['test_clientsecret'],
            'isProductionMode' => false,
            'language' => $this->language
            ]);
				}
        $response = $gopay->createPayment([
          'payer' => [
            'default_payment_instrument' => PaymentInstrument::PAYMENT_CARD,
            'allowed_payment_instruments' => [PaymentInstrument::PAYMENT_CARD,PaymentInstrument::BANK_ACCOUNT],
            'default_swift' => BankSwiftCode::FIO_BANKA,
            'allowed_swifts' => $this->get_all_swift_codes(),
            'contact' => $contact
          ],
          'amount' => $totalInCents,
          'currency' => $order->get_currency(),
          'order_number' => strval($order_id),
          'order_description' => 'Order #'.$order_id,
          'items' => $items,
          'callback' => [
            'return_url' => $redirect_url,
            'notification_url' => $redirect_url
          ],
          'lang' => $this->language, // if lang is not specified, then default lang is used
        ]);
        if ($response->hasSucceed()) {
          $order->reduce_order_stock();
				  $woocommerce -> cart -> empty_cart();
          $order->update_status('on-hold', __( 'Očekáváme platbu', 'woogopay' ));
          return array(
            'result' => 'success',
            'redirect' => $response->json['gw_url']
          );
        }else {
          wc_add_notice(  $response->__toString(), 'error' );
          $order -> add_order_note($response->__toString());
          return array(
			         'result' => 'failure',
			         'redirect' => ''
	         );
        }
    }


    public function check_gopay_response($order_id){
      $payment_id = $_GET['id'];
      $order = new WC_Order( $order_id );
      if ( 'no' == $this->settings['test_mode'] ) {
        $gopay = GoPay\payments([
            'goid' => $this->settings['goid'],
            'clientId' => $this->settings['clientid'],
            'clientSecret' => $this->settings['clientsecret'],
            'isProductionMode' => true,
            'language' => $this->language
          ]);
      }else {
        $gopay = GoPay\payments([
          'goid' => $this->settings['test_goid'],
          'clientId' => $this->settings['test_clientid'],
          'clientSecret' => $this->settings['test_clientsecret'],
          'isProductionMode' => false,
          'language' => $this->language
          ]);
      }
      $response = $gopay->getStatus($payment_id);
      if ($response->hasSucceed()) {
        // response format: https://doc.gopay.com/en/?shell#status-of-the-payment
        if ($response->json['state'] == PaymentStatus::PAID){
          $order -> payment_complete();
					$order -> add_order_note('Objednávka úspěšně zaplacena');
        }
        elseif ($response->json['state'] == PaymentStatus::AUTHORIZED) {
          $order -> update_status('pending');
					$order -> add_order_note('Čekáme na platbu. Platba autorizována na straně GoPay');
        }
        elseif ($response->json['state'] == PaymentStatus::CREATED) {
          $order -> update_status('failed');
					$order -> add_order_note('Čekáme na platbu. Platba vytvořena na straně Gopay a čeká se na zaplacení');
        }
        elseif ($response->json['state'] == PaymentStatus::PAYMENT_METHOD_CHOSEN) {
          $order -> update_status('failed');
					$order -> add_order_note('Čekáme na platbu. Vybrána metoda platby na straně GoPay');
        }
        elseif ($response->json['state'] == PaymentStatus::CANCELED) {
          $order -> update_status('failed');
          $order -> add_order_note('Objednávka zrušena na straně GoPay');
        }
        elseif ($response->json['state'] == PaymentStatus::TIMEOUTED) {
          $order -> update_status('failed');
          $order -> add_order_note('Objednávka vypršela na straně GoPay');
        }
        else{
          $order -> update_status('failed');
					$order -> add_order_note('Chyba objednávky');
          $order -> add_order_note('Status objednávky na straně Gopay: '. $response);
        }
      } else {
        $order -> update_status('failed');
        $order -> add_order_note('Kritická Chyba objednávky');
        $errorResponse = $response->statusCode .': '.$response;
        $order -> add_order_note($errorResponse);
      }
    }
}//class WC_Cleverstart_Gopay

}// woocommerce_cleverstart_gopay_init

function woocommerce_add_cleverstart_gopay_gateway($methods) {
		 $methods[] = 'WC_Cleverstart_Gopay';
		 return $methods;
 }

 add_filter('woocommerce_payment_gateways', 'woocommerce_add_cleverstart_gopay_gateway' );

add_action('init', 'woocommerce_gopay_gateway_pingback');



function woocommerce_gopay_gateway_pingback(){

	if ( isset( $_GET['listener'] ) && $_GET['listener'] == 'woogopay' ) {

	 WC()->payment_gateways();
   $clvr_gopay = new WC_Cleverstart_Gopay();
   $order_id = $_GET['order_id'];
   $clvr_gopay->check_gopay_response($order_id);
  }
}
