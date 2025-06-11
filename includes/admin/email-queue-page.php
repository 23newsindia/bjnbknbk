<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'wns_add_email_queue_page');

function wns_add_email_queue_page() {
    add_submenu_page(
        'wns-settings',
        __('Email Queue', 'wp-newsletter-subscription'),
        __('Email Queue', 'wp-newsletter-subscription'),
        'manage_options',
        'wns-email-queue',
        'wns_render_email_queue_page'
    );
}