<?php
namespace cncDonation;

class Shortcode {
	public function __construct($Object)
	{
		$form_shortcode = add_shortcode('donation_form', array($Object, 'donationFormShortcode'));
		$results_shortcode = add_shortcode('donation_results', array($Object, 'donationResultsShortcode'));
		add_shortcode('cnc-donation-packages', [$Object, 'donationPackagesShortcode']);
		add_shortcode('cnc-donation-indie', [$Object, 'donationIndieShortcode']);
		add_shortcode('cnc-petition', [$Object, 'petitionShortcode']);
	}
}
