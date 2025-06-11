<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_notices', 'wns_admin_unverified_notice');

function wns_admin_unverified_notice() {
    global $wpdb;

    $table_name = WNS_TABLE_SUBSCRIBERS;
    $unverified_count = $wpdb->get_var("SELECT COUNT(*) FROM `$table_name` WHERE `verified` = 0");

    if ($unverified_count > 0) {
        echo '<div class="notice notice-warning is-dismissible">';
        printf('<p>' . _n(
            '%d subscriber is waiting for email verification.',
            '%d subscribers are waiting for email verification.',
            $unverified_count,
            'wp-newsletter-subscription'
        ) . '</p>', $unverified_count);
        echo '</div>';
    }
}