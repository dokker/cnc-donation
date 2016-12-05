<div class="donation-popup zoom-anim-dialog mfp-hide popup-<?php echo $package_id; ?>" id="donation-popup-<?php echo $package_id; ?>">
<!-- Begin MailChimp Signup Form -->
<img src="<?php echo get_template_directory_uri(); ?>/dist/images/TI_logo_hrl.png" alt="Transparency International logo" class="logo" />
<div class="donation-package">
<form action="" method="post" id="" name="donation-package-form" class="">
  <div id="mc_embed_signup_scroll">
    <h3 class="title"><?php echo $package_name; ?></h3>
    <div class="terms"><?php echo $terms; ?></div>
    <div class="mc-field-group form-group">
      <label class="sr-only" for="mce-MMERGE3"><?php _e('Name', 'sage'); ?> </label>
      <input type="text" value="" name="MMERGE3" class="form-control input-lg" id="mce-MMERGE3" placeholder="<?php _e('Name'); ?>">
      <div class="help-block"><?php _e('Missing name', 'sage'); ?></div>
    </div>
    <div class="mc-field-group form-group">
      <label class="sr-only" for="mce-EMAIL"><?php _e('Email', 'sage'); ?> </label>
      <input type="email" value="" name="EMAIL" class="required email form-control input-lg" id="mce-EMAIL" placeholder="<?php _e('Email'); ?>">
      <div class="help-block"><?php _e('Missing Email', 'sage'); ?></div>
    </div>
    <div class="mc-field-group form-group text-left">
      <label class="custom-control custom-checkbox">
        <input type="checkbox" class="custom-control-input">
        <span class="custom-control-indicator"></span>
        <span class="custom-control-description">I would like to sign up for the Transparency Newsletter</span>
      </label>
    </div>
    <div class="mc-field-group form-group text-left">
      <label class="custom-control custom-checkbox">
        <input type="checkbox" class="custom-control-input">
        <span class="custom-control-indicator"></span>
        <span class="custom-control-description">I accept the terms and conditions</span>
      </label>
    </div>
    <div class="clear form-group-submit">
      <input type="hidden" name="cnc-package-id">
      <input type="submit" value="<?php _e('I support Transparency International Hungary!', 'sage'); ?>" name="subscribe" id="mc-embedded-subscribe" class="button btn btn-primary">
    </div>
  </div>
  <p class="footnote"><?php _e('You can cancel your subscription any time.', 'sage'); ?></p>
</form>
</div>

<!--End mc_embed_signup-->
</div>
