<?php
/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    GautamMKGarg <GautamMKGarg@gmail.com>
 * @author    Thirty Bees <modules@thirtybees.com>
 * @copyright 2017-2018 GautamMKGarg
 * @copyright 2017-2018 thirty bees
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */
if (! defined('_PS_VERSION_')) {
    exit();
}

function is_not_17()
{
    return version_compare(_PS_VERSION_, '1.7', '<');
}

class Instamojo extends PaymentModule
{

    private $error_messages;

    private $signUpUrl = "http://go.thearrangers.xyz/instamojo?utm_source=admin_help&utm_medium=thirtybees&utm_campaign=ecommerce_module";

    private $specialOfferUrl = "http://go.thearrangers.xyz/instamojo?utm_source=admin_special_offer&utm_medium=thirtybees&utm_campaign=ecommerce_module";

    public function __construct()
    {
        $this->name = 'instamojo';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.1';
        $this->author = 'Gautam Garg';
        $this->controllers = array(
            'validation'
        );
        $this->is_eu_compatible = 1;
        $this->error_messages;
        $this->bootstrap = true;
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        
        parent::__construct();
        
        $this->displayName = $this->l('Instamojo - Indian Payment Gateway');
        $this->description = $this->l(
            'Accept Debit Cards, Credit Cards, Net Banking, UPI, Wallets, EMI by integrating Instamojo Indian Payment Gateway.');
        
        $this->confirmUninstall = $this->l(
            'If you need any help with setup/integration, feel free to contact at +917738456813. Are you sure about uninstalling Instamojo?');
        
        if (! (string) Configuration::get('instamojo_client_id')) {
            $this->warning = $this->l(
                'Please configure Client ID and Client Secret to use this module. Click here to know more.');
        }
        
        /* For 1.4.3 and less compatibility */
        $updateConfig = array(
            'PS_OS_CHEQUE',
            'PS_OS_PAYMENT',
            'PS_OS_PREPARATION',
            'PS_OS_SHIPPING',
            'PS_OS_CANCELED',
            'PS_OS_REFUND',
            'PS_OS_ERROR',
            'PS_OS_OUTOFSTOCK',
            'PS_OS_BANKWIRE',
            'PS_OS_PAYPAL',
            'PS_OS_WS_PAYMENT'
        );
        if (! Configuration::get('PS_OS_PAYMENT')) {
            foreach ($updateConfig as $u)
                if (! Configuration::get($u) && defined('_' . $u . '_'))
                    Configuration::updateValue($u, constant('_' . $u . '_'));
        }
        
        /* Check if cURL is enabled */
        if (! is_callable('curl_exec')) {
            $this->warning = $this->l('cURL extension must be enabled on your server to use this module.');
        }
    }

    public function install()
    {
        parent::install();
        $this->registerHook('payment');
        $this->registerHook('displayPaymentEU');
        $this->registerHook('paymentReturn');
        if (is_not_17()) {
            $this->registerHook('displayProductButtons');
        }
        
        Configuration::updateValue('instamojo_checkout_label',
            'Debit Cards, Credit Cards, Net Banking, UPI, Wallets, EMI (Processed securely by Instamojo)');
        
        /*
         * Copy Payment Logo in Theme's productpaymentlogos module
         * if (!file_exists(_THEMES_DIR_.'community-theme-default/modules/productpaymentlogos/img/')) {
         * mkdir(_THEMES_DIR_.'community-theme-default/modules/productpaymentlogos/img/');
         * }
         * copy(__DIR__.'/views/img/payment-logo.png', _THEMES_DIR_.'community-theme-default/modules/productpaymentlogos/img/payment-logo.png');
         */
        return true;
    }

    public function uninstall()
    {
        parent::uninstall();
        Configuration::deleteByName('instamojo_client_id');
        Configuration::deleteByName('instamojo_client_secret');
        Configuration::deleteByName('instmaojo_testmode');
        Configuration::deleteByName('instamojo_checkout_label');
        return true;
    }

    public function hookDisplayProductButtons($params)
    {
        if (Configuration::get('PS_CATALOG_MODE')) {
            return;
        }
        if (! $this->isCached('powerbyinstamojo.tpl', $this->getCacheId())) {
            $this->smarty->assign(
                array(
                    'img_path' => $this->_path,
                    'banner_title' => $this->l('Powered by Instamojo'),
                    'instamojo_link' => "http://go.thearrangers.xyz/instamojo"
                ));
        }
        
        return $this->display(__FILE__, 'powerbyinstamojo.tpl', $this->getCacheId());
    }

