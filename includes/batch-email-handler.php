<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('plugins_loaded', 'wns_setup_batch_email_cron');

function wns_setup_batch_email_cron() {
    if (!wp_next_scheduled('wns_cron_process_email_queue')) {
        wp_schedule_event(time(), 'every_minute', 'wns_cron_process_email_queue');
    }
}

// Register custom cron interval
add_filter('cron_schedules', 'wns_add_custom_cron_intervals');

function wns_add_custom_cron_intervals($schedules) {
    $schedules['every_minute'] = array(
        'interval' => 60,
        'display' => __('Once Every Minute')
    );
    return $schedules;
}

add_action('wns_cron_process_email_queue', 'wns_process_email_queue');

function wns_process_email_queue() {
    global $wpdb;

    // Ensure tables exist before processing
    wns_check_and_create_tables_if_missing();

    $batch_size = get_option('wns_email_batch_size', 100);
    $now = current_time('timestamp');
    $queue_table = $wpdb->prefix . 'newsletter_email_queue';

    // Check if table exists before querying
    if ($wpdb->get_var("SHOW TABLES LIKE '$queue_table'") != $queue_table) {
        return; // Table doesn't exist, skip processing
    }

    // Get all pending emails
    $emails = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM `$queue_table`
             WHERE send_at <= %s AND sent = 0
             ORDER BY id ASC LIMIT %d",
            date('Y-m-d H:i:s', $now),
            $batch_size
        )
    );

    if (!$emails || $wpdb->last_error) {
        if ($wpdb->last_error) {
            error_log('WNS Plugin Error in email queue processing: ' . $wpdb->last_error);
        }
        return;
    }

    foreach ($emails as $email) {
        $headers = maybe_unserialize($email->headers);
        $sent = wp_mail($email->recipient, $email->subject, $email->body, $headers);

        // Mark as sent
        $wpdb->update(
            $queue_table,
            array('sent' => 1, 'sent_at' => current_time('mysql')),
            array('id' => $email->id),
            array('%d', '%s'),
            array('%d')
        );
    }
}

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