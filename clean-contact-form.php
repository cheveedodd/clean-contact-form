<?php
/**
 * Plugin Name: Clean Contact Form
 * Description: Zero-database, zero-tracking contact form with customizable CSS, anti-spam, custom email settings, blocklist, and autoresponder.
 * Version: 1.5.1
 * Author: Chevee Dodd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CCF_PATH', plugin_dir_path( __FILE__ ) );
define( 'CCF_URL', plugin_dir_url( __FILE__ ) );

// Load shared helpers and form processing
require_once CCF_PATH . 'includes/helpers.php';
require_once CCF_PATH . 'includes/forms.php';

// Conditionally load admin code only when inside WordPress dashboard
if ( is_admin() ) {
    require_once CCF_PATH . 'includes/admin.php';
}

// Activation hook for defaults
register_activation_hook( __FILE__, 'ccf_set_default_options' );