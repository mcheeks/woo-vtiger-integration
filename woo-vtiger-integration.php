<?php
/*
Plugin Name: Woocommerce vTiger Integration
Description: Integrate woocommerce orders with vtiger
Version: 1.0
Author: Melissa Spurr
Text Domain: wvi
*/
defined( 'ABSPATH' ) || exit;

define('WOO_VTIGER_INT', plugin_dir_path(__FILE__));

require_once('includes/wvi-functions.php');
require_once('includes/wvi-admin-functions.php');