<?php
/**
 * Plugin Name: WP Newsletter Subscription
 * Plugin URI: https://example.com/wp-newsletter-subscription 
 * Description: A simple newsletter subscription system with email verification, import/export, and scheduled mail sending.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com 
 * Text Domain: wp-newsletter-subscription
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Ensure WordPress core functions are available
if (!function_exists('add_action')) {
    exit("WordPress not loaded!");
}

// Define plugin constants
define('WNS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WNS_PLUGIN_URL', plugin_dir_url(__FILE__));
global $wpdb;
define('WNS_TABLE_SUBSCRIBERS', $wpdb->prefix . 'newsletter_subscribers');

// Hook for activation
register_activation_hook(__FILE__, 'wns_activate_plugin');
register_deactivation_hook(__FILE__, 'wns_deactivate_plugin');
register_uninstall_hook(__FILE__, 'wns_uninstall_plugin');

// Include core plugin files
require_once WNS_PLUGIN_DIR . 'includes/install.php';
require_once WNS_PLUGIN_DIR . 'includes/activation.php';

// Admin includes
require_once WNS_PLUGIN_DIR . 'includes/admin/settings-page.php';
require_once WNS_PLUGIN_DIR . 'includes/admin/subscriber-list-page.php';
require_once WNS_PLUGIN_DIR . 'includes/admin/class-wns-subscriber-list-table.php';
require_once WNS_PLUGIN_DIR . 'includes/admin/subscriber-list-view.php';
require_once WNS_PLUGIN_DIR . 'includes/admin/import-export-page.php';
require_once WNS_PLUGIN_DIR . 'includes/admin/unverified-notice.php';
require_once WNS_PLUGIN_DIR . 'includes/admin/post-notification-settings.php';
require_once WNS_PLUGIN_DIR . 'includes/admin/rate-limit-settings.php';
require_once WNS_PLUGIN_DIR . 'includes/admin/broadcast-page.php';
require_once WNS_PLUGIN_DIR . 'includes/admin/broadcast-handler.php';
require_once WNS_PLUGIN_DIR . 'includes/admin/broadcast-view.php';
require_once WNS_PLUGIN_DIR . 'includes/admin/email-queue-page.php';
require_once WNS_PLUGIN_DIR . 'includes/admin/class-wns-email-queue-list-table.php';
require_once WNS_PLUGIN_DIR . 'includes/admin/email-queue-view.php';

// Frontend includes
require_once WNS_PLUGIN_DIR . 'includes/subscription-form.php';
require_once WNS_PLUGIN_DIR . 'includes/assets.php';
require_once WNS_PLUGIN_DIR . 'includes/verification.php';
require_once WNS_PLUGIN_DIR . 'includes/import-export-functions.php';
require_once WNS_PLUGIN_DIR . 'includes/auto-import-users.php';
require_once WNS_PLUGIN_DIR . 'includes/unsubscribe-handler.php';
require_once WNS_PLUGIN_DIR . 'includes/new-post-notification.php';
require_once WNS_PLUGIN_DIR . 'includes/batch-email-handler.php';
require_once WNS_PLUGIN_DIR . 'includes/cleanup.php';