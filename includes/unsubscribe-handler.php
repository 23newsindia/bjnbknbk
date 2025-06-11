<?php
if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('newsletter_unsubscribe', 'wns_render_unsubscribe_form');

function wns_render_unsubscribe_form() {
    ob_start();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wns_unsubscribe_email'])) {
        $email = sanitize_email($_POST['wns_unsubscribe_email']);
        $result = wns_handle_unsubscribe($email);

        if ($result === true) {
            echo '<div class="wns-message success">' . __('You have been successfully unsubscribed.', 'wp-newsletter-subscription') . '</div>';
        } else {
            echo '<div class="wns-message error">' . esc_html($result) . '</div>';
        }
    }

    ?>
    <form method="post" class="wns-unsubscribe-form">
        <p><?php _e('Enter your email to unsubscribe:', 'wp-newsletter-subscription'); ?></p>
        <input type="email" name="wns_unsubscribe_email" placeholder="<?php esc_attr_e('Your email address', 'wp-newsletter-subscription'); ?>" required />
        <button type="submit"><?php _e('Unsubscribe', 'wp-newsletter-subscription'); ?></button>
    </form>
    <?php

    return ob_get_clean();
}

function wns_handle_unsubscribe($email) {
    global $wpdb;

    if (!is_email($email)) {
        return __('Invalid email address.', 'wp-newsletter-subscription');
    }

    $table_name = WNS_TABLE_SUBSCRIBERS;

    // Check if exists
    $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `$table_name` WHERE `email` = %s", $email));
    if ($exists == 0) {
        return __('This email is not subscribed.', 'wp-newsletter-subscription');
    }

    $deleted = $wpdb->delete($table_name, array('email' => $email), array('%s'));

    if (!$deleted) {
        return __('An error occurred. Please try again later.', 'wp-newsletter-subscription');
    }

    $subject = get_option('wns_template_unsubscribe_subject', __('You Have Been Unsubscribed', 'wp-newsletter-subscription'));
    $body = get_option('wns_template_unsubscribe_body', __("You have successfully unsubscribed from our newsletter. We're sorry to see you go!", 'wp-newsletter-subscription'));

    $headers = array('Content-Type: text/plain; charset=UTF-8');
    wp_mail($email, $subject, $body, $headers);

    return true;
}