<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('publish_post', 'wns_send_new_post_notifications');

function wns_send_new_post_notifications($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;

    $enabled = get_option('wns_enable_new_post_notification', false);
    if (!$enabled) return;

    // Schedule sending of emails via cron
    if (!wp_next_scheduled('wns_cron_send_post_notification', array($post_id))) {
        wp_schedule_single_event(time(), 'wns_cron_send_post_notification', array($post_id));
    }
}

add_action('wns_cron_send_post_notification', 'wns_cron_handler_send_post_notification', 10, 1);

function wns_cron_handler_send_post_notification($post_id) {
    global $wpdb;

    $post = get_post($post_id);
    if (!$post || $post->post_status !== 'publish') return;

    $table_name = WNS_TABLE_SUBSCRIBERS;
    $subscribers = $wpdb->get_results("SELECT email FROM `$table_name` WHERE verified = 1");

    if (!$subscribers) return;

    $subject = str_replace('{post_title}', $post->post_title, get_option('wns_template_new_post_subject'));
    $excerpt = has_excerpt($post_id) ? $post->post_excerpt : wp_trim_words($post->post_content, 50);
    $body = str_replace(
        array('{post_title}', '{post_excerpt}', '{post_url}'),
        array($post->post_title, $excerpt, get_permalink($post_id)),
        get_option('wns_template_new_post_body')
    );

    $headers = array('Content-Type: text/plain; charset=UTF-8');

    $send_after = current_time('mysql');
    $queue_table = $wpdb->prefix . 'newsletter_email_queue';

    foreach ($subscribers as $subscriber) {
        $wpdb->insert($queue_table, array(
            'recipient' => $subscriber->email,
            'subject'   => $subject,
            'body'      => $body,
            'headers'   => maybe_serialize($headers),
            'send_at'   => $send_after,
            'sent'      => 0
        ));
    }
}