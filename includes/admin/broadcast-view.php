<?php
if (!defined('ABSPATH')) {
    exit;
}

function wns_render_broadcast_page() {
    settings_errors('wns_broadcast_messages');

    $default_subject = __('A Message From Our Team', 'wp-newsletter-subscription');
    $default_body = __('Hello there,<br><br>This is a sample message you can customize before sending to your subscribers.<br><br>Best regards,<br>The Team', 'wp-newsletter-subscription');

    $subject = isset($_POST['wns_email_subject']) ? esc_attr($_POST['wns_email_subject']) : $default_subject;
    $body = isset($_POST['wns_email_body']) ? wp_kses_post($_POST['wns_email_body']) : $default_body;
    ?>
    <div class="wrap">
        <h1><?php _e('Send Newsletter Broadcast', 'wp-newsletter-subscription'); ?></h1>
        <form method="post" id="wns-broadcast-form">
            <?php wp_nonce_field('wns_send_newsletter'); ?>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="wns_email_subject"><?php _e('Email Subject', 'wp-newsletter-subscription'); ?></label></th>
                    <td><input type="text" name="wns_email_subject" value="<?php echo esc_attr($subject); ?>" class="large-text" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Email Body', 'wp-newsletter-subscription'); ?></th>
                    <td>
                        <?php wp_editor($body, 'wns_email_body', array(
                            'textarea_name' => 'wns_email_body',
                            'teeny' => false,
                            'quicktags' => true,
                            'media_buttons' => false,
                            'textarea_rows' => 15,
                            'tinymce' => true
                        )); ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Send Options', 'wp-newsletter-subscription'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="wns_send_now" checked />
                            <?php _e('Send immediately', 'wp-newsletter-subscription'); ?>
                        </label>
                        <p class="description"><?php _e('Uncheck this to schedule the email to be sent in 1 minute.', 'wp-newsletter-subscription'); ?></p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Send Newsletter', 'wp-newsletter-subscription'); ?></button>
            </p>
        </form>
    </div>
    <?php
}