<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Register Gutenberg Blocks
add_action( 'init', 'ccf_register_block' );
function ccf_register_block() {
    wp_register_script(
        'clean-contact-form-editor-script',
        CCF_URL . 'block.js',
        array( 'wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-editor' ),
        '1.5.0'
    );

    wp_register_style(
        'clean-contact-form-style',
        CCF_URL . 'style.css',
        array(),
        '1.0.0'
    );

    register_block_type( 'clean-contact-form/form', array(
        'editor_script'   => 'clean-contact-form-editor-script',
        'style'           => 'clean-contact-form-style',
        'render_callback' => 'ccf_render_form_html',
    ) );

    register_block_type( 'clean-contact-form/mailing-list', array(
        'editor_script'   => 'clean-contact-form-editor-script',
        'style'           => 'clean-contact-form-style',
        'render_callback' => 'ccf_render_mailing_list_form_html',
    ) );
}

// Inline Dynamic CSS
add_action( 'wp_enqueue_scripts', 'ccf_enqueue_custom_css' );
add_action( 'enqueue_block_editor_assets', 'ccf_enqueue_custom_css' );
function ccf_enqueue_custom_css() {
    $dynamic_vars = "
        .wp-block-clean-contact-form-form {
            --ccf-label-color: " . get_option( 'ccf_color_label', '#ffffff' ) . ";
            --ccf-input-bg: " . get_option( 'ccf_color_input_bg', '#1a1a1a' ) . ";
            --ccf-input-text: " . get_option( 'ccf_color_input_text', '#ffffff' ) . ";
            --ccf-input-border: " . get_option( 'ccf_color_input_border', '#444444' ) . ";
            --ccf-focus-border: " . get_option( 'ccf_color_focus_border', '#ffffff' ) . ";
            --ccf-btn-bg: " . get_option( 'ccf_color_btn_bg', '#ffffff' ) . ";
            --ccf-btn-text: " . get_option( 'ccf_color_btn_text', '#000000' ) . ";
            --ccf-btn-hover-bg: " . get_option( 'ccf_color_btn_hover_bg', '#cccccc' ) . ";
        }
    ";

    $custom_css = get_option( 'ccf_custom_css', '' );
    if ( ! empty( $custom_css ) ) {
        $dynamic_vars .= "\n" . wp_strip_all_tags( $custom_css );
    }

    wp_add_inline_style( 'clean-contact-form-style', $dynamic_vars );
}

// Shortcode Handlers
add_shortcode( 'clean_contact_form', 'ccf_shortcode_handler' );
function ccf_shortcode_handler() {
    return '<div class="custom-contact-form-wrapper">' . ccf_render_form_html() . '</div>';
}

add_shortcode( 'clean_mailing_list', 'ccf_mailing_list_shortcode_handler' );
function ccf_mailing_list_shortcode_handler() {
    return '<div class="custom-contact-form-wrapper">' . ccf_render_mailing_list_form_html() . '</div>';
}

