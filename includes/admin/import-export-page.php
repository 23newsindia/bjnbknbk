<?php
if (!defined('ABSPATH')) {
    exit;
}

// Function is called from admin-menu.php, no need to add_action here
function wns_add_import_export_page() {
    add_submenu_page(
        'wns-settings',
        __('Import / Export Subscribers', 'wp-newsletter-subscription'),
        __('Import / Export', 'wp-newsletter-subscription'),
        'manage_options',
        'wns-import-export',
        'wns_render_import_export_page'
    );
}

function wns_render_import_export_page() {
    $message = '';

    // Handle export
    if (isset($_GET['action']) && $_GET['action'] === 'export' && check_admin_referer('wns_export_subscribers')) {
        wns_export_subscribers();
        exit;
    }

    // Handle import
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_subscribers']) && check_admin_referer('wns_import_subscribers')) {
        if (!empty($_FILES['subscriber_csv']['tmp_name'])) {
            $file = $_FILES['subscriber_csv'];
            $result = wns_import_subscribers_from_csv($file['tmp_name']);
            if ($result['success']) {
                $message = sprintf(
                    _n('%d subscriber imported successfully.', '%d subscribers imported successfully.', $result['count'], 'wp-newsletter-subscription'),
                    $result['count']
                );
            } else {
                $message = '<span class="error">' . esc_html($result['error']) . '</span>';
            }
        } else {
            $message = '<span class="error">' . __('Please select a CSV file to import.', 'wp-newsletter-subscription') . '</span>';
        }
    }
    ?>
    <div class="wrap">
        <h1><?php _e('Import / Export Newsletter Subscribers', 'wp-newsletter-subscription'); ?></h1>

        <?php if ($message): ?>
            <div id="message" class="updated fade">
                <p><?php echo $message; ?></p>
            </div>
        <?php endif; ?>

        <h2><?php _e('Export Subscribers', 'wp-newsletter-subscription'); ?></h2>
        <p>
            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=wns-import-export&action=export'), 'wns_export_subscribers'); ?>" class="button button-primary">
                <?php _e('Export All Subscribers', 'wp-newsletter-subscription'); ?>
            </a>
        </p>

        <h2><?php _e('Import Subscribers', 'wp-newsletter-subscription'); ?></h2>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('wns_import_subscribers'); ?>
            <input type="file" name="subscriber_csv" accept=".csv" required />
            <p class="description"><?php _e('CSV must have one column with header "email".', 'wp-newsletter-subscription'); ?></p>
            <br />
            <button type="submit" name="import_subscribers" class="button button-primary"><?php _e('Import Subscribers', 'wp-newsletter-subscription'); ?></button>
        </form>
    </div>
    <?php
}