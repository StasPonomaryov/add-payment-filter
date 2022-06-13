<?php

/**
 * Plugin Name: Add payment method filter to analytics
 * Description: Filter orders by payment method in Analytics -> Orders
 * Author: Stas Ponomaryov
 * @package WooCommerce\Admin
 */

/**
 * Register payment methods
 */
function add_payment_settings()
{
  // Theese only two payment methods we need
  $payment_ids = array('wayforpay' => 'Безготівковий', 'cod' => 'Готівка');
  // Get all payment methods
  $gateways = WC()->payment_gateways->payment_gateways();
  // Prepare default label for dropdown
  $payment_methods = array();
  array_push($payment_methods, array('label' => 'Всі', 'value' => 'All'));
  // Add proper labels to dropdown
  foreach ($gateways as $id => $gateway) {
    if (array_key_exists(esc_attr($id), $payment_ids)) array_push($payment_methods, array('label' => $payment_ids[$id], 'value' => esc_attr($id)));
  };
  $data_registry = Automattic\WooCommerce\Blocks\Package::container()->get(
    Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry::class
  );
  $data_registry->add('multiPaymentMethods', $payment_methods);
}

/**
 * Register the JS.
 */
function add_report_register_script()
{

  if (!class_exists('Automattic\WooCommerce\Admin\PageController')) {
    return;
  }

  add_payment_settings();

  $asset_file = require __DIR__ . '/build/index.asset.php';
  wp_register_script(
    'sql-modification',
    plugins_url('/build/index.js', __FILE__),
    $asset_file['dependencies'],
    $asset_file['version'],
    true
  );

  wp_enqueue_script('sql-modification');
}
add_action('admin_enqueue_scripts', 'add_report_register_script');

/**
 * Add the query argument `_shop_order_payment_method` for caching purposes. Otherwise, a
 * change of the currency will return the previous request's data.
 *
 * @param array $args query arguments.
 * @return array augmented query arguments.
 */
function apply_payment_args($args)
{
  if (isset($_GET['_shop_order_payment_method']) && !empty($_GET['_shop_order_payment_method'])) {
    $payment_method = wc_clean($_GET['_shop_order_payment_method']);
    $args['_shop_order_payment_method'] = $payment_method;
  }

  return $args;
}
add_filter('woocommerce_analytics_orders_query_args', 'apply_payment_args');
add_filter('woocommerce_analytics_orders_stats_query_args', 'apply_payment_args');

/**
 * Add a JOIN clause.
 *
 * @param array $clauses an array of JOIN query strings.
 * @return array augmented clauses.
 */
function add_join_subquery($clauses)
{
  global $wpdb;
  if (isset($_GET['_shop_order_payment_method'])) {
    $clauses[] = "JOIN {$wpdb->postmeta} payment_postmeta ON {$wpdb->prefix}wc_order_stats.order_id = payment_postmeta.post_id";
  }

  return $clauses;
}
add_filter('woocommerce_analytics_clauses_join_orders_subquery', 'add_join_subquery');
add_filter('woocommerce_analytics_clauses_join_orders_stats_total', 'add_join_subquery');
add_filter('woocommerce_analytics_clauses_join_orders_stats_interval', 'add_join_subquery');

/**
 * Add a WHERE clause.
 *
 * @param array $clauses an array of WHERE query strings.
 * @return array augmented clauses.
 */
function add_where_subquery($clauses)
{
  if (isset($_GET['_shop_order_payment_method'])) {
    $payment_method = sanitize_text_field(wp_unslash($_GET['_shop_order_payment_method']));
    $clauses[] = "AND payment_postmeta.meta_key = '_payment_method' AND payment_postmeta.meta_value = '{$payment_method}'";
  }

  return $clauses;
}
add_filter('woocommerce_analytics_clauses_where_orders_subquery', 'add_where_subquery');
add_filter('woocommerce_analytics_clauses_where_orders_stats_total', 'add_where_subquery');
add_filter('woocommerce_analytics_clauses_where_orders_stats_interval', 'add_where_subquery');

/**
 * Add a SELECT clause.
 *
 * @param array $clauses an array of WHERE query strings.
 * @return array augmented clauses.
 */
function add_select_subquery($clauses)
{
  if (isset($_GET['_shop_order_payment_method'])) {
    $clauses[] = ', payment_postmeta.meta_value AS payment';
  }
  return $clauses;
}

add_filter('woocommerce_analytics_clauses_select_orders_subquery', 'add_select_subquery');
add_filter('woocommerce_analytics_clauses_select_orders_stats_total', 'add_select_subquery');
add_filter('woocommerce_analytics_clauses_select_orders_stats_interval', 'add_select_subquery');
