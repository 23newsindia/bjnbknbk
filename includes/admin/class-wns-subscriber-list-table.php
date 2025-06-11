<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Subscriber List Table Class
 */
class WNS_Subscriber_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => __('Subscriber', 'wp-newsletter-subscription'),
            'plural'   => __('Subscribers', 'wp-newsletter-subscription'),
            'ajax'     => false
        ]);
    }

    /**
     * Columns shown in table
     */
    public function get_columns() {
        return [
            'cb'         => '<input type="checkbox" />',
            'email'      => __('Email', 'wp-newsletter-subscription'),
            'verified'   => __('Verified', 'wp-newsletter-subscription'),
            'created_at' => __('Date Subscribed', 'wp-newsletter-subscription')
        ];
    }

    /**
     * Default column handler
     */
    protected function column_default($item, $column_name) {
        switch ($column_name) {
            case 'email':
            case 'created_at':
                return esc_html($item->$column_name);
            default:
                return print_r($item, true);
        }
    }

    /**
     * Verified column
     */
    function column_verified($item) {
        return $item->verified ? 
            '<span class="dashicons dashicons-yes"></span> ' . __('Yes', 'wp-newsletter-subscription') : 
            '<span class="dashicons dashicons-no-alt"></span> ' . __('No', 'wp-newsletter-subscription');
    }

    /**
     * Checkbox column
     */
    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="subscriber[]" value="%s" />',
            $item->id
        );
    }

    /**
     * Bulk actions
     */
    public function get_bulk_actions() {
        return [
            'delete' => __('Delete', 'wp-newsletter-subscription')
        ];
    }

    /**
     * Prepare items for display
     */
    public function prepare_items() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'newsletter_subscribers';

        $columns = $this->get_columns();
        $hidden = [];
        $sortable = [];
        $this->_column_headers = [$columns, $hidden, $sortable];

        // Handle bulk delete
        if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'delete' && isset($_REQUEST['subscriber'])) {
            $ids = array_map('absint', $_REQUEST['subscriber']);
            if (!empty($ids)) {
                $wpdb->query("DELETE FROM `$table_name` WHERE `id` IN (" . implode(',', $ids) . ")");
            }
        }

        // Build query
        $where = '';
        if (!empty($_REQUEST['s'])) {
            $search = '%' . $wpdb->esc_like(sanitize_email($_REQUEST['s'])) . '%';
            $where .= " WHERE `email` LIKE '$search'";
        }

        if (isset($_REQUEST['verified']) && $_REQUEST['verified'] === 'yes') {
            $where .= $where ? " AND `verified` = 1" : " WHERE `verified` = 1";
        } elseif (isset($_REQUEST['verified']) && $_REQUEST['verified'] === 'no') {
            $where .= $where ? " AND `verified` = 0" : " WHERE `verified` = 0";
        }

        // Pagination
        $orderby = !empty($_REQUEST['orderby']) ? sanitize_sql_orderby($_REQUEST['orderby']) : 'created_at';
        $order = (!empty($_REQUEST['order']) && strtoupper($_REQUEST['order']) === 'ASC') ? 'ASC' : 'DESC';

        $per_page = 20;
        $current_page = $this->get_pagenum();

        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM `$table_name` $where");

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page
        ]);

        $start = ($current_page - 1) * $per_page;

        $this->items = $wpdb->get_results(
            "SELECT * FROM `$table_name` $where ORDER BY `$orderby` $order LIMIT $start, $per_page"
        );
    }

    /**
     * Extra controls above table
     */
    function extra_tablenav($which) {
        if ($which === 'top') {
            ?>
            <div class="alignleft actions">
                <?php
                $verified_filter = isset($_REQUEST['verified']) ? $_REQUEST['verified'] : '';
                ?>
                <label for="filter-by-verified"><?php _e('Show:', 'wp-newsletter-subscription'); ?></label>
                <select name="verified" id="filter-by-verified">
                    <option value=""><?php _e('All Statuses', 'wp-newsletter-subscription'); ?></option>
                    <option value="yes" <?php selected($verified_filter, 'yes'); ?>><?php _e('Verified', 'wp-newsletter-subscription'); ?></option>
                    <option value="no" <?php selected($verified_filter, 'no'); ?>><?php _e('Unverified', 'wp-newsletter-subscription'); ?></option>
                </select>

                <?php
                submit_button(__('Filter', 'wp-newsletter-subscription'), '', 'filter_action', false);

                echo '<input type="text" name="s" value="' . (isset($_REQUEST['s']) ? esc_attr($_REQUEST['s']) : '') . '" placeholder="' . __('Search Email', 'wp-newsletter-subscription') . '" />';
                submit_button(__('Search', 'wp-newsletter-subscription'), '', '', false);
                ?>
            </div>
            <?php
        }
    }
}