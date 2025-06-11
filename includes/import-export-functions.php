<?php
if (!defined('ABSPATH')) {
    exit;
}

function wns_export_subscribers() {
    global $wpdb;

    $table_name = WNS_TABLE_SUBSCRIBERS;
    $subscribers = $wpdb->get_results("SELECT email FROM `$table_name` ORDER BY created_at DESC");

    $filename = 'newsletter-subscribers-' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, array('email'));

    foreach ($subscribers as $subscriber) {
        fputcsv($output, array($subscriber->email));
    }

    fclose($output);
    exit;
}

function wns_import_subscribers_from_csv($file_path) {
    $handle = fopen($file_path, 'r');
    if (!$handle) {
        return array('success' => false, 'error' => __('Failed to open CSV file.', 'wp-newsletter-subscription'));
    }

    $count = 0;
    $headers = fgetcsv($handle);

    if (!is_array($headers) || !in_array('email', $headers)) {
        fclose($handle);
        return array('success' => false, 'error' => __('Invalid CSV format. Missing "email" column.', 'wp-newsletter-subscription'));
    }

    while (($data = fgetcsv($handle)) !== false) {
        $email = trim($data[0]);
        if (!is_email($email)) {
            continue;
        }

        $exists = wns_email_exists_in_subscribers($email);
        if (!$exists) {
            wns_add_subscriber_to_db($email);
            $count++;
        }
    }

    fclose($handle);

    return array('success' => true, 'count' => $count);
}

function wns_email_exists_in_subscribers($email) {
    global $wpdb;
    $table_name = WNS_TABLE_SUBSCRIBERS;
    return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `$table_name` WHERE `email` = %s", $email)) > 0;
}

function wns_add_subscriber_to_db($email) {
    global $wpdb;
    $table_name = WNS_TABLE_SUBSCRIBERS;
    $verified = get_option('wns_enable_verification', false) ? 0 : 1;

    $wpdb->insert($table_name, array(
        'email'     => $email,
        'verified'  => $verified
    ));
}