<div class="petition-container col-md-6">
<form action="" method="post" id="" name="petition-form" class="petition-form">
    <div class="mc-field-group form-group">
      <label class="sr-only" for="petitioner-name"><?php _e('Name', 'sage'); ?> </label>
      <input type="text" value="" name="petitioner-name" class="form-control input-name" placeholder="<?php _e('Name'); ?>">
      <div class="help-block"><?php _e('Missing name', 'sage'); ?></div>
    </div>
    <div class="mc-field-group form-group">
      <label class="sr-only" for="petitioner-email"><?php _e('Email', 'sage'); ?> </label>
      <input type="email" value="" name="petitioner-email" class="required email form-control" placeholder="<?php _e('Email'); ?>">
      <div class="help-block"><?php _e('Missing Email', 'sage'); ?></div>
    </div>
    <div class="clear form-group-submit">
      <input type="submit" value="<?php _e('I sign it', 'cnc-donation'); ?>" name="petition-submitted" class="button btn btn-primary">
    </div>
</form>

</div>
