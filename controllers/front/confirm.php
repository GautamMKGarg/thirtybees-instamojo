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
class InstamojoconfirmModuleFrontController extends ModuleFrontController
{

    public function initContent()
    {
        // prepare logger.
        $logger = new FileLogger(0); // 0 == debug level, logDebug() won’t work without this.
        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            $logger->setFilename(_PS_ROOT_DIR_ . "/log/imojo.log");
        } else {
            $logger->setFilename(_PS_ROOT_DIR_ . "/app/logs/imojo.log");
        }
        
        $base_url = _PS_BASE_URL_ . __PS_BASE_URI__;
        
        if (Tools::getValue('payment_id') and Tools::getValue('id')) {
            $payment_request_id = Tools::getValue("id");
            $payment_id = Tools::getValue("payment_id");
            $logger->logDebug(
                "Callback called with payment ID: $payment_id and payment request ID : $payment_request_id ");
            
            if ($payment_request_id != $this->context->cookie->payment_request_id) {
                $logger->logDebug(
                    "Payment Request ID not matched  payment request stored in session (" .
                    $this->context->cookie->payment_request_id . ") with Get Request ID $payment_request_id.");
                Tools::redirectLink($base_url);
            }
            
            try {
                $api = $this->module->getInstamojoObject($logger);
                $response = $api->getOrderById($payment_request_id);
                $logger->logDebug(
                    "Response from server for PaymentRequest ID $payment_request_id " . PHP_EOL .
                    print_R($response, true));
                $payment_status = $api->getPaymentStatus($payment_id, $response->payments);
                $logger->logDebug("Payment status for $payment_id is $payment_status");
                
                if ($payment_status == "successful" or $payment_status == "failed") {
                    $logger->logDebug("Response from server is $payment_status.");
                    $order_id = $response->transaction_id;
                    $order_id = explode("-", $order_id);
                    $order_id = $order_id[1];
                    $logger->logDebug("Extracted order id from trasaction_id: " . $order_id);
                    
                    if ($this->context->cart->id != $order_id) {
                        $logger->logDebug(
                            "Cart ID sent to Intamojo ($order_id) doesn't match with current cart id (" .
                            $this->context->cart->id . ")");
                        Tools::redirectLink($this->context->link->getPageLink('order', true) . "?step=1");
                    }
                    
                    $extra_vars = array();
                    $extra_vars['transaction_id'] = $payment_id;
                    $customer = new Customer($this->context->cart->id_customer);
                    $total = $this->context->cart->getOrderTotal(true, Cart::BOTH);
                    
                    if ($payment_status == "successful") {
                        $logger->logDebug("Payment for $payment_id was credited.");
                        $this->module->validateOrder($this->context->cart->id, _PS_OS_PAYMENT_, $total,
                            $this->module->displayName, null, $extra_vars, null, false, $customer->secure_key, null);
                        Tools::redirectLink(
                            __PS_BASE_URI__ . 'index.php?controller=order-detail&id_order=' .
                            (int) $this->module->currentOrder);
                    } else if ($payment_status == "failed") {
                        $logger->logDebug("Payment for $payment_id failed.");
                        $cart_id = $this->context->cart->id;
                        $this->module->validateOrder($this->context->cart->id, _PS_OS_ERROR_, $total,
                            $this->module->displayName, null, $extra_vars, null, false, $customer->secure_key, null);
                        
                        $this->context->cart = new Cart($cart_id);
                        $duplicated_cart = $this->context->cart->duplicate();
                        $this->context->cart = $duplicated_cart['cart'];
                        $this->context->cookie->id_cart = (int) $this->context->cart->id;
                        
                        Tools::redirectLink($this->context->link->getPageLink('order', true));
                    }
                }
            } catch (CurlException $e) {
                $logger->logDebug($e);
                Tools::redirectLink($base_url);
            } catch (Exception $e) {
                $logger->logDebug($e->getMessage());
                $logger->logDebug("Payment for $payment_id was not credited.");
                Tools::redirectLink($base_url);
            }
        } else {
            $logger->logDebug("Callback called with no payment ID or payment_request Id.");
            Tools::redirectLink($base_url);
        }
    }
}
