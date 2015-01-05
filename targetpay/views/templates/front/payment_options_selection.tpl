{if $error}
	<p class="payment_module">
		<a href="#" title="PaymentError" style="background-color:#FFA8A8; border-color : #c00;">
			{if $method == "IDE"}
				<img src="{$this_path}method-ide.png" alt="{l s='iDEAL' mod='targetpay'}" />
            {/if}
			{if $method == "MRC"}
				<img src="{$this_path}method-mrc.png" alt="{l s='Mister Cash' mod='targetpay'}" />
            {/if}
			{if $method == "DEB"}
				<img src="{$this_path}method-deb.png" alt="{l s='SOFORT Banking' mod='targetpay'}" />
			{/if}
			<b>Error:</b> {$error}
		</a>
	</p>
{/if}
<p class="payment_module">
	<img src="{$this_path}method-ide.png" alt="{l s='iDeal' mod='targetpay'}" />{l s='iDeal' mod='targetpay'}
	<form method="post" action="{$this_path}payment.php">
	{html_options name=bankID options=$idealBankListArr}
	<input type="submit" name="submit" value="{l s='Betaal' mod='targetpay'}" class="exclusive_large">
	</form>
</p>

<p class="payment_module">
	<img src="{$this_path}method-mrc.png" alt="{l s='Bancontact/Mister Cash' mod='targetpay'}" />{l s='Bancontact/Mister Cash' mod='targetpay'}
	<form method="post" action="{$this_path_ssl}payment.php">
	{foreach from=$mrCashOBJBankListArr key=k item=v}
	<input type="hidden" name="bankID" value="{$k}" />
	{/foreach}
	<input type="submit" name="submit" value="{l s='Betaal' mod='targetpay'}" class="exclusive_large">
	</form>
</p>

<p class="payment_module">
	<img src="{$this_path}method-deb.png" alt="{l s='SOFORT Banking' mod='targetpay'}" />{l s='SOFORT Banking' mod='targetpay'}
	<form method="post" action="{$this_path_ssl}payment.php">
	{html_options name=bankID options=$directEBankingBankListArr}
	<input type="submit" name="submit" value="{l s='Betaal' mod='targetpay'}" class="exclusive_large">
	</form>
</p>

