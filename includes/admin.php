<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Register Plugin Settings
add_action( 'admin_init', 'ccf_register_settings' );
function ccf_register_settings() {
    // Group: Email
    register_setting( 'ccf_options_email', 'ccf_recipient_email', 'sanitize_email' );
    register_setting( 'ccf_options_email', 'ccf_subject_prefix', 'sanitize_text_field' );
    register_setting( 'ccf_options_email', 'ccf_disable_reply_to', 'absint' );
    register_setting( 'ccf_options_email', 'ccf_enable_autoresponder', 'absint' );
    register_setting( 'ccf_options_email', 'ccf_admin_email_subject', 'sanitize_text_field' );
    register_setting( 'ccf_options_email', 'ccf_admin_email_body', 'sanitize_textarea_field' );
    register_setting( 'ccf_options_email', 'ccf_autoresponder_subject', 'sanitize_text_field' );
    register_setting( 'ccf_options_email', 'ccf_autoresponder_body', 'sanitize_textarea_field' );
    register_setting( 'ccf_options_email', 'ccf_newsletter_enable_autoresponder', 'absint' );
    register_setting( 'ccf_options_email', 'ccf_newsletter_autoresponder_subject', 'sanitize_text_field' );
    register_setting( 'ccf_options_email', 'ccf_newsletter_autoresponder_body', 'sanitize_textarea_field' );

    // Group: Security
    register_setting( 'ccf_options_security', 'ccf_enable_honeypot', 'absint' );
    register_setting( 'ccf_options_security', 'ccf_enable_timecheck', 'absint' );
    register_setting( 'ccf_options_security', 'ccf_enable_qa', 'absint' );
    register_setting( 'ccf_options_security', 'ccf_qa_question', 'sanitize_text_field' );
    register_setting( 'ccf_options_security', 'ccf_qa_answer', 'sanitize_text_field' );
    register_setting( 'ccf_options_security', 'ccf_blocklist', 'sanitize_textarea_field' );

    // Group: Messages
    register_setting( 'ccf_options_messages', 'ccf_msg_success', 'sanitize_text_field' );
    register_setting( 'ccf_options_messages', 'ccf_msg_error', 'sanitize_text_field' );
    register_setting( 'ccf_options_messages', 'ccf_redirect_page_id', 'absint' );
    register_setting( 'ccf_options_messages', 'ccf_redirect_url', 'esc_url_raw' );

    // Group: Styling
    register_setting( 'ccf_options_styling', 'ccf_color_label', 'sanitize_hex_color' );
    register_setting( 'ccf_options_styling', 'ccf_color_input_bg', 'sanitize_hex_color' );
    register_setting( 'ccf_options_styling', 'ccf_color_input_text', 'sanitize_hex_color' );
    register_setting( 'ccf_options_styling', 'ccf_color_input_border', 'sanitize_hex_color' );
    register_setting( 'ccf_options_styling', 'ccf_color_focus_border', 'sanitize_hex_color' );
    register_setting( 'ccf_options_styling', 'ccf_color_btn_bg', 'sanitize_hex_color' );
    register_setting( 'ccf_options_styling', 'ccf_color_btn_text', 'sanitize_hex_color' );
    register_setting( 'ccf_options_styling', 'ccf_color_btn_hover_bg', 'sanitize_hex_color' );
    register_setting( 'ccf_options_styling', 'ccf_custom_css', 'wp_strip_all_tags' );

    // Group: SMTP
    register_setting( 'ccf_options_smtp', 'ccf_smtp_enable', 'absint' );
    register_setting( 'ccf_options_smtp', 'ccf_smtp_host', 'sanitize_text_field' );
    register_setting( 'ccf_options_smtp', 'ccf_smtp_port', 'absint' );
    register_setting( 'ccf_options_smtp', 'ccf_smtp_encryption', 'sanitize_text_field' );
    register_setting( 'ccf_options_smtp', 'ccf_smtp_auth', 'absint' );
    register_setting( 'ccf_options_smtp', 'ccf_smtp_username', 'sanitize_text_field' );
    register_setting( 'ccf_options_smtp', 'ccf_smtp_password', 'sanitize_text_field' );
}

// Seed default options upon activation
function ccf_set_default_options() {
    $defaults = ccf_get_default_options();

    foreach ( $defaults as $option => $value ) {
        if ( false === get_option( $option ) ) {
            add_option( $option, $value );
        }
    }
}

// Admin Menu
add_action( 'admin_menu', 'ccf_add_admin_menu' );
function ccf_add_admin_menu() {
    add_options_page( 'Clean Contact Form', 'Clean Contact Form', 'manage_options', 'clean-contact-form', 'ccf_admin_page' );
}

