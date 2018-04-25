<?php

defined('ABSPATH') or exit;

// settings page
class Wc_Bitcoinus_Settings extends WC_Payment_Gateway
{

  static public function create()
  {
    return new self();
  }

  public function __construct()
  {
    $this->projectID  = 0;
    $this->formFields = [
      'enabled' => [
        'title'       => __('Payment method on/off', 'woo-payment-gateway-bitcoinus'),
        'label'       => __('Enabled', 'woo-payment-gateway-bitcoinus'),
        'type'        => 'checkbox',
        'default'     => 'no'
      ],
      'pid' => [
        'title'       => __('Project ID', 'woo-payment-gateway-bitcoinus'),
        'type'        => 'number',
        'description' => __('Bitcoinus.io issued project identification number', 'woo-payment-gateway-bitcoinus'),
        'default'     => __('', 'woo-payment-gateway-bitcoinus')
      ],
      'key' => [
        'title'       => __('Secret key', 'woo-payment-gateway-bitcoinus'),
        'type'        => 'text',
        'default'     => __('', 'woo-payment-gateway-bitcoinus')
      ],
      'items' => [
        'title'       => __('Purchased items info', 'woo-payment-gateway-bitcoinus'),
        'type'        => 'checkbox',
        'label'       => __('Show in payment details', 'woo-payment-gateway-bitcoinus'),
        'default'     => 'yes',
        'description' => __('Enable this if you want to send Bitcoinus information about purchased items. Bitcoinus doesn\'t store this information, it is only displayed for your client while payment is being processed.', 'woo-payment-gateway-bitcoinus'),
      ],
      'test' => [
        'title'       => __('Test mode', 'woo-payment-gateway-bitcoinus'),
        'type'        => 'checkbox',
        'label'       => __('Enabled', 'woo-payment-gateway-bitcoinus'),
        'default'     => 'yes',
        'description' => __('Enable this if you only want to test payment gateway without processing real payments', 'woo-payment-gateway-bitcoinus'),
      ],
    ];
  }

  public function settingsForm($tabs)
  {
    $htmlData = $this->createFields($tabs);
    $html = '<div class="plugin_config">'.
      '<h2>'.$htmlData['links'].'</h2>'.
      '<div style="clear:both;"><hr /></div>'.
        $htmlData['tabs'].
      '</div>';
    echo $html;
  }

  public function newSettings()
  {
    return [];
  }

  protected function createFields($tabs)
  {
    $tabsLink = '';
    $tabsContent = '';
    foreach ($tabs as $key => $value) {
      $link .= '<a href="javascript:void(0)"';
      $link .= ' id="tab'.$key.'" class="nav-tab"';
      $link .= ' data-cont="content'.$key.'">';
      $link .=  $value['name'].'</a>';
      $content .= '<div id="content'.$key.'" class="tabContent">';
      $content .= '<table class="form-table">'.$value['slice'].'</table>';
      $content .= '</div>';
    }
    return [
      'links' => $link,
      'tabs' => $content
    ];
  }

  public function getFields()
  {
    return $this->formFields;
  }

}
