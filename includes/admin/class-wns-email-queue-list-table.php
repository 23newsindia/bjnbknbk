<?php
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WNS_Email_Queue_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => __('Email', 'wp-newsletter-subscription'),
            'plural'   => __('Emails', 'wp-newsletter-subscription'),
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        return [
            'cb'        => '<input type="checkbox" />',
            'recipient' => __('Recipient', 'wp-newsletter-subscription'),
            'subject'   => __('Subject', 'wp-newsletter-subscription'),
            'send_at'   => __('Scheduled For', 'wp-newsletter-subscription'),
            'sent_at'   => __('Sent At', 'wp-newsletter-subscription'),
            'status'    => __('Status', 'wp-newsletter-subscription')
        ];
    }

    protected function column_default($item, $column_name) {
        switch ($column_name) {
            case 'recipient':
            case 'subject':
            case 'send_at':
            case 'sent_at':
                return esc_html($item->$column_name);
            default:
                return print_r($item, true);
        }
    }

    function column_status($item) {
        return $item->sent ? '<span class="dashicons dashicons-yes"></span> ' . __('Sent', 'wp-newsletter-subscription') : '<span class="dashicons dashicons-clock"></span> ' . __('Pending', 'wp-newsletter-subscription');
    }

    function get_bulk_actions() {
        return array(
            'delete' => __('Delete', 'wp-newsletter-subscription')
        );
    }

    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="email[]" value="%s" />',
            $item->id
        );
    }

    public function prepare_items() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'newsletter_email_queue';

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);

        // Handle bulk actions
        if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'delete' && isset($_REQUEST['email'])) {
            $ids = array_map('intval', $_REQUEST['email']);
            if (!empty($ids)) {
                $wpdb->query("DELETE FROM `$table_name` WHERE `id` IN (" . implode(',', $ids) . ")");
            }
        }

        // Query
        $where = '';
        if (isset($_REQUEST['status']) && $_REQUEST['status'] === 'sent') {
            $where = "WHERE `sent` = 1";
        } elseif (isset($_REQUEST['status']) && $_REQUEST['status'] === 'pending') {
            $where = "WHERE `sent` = 0";
        }

        $orderby = !empty($_REQUEST['orderby']) ? sanitize_sql_orderby($_REQUEST['orderby']) : 'send_at';
        $order = !empty($_REQUEST['order']) && strtoupper($_REQUEST['order']) === 'ASC' ? 'ASC' : 'DESC';

        $per_page = 20;
        $current_page = $this->get_pagenum();

        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM `$table_name` $where");

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page
        ));

        $start = ($current_page - 1) * $per_page;

        $this->items = $wpdb->get_results(
            "SELECT * FROM `$table_name` $where ORDER BY `$orderby` $order LIMIT $start, $per_page"
        );
    }

    function extra_tablenav($which) {
        if ($which === 'top') {
            $status = isset($_REQUEST['status']) ? $_REQUEST['status'] : '';
            ?>
            <div class="alignleft actions">
                <label for="filter-by-status" class="screen-reader-text"><?php _e('Filter by status', 'wp-newsletter-subscription'); ?></label>
                <select name="status" id="filter-by-status">
                    <option value=""><?php _e('All Statuses', 'wp-newsletter-subscription'); ?></option>
                    <option value="pending" <?php selected($status, 'pending'); ?>><?php _e('Pending', 'wp-newsletter-subscription'); ?></option>
                    <option value="sent" <?php selected($status, 'sent'); ?>><?php _e('Sent', 'wp-newsletter-subscription'); ?></option>
                </select>
                <?php submit_button(__('Filter', 'wp-newsletter-subscription'), '', 'filter_action', false); ?>
            </div>
            <?php
        }
    }
}