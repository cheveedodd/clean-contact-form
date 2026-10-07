<?php
/**
 * Clean Contact Form Uninstall
 *
 * Removes all saved options from the wp_options table upon plugin deletion.
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Complete array of all option keys created by this plugin
$ccf_options = array(
    // Basic & Email Settings
    'ccf_recipient_email',
    'ccf_subject_prefix',
    'ccf_disable_reply_to',
    'ccf_enable_autoresponder',
    'ccf_admin_email_subject',
    'ccf_admin_email_body',
    'ccf_autoresponder_subject',
    'ccf_autoresponder_body',
    'ccf_newsletter_autoresponder_subject',
    'ccf_newsletter_autoresponder_body',

    // Security & Anti-Spam
    'ccf_enable_honeypot',
    'ccf_enable_timecheck',
    'ccf_timecheck_threshold',
    'ccf_enable_qa',
    'ccf_qa_question',
    'ccf_qa_answer',
    'ccf_blocklist',
    'ccf_enable_namenolink',
    'ccf_enable_antispam_token',
    'ccf_antispam_token_expiration',
    'ccf_antispam_token_secret',

    // Messages & Redirects
    'ccf_msg_success',
    'ccf_msg_error',
    'ccf_redirect_page_id',
    'ccf_redirect_url',

    // Styling & CSS
    'ccf_color_label',
    'ccf_color_input_bg',
    'ccf_color_input_text',
    'ccf_color_input_border',
    'ccf_color_focus_border',
    'ccf_color_btn_bg',
    'ccf_color_btn_text',
    'ccf_color_btn_hover_bg',
    'ccf_custom_css',

    // SMTP
    'ccf_smtp_enable',
    'ccf_smtp_host',
    'ccf_smtp_port',
    'ccf_smtp_encryption',
    'ccf_smtp_auth',
    'ccf_smtp_username',
    'ccf_smtp_password',
);

// Delete each option from single-site and multisite networks
foreach ( $ccf_options as $option_name ) {
    delete_option( $option_name );
    delete_site_option( $option_name );
}

// Delete any scheduled events related to the plugin
wp_clear_scheduled_hook( 'ccf_send_deferred_autoresponder' );

// Delete log file if it exists
$upload_dir = wp_upload_dir();
$log_dir    = trailingslashit( $upload_dir['basedir'] ) . 'ccf-logs';

if ( is_dir( $log_dir ) ) {
    $files = glob( $log_dir . '/*', GLOB_MARK );
    if ( is_array( $files ) ) {
        foreach ( $files as $file ) {
            if ( is_file( $file ) ) {
                unlink( $file );
            }
        }
    }
    rmdir( $log_dir );
}