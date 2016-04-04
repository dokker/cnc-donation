<?php
/*
Plugin Name: PMGW donation
Plugin URI: https://github.com/dokker/cac-donation
Description: Handle donation with PMGW
Version: 1.0
Author: docker
Author URI: https://hu.linkedin.com/in/docker
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initial settings
 */
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

define('PROJECT_PATH', realpath(dirname(__FILE__)));
define('DS', DIRECTORY_SEPARATOR);

/**
 * Autoload
 */
$vendorAutoload = PROJECT_PATH . DS . 'vendor' . DS . 'autoload.php';
$sdkAutoload = PROJECT_PATH . DS . 'vendor' . DS . 'bigfish' . DS . 'paymentgateway' . DS . 'src' . DS . 'BigFish' . DS . 'PaymentGateway' . DS . 'Autoload.php';
if (!is_file($sdkAutoload)) {
	echo 'BIG FISH Payment Gateway - PHP SDK module not installed.';
	exit;
}
if (is_file($vendorAutoload)) {
	require_once($vendorAutoload);
} else {
	require_once($sdkAutoload);
	BigFish\PaymentGateway\Autoload::register();
}

// load translations
load_plugin_textdomain( 'cnc-donation', false, 'cnc-donation/languages' );

/**
 * Instantiate plugin class
 * @var object
 */
$virtualpage = new \cncDonation\Virtualpage();
$component = new \cncDonation\Component();
$shortcode = new \cncDonation\Shortcode($component);

// Handling plugin activation
register_activation_hook(__FILE__, [$component, 'pluginActivate']);
// Handling plugin uninstall
// NOTE: Not working. Using unintsall.php instead
// register_uninstall_hook(__FILE__, ['\cncDonation\Component', 'pluginUninstall']);

add_filter('cron_schedules', [$component, 'cronDefiner']);    
if (!wp_next_scheduled('cnc_recurring_payment')) {
	wp_schedule_event(time(), 'monthly', 'cnc_recurring_payment');
}
add_action( 'cnc_recurring_payment', [$component, 'recurringPaymentCron'] );

add_filter('body_class', [$component, 'setBodyClass']);
