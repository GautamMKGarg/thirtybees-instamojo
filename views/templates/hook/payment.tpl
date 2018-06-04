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

{if !$invalid_currency}
{if isset($api_errors)}

	<p class="payment_module">
		<a class='im-checkout-btn' data-toggle="collapse" data-target="#update-phone" title="{l s='Pay with Instamojo' mod='instamojo'}" rel="nofollow">
			{$checkout_label|escape:'htmlall':'UTF-8'}
		</a>
	</p>

	<div id="update-phone" class="collapse">
		{if isset($api_errors)}
			<div class="panel panel-error">
			  <div class="panel-heading">
				{foreach $api_errors as $error}
					<div class='alert alert-danger error'>{$error|escape:'htmlall':'UTF-8'}</div>
				{/foreach}
			  </div>
			  {if isset($showPhoneBox)}
			  <div class="panel-body">
				  <form class="form-inline" action="{$link->getModuleLink('instamojo', 'validation', [], true)|escape:'htmlall':'UTF-8'}" method="post">

					<div class="form-group">
					  <label for="mobile">Mobile No.</label>
					  <input type="text" class="form-control" id="mobile" name='mobile' value="{$mobile|escape:'htmlall':'UTF-8'}">
					  <input class='btn btn-primary' type='submit' name='updatePhone' value='Update Phone'>
					</div>
				  </form>
			  </div>
			  {/if}
			</div>
		{/if}
	</div> 

{else}

	{if $instamojo_payment_method == 0}
	<p class="payment_module">
		<a class='im-checkout-btn' href="{$redirectUrl|escape:'htmlall':'UTF-8'}" rel="im-checkout" data-behaviour="remote" data-style="no-style" data-text="{$checkout_label}"></a>
	</p>
	<script src="{$this_path_instamojo|escape:'htmlall':'UTF-8'}views/js/button.js"></script>

	{else if $instamojo_payment_method == 1}
	<p class="payment_module">
		<a class='im-checkout-btn' href="{$redirectUrl|escape:'htmlall':'UTF-8'}" title="{l s='Pay with Instamojo' mod='instamojo'}" rel="nofollow">
			{$checkout_label|escape:'htmlall':'UTF-8'}
		</a>
	</p>
	
	{else}
	<p class="payment_module">
		<a class='im-checkout-btn' href="{$link->getModuleLink('instamojo', 'validation', [], true)|escape:'htmlall':'UTF-8'}" title="{l s='Pay with Instamojo' mod='instamojo'}" rel="nofollow">
			{$checkout_label|escape:'htmlall':'UTF-8'}
		</a>
	</p>
	{/if}

{/if}




<style>
.payment_module a.im-checkout-btn {
	background: url("{$this_path_instamojo|escape:'htmlall':'UTF-8'}views/img/logo.png") 20px 16px no-repeat #fbfbfb;
}

.payment_module a.im-checkout-btn::after {
    display: block;
    content: "\f054";
    position: absolute;
    right: 15px;
    margin-top: -11px;
    top: 50%;
    font-family: "FontAwesome";
    font-size: 25px;
    height: 22px;
    width: 14px;
}
</style>
{/if}