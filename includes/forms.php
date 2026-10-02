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
        '1.5.2'
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
            --ccf-label-color: " . ccf_get_option( 'ccf_color_label' ) . ";
            --ccf-input-bg: " . ccf_get_option( 'ccf_color_input_bg' ) . ";
            --ccf-input-text: " . ccf_get_option( 'ccf_color_input_text' ) . ";
            --ccf-input-border: " . ccf_get_option( 'ccf_color_input_border' ) . ";
            --ccf-focus-border: " . ccf_get_option( 'ccf_color_focus_border' ) . ";
            --ccf-btn-bg: " . ccf_get_option( 'ccf_color_btn_bg' ) . ";
            --ccf-btn-text: " . ccf_get_option( 'ccf_color_btn_text' ) . ";
            --ccf-btn-hover-bg: " . ccf_get_option( 'ccf_color_btn_hover_bg' ) . ";
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
    return '<div class="ccf-custom-form-wrapper">' . ccf_render_form_html() . '</div>';
}

add_shortcode( 'clean_mailing_list', 'ccf_mailing_list_shortcode_handler' );
function ccf_mailing_list_shortcode_handler() {
    return '<div class="ccf-custom-form-wrapper">' . ccf_render_mailing_list_form_html() . '</div>';
}


/*
* Helper: Render Hidden Anti-Spam Fields
* 
* @param string $nonce_action The action name for nonce verification.
* @param string $nonce_field The name of the nonce field in the form.
* @param string $submit_field The name of the hidden submit field in the form.
* 
* @return string HTML output for hidden fields.
*/
function ccf_render_antispam_fields( $nonce_action, $nonce_field, $submit_field ) {
    $html  = wp_nonce_field( $nonce_action, $nonce_field, true, false );
    $html .= '<input type="hidden" name="' . esc_attr( $submit_field ) . '" value="1">';

    if ( ccf_get_option( 'ccf_enable_timecheck' ) ) {
        $html .= '<input type="hidden" name="ccf_time" value="' . time() . '">';
    }

    if ( ccf_get_option( 'ccf_enable_antispam_token' ) ) {
        $html .= '<input type="hidden" name="ccf_antispam_token" value="' . esc_attr( ccf_generate_antispam_token() ) . '">';
    }

    if ( ccf_get_option( 'ccf_enable_honeypot' ) ) {
        $html .= '<div style="display:none !important; visibility:hidden !important;" aria-hidden="true">';
        $html .= '    <input type="text" name="ccf_website" tabindex="-1" autocomplete="off">';
        $html .= '</div>';
    }

    return $html;
}

/*
 * Anti-Spam Validation
 * 
 * @param array $post_data The submitted form data ($_POST).
 * @param string $nonce_action The action name for nonce verification.
 * @param string $nonce_field The name of the nonce field in the form.
 * @param array $check_text_fields An array of field names to check against the blocklist.
 * 
 * @return true|string Returns true if validation passes, or an error message string if validation fails.
 */
