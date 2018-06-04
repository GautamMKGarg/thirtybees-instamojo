{*
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
 * @author    Thirty Bees <modules@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2018 thirty bees
 * @copyright 2007-2018 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
*}

{capture name=path}
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'htmlall':'UTF-8'}" rel="nofollow" title="{l s='Go back to the Checkout' mod='instamojo'}">
	{l s='Checkout' mod='instamojo'}
	{l s='Pay using instamojo' mod='instamojo'}
{/capture}



{assign var='current_step' value='payment'}

{if isset($api_errors)}
	<div class="errors">
	{foreach $api_errors as $error}
		<div class='alert alert-danger error'>{$error|escape:'htmlall':'UTF-8'}</div>
	{/foreach}
    </div>
{/if}

<P>Selected Payment Method : <b>{$checkout_label|escape:'htmlall':'UTF-8'}</b></p>

<form action="{$link->getModuleLink('instamojo', 'validation', [], true)|escape:'htmlall':'UTF-8'}" method="post">
{if isset($showPhoneBox)}
	<div style="padding:20px 0px;margin:20px 0;">
		<label>Mobile No.</label>
		<input class='form-controls' type="text" name='mobile' value="{$mobile|escape:'htmlall':'UTF-8'}">
		<div>
		<input class='btn btn-primary' type='submit' name='updatePhone' value='Update Phone'>
		</div>
	</div>
{else}
	<input type="hidden" name="confirm" value="1" />
		
	<p class="cart_navigation" id="cart_navigation">
		<a href="{$link->getPageLink('order', true)|escape:'htmlall':'UTF-8'}?step=3" class="button_large">{l s='Other payment methods' mod='instamojo'}</a>
		<input type="submit" value="{l s='Confirm Order' mod='instamojo'}" class="exclusive_large" />
	</p>
{/if}
</form>