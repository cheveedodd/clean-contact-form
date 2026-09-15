<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Helper: Parse Email Placeholders
function ccf_parse_email_template( $template, $data ) {
    $placeholders = array(
        '{name}'      => isset( $data['name'] ) ? $data['name'] : '',
        '{email}'     => isset( $data['email'] ) ? $data['email'] : '',
        '{message}'   => isset( $data['message'] ) ? $data['message'] : '',
        '{site_name}' => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
        '{date}'      => wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
    );

    return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $template );
}

// Helper: Fetch option template and parse placeholders
function ccf_get_parsed_email( $subject_option, $default_subject, $body_option, $default_body, $template_data ) {
    $subject_raw = get_option( $subject_option, $default_subject );
    $body_raw    = get_option( $body_option, $default_body );

    return array(
        'subject' => ccf_parse_email_template( $subject_raw, $template_data ),
        'body'    => ccf_parse_email_template( $body_raw, $template_data ),
    );
}

// Helper: Get verified site sender address
function ccf_get_from_address() {
    $from_email = ( get_option( 'ccf_smtp_enable', 0 ) && get_option( 'ccf_smtp_username' ) )
        ? get_option( 'ccf_smtp_username' )
        : get_option( 'admin_email' );

    return wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) . ' <' . sanitize_email( $from_email ) . '>';
}

// Helper: Dynamic PHPMailer Configuration
function ccf_apply_smtp_settings( $phpmailer ) {
    if ( ! get_option( 'ccf_smtp_enable', 0 ) ) {
        return;
    }

    $host       = get_option( 'ccf_smtp_host' );
    $port       = get_option( 'ccf_smtp_port', 587 );
    $encryption = get_option( 'ccf_smtp_encryption', 'tls' );
    $auth       = get_option( 'ccf_smtp_auth', 1 );
    $username   = get_option( 'ccf_smtp_username' );
    $password   = get_option( 'ccf_smtp_password' );

    if ( empty( $host ) ) {
        return;
    }

    $phpmailer->isSMTP();
    $phpmailer->Host     = $host;
    $phpmailer->Port     = $port;
    $phpmailer->SMTPAuth = (bool) $auth;

    if ( $auth ) {
        $phpmailer->Username = $username;
        $phpmailer->Password = $password;
    }

    if ( 'none' !== $encryption && ! empty( $encryption ) ) {
        $phpmailer->SMTPSecure = $encryption;
    } else {
        $phpmailer->SMTPSecure  = '';
        $phpmailer->SMTPAutoTLS = false;
    }
}

// Helper: Deferred Autoresponder Callback
add_action( 'ccf_send_deferred_autoresponder', 'ccf_process_deferred_autoresponder', 10, 4 );
function ccf_process_deferred_autoresponder( $to, $subject, $body, $headers ) {
    add_action( 'phpmailer_init', 'ccf_apply_smtp_settings' );
    $sent = wp_mail( $to, $subject, $body, $headers );
    remove_action( 'phpmailer_init', 'ccf_apply_smtp_settings' );

    if ( ! $sent ) {
        error_log( 'CCF Autoresponder Failed: Could not send email to ' . $to );
    }
}

// Helper: AJAX Handler for SMTP Test
add_action( 'wp_ajax_ccf_send_test_email', 'ccf_handle_send_test_email' );
function ccf_handle_send_test_email() {
    check_ajax_referer( 'ccf_test_email_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Unauthorized user.' ) );
    }

    $to = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';

    if ( ! is_email( $to ) ) {
        wp_send_json_error( array( 'message' => 'Please provide a valid email address.' ) );
    }

    $site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
    $subject   = '[' . $site_name . '] CCF SMTP Test Email';
    $body      = "Success!\n\nYour custom SMTP settings for Clean Contact Form are working properly.";
    $headers   = array( 'Content-Type: text/plain; charset=UTF-8' );

    add_action( 'phpmailer_init', 'ccf_apply_smtp_settings' );
    $sent = wp_mail( $to, $subject, $body, $headers );
    remove_action( 'phpmailer_init', 'ccf_apply_smtp_settings' );

    if ( $sent ) {
        wp_send_json_success( array( 'message' => 'Test email sent successfully to ' . $to ) );
    } else {
        wp_send_json_error( array( 'message' => 'Failed to send test email. Check your settings or credentials.' ) );
    }
}