<div class="cnc-donation" id="cnc-donation">
	<form class="donation-form" action="<?php echo get_bloginfo( 'url' ); ?>/cnc-donation" method="post">
		<label for="donation-amount"><?php echo __('Amount', 'cnc-donation'); ?>:</label>
		<div class="radio-wrap"><input type="radio" name="donation-amount" value="5000" class="donation-amount" checked="checked" /> 5.000 Ft.</div>
		<div class="radio-wrap"><input type="radio" name="donation-amount" value="10000" class="donation-amount" /> 10.000 Ft.</div>
		<div class="radio-wrap"><input type="radio" name="donation-amount" value="20000" class="donation-amount" /> 20.000 Ft.</div>
		<div class="radio-wrap"><input type="radio" name="donation-amount" value="custom" class="donation-amount" /> <?php echo __('Given amount', 'cnc-donation'); ?></div>
		<div class="given-amount-wrapper"><label for="given-amount"><?php echo __('Given amount of donation', 'cnc-donation'); ?>:</label>
		<input type="text" name="given-amount" /> Ft.</div>
		<label for="donation-method"><?php echo __('Donation frequency', 'cnc-donation'); ?>:</label>
		<div class="radio-wrap"><input type="radio" name="donation-method" class="donation-method recurring" value="1" /><?php echo __('Regular monthly donation', 'cnc-donation'); ?></div>
		<div class="radio-wrap"><input type="radio" name="donation-method" class="donation-method single" value="0" checked="checked" /><?php echo __('One-off donation', 'cnc-donation'); ?></div>
		<label for="provider"><?php echo __('Payment method', 'cnc-donation'); ?>:</label>
		<div class="radio-wrap"><input type="radio" class="provider-field" name="provider" value="CIB" /><span class="provider-icon provider-cib"><?php echo __('CIB', 'cnc-donation'); ?></span></div>
		<div class="radio-wrap"><input type="radio" class="provider-field" name="provider" value="PayPal" checked="checked" /><span class="provider-icon provider-paypal"><?php echo __('PayPal', 'cnc-donation'); ?></span></div>
		<input class="form-submit" type="submit" name="donation-submitted" value="<?php echo __('Send', 'cnc-donation'); ?>" />
	</form>
</div>