function ccf_check_antispam_fields( $post_data, $nonce_action, $nonce_field, $check_text_fields = []) {
    $success_message = '<div class="ccf-message ccf-success">' . esc_html( ccf_get_option( 'ccf_msg_success' ) ) . '</div>';

    // Anti-Spam: Honeypot
    if ( ccf_get_option( 'ccf_enable_honeypot' ) && ! empty( $post_data['ccf_website'] ) ) {
        return $success_message;
    }

    // Anti-Spam: Link in name
    if ( ccf_get_option( 'ccf_enable_namenolink' ) && ! empty( $post_data['ccf_name'] ) && strpos( $post_data['ccf_name'], 'http' ) !== false ) {
        return $success_message;
    }

    // Anti-Spam: Time Check
    if ( ccf_get_option( 'ccf_enable_timecheck' ) ) {
        $load_time = isset( $post_data['ccf_time'] ) ? intval( $post_data['ccf_time'] ) : time();
        if ( ( time() - $load_time ) < ccf_get_option( 'ccf_timecheck_threshold' ) ) {
            return $success_message;
        }
    }

    // Anti-Spam: Blocklist
    $blocklist = ccf_get_option( 'ccf_blocklist' );
    if ( ! empty( $blocklist ) && ! empty( $check_text_fields ) ) {
        $blocked_words   = array_map( 'trim', explode( ',', strtolower( $blocklist ) ) );
        $submission_text = '';

        foreach ( $check_text_fields as $field ) {
            if ( isset( $post_data[ $field ] ) ) {
                $submission_text .= ' ' . strtolower( $post_data[ $field ] );
            }
        }

        foreach ( $blocked_words as $word ) {
            if ( ! empty( $word ) && strpos( $submission_text, $word ) !== false ) {
                return $success_message;
            }
        }
    }

    // Anti-Spam: Q&A
    if ( ccf_get_option( 'ccf_enable_qa' ) &&  isset( $post_data['ccf_qa_response'] ) ) {
        $user_answer   = strtolower( trim( $post_data['ccf_qa_response'] ?? '' ) );
        $target_answer = strtolower( trim( ccf_get_option( 'ccf_qa_answer' ) ) );
        if ( $user_answer !== $target_answer ) {
            return '<div class="ccf-message ccf-error">Incorrect answer to the security question. Please try again.</div>';
        }
    }

    // Anti-Spam: Antispam Token Validation
    if ( ccf_get_option( 'ccf_enable_antispam_token' ) && ! ccf_validate_antispam_token( $post_data['ccf_antispam_token'] ) ) {
        return '<div class="ccf-message ccf-error">Security check failed. Please try again.</div>';
    }

    // Nonce Check
    if ( empty( $post_data[ $nonce_field ] ) || ! wp_verify_nonce( $post_data[ $nonce_field ], $nonce_action ) ) {
        return '<div class="ccf-message ccf-error">Security check failed. Please try again.</div>';
    }

    return true;
}


// Contact Form Handler & Output
function ccf_render_form_html() {
    $output      = '';
    $msg_success = esc_html( ccf_get_option( 'ccf_msg_success' ) );
    $msg_error   = esc_html( ccf_get_option( 'ccf_msg_error' ) );
    $defaults = ccf_get_default_options();

    $is_rest_request = defined( 'REST_REQUEST' ) && REST_REQUEST;

    if ( ! $is_rest_request && isset( $_POST['ccf_cf_submitted'] ) ) {
        
        // Anti-Spam & Nonce Validation
        $antispam_check = ccf_check_antispam_fields( $_POST, 'ccf_cf_action', 'ccf_cf_nonce', ['ccf_name', 'ccf_email', 'ccf_message'] );
        if ( $antispam_check !== true ) {
            return $antispam_check; // Return the error message or success message from the anti-spam check
        }

        // Processing Submission
        if ( empty( $output ) ) {
            $name    = sanitize_text_field( $_POST['ccf_name'] ?? '' );
            $email   = sanitize_email( $_POST['ccf_email'] ?? '' );
            $message = sanitize_textarea_field( $_POST['ccf_message'] ?? '' );

            if ( empty( $name ) || empty( $email ) || empty( $message ) || ! is_email( $email ) ) {
                $output .= '<div class="ccf-message ccf-error">' . $msg_error . '</div>';
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
                    $defaults['ccf_admin_email_subject'],
                    'ccf_admin_email_body',
                    $defaults['ccf_admin_email_body'],
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
                            $defaults['ccf_autoresponder_subject'],
                            'ccf_autoresponder_body',
                            $defaults['ccf_autoresponder_body'],
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

                    return '<div class="ccf-message ccf-success">' . $msg_success . '</div>';
                } else {
                    return '<div class="ccf-message ccf-error">Mail delivery failed. Please try again later.</div>';
                }
            }
        }
    }

    // HTML Form Output
    $output .= '
    <div class="wp-block-clean-contact-form-form ccf-container">
    <form method="post" class="ccf-custom-form">
        ' . ccf_render_antispam_fields('ccf_cf_action', 'ccf_cf_nonce', 'ccf_cf_submitted') . '

        <div class="ccf-field-group">
            <label for="ccf_name">Name</label>
            <input type="text" id="ccf_name" name="ccf_name" maxlength="100" required value="' . ( isset( $_POST['ccf_name'] ) ? esc_attr( $_POST['ccf_name'] ) : '' ) . '">
        </div>

        <div class="ccf-field-group">
            <label for="ccf_email">Email</label>
            <input type="email" id="ccf_email" name="ccf_email" maxlength="128" required value="' . ( isset( $_POST['ccf_email'] ) ? esc_attr( $_POST['ccf_email'] ) : '' ) . '">
        </div>';

    if ( ccf_get_option( 'ccf_enable_qa' ) ) {
        $output .= '
        <div class="ccf-field-group">
            <label for="ccf_qa_response">' . esc_html( ccf_get_option( 'ccf_qa_question' ) ) . '</label>
            <input type="text" id="ccf_qa_response" name="ccf_qa_response" required autocomplete="off">
        </div>';
    }

    $output .= '
        <div class="ccf-field-group">
            <label for="ccf_message">Message</label>
            <textarea id="ccf_message" name="ccf_message" rows="5" required>' . ( isset( $_POST['ccf_message'] ) ? esc_textarea( $_POST['ccf_message'] ) : '' ) . '</textarea>
        </div>

        <div class="ccf-submit-group">
            <input type="submit" name="ccf_submit_button" value="Send Message">
        </div>
    </form>
    </div>';

    return $output;
}

