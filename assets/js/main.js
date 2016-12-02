(function($) {

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
	}

	init_donation_popup();

})(jQuery);