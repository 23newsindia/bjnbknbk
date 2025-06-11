<?php
if (!defined('ABSPATH')) {
    exit;
}

// Use transition_post_status to detect only new posts
add_action('transition_post_status', 'wns_send_new_post_notifications', 10, 3);

function wns_send_new_post_notifications($new_status, $old_status, $post) {
    // Only proceed if this is a post being published for the first time
    if ($new_status !== 'publish' || $old_status === 'publish') {
        return;
    }
    
    // Only handle 'post' post type (you can modify this to include other post types)
    if ($post->post_type !== 'post') {
        return;
    }
    
    // Skip if doing autosave or revision
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (wp_is_post_revision($post->ID)) {
        return;
    }
    
    // Skip if doing cron (to avoid duplicate sends)
    if (defined('DOING_CRON') && DOING_CRON) {
        return;
    }

    $enabled = get_option('wns_enable_new_post_notification', false);
    if (!$enabled) {
        return;
    }

    // Check if we've already sent notification for this post
    $notification_sent = get_post_meta($post->ID, '_wns_notification_sent', true);
    if ($notification_sent) {
        return;
    }

    // Mark that we're about to send notification to prevent duplicates
    update_post_meta($post->ID, '_wns_notification_sent', '1');

    // Schedule sending of emails via cron (delay by 30 seconds to ensure post is fully saved)
    if (!wp_next_scheduled('wns_cron_send_post_notification', array($post->ID))) {
        wp_schedule_single_event(time() + 30, 'wns_cron_send_post_notification', array($post->ID));
    }
}

add_action('wns_cron_send_post_notification', 'wns_cron_handler_send_post_notification', 10, 1);

function wns_cron_handler_send_post_notification($post_id) {
    global $wpdb;

    $post = get_post($post_id);
    if (!$post || $post->post_status !== 'publish') {
        return;
    }

    // Double-check that notification hasn't been sent already
    $notification_sent = get_post_meta($post_id, '_wns_notification_sent', true);
    if (!$notification_sent) {
        return;
    }

    // Ensure tables exist
    wns_check_and_create_tables_if_missing();

    $table_name = WNS_TABLE_SUBSCRIBERS;
    
    // Check if table exists before querying
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        return;
    }

    $subscribers = $wpdb->get_results("SELECT email FROM `$table_name` WHERE verified = 1");

    if (!$subscribers || $wpdb->last_error) {
        if ($wpdb->last_error) {
            error_log('WNS Plugin Error in new post notification: ' . $wpdb->last_error);
        }
        return;
    }

    $subject_template = get_option('wns_template_new_post_subject', __('New Blog Post: {post_title}', 'wp-newsletter-subscription'));
    $body_template = get_option('wns_template_new_post_body', __("Hi there,\n\nWe've just published a new blog post that you might enjoy:\n\n{post_title}\n{post_excerpt}\n\nRead more: {post_url}\n\nThanks,\nThe Team\n\n{unsubscribe_link}", 'wp-newsletter-subscription'));

    $subject = str_replace('{post_title}', $post->post_title, $subject_template);
    
    $excerpt = '';
    if (has_excerpt($post_id)) {
        $excerpt = $post->post_excerpt;
    } else {
        $excerpt = wp_trim_words(strip_tags($post->post_content), 50);
    }
    
    $post_url = get_permalink($post_id);
    
    $body = str_replace(
        array('{post_title}', '{post_excerpt}', '{post_url}'),
        array($post->post_title, $excerpt, $post_url),
        $body_template
    );

    $headers = array('Content-Type: text/html; charset=UTF-8');
    $send_after = current_time('mysql');
    $queue_table = $wpdb->prefix . 'newsletter_email_queue';

    // Check if queue table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$queue_table'") != $queue_table) {
        error_log('WNS Plugin Error: Email queue table does not exist');
        return;
    }

    $emails_queued = 0;
    foreach ($subscribers as $subscriber) {
        $result = $wpdb->insert($queue_table, array(
            'recipient' => $subscriber->email,
            'subject'   => $subject,
            'body'      => $body,
            'headers'   => maybe_serialize($headers),
            'send_at'   => $send_after,
            'sent'      => 0
        ));
        
        if ($result) {
            $emails_queued++;
        }
    }

    // Log successful queuing
    if ($emails_queued > 0) {
        error_log("WNS Plugin: Queued $emails_queued emails for new post: " . $post->post_title);
        
        // Update post meta to indicate notification was successfully queued
        update_post_meta($post_id, '_wns_notification_queued', current_time('mysql'));
        update_post_meta($post_id, '_wns_emails_queued', $emails_queued);
    }
}

// Helper function to check and create tables if missing
function wns_check_and_create_tables_if_missing() {
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

// Add admin notice to show when notifications are sent
add_action('admin_notices', 'wns_show_notification_status');

function wns_show_notification_status() {
    global $post;
    
    if (!$post || $post->post_type !== 'post') {
        return;
    }
    
    $notification_sent = get_post_meta($post->ID, '_wns_notification_sent', true);
    $emails_queued = get_post_meta($post->ID, '_wns_emails_queued', true);
    
    if ($notification_sent && $emails_queued) {
        echo '<div class="notice notice-info is-dismissible">';
        printf(
            '<p>' . __('Newsletter notification sent: %d emails queued for this post.', 'wp-newsletter-subscription') . '</p>',
            intval($emails_queued)
        );
        echo '</div>';
    }
}