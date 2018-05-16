<?php
/*
  Plugin Name: WooCommerce Payment Gateway - Bitcoinus
  Plugin URI: https://bitcoinus.io
  Text Domain: woo-payment-gateway-bitcoinus
  Description: Bitcoinus payment method support
  Version: 0.0.1
  Author: Bitcoinus.io
  Author URI: https://bitcoinus.io
  License: GPL version 3 or later - http://www.gnu.org/licenses/gpl-3.0.html

  @package WordPress
  @author Bitcoinus.io (https://bitcoinus.io)
  @since 0.0.1
 */

defined('ABSPATH') or exit;

if (!in_array('woocommerce/woocommerce.php',apply_filters('active_plugins',get_option('active_plugins')))) return;
add_filter('woocommerce_payment_gateways','add_bitcoinus_gateway');

function add_bitcoinus_gateway($methods){
  $methods[] = 'WC_Gateway_Bitcoinus';
  return $methods;
}

// init gateway
add_action('plugins_loaded','wc_gateway_bitcoinus_init');
function wc_gateway_bitcoinus_init(){

  class WC_Gateway_Bitcoinus extends WC_Payment_Gateway {

    const LOGO = 'assets/img/logo.svg';
    protected $pid;
    protected $currency;
    protected $amount;
    protected $name;
    protected $email;
    protected $street;
    protected $redirect;
    protected $back;
    protected $test;

    // constructor
    public function __construct(){
      $this->title = 'Bitcoinus';
      $this->description = 'Pay with cryptocurrencies.';
      $this->id = 'bitcoinus';
      $this->has_fields = true;
      $this->method_title = __('Bitcoinus','woo-payment-gateway-bitcoinus');
      $this->icon = apply_filters('woocommerce_bitcoinus_icon',plugin_dir_url(__FILE__).$this::LOGO);
      $this->init_form_fields();
      $this->init_settings();
      $this->pid = $this->get_option('pid');
      $this->key = $this->get_option('key');
      $this->test = $this->get_option('test')=='yes' ? 1 : 0;
      $this->items = $this->get_option('items')=='yes' ? 1 : 0;
      add_action('woocommerce_api_wc_gateway_bitcoinus',array($this, 'check_callback_request'));
      add_action('woocommerce_update_options_payment_gateways_bitcoinus',[ $this,'process_admin_options' ]);
    }

    public function init_form_fields(){
      if (!class_exists('Wc_Bitcoinus_Settings')) require_once 'includes/class-wc-bitcoinus-settings.php';
      $this->setPluginSettings(Wc_Bitcoinus_Settings::create());
      $this->form_fields = $this->getPluginSettings()->getFields();
    }

    public function admin_options(){
      $this->updateAdminSettings($this->getPluginSettings()->newSettings());
      $all_fields = $this->get_form_fields();
      $tabs = $this->generateTabs(array([
        'name' => __('General settings','woo-payment-gateway-bitcoinus'),
        'slice' => array_slice($all_fields,0,5)
      ]));
      $this->getPluginSettings()->settingsForm($tabs);
      wp_enqueue_script('custom-backend-script',plugin_dir_url(__FILE__).'assets/js/theme.js',array('jquery'));
    }

    public function validate_pid_field($key,$value) {
      if (strlen($value) < 1) WC_Admin_Settings::add_error(esc_html__('Project ID is required, it cannot be empty','woo-payment-gateway-bitcoinus'));
      return $value;
    }

    public function validate_key_field($key, $value) {
      if (strlen($value) < 1) WC_Admin_Settings::add_error(esc_html__('Secret key is required, it cannot be empty','woo-payment-gateway-bitcoinus'));
      return $value;
    }

    public function process_payment($order_id){

      // load order
      $order = wc_get_order($order_id);

      // init variables
      $fullname = $order->data['billing']['first_name']!='' ? $order->data['billing']['first_name'] : ''.' '.$order->data['billing']['last_name']!='' ? $order->data['billing']['last_name'] : '';
      $email = $order->data['billing']['email']!='' ? $order->data['billing']['email'] : '';
      $street = $order->data['billing']['address_1']!='' ? $order->data['billing']['address_1'] : '';

      // redirect URLs
      $redirect = $order->get_checkout_order_received_url();
      $back = $order->get_cancel_order_url();

      // create payment array
      $data = json_encode((object)[
        'pid' => $this->pid,
        'orderid' => "$order_id",
        'currency' => $order->currency,
        'amount' => $order->total,
        'name' => $fullname,
        'email' => $email,
        'street' => $street,
        'redirect' => $redirect,
        'back' => $back,
        'test' => $this->test
      ]);

      // create items array
      if ($this->items == 1) {
        $items = [];
        foreach ($order->items as $item) {
          $item_object = (object)[
            'title' => $item['name'],
            'qty' => number_format($item['quantity'],2),
            'price' => $item['total']
          ];
          $items[] = json_encode($item_object);
        }
      }

      // create request body
      $body = [
        'data' => base64_encode($data),
        'signature' => hash_hmac('sha256',$data,$this->key)
      ];
      if (isset($items)) $body['items'] = base64_encode(json_encode($items));

      // perform redirect
      return array(
        'result' => 'success',
        'redirect' => add_query_arg($body,'https://pay.bitcoinus.io/init')
      );

    }

    // process callback
    public function check_callback_request(){

      // init variables
      $data = stripslashes(filter_var($_REQUEST['data'],FILTER_SANITIZE_STRING,FILTER_FLAG_NO_ENCODE_QUOTES));
      $signature = filter_var($_REQUEST['signature'],FILTER_SANITIZE_STRING);

      // check signature
      $signature_local = hash_hmac('sha256',$data,$this->key);
      if ($signature_local != $signature) return 'Invalid signature';

      // decode object
      if (is_object($data=json_decode($data))) {
        $orderid = filter_var($data->orderid,FILTER_SANITIZE_STRING);
        $status = intval($data->status);
        // success
        if ($status == 1) {
          // load order
          $order = wc_get_order($orderid);
          // update order status
          if (is_object($order)) {
            $order->update_status('processing','');
            $order->add_order_note(__('Payment received.','woo-payment-gateway-bitcoinus'));
          } else {
            error_log('Order referenced in Bitcoinus callback cannot be found');
          }
        } else {
          $error = 'Payment failed';
          error_log($error);
          echo $error;
        }
      }
      exit();

    }

    protected function generateTabs($tabs){
      $data = [];
      foreach ($tabs as $key => $value) {
        $data[$key]['name'] = $value['name'];
        $data[$key]['slice'] = $this->generate_settings_html($value['slice'],FALSE);
      }
      return $data;
    }

    public function getPluginSettings(){
      return $this->pluginSettings;
    }

    public function setPluginSettings($pluginSettings){
      $this->pluginSettings = $pluginSettings;
    }

    protected function updateAdminSettings($data){}

  }
}
