<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_init', 'wns_handle_broadcast_submission');

function wns_handle_broadcast_submission() {
    if (!isset($_POST['wns_send_newsletter']) || !check_admin_referer('wns_send_newsletter')) {
        return;
    }

    $subject = sanitize_text_field($_POST['wns_email_subject']);
    $body = wp_kses_post($_POST['wns_email_body']);
    $send_now = isset($_POST['wns_send_now']) ? true : false;

    if (empty($subject) || empty($body)) {
        add_settings_error('wns_broadcast_messages', 'error', __('Subject and body are required.', 'wp-newsletter-subscription'), 'error');
        return;
    }

    global $wpdb;
    $subscriber_table = WNS_TABLE_SUBSCRIBERS;
    $queue_table = $wpdb->prefix . 'newsletter_email_queue';

    $headers = array('Content-Type: text/html; charset=UTF-8');
    $send_at = $send_now ? current_time('mysql') : date('Y-m-d H:i:s', strtotime('+1 minute'));

    $subscribers = $wpdb->get_results("SELECT email FROM `$subscriber_table` WHERE verified = 1");

    foreach ($subscribers as $subscriber) {
        $wpdb->insert($queue_table, array(
            'recipient' => $subscriber->email,
            'subject'   => $subject,
            'body'      => $body,
            'headers'   => maybe_serialize($headers),
            'send_at'   => $send_at,
            'sent'      => 0
        ));
    }

    $count = count($subscribers);
    $message = sprintf(_n('%d email added to queue.', '%d emails added to queue.', $count, 'wp-newsletter-subscription'), $count);

    add_settings_error('wns_broadcast_messages', 'success', $message, 'success');
}