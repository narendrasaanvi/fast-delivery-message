<?php
/**
 * Plugin Name: Fast Delivery Message
 * Description: WooCommerce fast delivery message based on pincode ranges.
 * Version: 1.0.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit;
}

define('FDM_VERSION', '1.0.0');

function fdm_activate()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'fdm_pincode_ranges';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        from_pincode int(11) NOT NULL,
        to_pincode int(11) NOT NULL,
        status tinyint(1) NOT NULL DEFAULT 1,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta($sql);

    if (get_option('fdm_delivery_message') === false) {
        update_option(
            'fdm_delivery_message',
            'Your product will be delivered within 48 hours.'
        );
    }

    if (get_option('fdm_enabled') === false) {
        update_option('fdm_enabled', 1);
    }

    fdm_insert_default_ranges();
}

register_activation_hook(__FILE__, 'fdm_activate');

function fdm_insert_default_ranges()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'fdm_pincode_ranges';

    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

    if ($count > 0) {
        return;
    }

    $ranges = [
        [600001, 600026],
        [600028, 600028],
        [600030, 600047],
        [600049, 600051],
        [600053, 600055],
        [600058, 600062],
        [600064, 600064],
        [600069, 600071],
        [600073, 600076],
        [600078, 600078],
        [600081, 600097],
        [600099, 600102],
        [600104, 600104],
        [600106, 600107],
        [600110, 600110],
        [600113, 600113],
        [600116, 600118],
        [600122, 600122],
        [600125, 600125],
        [600129, 600129],

        [560001, 560013],
        [560015, 560027],
        [560029, 560030],
        [560032, 560038],
        [560040, 560043],
        [560045, 560051],
        [560053, 560080],
        [560082, 560100],
        [560102, 560105],
        [560107, 560117],
        [560119, 560119],
        [560300, 560300],

        [500001, 500013],
        [500015, 500020],
        [500023, 500024],
        [500026, 500042],
        [500044, 500050],
        [500052, 500062],
        [500064, 500065],
        [500067, 500068],
        [500070, 500070],
        [500072, 500074],
        [500076, 500077],
        [500079, 500086],
        [500089, 500092],
        [500094, 500094],
        [500096, 500098],
        [500100, 500100],
        [500102, 500104],
        [500110, 500110],
        [500112, 500112],
        [500114, 500114],
        [500117, 500118],
        [502032, 502033],
        [502324, 502324],

        [400001, 400022],
        [400024, 400035],
        [400037, 400037],
        [400042, 400043],
        [400049, 400061],
        [400063, 400072],
        [400074, 400089],
        [400091, 400099],
        [400101, 400104],

        [110001, 110044],
        [110046, 110049],
        [110051, 110058],
        [110060, 110067],
        [110069, 110075],
        [110077, 110078],
        [110080, 110096],
        [110099, 110099],

        [700001, 700057],
        [700059, 700089],
        [700091, 700095],
        [700097, 700097],
        [700100, 700102],
        [700105, 700108],
        [700129, 700129],
        [700131, 700134],
        [700156, 700163],
    ];

    foreach ($ranges as $range) {
        $wpdb->insert(
            $table_name,
            [
                'from_pincode' => $range[0],
                'to_pincode' => $range[1],
                'status' => 1,
            ],
            [
                '%d',
                '%d',
                '%d',
            ]
        );
    }
}

function fdm_admin_menu()
{
    add_menu_page(
        'Fast Delivery',
        'Fast Delivery',
        'manage_options',
        'fast-delivery',
        'fdm_admin_page',
        'dashicons-car',
        56
    );
}

add_action('admin_menu', 'fdm_admin_menu');

function fdm_admin_page()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'fdm_pincode_ranges';

    if (
        isset($_POST['fdm_save_settings']) &&
        check_admin_referer('fdm_settings_action', 'fdm_settings_nonce')
    ) {
        $message = sanitize_text_field(
            wp_unslash($_POST['delivery_message'] ?? '')
        );

        $enabled = isset($_POST['fdm_enabled']) ? 1 : 0;

        update_option('fdm_delivery_message', $message);
        update_option('fdm_enabled', $enabled);

        echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully.</p></div>';
    }

    if (
        isset($_POST['fdm_add_range']) &&
        check_admin_referer('fdm_range_action', 'fdm_range_nonce')
    ) {
        $from_pincode = absint($_POST['from_pincode'] ?? 0);
        $to_pincode = absint($_POST['to_pincode'] ?? 0);

        if (
            strlen((string) $from_pincode) === 6 &&
            strlen((string) $to_pincode) === 6 &&
            $from_pincode <= $to_pincode
        ) {
            $wpdb->insert(
                $table_name,
                [
                    'from_pincode' => $from_pincode,
                    'to_pincode' => $to_pincode,
                    'status' => 1,
                ],
                [
                    '%d',
                    '%d',
                    '%d',
                ]
            );

            echo '<div class="notice notice-success is-dismissible"><p>Pincode range added successfully.</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Please enter valid 6 digit pincodes.</p></div>';
        }
    }

    if (
        isset($_GET['delete_range']) &&
        check_admin_referer(
            'fdm_delete_range_' . absint($_GET['delete_range'])
        )
    ) {
        $id = absint($_GET['delete_range']);

        $wpdb->delete(
            $table_name,
            ['id' => $id],
            ['%d']
        );

        echo '<div class="notice notice-success is-dismissible"><p>Pincode range deleted.</p></div>';
    }

    if (
        isset($_GET['toggle_range']) &&
        check_admin_referer(
            'fdm_toggle_range_' . absint($_GET['toggle_range'])
        )
    ) {
        $id = absint($_GET['toggle_range']);

        $current_status = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT status FROM $table_name WHERE id = %d",
                $id
            )
        );

        $new_status = $current_status ? 0 : 1;

        $wpdb->update(
            $table_name,
            ['status' => $new_status],
            ['id' => $id],
            ['%d'],
            ['%d']
        );

        echo '<div class="notice notice-success is-dismissible"><p>Status updated.</p></div>';
    }

    $ranges = $wpdb->get_results(
        "SELECT * FROM $table_name ORDER BY id DESC"
    );

    $delivery_message = get_option(
        'fdm_delivery_message',
        'Your product will be delivered within 48 hours.'
    );

    $enabled = get_option('fdm_enabled', 1);
    ?>

    <div class="wrap">

        <h1>Fast Delivery</h1>

        <h2>Settings</h2>

        <form method="post">

            <?php wp_nonce_field('fdm_settings_action', 'fdm_settings_nonce'); ?>

            <table class="form-table">

                <tr>
                    <th scope="row">
                        Enable Fast Delivery
                    </th>

                    <td>
                        <label>
                            <input
                                type="checkbox"
                                name="fdm_enabled"
                                value="1"
                                <?php checked($enabled, 1); ?>
                            >

                            Enable
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        Delivery Message
                    </th>

                    <td>
                        <input
                            type="text"
                            name="delivery_message"
                            value="<?php echo esc_attr($delivery_message); ?>"
                            class="regular-text"
                        >
                    </td>
                </tr>

            </table>

            <p>
                <button
                    type="submit"
                    name="fdm_save_settings"
                    class="button button-primary"
                >
                    Save Settings
                </button>
            </p>

        </form>

        <hr>

        <h2>Add Pincode Range</h2>

        <form method="post">

            <?php wp_nonce_field('fdm_range_action', 'fdm_range_nonce'); ?>

            <table class="form-table">

                <tr>
                    <th scope="row">
                        From Pincode
                    </th>

                    <td>
                        <input
                            type="number"
                            name="from_pincode"
                            placeholder="600001"
                            min="100000"
                            max="999999"
                            required
                        >
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        To Pincode
                    </th>

                    <td>
                        <input
                            type="number"
                            name="to_pincode"
                            placeholder="600026"
                            min="100000"
                            max="999999"
                            required
                        >
                    </td>
                </tr>

            </table>

            <p>
                <button
                    type="submit"
                    name="fdm_add_range"
                    class="button button-primary"
                >
                    Add Range
                </button>
            </p>

        </form>

        <hr>

        <h2>Pincode Ranges</h2>

        <table class="widefat striped">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>From Pincode</th>
                    <th>To Pincode</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

                <?php if ($ranges) : ?>

                    <?php foreach ($ranges as $range) : ?>

                        <tr>

                            <td>
                                <?php echo esc_html($range->id); ?>
                            </td>

                            <td>
                                <?php echo esc_html($range->from_pincode); ?>
                            </td>

                            <td>
                                <?php echo esc_html($range->to_pincode); ?>
                            </td>

                            <td>

                                <?php if ($range->status) : ?>

                                    <span style="color:green;font-weight:600;">
                                        Active
                                    </span>

                                <?php else : ?>

                                    <span style="color:red;font-weight:600;">
                                        Inactive
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php
                                $toggle_url = wp_nonce_url(
                                    admin_url(
                                        'admin.php?page=fast-delivery&toggle_range=' . $range->id
                                    ),
                                    'fdm_toggle_range_' . $range->id
                                );

                                $delete_url = wp_nonce_url(
                                    admin_url(
                                        'admin.php?page=fast-delivery&delete_range=' . $range->id
                                    ),
                                    'fdm_delete_range_' . $range->id
                                );
                                ?>

                                <a
                                    href="<?php echo esc_url($toggle_url); ?>"
                                    class="button"
                                >
                                    <?php echo $range->status ? 'Disable' : 'Enable'; ?>
                                </a>

                                <a
                                    href="<?php echo esc_url($delete_url); ?>"
                                    class="button"
                                    onclick="return confirm('Are you sure you want to delete this range?');"
                                >
                                    Delete
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else : ?>

                    <tr>
                        <td colspan="5">
                            No pincode ranges found.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

    <?php
}

function fdm_check_delivery_pincode($pincode)
{
    global $wpdb;

    $pincode = absint(
        preg_replace('/\D/', '', $pincode)
    );

    if (!$pincode) {
        return false;
    }

    $table_name = $wpdb->prefix . 'fdm_pincode_ranges';

    $result = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id
             FROM $table_name
             WHERE status = 1
             AND %d BETWEEN from_pincode AND to_pincode
             LIMIT 1",
            $pincode
        )
    );

    return !empty($result);
}

function fdm_get_customer_pincode()
{
    $pincode = '';

    if (
        function_exists('WC') &&
        WC()->customer
    ) {
        $pincode = WC()->customer->get_shipping_postcode();

        if (!$pincode) {
            $pincode = WC()->customer->get_billing_postcode();
        }
    }

    if (
        is_checkout() &&
        !empty($_POST['post_data'])
    ) {
        parse_str(
            wp_unslash($_POST['post_data']),
            $post_data
        );

        $pincode = $post_data['shipping_postcode']
            ?? $post_data['billing_postcode']
            ?? $pincode;
    }

    return $pincode;
}

function fdm_show_delivery_message()
{
    if (!get_option('fdm_enabled', 1)) {
        return;
    }

    if (
        !function_exists('WC') ||
        !WC()->customer
    ) {
        return;
    }

    $pincode = fdm_get_customer_pincode();

    if (
        !$pincode ||
        !fdm_check_delivery_pincode($pincode)
    ) {
        return;
    }

    $message = get_option(
        'fdm_delivery_message',
        'Your product will be delivered within 48 hours.'
    );

    echo '<div class="fdm-delivery-message">'
        . esc_html($message)
        . '</div>';
}

add_action(
    'woocommerce_single_product_summary',
    'fdm_show_delivery_message',
    25
);

add_action(
    'woocommerce_cart_totals_before_order_total',
    'fdm_show_delivery_message'
);

add_action(
    'woocommerce_review_order_before_order_total',
    'fdm_show_delivery_message'
);

function fdm_frontend_style()
{
    if (
        is_product() ||
        is_cart() ||
        is_checkout()
    ) {
        ?>
        <style>
            .fdm-delivery-message {
                color: #218838;
                font-size: 15px;
                font-weight: 500;
                margin: 10px 0;
            }
        </style>
        <?php
    }
}

add_action(
    'wp_head',
    'fdm_frontend_style'
);