// Mailing List Handler & Output
function ccf_render_mailing_list_form_html() {
    $output      = '';
    $msg_success = esc_html( ccf_get_option( 'ccf_msg_success' ) );
    $msg_error   = esc_html( ccf_get_option( 'ccf_msg_error' ) );
    $defaults = ccf_get_default_options();

    $is_rest_request = defined( 'REST_REQUEST' ) && REST_REQUEST;

    if ( ! $is_rest_request && isset( $_POST['ccf_ml_submitted'] ) ) {
        
        // Anti-Spam & Nonce Validation
        $antispam_check = ccf_check_antispam_fields( $_POST, 'ccf_ml_action', 'ccf_ml_nonce', ['ccf_email'] );
        if ( $antispam_check !== true ) {
            return $antispam_check; // Return the error message or success message from the anti-spam check
        }    


        if ( empty( $email ) || ! is_email( $email ) ) {
            $output .= '<div class="ccf-message -error">' . $msg_error . '</div>';
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
                $defaults['ccf_admin_email_subject'],
                'ccf_admin_email_body',
                $defaults['ccf_admin_email_body'],
                $template_data
            );

            add_action( 'phpmailer_init', 'ccf_apply_smtp_settings' );
            $sent = wp_mail( $to, $mail['subject'], $mail['body'], $headers );
            remove_action( 'phpmailer_init', 'ccf_apply_smtp_settings' );

            if ( $sent ) {
                $auto_mail = ccf_get_parsed_email(
                    'ccf_newsletter_autoresponder_subject',
                    $defaults['ccf_newsletter_autoresponder_subject'],
                    'ccf_newsletter_autoresponder_body',
                    $defaults['ccf_newsletter_autoresponder_body'],
                    $template_data
                );

                wp_schedule_single_event(
                    time() + 5,
                    'ccf_send_deferred_autoresponder',
                    array( $email, $auto_mail['subject'], $auto_mail['body'], array( 'Content-Type: text/plain; charset=UTF-8', 'From: ' . $site_from ) )
                );
                return '<div class="ccf-message ccf-success">' . $msg_success . '</div>';
            } else {
                return '<div class="ccf-message ccf-error">Subscription failed. Please try again later.</div>';
            }
        }
    }

    $output .= '
    <div class="wp-block-clean-contact-form-form ccf-container ccf-mailing-list-container">
    <form method="post" class="ccf-custom-form ccf-mailing-list-form">
        ' . ccf_render_antispam_fields('ccf_ml_action', 'ccf_ml_nonce', 'ccf_ml_submitted') . '

        <div class="ccf-field-group">
            <label for="ccf_ml_email">Email Address</label>
            <input type="email" id="ccf_ml_email" name="ccf_ml_email" required value="' . ( isset( $_POST['ccf_ml_email'] ) ? esc_attr( $_POST['ccf_ml_email'] ) : '' ) . '" placeholder="your@email.com">
        </div>

        <div class="ccf-submit-group">
            <input type="submit" name="ccf_ml_submit" value="Subscribe">
        </div>
    </form>
    </div>';

    return $output;
}