// Contact Form Handler & Output
function ccf_render_form_html() {
    $output      = '';
    $msg_success = esc_html( get_option( 'ccf_msg_success', 'Thank you! Your message has been sent.' ) );
    $msg_error   = esc_html( get_option( 'ccf_msg_error', 'Please fill out all fields with a valid email address.' ) );

    $is_rest_request = defined( 'REST_REQUEST' ) && REST_REQUEST;

    if ( ! $is_rest_request && isset( $_POST['cf_submitted'] ) ) {
        
        // Anti-Spam: Honeypot
        if ( get_option( 'ccf_enable_honeypot', 1 ) && ! empty( $_POST['cf_website'] ) ) {
            return '<div class="cf-message cf-success">' . $msg_success . '</div>';
        }

        // Anti-Spam: Time Check
        if ( get_option( 'ccf_enable_timecheck', 1 ) ) {
            $load_time = isset( $_POST['cf_time'] ) ? intval( $_POST['cf_time'] ) : 0;
            if ( ( time() - $load_time ) < 3 ) {
                return '<div class="cf-message cf-success">' . $msg_success . '</div>';
            }
        }

        // Anti-Spam: Blocklist
        $blocklist = get_option( 'ccf_blocklist', '' );
        if ( ! empty( $blocklist ) ) {
            $blocked_words   = array_map( 'trim', explode( ',', strtolower( $blocklist ) ) );
            $submission_text = strtolower(
                ( $_POST['cf_name'] ?? '' ) . ' ' . ( $_POST['cf_email'] ?? '' ) . ' ' . ( $_POST['cf_message'] ?? '' )
            );
            foreach ( $blocked_words as $word ) {
                if ( ! empty( $word ) && strpos( $submission_text, $word ) !== false ) {
                    return '<div class="cf-message cf-success">' . $msg_success . '</div>';
                }
            }
        }

        // Anti-Spam: Q&A
        if ( get_option( 'ccf_enable_qa', 0 ) ) {
            $user_answer   = strtolower( trim( $_POST['cf_qa_response'] ?? '' ) );
            $target_answer = strtolower( trim( get_option( 'ccf_qa_answer', '' ) ) );
            if ( $user_answer !== $target_answer ) {
                $output .= '<div class="cf-message cf-error">Incorrect answer to the security question. Please try again.</div>';
            }
        }

        // Nonce Check
        if ( empty( $output ) && ( ! isset( $_POST['cf_nonce'] ) || ! wp_verify_nonce( $_POST['cf_nonce'], 'ccf_form_action' ) ) ) {
            return '<div class="cf-message cf-error">Security check failed. Please try again.</div>';
        }

        // Processing Submission
        if ( empty( $output ) ) {
            $name    = sanitize_text_field( $_POST['cf_name'] ?? '' );
            $email   = sanitize_email( $_POST['cf_email'] ?? '' );
            $message = sanitize_textarea_field( $_POST['cf_message'] ?? '' );

            if ( empty( $name ) || empty( $email ) || empty( $message ) || ! is_email( $email ) ) {
                $output .= '<div class="cf-message cf-error">' . $msg_error . '</div>';
            } else {
                $configured_email = sanitize_email( get_option( 'ccf_recipient_email' ) );
                $to               = ! empty( $configured_email ) ? $configured_email : get_option( 'admin_email' );
                $site_from        = ccf_get_from_address();

                $headers   = array( 'Content-Type: text/plain; charset=UTF-8' );
                $headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
                $headers[] = 'From: ' . ( get_option( 'ccf_disable_reply_to', 0 ) ? $site_from : $name . ' <' . $email . '>' );

                $template_data = array(
                    'name'    => $name,
                    'email'   => $email,
                    'message' => $message,
                );

                $admin_mail = ccf_get_parsed_email(
                    'ccf_admin_email_subject',
                    '[{site_name}] New Message from {name}',
                    'ccf_admin_email_body',
                    "Name: {name}\nEmail: {email}\n\nMessage:\n{message}",
                    $template_data
                );

                add_action( 'phpmailer_init', 'ccf_apply_smtp_settings' );
                $sent = wp_mail( $to, $admin_mail['subject'], $admin_mail['body'], $headers );
                remove_action( 'phpmailer_init', 'ccf_apply_smtp_settings' );

                if ( $sent ) {
                    // Deferred Autoresponder
                    if ( get_option( 'ccf_enable_autoresponder', 0 ) ) {
                        $auto_mail = ccf_get_parsed_email(
                            'ccf_autoresponder_subject',
                            'We received your message!',
                            'ccf_autoresponder_body',
                            "Hi {name},\n\nThanks for reaching out! We received your message and will get back to you soon.\n\nYour Message:\n{message}",
                            $template_data
                        );

                        wp_schedule_single_event(
                            time() + 5,
                            'ccf_send_deferred_autoresponder',
                            array( $email, $auto_mail['subject'], $auto_mail['body'], array( 'Content-Type: text/plain; charset=UTF-8', 'From: ' . $site_from ) )
                        );
                    }

                    // Redirect handling
                    $redirect_page_id = get_option( 'ccf_redirect_page_id', 0 );
                    $redirect_target  = ! empty( $redirect_page_id ) ? get_permalink( $redirect_page_id ) : esc_url_raw( get_option( 'ccf_redirect_url', '' ) );

                    if ( ! empty( $redirect_target ) ) {
                        if ( ! headers_sent() ) {
                            wp_safe_redirect( $redirect_target );
                            exit;
                        } else {
                            return '<script type="text/javascript">window.location.href="' . esc_js( $redirect_target ) . '";</script><noscript><meta http-equiv="refresh" content="0;url=' . esc_attr( $redirect_target ) . '" /></noscript>';
                        }
                    }

                    return '<div class="cf-message cf-success">' . $msg_success . '</div>';
                } else {
                    return '<div class="cf-message cf-error">Mail delivery failed. Please try again later.</div>';
                }
            }
        }
    }

    // HTML Form Output
    $qa_enabled  = get_option( 'ccf_enable_qa', 0 );
    $qa_question = get_option( 'ccf_qa_question', 'What is 2 + 2?' );

    $output .= '
    <div class="wp-block-clean-contact-form-form ccf-container">
    <form method="post" class="custom-contact-form">
        ' . wp_nonce_field( 'ccf_form_action', 'cf_nonce', true, false ) . '
        <input type="hidden" name="cf_submitted" value="1">
        <input type="hidden" name="cf_time" value="' . time() . '">

        <div style="display:none !important; visibility:hidden !important;" aria-hidden="true">
            <input type="text" name="cf_website" tabindex="-1" autocomplete="off">
        </div>

        <div class="cf-field-group">
            <label for="cf_name">Name</label>
            <input type="text" id="cf_name" name="cf_name" required value="' . ( isset( $_POST['cf_name'] ) ? esc_attr( $_POST['cf_name'] ) : '' ) . '">
        </div>

        <div class="cf-field-group">
            <label for="cf_email">Email</label>
            <input type="email" id="cf_email" name="cf_email" required value="' . ( isset( $_POST['cf_email'] ) ? esc_attr( $_POST['cf_email'] ) : '' ) . '">
        </div>';

    if ( $qa_enabled ) {
        $output .= '
        <div class="cf-field-group">
            <label for="cf_qa_response">' . esc_html( $qa_question ) . '</label>
            <input type="text" id="cf_qa_response" name="cf_qa_response" required autocomplete="off">
        </div>';
    }

    $output .= '
        <div class="cf-field-group">
            <label for="cf_message">Message</label>
            <textarea id="cf_message" name="cf_message" rows="5" required>' . ( isset( $_POST['cf_message'] ) ? esc_textarea( $_POST['cf_message'] ) : '' ) . '</textarea>
        </div>

        <div class="cf-submit-group">
            <input type="submit" name="cf_submit_button" value="Send Message">
        </div>
    </form>
    </div>';

    return $output;
}

