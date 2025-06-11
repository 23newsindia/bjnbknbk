<?php
if (!defined('ABSPATH')) {
    exit;
}

function wns_activate_plugin() {
    wns_install_subscriber_table();
    flush_rewrite_rules();
}

function wns_deactivate_plugin() {
    wp_clear_scheduled_hook('wns_cron_process_email_queue');
    flush_rewrite_rules();
}

function wns_uninstall_plugin() {
    global $wpdb;

    $subscriber_table = $wpdb->prefix . 'newsletter_subscribers';
    $queue_table = $wpdb->prefix . 'newsletter_email_queue';

    $wpdb->query("DROP TABLE IF EXISTS `$subscriber_table`, `$queue_table`");

    delete_option('wns_enable_verification');
    delete_option('wns_template_subscribe_subject');
    delete_option('wns_template_subscribe_body');
    delete_option('wns_template_unsubscribe_subject');
    delete_option('wns_template_unsubscribe_body');
    delete_option('wns_enable_new_post_notification');
    delete_option('wns_template_new_post_subject');
    delete_option('wns_template_new_post_body');
    delete_option('wns_email_batch_size');
    delete_option('wns_email_send_interval_minutes');
    delete_option('wns_unsubscribe_page_id');
}