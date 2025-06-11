<?php
if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('newsletter_subscribe', 'wns_render_subscription_form');

function wns_render_subscription_form($atts) {
    $atts = shortcode_atts(array(
        'show_unsubscribe' => false,
    ), $atts, 'newsletter_subscribe');

    ob_start();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wns_subscribe_email'])) {
        $email = sanitize_email($_POST['wns_subscribe_email']);
        $result = wns_handle_subscription($email);

        if ($result === true) {
            echo '<div class="wns-message success">' . __('Thank you for subscribing!', 'wp-newsletter-subscription') . '</div>';
        } else {
            echo '<div class="wns-message error">' . esc_html($result) . '</div>';
        }
    }

    ?>
    <form method="post" class="wns-subscribe-form">
        <input type="email" name="wns_subscribe_email" placeholder="<?php esc_attr_e('Enter your email', 'wp-newsletter-subscription'); ?>" required />
        <button type="submit"><?php _e('Subscribe', 'wp-newsletter-subscription'); ?></button>
    </form>
    <?php

    return ob_get_clean();
}

function wns_handle_subscription($email) {
    global $wpdb;

    if (!is_email($email)) {
        return __('Invalid email address.', 'wp-newsletter-subscription');
    }

    // Ensure tables exist
    wns_check_and_create_tables();

    $table_name = WNS_TABLE_SUBSCRIBERS;

    // Check if already exists
    $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `$table_name` WHERE `email` = %s", $email));
    
    if ($wpdb->last_error) {
        error_log('WNS Plugin Error: ' . $wpdb->last_error);
        return __('Database error. Please contact administrator.', 'wp-newsletter-subscription');
    }
    
    if ($exists > 0) {
        return __('You are already subscribed.', 'wp-newsletter-subscription');
    }

    $enable_verification = get_option('wns_enable_verification', false);
    $verified = $enable_verification ? 0 : 1;

    $inserted = $wpdb->insert($table_name, array(
        'email'     => $email,
        'verified'  => $verified
    ));

    if (!$inserted) {
        error_log('WNS Plugin Error: Failed to insert subscriber - ' . $wpdb->last_error);
        return __('An error occurred. Please try again later.', 'wp-newsletter-subscription');
    }

    if ($enable_verification) {
        $sent = wns_send_verification_email($email);
        if (!$sent) {
            return __('Failed to send verification email.', 'wp-newsletter-subscription');
        }
        return __('A verification email has been sent. Please check your inbox.', 'wp-newsletter-subscription');
    }

    return true;
}

function wns_check_and_create_tables() {
    global $wpdb;
    
    $subscriber_table = $wpdb->prefix . 'newsletter_subscribers';
    $queue_table = $wpdb->prefix . 'newsletter_email_queue';
    
    // Check if tables exist
    $subscriber_exists = $wpdb->get_var("SHOW TABLES LIKE '$subscriber_table'") == $subscriber_table;
    $queue_exists = $wpdb->get_var("SHOW TABLES LIKE '$queue_table'") == $queue_table;
    
    if (!$subscriber_exists || !$queue_exists) {
        wns_install_subscriber_table();
    }
}