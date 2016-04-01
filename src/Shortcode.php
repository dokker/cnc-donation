<?php
namespace cncDonation;

class Shortcode {
	public function __construct($Object)
	{
		$shortcode = add_shortcode('donation_form', array($Object, 'donationFormShortcode'));
	}
}