    public function hookPayment()
    {
        if (! $this->active) {
            return;
        }
        
        $this->template_data = array();
        $this->invalid_currency = false;
        $redirectUrl = null;
        
        $this->currency = new Currency($this->context->cart->id_currency);
        if ($this->currency->iso_code != "INR") {
            $this->invalid_currency = true;
        } else {
            if (Configuration::get('instamojo_payment_method') == 0 ||
                Configuration::get('instamojo_payment_method') == 1) {
                $redirectUrl = $this->createRequest();
            }
        }
        $temp_data = array(
            'this_path' => $this->_path, // keep for retro compat
            'this_path_instamojo' => $this->_path,
            'checkout_label' => $this->l(
                (Configuration::get('instamojo_checkout_label')) ? Configuration::get('instamojo_checkout_label') : "Pay using Instamojo"),
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/',
            'redirectUrl' => $redirectUrl,
            'instamojo_payment_method' => Configuration::get('instamojo_payment_method'),
            'invalid_currency' => $this->invalid_currency
        
        );
        $this->template_data = array_merge($this->template_data, $temp_data);
        $this->smarty->assign($this->template_data);
        return $this->display(__FILE__, 'payment.tpl');
    }

    private function createRequest()
    {
        if ($this->context->cart->id_customer == 0 || $this->context->cart->id_address_delivery == 0 ||
            $this->context->cart->id_address_invoice == 0) {
            Tools::redirectLink(__PS_BASE_URI__ . 'order.php?step=1');
        }
        
        $customer = new Customer($this->context->cart->id_customer);
        if (! Validate::isLoadedObject($customer)) {
            Tools::redirectLink(__PS_BASE_URI__ . 'order.php?step=1');
        }
        $method_data = array();
        // if (Tools::getValue('confirm') or Tools::getValue('updatePhone') ) {
        // prepare some object to fetch necessary information.
        $customer = new Customer((int) $this->context->cart->id_customer);
        $address = new Address((int) $this->context->cart->id_address_invoice);
        $total = $this->context->cart->getOrderTotal(true, Cart::BOTH);
        
        // prepare logger
        $logger = new FileLogger(0); // 0 == debug level, logDebug() won’t work without this.
        if (is_not_17()) {
            $logger->setFilename(_PS_ROOT_DIR_ . "/log/imojo.log");
        } else {
            $logger->setFilename(_PS_ROOT_DIR_ . "/app/logs/imojo.log");
        }
        $logger->logDebug("Creating Instamojo order for  " . $this->context->cart->id);
        
        // prepare data
        $api_data = array();
        $api_data['name'] = Tools::substr(
            trim((html_entity_decode($customer->firstname . ' ' . $customer->lastname, ENT_QUOTES, 'UTF-8'))), 0, 20);
        $api_data['email'] = Tools::substr($customer->email, 0, 75);
        $api_data['amount'] = $total;
        $api_data['currency'] = $this->currency->iso_code;
        $api_data['redirect_url'] = $this->context->link->getModuleLink($this->name, 'confirm', array(), true);
        $api_data['transaction_id'] = time() . "-" . $this->context->cart->id;
        
        if ($address->phone_mobile) {
            $api_data['phone'] = $address->phone_mobile;
        } else {
            $api_data['phone'] = $address->phone;
        }
        
        try {
            $api = $this->getInstamojoObject($logger);
            $logger->logDebug("Data sent for creating order " . print_r($api_data, true));
            $response = $api->createOrderPayment($api_data);
            $logger->logDebug("Response from Server" . print_r($response, true));
            
            if (isset($response->order)) {
                $redirectUrl = $response->payment_options->payment_url;
                $this->context->cookie->__set('payment_request_id', $response->order->id);
                return $redirectUrl;
            }
        } catch (CurlException $e) {
            // handle exception releted to connection to the sever
            $logger->logDebug((string) $e);
            $method_data['api_errors'][] = $e->getMessage();
        } catch (ValidationException $e) {
            // handle exceptions releted to response from the server.
            $logger->logDebug($e->getMessage() . " with ");
            $logger->logDebug(print_r($e->getResponse(), true) . "");
            $method_data['api_errors'] = $e->getErrors();
        } catch (Exception $e) { // handled common exception messages which will not caught above.
            $method_data['api_errors'][] = $e->getMessage();
            $logger->logDebug('Error While Creating Order : ' . $e->getMessage());
        }
        
        $this->template_data = $method_data;
        $this->template_data['mobile'] = $api_data['phone'];
        
        // check if phone input box should b displayed or not
        if (isset($method_data['api_errors'])) {
            foreach ($method_data['api_errors'] as $e) {
                if (stristr($e, "phone")) {
                    $this->template_data['showPhoneBox'] = 1;
                    break;
                }
            }
        }
        
        // }
    }

    public function hookDisplayPaymentEU()
    {
        if (! $this->active) {
            return;
        }
        
        if (! is_not_17()) {
            return array(
                'cta_text' => $this->l(
                    (Configuration::get('instamojo_checkout_label')) ? Configuration::get('instamojo_checkout_label') : "Pay using Instamojo"),
                'logo' => Media::getMediaPath(dirname(__FILE__) . '/instamojo.png'),
                'action' => $this->context->link->getModuleLink($this->name, 'validation',
                    array(
                        'confirm' => true
                    ), true)
            );
        }
    }

