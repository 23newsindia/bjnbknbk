<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'wns_add_subscriber_list_page');

function wns_add_subscriber_list_page() {
    add_submenu_page(
        'wns-settings',
        __('Subscribers', 'wp-newsletter-subscription'),
        __('Subscribers', 'wp-newsletter-subscription'),
        'manage_options',
        'wns-subscribers',
        'wns_render_subscriber_list_page'
    );
}