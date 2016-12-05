(function($) {


  // Email input validator
  function validateEmail($email) {
    var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
    return emailReg.test( $email );
  }

  function validateNlForm() {
    $('.donation-popup .form-group').removeClass('has-error');
    var $email = $(".donation-popup .form-control[type=email]");
    var $text = $(".donation-popup .form-control[type=text]");
    var $terms = $(".donation-popup .custom-control-input.terms-accept");
    var validate = true;
    if (!$email.val() || !validateEmail($email.val())) {
      $email.parent().addClass('has-error');
      validate = false;
    }
    if (!$text.val()) {
      $text.parent().addClass('has-error');
      validate = false;
    }
    if (!$terms.is(':checked')) {
    	$terms.parent().parent().addClass('has-error');
      validate = false;
    }
    return validate;
  }

	function init_donation_popup() {
		$('.payment-packages .package-selector').click(function (e) {
			e.preventDefault();
			var donation_id = $(this).data('id');

			$.magnificPopup.open({
				items: {
					src: '#donation-popup-' + donation_id,
					type: 'inline',

					fixedContentPos: false,
					fixedBgPos: true,

					overflowY: 'auto',

					closeBtnInside: true,
					preloader: false,

					midClick: true,
					removalDelay: 300,
					mainClass: 'my-mfp-slide-bottom'
				}
			});
		});

		$('.donation-popup form').submit(function(e) {
			if(!validateNlForm()) {
				e.preventDefault();
			}
		});

		$('.donation-popup form input').focusout(function(e) {
			validateNlForm();
		});
	}

	init_donation_popup();

})(jQuery);