    public function getInstamojoObject($logger)
    {
        include_once _PS_MODULE_DIR_ . DIRECTORY_SEPARATOR . $this->name . "/lib/instamojo.php";
        $credentials = $this->getConfigValues();
        $logger->logDebug(
            "Credintials Client ID: $credentials[instamojo_client_id] Client Secret : $credentials[instamojo_client_secret] TestMode : $credentials[instamojo_testmode] ");
        $api = new InstamojoApi($credentials['instamojo_client_id'], $credentials['instamojo_client_secret'],
            $credentials['instamojo_testmode']);
        return $api;
    }

    public function hookPaymentReturn()
    {
        if (! $this->active) {
            return;
        }
    }

    public function getConfigValues()
    {
        $data = array();
        $data['instamojo_client_id'] = Configuration::get('instamojo_client_id');
        $data['instamojo_client_secret'] = Configuration::get('instamojo_client_secret');
        $data['instamojo_testmode'] = Configuration::get('instamojo_testmode');
        $data['instamojo_payment_method'] = Configuration::get('instamojo_payment_method');
        $data['instamojo_checkout_label'] = Configuration::get('instamojo_checkout_label');
        return $data;
    }

    public function validateData()
    {
        $this->error_messages = "";
        
        if (! (string) Tools::getValue('instamojo_client_id')) {
            $this->error_messages .= "Client ID is Required<br/>";
        }
        if (! (string) Tools::getValue('instamojo_client_secret')) {
            $this->error_messages .= "Client Secret is Required<br/>";
        }
        
        return ! $this->error_messages;
    }

    // Show Configuration form in admin panel.
    public function getContent()
    {
        $output = null;
        // $order_states = OrderState::getOrderStates((int)$this->context->cookie->id_lang);
        if (Tools::isSubmit('submit' . $this->name)) {
            $data = array();
            $data['instamojo_client_id'] = (string) Tools::getValue('instamojo_client_id');
            $data['instamojo_client_secret'] = (string) Tools::getValue('instamojo_client_secret');
            $data['instamojo_testmode'] = (string) Tools::getValue('instamojo_testmode');
            $data['instamojo_payment_method'] = (string) Tools::getValue('instamojo_payment_method');
            $data['instamojo_checkout_label'] = (string) Tools::getValue('instamojo_checkout_label');
            if ($this->validateData($data)) {
                Configuration::updateValue('instamojo_client_id', $data['instamojo_client_id']);
                Configuration::updateValue('instamojo_client_secret', $data['instamojo_client_secret']);
                Configuration::updateValue('instamojo_testmode', $data['instamojo_testmode']);
                Configuration::updateValue('instamojo_payment_method', $data['instamojo_payment_method']);
                Configuration::updateValue('instamojo_checkout_label', $data['instamojo_checkout_label']);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            } else {
                $output .= $this->displayError($this->error_messages);
            }
        }
        
        $temp_data = array(
            'signUpUrl' => $this->signUpUrl,
            'specialOfferUrl' => $this->specialOfferUrl,
            'configured' => (string) Configuration::get('instamojo_client_id') ? true : false
        );
        $this->smarty->assign($temp_data);
        if (! (string) Configuration::get('instamojo_client_id')) {
            $output .= $this->display(__FILE__, 'infos.tpl');
        }
        $output .= $this->display(__FILE__, 'views/templates/admin/howTo.tpl');
        return $output . $this->displayForm();
    }

    public function displayForm()
    {
        // Get default language
        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        
        // Init Fields form array
        $fields_form = array();
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings')
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Checkout Label'),
                    'name' => 'instamojo_checkout_label',
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Client ID'),
                    'name' => 'instamojo_client_id',
                    'required' => true
                ),
                
                array(
                    'type' => 'text',
                    'label' => $this->l('Client Secret'),
                    'name' => 'instamojo_client_secret',
                    'required' => true
                ),
                
                array(
                    'type' => 'radio',
                    'label' => $this->l('Test Mode'),
                    'name' => 'instamojo_testmode',
                    'required' => true,
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        )
                    )
                ),
                array(
                    'type' => 'radio',
                    'label' => $this->l('Payment Method'),
                    'name' => 'instamojo_payment_method',
                    'required' => true,
                    'values' => array(
                        array(
                            'id' => 'popup_box',
                            'value' => 0,
                            'label' => $this->l('Popup Box')
                        ),
                        array(
                            'id' => 'redirect',
                            'value' => 1,
                            'label' => $this->l('Redirect / Redirect without confirmation')
                        ),
                        array(
                            'id' => 'classic',
                            'value' => 2,
                            'label' => $this->l('Classic / Redirect after confirmation (Not Recommended)')
                        )
                    )
                )
            
            ),
            
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            )
        );
        
        $helper = new HelperForm();
        
        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        
        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        
        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true; // false -> remove toolbar
        $helper->toolbar_scroll = true; // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit' . $this->name;
        
        // Load current value
        $helper->fields_value = $this->getConfigValues();
        
        return $helper->generateForm($fields_form);
    }
}
