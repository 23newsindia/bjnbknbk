<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'wns_add_broadcast_page');

function wns_add_broadcast_page() {
    add_submenu_page(
        'wns-settings',
        __('Send Newsletter', 'wp-newsletter-subscription'),
        __('Send Newsletter', 'wp-newsletter-subscription'),
        'manage_options',
        'wns-send-newsletter',
        'wns_render_broadcast_page'
    );
}