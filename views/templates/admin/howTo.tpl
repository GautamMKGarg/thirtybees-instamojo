{*
 * Copyright (C) 2017-2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    0RS <admin@prestalab.ru>
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2009-2017 PrestaLab.Ru
 * @copyright 2017-2018 thirty bees
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 * This module is based on the original `universalpay` module
 * which you can find on https://github.com/universalpay/universalpay
 *
 * Credits go to PrestaLab.Ru (http://www.prestalab.ru) for making the initial version
*}

<div class="panel">
    <h3><i class="icon icon-question"></i> {l s='How to Configure?' mod='instamojo'}</h3>
    <a target="_new" href="{$signUpUrl|escape:'htmlall':'UTF-8'}"><img src="../modules/instamojo/views/img/full-logo.png" style="height: 40px;"></a>
		<br/><br>
		Instamojo is a free Payment Gateway for 4,00,000+ Businesses in India. There is no setup or annual fee. Just pay a transaction fee of 2% + ₹3 for the transactions. Instamojo accepts Debit Cards, Credit Cards, Net Banking, UPI, Wallets, and EMI.
		<br/><br/>
    <strong>{l s='Steps to Integrate Instamojo' mod='instamojo'}</strong>
		<ol>
		<li>Some features may not work with old Instamojo account! We recommend you to create a new account. Sign up process will hardly take 10-15 minutes.<br/><br/>
		<a target="_new" href="{$signUpUrl|escape:'htmlall':'UTF-8'}" class="btn btn-lg btn-primary" role="button">Sign Up on Instamojo</a></li><br/>

		<li>During signup, Instamojo will ask your PAN and Bank account details, after filling these details, you will reach Instamojo Dashboard.</li><br/>

		<li>On the left-hand side menu, you will see the option "API & Plugins" click on this button.</li><br/>

		<li>This module is based on  Instamojo API v2.0, So it will not work with API Key and Auth Token. For this module to work, you will have to generate Client ID and Client Secret. On the bottom of "API & Plugins" page, you will see Generate Credentials / Create new Credentials button. Click on this button.</li><br/>

		<li>Now you will have to choose a platform from the drop-down menu. You can choose any of them, but I will recommend choosing option PrestaShop</li><br/>

		<li>Copy "Client ID" & "Client Secret" and paste it in the Thirtybees' Instamojo extension.</li><br/>

		<li>Select Test mode "Enabled" if the website is currently under development and "Disabled" if the website is in Live Mode</li><br/>

		<li>Save the settings and its done.</li>
		</ol>
</div>


{if !$configured}


	<!-- Special Info Modal -->
	<div id="specialOffer" class="modal fade" role="dialog">
	  <div class="modal-dialog">
	
	    <!-- Modal content-->
	    <div class="modal-content">
	      <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal">&times;</button>
	        <h4 class="modal-title">Special Offer for New Instamojo Accounts</h4>
	      </div>
	      <div class="modal-body">
	        <p>Create a new account using below link and get Instamojo account at <b>1.9% + ₹1</b> transaction fee instead of 2% + ₹3.</p>
	        <a class="btn btn-primary"
			                         target="_blank"
			                         href="{$specialOfferUrl|escape:'htmlall':'UTF-8'}"
									 title="Sign Up Now">Sign Up Now</a>
	      </div>
	      <div class="modal-footer">
	        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
	      </div>
	    </div>
	
	  </div>
	</div>
	
	<script>
	$(document).ready(function(){
		setTimeout(function(){
			$("#specialOffer").modal();
		}, 5000);    
	});
	</script>
{/if}
