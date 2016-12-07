(function($) {


  // Email input validator
  function validateEmail($email) {
    var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
    return emailReg.test( $email );
  }

  function validateNlForm() {
    $('.donation-popup .form-group').removeClass('has-error');
    var $email = $(".donation-popup .form-control[type=email]");
    var $name = $(".donation-popup .form-control[type=text].input-name");
    var $terms = $(".donation-popup .custom-control-input.terms-accept");
    var validate = true;
    if (!$email.val() || !validateEmail($email.val())) {
      $email.parent().addClass('has-error');
      validate = false;
    }
    if (!$name.val()) {
      $name.parent().addClass('has-error');
      validate = false;
    }
    if (!$terms.is(':checked')) {
    	$terms.parent().parent().addClass('has-error');
      validate = false;
    }
    return validate;
  }

  function validateDonationAmount() {
    var $donate_single = $(".donation-popup .input-donate-single");
    var $donate_recurring = $(".donation-popup .input-donate-recurring");
    var validate = true;
    if (!$donate_single.val() && !$donate_recurring.val()) {
      $donate_single.parent().addClass('has-error');
      $donate_recurring.parent().addClass('has-error');
      validate = false;
    }
    return validate;
  }

  function handleMultipleAmounts() {
    var $donate_single = $(".donation-popup .input-donate-single");
    var $donate_recurring = $(".donation-popup .input-donate-recurring");
    $donate_single.keyup(function() {
    	$donate_recurring.val('');
    });
    $donate_recurring.keyup(function() {
    	$donate_single.val('');
    });
    $donate_single.change(function() {
    	$donate_recurring.val('');
    });
    $donate_recurring.change(function() {
    	$donate_single.val('');
    });
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
			if(!validateNlForm() || !validateDonationAmount()) {
				e.preventDefault();
			}
		});

		$('.donation-popup form input').focusout(function(e) {
			validateNlForm();
		});

		handleMultipleAmounts();
	}

	init_donation_popup();

})(jQuery);