<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'wns_check_verification_request');

function wns_check_verification_request() {
    if (isset($_GET['verify_email']) && isset($_GET['token'])) {
        $email = sanitize_email($_GET['verify_email']);
        $token = sanitize_text_field($_GET['token']);

        if (wns_verify_email_token($email, $token)) {
            wns_mark_email_as_verified($email);
            wp_safe_redirect(add_query_arg('verified', 'success', wp_get_referer()));
            exit;
        } else {
            wp_safe_redirect(add_query_arg('verified', 'invalid', wp_get_referer()));
            exit;
        }
    }
}

function wns_generate_verification_token($email) {
    return substr(hash_hmac('sha256', $email, AUTH_SALT), 0, 32);
}

function wns_verify_email_token($email, $token) {
    global $wpdb;

    $stored_token = wns_generate_verification_token($email);

    if (!hash_equals($stored_token, $token)) {
        return false;
    }

    // Ensure email exists and is not already verified
    $table_name = WNS_TABLE_SUBSCRIBERS;
    $subscriber = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$table_name` WHERE `email` = %s", $email));

    if (!$subscriber || $subscriber->verified) {
        return false;
    }

    return true;
}

function wns_mark_email_as_verified($email) {
    global $wpdb;
    $table_name = WNS_TABLE_SUBSCRIBERS;

    $wpdb->update(
        $table_name,
        array('verified' => 1),
        array('email' => $email),
        array('%d'),
        array('%s')
    );
}

function wns_send_verification_email($email) {
    $token = wns_generate_verification_token($email);
    $verify_link = add_query_arg(array(
        'verify_email' => urlencode($email),
        'token' => $token
    ), home_url());

    $subject = __('Confirm Your Subscription', 'wp-newsletter-subscription');
    $message = sprintf(__('Thank you for subscribing! Please verify your email by clicking the link below:\n\n%s', 'wp-newsletter-subscription'), $verify_link);
    $headers = array('Content-Type: text/plain; charset=UTF-8');

    return wp_mail($email, $subject, $message, $headers);
}