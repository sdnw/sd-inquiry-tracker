<?php
/** 
 * Plugin Name: SD Inquiry Tracker
 * Description: Tracks client inquiries submitted from front end.
 * Version: 1.0.0
 * Requires PHP: 8.1
 * Author: Steven Dawn
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/vendor/autoload.php';

( new \SD\InquiryTracker\Plugin( __FILE__, $GLOBALS['wpdb'] ) )->register();
