<?php		
// direct calling protection
if( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit();

global $wpdb;

$wpdb->query("DROP TABLE IF EXISTS {$this->db_table}");
wp_clear_scheduled_hook( 'recurring_payment' );
