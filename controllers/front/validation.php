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
class InstamojovalidationModuleFrontController extends ModuleFrontController
{

    public $ssl = true;

    private $template_data = array();

    public $display_column_left = false;

    public function postProcess()
    {
        if ($this->context->cart->id_customer == 0 || $this->context->cart->id_address_delivery == 0 ||
            $this->context->cart->id_address_invoice == 0 || ! $this->module->active) {
            Tools::redirectLink(__PS_BASE_URI__ . 'order.php?step=1');
        }
        
        $customer = new Customer($this->context->cart->id_customer);
        if (! Validate::isLoadedObject($customer)) {
            Tools::redirectLink(__PS_BASE_URI__ . 'order.php?step=1');
        }
        $method_data = array();
        if (Tools::getValue('confirm') or Tools::getValue('updatePhone')) {
            // prepare some object to fetch necessary information.
            $customer = new Customer((int) $this->context->cart->id_customer);
            $address = new Address((int) $this->context->cart->id_address_invoice);
            $total = $this->context->cart->getOrderTotal(true, Cart::BOTH);
            
            // update phone.
            if (Tools::getValue('updatePhone')) {
                $address->phone_mobile = Tools::getValue("mobile");
                $address->save();
            }
            
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
            $api_data['currency'] = "INR";
            $api_data['redirect_url'] = $this->context->link->getModuleLink($this->module->name, 'confirm', array(), true);
            $api_data['transaction_id'] = time() . "-" . $this->context->cart->id;
            
            if ($address->phone_mobile) {
                $api_data['phone'] = $address->phone_mobile;
            } else {
                $api_data['phone'] = $address->phone;
            }
            
            try {
                $api = $this->module->getInstamojoObject($logger);
                $logger->logDebug("Data sent for creating order " . print_r($api_data, true));
                $response = $api->createOrderPayment($api_data);
                $logger->logDebug("Response from Server" . print_r($response, true));
                
                if (isset($response->order)) {
                    $redirectUrl = $response->payment_options->payment_url;
                    $this->context->cookie->__set('payment_request_id', $response->order->id);
                    Tools::redirectLink($redirectUrl);
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
        }
    }

    /**
     *
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        $temp_data = array(
            'total' => $this->context->cart->getOrderTotal(true, Cart::BOTH),
            'this_path' => $this->module->getPathUri(), // keep for retro compat
            'checkout_label' => Configuration::get('instamojo_checkout_label'),
            'this_path_instamojo' => $this->module->getPathUri(),
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->module->name .
            '/'
        );
        $this->template_data = array_merge($this->template_data, $temp_data);
        $this->context->smarty->assign($this->template_data);
        $this->display_column_left = false;
        $this->display_column_right = false;
        if (is_not_17()) {
            $this->setTemplate('validation_old.tpl');
        } else {
            $this->setTemplate('module:instamojo/views/templates/front/validation_new.tpl');
        }
        parent::initContent();
    }
}
