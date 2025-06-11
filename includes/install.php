<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

function wns_install_subscriber_table() {
    global $wpdb;

    $subscriber_table = $wpdb->prefix . 'newsletter_subscribers';
    $queue_table = $wpdb->prefix . 'newsletter_email_queue';

    $charset_collate = $wpdb->get_charset_collate();

    // Subscribers table
    $sql = "CREATE TABLE IF NOT EXISTS `$subscriber_table` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `email` VARCHAR(255) NOT NULL UNIQUE,
        `verified` TINYINT NOT NULL DEFAULT 0,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `email_verified` (`email`, `verified`)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    // Email queue table
    $sql = "CREATE TABLE IF NOT EXISTS `$queue_table` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `recipient` VARCHAR(255) NOT NULL,
        `subject` TEXT NOT NULL,
        `body` LONGTEXT NOT NULL,
        `headers` TEXT,
        `send_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `sent` TINYINT NOT NULL DEFAULT 0,
        `sent_at` DATETIME DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `send_at_sent` (`send_at`, `sent`)
    ) $charset_collate;";

    dbDelta($sql);
}