// Mailing List Handler & Output
function ccf_render_mailing_list_form_html() {
    $output      = '';
    $msg_success = esc_html( get_option( 'ccf_msg_success', 'Thank you for subscribing!' ) );
    $msg_error   = esc_html( get_option( 'ccf_msg_error', 'Please enter a valid email address.' ) );

    $is_rest_request = defined( 'REST_REQUEST' ) && REST_REQUEST;

    if ( ! $is_rest_request && isset( $_POST['ccf_ml_submitted'] ) ) {
        
        // Anti-Spam: Honeypot & Timecheck
        if ( ( get_option( 'ccf_enable_honeypot', 1 ) && ! empty( $_POST['cf_website'] ) ) ||
             ( get_option( 'ccf_enable_timecheck', 1 ) && ( time() - intval( $_POST['cf_time'] ?? 0 ) ) < 3 ) ) {
            return '<div class="cf-message cf-success">' . $msg_success . '</div>';
        }

        // Anti-Spam: Blocklist
        $email     = sanitize_email( $_POST['ccf_ml_email'] ?? '' );
        $blocklist = get_option( 'ccf_blocklist', '' );
        if ( ! empty( $blocklist ) ) {
            foreach ( array_map( 'trim', explode( ',', strtolower( $blocklist ) ) ) as $word ) {
                if ( ! empty( $word ) && strpos( strtolower( $email ), $word ) !== false ) {
                    return '<div class="cf-message cf-success">' . $msg_success . '</div>';
                }
            }
        }

        // Nonce Verification
        if ( ! isset( $_POST['ccf_ml_nonce'] ) || ! wp_verify_nonce( $_POST['ccf_ml_nonce'], 'ccf_ml_action' ) ) {
            return '<div class="cf-message cf-error">Security check failed. Please try again.</div>';
        }

        if ( empty( $email ) || ! is_email( $email ) ) {
            $output .= '<div class="cf-message cf-error">' . $msg_error . '</div>';
        } else {
            $configured_email = sanitize_email( get_option( 'ccf_recipient_email' ) );
            $to               = ! empty( $configured_email ) ? $configured_email : get_option( 'admin_email' );
            $site_from        = ccf_get_from_address();

            $headers   = array( 'Content-Type: text/plain; charset=UTF-8' );
            $headers[] = 'Reply-To: ' . $email;
            $headers[] = 'From: ' . ( get_option( 'ccf_disable_reply_to', 0 ) ? $site_from : $email );

            $template_data = array(
                'name'    => 'Subscriber',
                'email'   => $email,
                'message' => 'New mailing list subscription request.',
            );

            $mail = ccf_get_parsed_email(
                'ccf_admin_email_subject',
                '[{site_name}] New Mailing List Subscriber',
                'ccf_admin_email_body',
                "New Subscriber Email: {email}\n\nSubscribed on: {date}",
                $template_data
            );

            add_action( 'phpmailer_init', 'ccf_apply_smtp_settings' );
            $sent = wp_mail( $to, $mail['subject'], $mail['body'], $headers );
            remove_action( 'phpmailer_init', 'ccf_apply_smtp_settings' );

            if ( $sent ) {
                return '<div class="cf-message cf-success">' . $msg_success . '</div>';
            } else {
                return '<div class="cf-message cf-error">Subscription failed. Please try again later.</div>';
            }
        }
    }

    $output .= '
    <div class="wp-block-clean-contact-form-form ccf-container ccf-mailing-list-container">
    <form method="post" class="custom-contact-form ccf-mailing-list-form">
        ' . wp_nonce_field( 'ccf_ml_action', 'ccf_ml_nonce', true, false ) . '
        <input type="hidden" name="ccf_ml_submitted" value="1">
        <input type="hidden" name="cf_time" value="' . time() . '">

        <div style="display:none !important; visibility:hidden !important;" aria-hidden="true">
            <input type="text" name="cf_website" tabindex="-1" autocomplete="off">
        </div>

        <div class="cf-field-group">
            <label for="ccf_ml_email">Email Address</label>
            <input type="email" id="ccf_ml_email" name="ccf_ml_email" required value="' . ( isset( $_POST['ccf_ml_email'] ) ? esc_attr( $_POST['ccf_ml_email'] ) : '' ) . '" placeholder="your@email.com">
        </div>

        <div class="cf-submit-group">
            <input type="submit" name="ccf_ml_submit" value="Subscribe">
        </div>
    </form>
    </div>';

    return $output;
}