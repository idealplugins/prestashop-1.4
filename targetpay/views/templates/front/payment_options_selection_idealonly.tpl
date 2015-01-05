{if $error}
	<p class="payment_module">
		<a href="#" title="PaymentError" style="background-color:#FFA8A8; border-color : #c00;">
			{if $method == "IDE"}
				<img src="{$this_path}method-ide.png" alt="{l s='iDEAL' mod='targetpay'}" />
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

