<div class="donation-popup zoom-anim-dialog mfp-hide popup-<?php echo $package_id; ?>" id="donation-popup-<?php echo $package_id; ?>">
<!-- Begin MailChimp Signup Form -->
<img src="<?php echo get_template_directory_uri(); ?>/dist/images/TI_logo_hrl.png" alt="Transparency International logo" class="logo" />
<div class="donation-package">
<form action="<?php echo get_site_url(); ?>/cnc-donation" method="post" id="" name="donation-package-form" class="">
  <div id="mc_embed_signup_scroll">
    <h3 class="title"><?php echo $package_name; ?></h3>
    <div class="terms"><?php echo $terms; ?></div>
    <div class="mc-field-group form-group">
      <label class="sr-only" for="supporter-name"><?php _e('Name', 'sage'); ?> </label>
      <input type="text" value="" name="supporter-name" class="form-control input-lg input-name" id="mce-MMERGE3" placeholder="<?php _e('Name'); ?>">
      <div class="help-block"><?php _e('Missing name', 'sage'); ?></div>
    </div>
    <div class="mc-field-group form-group">
      <label class="sr-only" for="supporter-email"><?php _e('Email', 'sage'); ?> </label>
      <input type="email" value="" name="supporter-email" class="required email form-control input-lg" id="mce-EMAIL" placeholder="<?php _e('Email'); ?>">
      <div class="help-block"><?php _e('Missing Email', 'sage'); ?></div>
    </div>
<!--<div class="mc-field-group form-group text-left">
      <label class="custom-control custom-checkbox">
        <input type="checkbox" class="custom-control-input">
        <span class="custom-control-indicator"></span>
        <span class="custom-control-description">I would like to sign up for the Transparency Newsletter</span>
      </label>
    </div> -->
    <div class="mc-field-group form-group text-left">
      <label class="custom-control custom-checkbox">
        <input type="checkbox" class="custom-control-input terms-accept">
        <span class="custom-control-indicator"></span>
        <span class="custom-control-description"><?php _e('I accept the terms and conditions', 'cnc-donation'); ?></span>
      </label>
      <div class="help-block"><?php _e('You have to accept the terms and conditions.', 'cnc-donation'); ?></div>
    </div>
    <div class="clear form-group-submit">
      <input type="hidden" class="cnc-package-id" name="cnc-package-id" value="<?php echo $package_id; ?>">
      <input type="submit" value="<?php _e('I support Transparency International Hungary!', 'cnc-donation'); ?>" name="donation-submitted" id="mc-embedded-subscribe" class="button btn btn-primary">
    </div>
  </div>
  <!-- <p class="footnote"><?php _e('You can cancel your subscription any time.', 'cnc-donation'); ?></p> -->
</form>
</div>

<!--End mc_embed_signup-->
</div>