// Admin Scripts
add_action( 'admin_enqueue_scripts', 'ccf_enqueue_admin_color_picker' );
function ccf_enqueue_admin_color_picker( $hook_suffix ) {
    if ( 'settings_page_clean-contact-form' !== $hook_suffix ) {
        return;
    }

    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' );
    wp_add_inline_script(
        'wp-color-picker',
        'jQuery(document).ready(function($){ $(".ccf-color-picker").wpColorPicker(); });'
    );
}

// Render Settings UI
function ccf_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $defaults = ccf_get_default_options();
    $allowed_tabs = array( 'email', 'security', 'messages', 'styling', 'smtp' );
    $active_tab   = isset( $_GET['tab'] ) && in_array( $_GET['tab'], $allowed_tabs, true ) ? $_GET['tab'] : 'email';
    $admin_mail   = get_option( 'admin_email' );
    ?>
    <div class="wrap">
        <h1>Clean Contact Form Settings</h1>
        <h2 class="nav-tab-wrapper">
            <a href="?page=clean-contact-form&tab=email" class="nav-tab <?php echo $active_tab === 'email' ? 'nav-tab-active' : ''; ?>">Email Settings</a>
            <a href="?page=clean-contact-form&tab=security" class="nav-tab <?php echo $active_tab === 'security' ? 'nav-tab-active' : ''; ?>">Spam & Security</a>
            <a href="?page=clean-contact-form&tab=messages" class="nav-tab <?php echo $active_tab === 'messages' ? 'nav-tab-active' : ''; ?>">Messages</a>
            <a href="?page=clean-contact-form&tab=styling" class="nav-tab <?php echo $active_tab === 'styling' ? 'nav-tab-active' : ''; ?>">Custom CSS</a>
            <a href="?page=clean-contact-form&tab=smtp" class="nav-tab <?php echo $active_tab === 'smtp' ? 'nav-tab-active' : ''; ?>">SMTP Settings</a>
        </h2>

        <form method="post" action="options.php">
            <?php
            settings_fields( 'ccf_options_' . $active_tab );

            if ( $active_tab === 'email' ) :
            ?>
                <h3>Notification Email (Admin)</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">Recipient Email</th>
                        <td><input type="email" name="ccf_recipient_email" value="<?php echo esc_attr( get_option( 'ccf_recipient_email', '' ) ); ?>" class="regular-text" placeholder="<?php echo esc_attr( $admin_mail ); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Email Subject</th>
                        <td><input type="text" name="ccf_admin_email_subject" value="<?php echo esc_attr( get_option( 'ccf_admin_email_subject', $defaults['ccf_admin_email_subject'] ) ); ?>" class="large-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Email Body Template</th>
                        <td><textarea name="ccf_admin_email_body" rows="6" class="large-text code"><?php echo esc_textarea( get_option( 'ccf_admin_email_body', $defaults['ccf_admin_email_body'] ) ); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row">Override From Header</th>
                        <td>
                            <label>
                                <input type="checkbox" name="ccf_disable_reply_to" value="1" <?php checked( 1, get_option( 'ccf_disable_reply_to', 0 ) ); ?> />
                                Force site admin email as "From" header (Required for strict SMTP providers).
                            </label>
                        </td>
                    </tr>
                </table>
                <hr />
                <h3>Autoresponder Email (Submitter)</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Autoresponder</th>
                        <td><label><input type="checkbox" name="ccf_enable_autoresponder" value="1" <?php checked( 1, get_option( 'ccf_enable_autoresponder', 0 ) ); ?> /> Send confirmation email to visitor.</label></td>
                    </tr>
                    <tr>
                        <th scope="row">Subject Line</th>
                        <td><input type="text" name="ccf_autoresponder_subject" value="<?php echo esc_attr( get_option( 'ccf_autoresponder_subject', $defaults['ccf_autoresponder_subject'] ) ); ?>" class="large-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Body Template</th>
                        <td><textarea name="ccf_autoresponder_body" rows="6" class="large-text code"><?php echo esc_textarea( get_option( 'ccf_autoresponder_body', $defaults['ccf_autoresponder_body'] ) ); ?></textarea></td>
                    </tr>
                </table>
                <hr />
                <h3>Newsletter Autoresponder</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Autoresponder</th>
                        <td>
                            <label>
                                <input type="checkbox" name="ccf_newsletter_enable_autoresponder" value="1" <?php checked( 1, get_option( 'ccf_newsletter_enable_autoresponder', 0 ) ); ?> />
                                Send automated welcome email to new newsletter subscribers.
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Subject Line</th>
                        <td>
                            <input type="text" name="ccf_newsletter_autoresponder_subject" value="<?php echo esc_attr( get_option( 'ccf_newsletter_autoresponder_subject', $defaults['ccf_newsletter_autoresponder_subject'] ) ); ?>" class="large-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Body Template</th>
                        <td>
                            <textarea name="ccf_newsletter_autoresponder_body" rows="6" class="large-text code"><?php echo esc_textarea( get_option( 'ccf_newsletter_autoresponder_body', $defaults['ccf_newsletter_autoresponder_body'] ) ); ?></textarea>
                        </td>
                    </tr>
                </table>

            <?php elseif ( $active_tab === 'security' ) : ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Honeypot Trap</th>
                        <td><label><input type="checkbox" name="ccf_enable_honeypot" value="1" <?php checked( 1, get_option( 'ccf_enable_honeypot', $defaults['ccf_enable_honeypot'] ) ); ?> /> Enable invisible honeypot field.</label></td>
                    </tr>
                    <tr>
                        <th scope="row">Time Check</th>
                        <td><label><input type="checkbox" name="ccf_enable_timecheck" value="1" <?php checked( 1, get_option( 'ccf_enable_timecheck', $defaults['ccf_enable_timecheck'] ) ); ?> /> Block submissions in under 3 seconds.</label></td>
                    </tr>
                    <tr>
                        <th scope="row">Human Q&A Challenge</th>
                        <td><label><input type="checkbox" name="ccf_enable_qa" value="1" <?php checked( 1, get_option( 'ccf_enable_qa', $defaults['ccf_enable_qa'] ) ); ?> /> Require custom question answer.</label></td>
                    </tr>
                    <tr>
                        <th scope="row">Question & Answer</th>
                        <td>
                            <input type="text" name="ccf_qa_question" value="<?php echo esc_attr( get_option( 'ccf_qa_question', 'What is 2 + 2?' ) ); ?>" class="regular-text" />
                            <input type="text" name="ccf_qa_answer" value="<?php echo esc_attr( get_option( 'ccf_qa_answer', '4' ) ); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Blocklist</th>
                        <td><textarea name="ccf_blocklist" rows="4" class="large-text code"><?php echo esc_textarea( get_option( 'ccf_blocklist', '' ) ); ?></textarea></td>
                    </tr>
                </table>

            <?php elseif ( $active_tab === 'messages' ) : ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Success Message</th>
                        <td><input type="text" name="ccf_msg_success" value="<?php echo esc_attr( get_option( 'ccf_msg_success', $defaults['ccf_msg_success'] ) ); ?>" class="large-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Redirect Page</th>
                        <td>
                            <?php
                            wp_dropdown_pages( array(
                                'name'             => 'ccf_redirect_page_id',
                                'show_option_none' => '— Select Page —',
                                'option_none_value'=> '0',
                                'selected'         => get_option( 'ccf_redirect_page_id', 0 ),
                            ) );
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Custom Redirect URL</th>
                        <td><input type="url" name="ccf_redirect_url" value="<?php echo esc_url( get_option( 'ccf_redirect_url', '' ) ); ?>" class="large-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Error Message</th>
                        <td><input type="text" name="ccf_msg_error" value="<?php echo esc_attr( get_option( 'ccf_msg_error', $defaults['ccf_msg_error'] ) ); ?>" class="large-text" /></td>
                    </tr>
                </table>

            <?php elseif ( $active_tab === 'styling' ) : ?>
                <table class="form-table">
                    <tr><th>Label Color</th><td><input type="text" name="ccf_color_label" value="<?php echo esc_attr( get_option( 'ccf_color_label', $defaults['ccf_color_label'] ) ); ?>" class="ccf-color-picker" /></td></tr>
                    <tr><th>Input Background</th><td><input type="text" name="ccf_color_input_bg" value="<?php echo esc_attr( get_option( 'ccf_color_input_bg', $defaults['ccf_color_input_bg'] ) ); ?>" class="ccf-color-picker" /></td></tr>
                    <tr><th>Input Text</th><td><input type="text" name="ccf_color_input_text" value="<?php echo esc_attr( get_option( 'ccf_color_input_text', $defaults['ccf_color_input_text'] ) ); ?>" class="ccf-color-picker" /></td></tr>
                    <tr><th>Input Border</th><td><input type="text" name="ccf_color_input_border" value="<?php echo esc_attr( get_option( 'ccf_color_input_border', $defaults['ccf_color_input_border'] ) ); ?>" class="ccf-color-picker" /></td></tr>
                    <tr><th>Focus Border</th><td><input type="text" name="ccf_color_focus_border" value="<?php echo esc_attr( get_option( 'ccf_color_focus_border', $defaults['ccf_color_focus_border'] ) ); ?>" class="ccf-color-picker" /></td></tr>
                    <tr><th>Button Background</th><td><input type="text" name="ccf_color_btn_bg" value="<?php echo esc_attr( get_option( 'ccf_color_btn_bg', $defaults['ccf_color_btn_bg'] ) ); ?>" class="ccf-color-picker" /></td></tr>
                    <tr><th>Button Text</th><td><input type="text" name="ccf_color_btn_text" value="<?php echo esc_attr( get_option( 'ccf_color_btn_text', $defaults['ccf_color_btn_text'] ) ); ?>" class="ccf-color-picker" /></td></tr>
                    <tr><th>Button Hover</th><td><input type="text" name="ccf_color_btn_hover_bg" value="<?php echo esc_attr( get_option( 'ccf_color_btn_hover_bg', $defaults['ccf_color_btn_hover_bg'] ) ); ?>" class="ccf-color-picker" /></td></tr>
                </table>
                <textarea name="ccf_custom_css" rows="8" class="large-text code"><?php echo esc_textarea( get_option( 'ccf_custom_css', '' ) ); ?></textarea>

            <?php elseif ( $active_tab === 'smtp' ) : $encryption = get_option( 'ccf_smtp_encryption', 'tls' ); ?>
                <table class="form-table">
                    <tr><th>Enable SMTP</th><td><label><input type="checkbox" name="ccf_smtp_enable" value="1" <?php checked( 1, get_option( 'ccf_smtp_enable', 0 ) ); ?> /> Enable custom SMTP routing.</label></td></tr>
                    <tr><th>SMTP Host</th><td><input type="text" name="ccf_smtp_host" value="<?php echo esc_attr( get_option( 'ccf_smtp_host', '' ) ); ?>" class="regular-text" /></td></tr>
                    <tr><th>SMTP Port</th><td><input type="number" name="ccf_smtp_port" value="<?php echo esc_attr( get_option( 'ccf_smtp_port', 465 ) ); ?>" class="small-text" /></td></tr>
                    <tr><th>Encryption</th><td>
                        <select name="ccf_smtp_encryption">
                            <option value="ssl" <?php selected( $encryption, 'ssl' ); ?>>SSL</option>
                            <option value="tls" <?php selected( $encryption, 'tls' ); ?>>TLS</option>
                            <option value="none" <?php selected( $encryption, 'none' ); ?>>None</option>
                        </select>
                    </td></tr>
                    <tr><th>Authentication</th><td><label><input type="checkbox" name="ccf_smtp_auth" value="1" <?php checked( 1, get_option( 'ccf_smtp_auth', 1 ) ); ?> /> Require Auth</label></td></tr>
                    <tr><th>Username</th><td><input type="text" name="ccf_smtp_username" value="<?php echo esc_attr( get_option( 'ccf_smtp_username', '' ) ); ?>" class="regular-text" autocomplete="off" /></td></tr>
                    <tr><th>Password</th><td><input type="password" name="ccf_smtp_password" value="<?php echo esc_attr( get_option( 'ccf_smtp_password', '' ) ); ?>" class="regular-text" autocomplete="new-password" /></td></tr>
                </table>
                <hr>
                <input type="email" id="ccf_test_email_target" class="regular-text" value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" />
                <button type="button" id="ccf_send_test_btn" class="button button-secondary">Send Test Email</button>
                <span id="ccf_test_result" style="margin-left: 10px; font-weight: bold;"></span>

                <script type="text/javascript">
                jQuery(document).ready(function($) {
                    $('#ccf_send_test_btn').on('click', function(e) {
                        e.preventDefault();
                        var $btn = $(this), $result = $('#ccf_test_result');
                        $btn.prop('disabled', true).text('Sending...');
                        $.post(ajaxurl, {
                            action: 'ccf_send_test_email',
                            nonce: '<?php echo wp_create_nonce( "ccf_test_email_nonce" ); ?>',
                            email: $('#ccf_test_email_target').val()
                        }, function(response) {
                            $btn.prop('disabled', false).text('Send Test Email');
                            $result.css('color', response.success ? 'green' : 'red').text(response.data.message);
                        });
                    });
                });
                </script>
            <?php endif; ?>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}