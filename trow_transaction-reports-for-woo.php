<?php
/**
* Plugin Name: Transaction reports for WooCommerce
* Description: Plugin generates charts and reports of product sales, customer, stocks, etc. 
* Version: 2.0.0
* Author: walia1
* Author URI: https://sitefreelancing.com/
* Plugin URI: https://sitefreelancing.com/product/transaction-reports-for-woo/
* License: GNU General Public License version 2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.en.html
**/


define('TROW_GET', true);
define('TROW_VERSION', '2.0.0');

define( 'TROW_STORE_URL', 'https://sitefreelancing.com/shop/' );
define( 'TROW_ITEM_NAME', 'Transaction reports for WooCommerce' );


require(dirname(__FILE__).'/trow_transaction_reports_extend.php');

// Add the Product Sales Report to the WordPress admin
add_action('admin_menu', 'hm_psr_admin_menu');
function hm_psr_admin_menu() {
	add_submenu_page('woocommerce', 'Product Transactions', 'Product Transactions', 'view_woocommerce_reports', 'hm_sbp', 'trow_sbp_page');
	
	add_option('hm_psr_license_key', 'x');
	update_option('hm_psr_license_key', 'x');
	add_option('hm_psr_license_status', 'valid');
	update_option('hm_psr_license_status', 'valid');
	add_option('hm_psr_first_activate', 1590438019);
	update_option('hm_psr_first_activate', 1590438019);
}

function trow_psr_default_report_settings() {
	return array(
		'report_time' => '30d',
		'report_start' => date('Y-m-d', current_time('timestamp') - (86400 * 31)),
		'report_start_dynamic' => '',
		'report_start_time' => '12:00:00 AM',
		'report_end' => date('Y-m-d', current_time('timestamp') - 86400),
		'report_end_dynamic' => '',
		'report_end_time' => '12:00:00 AM',
		'order_statuses' => array('wc-processing', 'wc-on-hold', 'wc-completed'),
		'products' => 'all',
		'product_cats' => array(),
		'product_ids' => '',
		'variations' => 1,
		'groupby' => '',
		'orderby' => 'quantity',
		'orderdir' => 'desc',
		'fields' => array('builtin::product_id', 'builtin::product_sku', 'builtin::variation_sku', 'builtin::product_name', 'builtin::quantity_sold', 'builtin::gross_sales'),
		'total_fields' => array('builtin::quantity_sold', 'builtin::gross_sales', 'builtin::gross_after_discount', 'builtin::taxes', 'builtin::total_with_tax'),
		'field_names' => array(),
		'limit_on' => 0,
		'limit' => 10,
		'include_nil' => 0,
		'include_unpublished' => 1,
		'include_shipping' => 0,
		'include_header' => 1,
		'include_totals' => 0,
		'format_amounts' => 1,
		'exclude_free' => 0,
		'format' => 'CSV',
		'order_meta_filter_on' => 0,
		'order_meta_filter_key' => '',
		'order_meta_filter_value' => '',
		'order_meta_filter_value_2' => '',
		'order_meta_filter_op' => '=',
		'customer_meta_filter_on' => 0,
		'customer_meta_filter_key' => '',
		'customer_meta_filter_value' => '',
		'customer_meta_filter_value_2' => '',
		'customer_meta_filter_op' => '=',
		'product_tag_filter_on' => 0,
		'product_tag_filter' => '',
		'product_meta_filter_on' => 0,
		'product_meta_filter_key' => '',
		'product_meta_filter_value' => '',
		'product_meta_filter_value_2' => '',
		'product_meta_filter_op' => '=',
		'customer_role' => 0,
		'refunds' => 1,
		'report_title_on' => 0,
		'report_title' => '[preset] - [start] to [end]',
		'filename' => 'Product Sales - [created]',
		'report_unfiltered' => 0,
		'hm_psr_debug' => 0,
		'time_limit' => 300,
		'object_caching_disable' => 0,
		'report_css' =>
'body {
	font-family: sans-serif;
}
h1 {
	font-size: 24px;
}
th, td {
	text-align: left;
	padding: 5px 10px;
}',
	'format_csv_delimiter' => ',',
	'format_csv_surround' => '"',
	'format_csv_escape' => '\\',
	'db_sort_buffer_size' => 512
	);
}

// This function generates the Transaction reports for WooCommerce page HTML
function trow_sbp_page() {
	global $hm_psr_email_result, $wp_roles;

	$savedReportSettings = get_option('hm_psr_report_settings');
	if (empty($savedReportSettings)) {
		$savedReportSettings = array(
			trow_psr_default_report_settings()
		);
	}
	
	
	if (isset($_REQUEST['hm_psr_action'])) {
		if ($_REQUEST['hm_psr_action'] == 'preset-save' && !empty($_GET['preset']) && isset($savedReportSettings[$_GET['preset']])) {
		
			$_POST = stripslashes_deep($_POST);
			
			// Map new (1.6.8) product category checklist onto old field name
			if (isset($_POST['tax_input']['product_cat'])) {
				$_POST['product_cats'] = $_POST['tax_input']['product_cat'];
				unset($_POST['tax_input']);
			}
			
			// Also update checkbox fields in trow_sbp_on_init
			foreach (array(
				'limit_on', 'include_nil', 'include_shipping', 'include_unpublished', 'include_header', 'include_totals',
				'format_amounts', 'exclude_free', 'order_meta_filter_on', 'customer_meta_filter_on', 'product_tag_filter_on', 'product_meta_filter_on', 'refunds', 'report_title_on', 'report_unfiltered', 'hm_psr_debug', 'object_caching_disable'
				) as $checkboxField) {
				
				if (!isset($_POST[$checkboxField])) {
					$_POST[$checkboxField] = 0;
				}
			}
			
			// Do not allow users without the edit_theme_options capability to change report CSS
			if (!current_user_can('edit_theme_options')) {
				if (isset($savedReportSettings[$_GET['preset']]['report_css'])) {
					$_POST['report_css'] = $savedReportSettings[$_GET['preset']]['report_css'];
				} else {
					unset($_POST['report_css']);
				}
			}
			
			if (isset($savedReportSettings[$_GET['preset']]['key'])) {
				$_POST['key'] = $savedReportSettings[$_GET['preset']]['key'];
			}
			
			$savedReportSettings[$_GET['preset']] = $_POST;
			update_option('hm_psr_report_settings', $savedReportSettings);
		} else if ($_REQUEST['hm_psr_action'] == 'preset-del' && !empty($_GET['preset']) && isset($savedReportSettings[$_GET['preset']])) {
			unset($savedReportSettings[$_GET['preset']]);
			update_option('hm_psr_report_settings', $savedReportSettings);
			unset($_GET['preset']);
			echo('<script type="text/javascript">location.href = \'?page=hm_sbp\';</script>');
			return;
		} else if ($_REQUEST['hm_psr_action'] == 'preset-create' && !empty($_POST['preset_name'])) {
			$savedReportSettings[] = $_POST;
			update_option('hm_psr_report_settings', $savedReportSettings);
			echo('<script type="text/javascript">location.href = \'?page=hm_sbp&preset='.(count($savedReportSettings) - 1).'\';</script>');
			return;
		}
	}
	
	
	$reportSettings = array_merge(trow_psr_default_report_settings(),
								$savedReportSettings[
									isset($_GET['preset']) && isset($savedReportSettings[$_GET['preset']]) ? $_GET['preset'] : 0
								]
						);
	
	// For backwards compatibility with pre-1.5 versions
	if (!empty($reportSettings['cat'])) {
		$reportSettings['products'] = 'cats';
		$reportSettings['product_cats'] = array($reportSettings['cat']);
	}
	
	$fieldOptions = trow_psr_get_default_fields();
	
	
	include(dirname(__FILE__).'/admin/admin.php');


}

function trow_psr_get_default_fields() {
	global $hm_psr_default_fields;
	
	if (!isset($hm_psr_default_fields)) {
		$hm_psr_default_fields = array(
			'builtin::product_id' => 'Product ID',
			'builtin::variation_id' => 'Variation ID',
			'builtin::product_sku' => 'Product SKU',
			'builtin::variation_sku' => 'Variation SKU',
			'builtin::product_name' => 'Product Name',
			'builtin::product_categories' => 'Product Categories',
			'builtin::product_price' => 'Current Product Price',
			'builtin::product_price_with_tax' => 'Current Product Price (Incl. Tax)',
			'builtin::product_stock' => 'Current Stock Quantity',
			'builtin::variation_attributes' => 'Variation Attributes',
			'builtin::quantity_sold' => 'Quantity Sold',
			'builtin::gross_sales' => 'Gross Sales',
			'builtin::gross_after_discount' => 'Gross Sales (After Discounts)',
			'builtin::discount' => 'Total Discount Amount',
			'builtin::taxes' => 'Taxes',
			'builtin::total_with_tax' => 'Total Sales Including Tax',
			'builtin::refund_quantity' => 'Quantity Refunded',
			'builtin::refund_gross' => 'Gross Amount Refunded (Excl. Tax)',
			'builtin::refund_with_tax' => 'Gross Amount Refunded (Incl. Tax)',
			'builtin::refund_taxes' => 'Tax Refunded',
			'builtin::publish_time' => 'Product Publish Date/Time'
		);
	}
	
	return $hm_psr_default_fields;
}

// Hook into WordPress init; this function performs report generation when
// the admin form is submitted
add_action('init', 'trow_sbp_on_init', 9999);
function trow_sbp_on_init() {
	echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>';
	echo '<script type="text/javascript" src="'.plugins_url('js/javas.js?v='.TROW_VERSION, __FILE__).'"></script>';
	global $pagenow, $hm_psr_email_result;
	
	// Check if we are in admin and on the report page
	if (!is_admin())
		return;
	if ($pagenow == 'admin.php' && isset($_GET['page']) && $_GET['page'] == 'hm_sbp'
			&& current_user_can('view_woocommerce_reports')
			&& !empty($_REQUEST['hm_psr_action']) && ($_REQUEST['hm_psr_action'] == 'run' || $_REQUEST['hm_psr_action'] == 'email')) {
		
		if ( empty($_REQUEST['hm-psr-nonce']) || !wp_verify_nonce($_REQUEST['hm-psr-nonce'], 'hm-psr-run') ) {
			wp_die('The current request is invalid. Please go back and try again.');
		}
		
		$savedReportSettings = get_option('hm_psr_report_settings', array());
		
		if (empty($_POST) && isset($_GET['preset']) && isset($savedReportSettings[$_GET['preset']])) {
			$_POST = $savedReportSettings[$_GET['preset']];
		} else {
			// Run report from $_POST
			$_POST = stripslashes_deep($_POST);
			
			// Do not allow users without the edit_theme_options capability to change report CSS
			if (!current_user_can('edit_theme_options')) {
				if (isset($_GET['preset'])) {
					if (isset($savedReportSettings[$_GET['preset']]['report_css'])) {
						$_POST['report_css'] = $savedReportSettings[$_GET['preset']]['report_css'];
					} else {
						unset($_POST['report_css']);
					}
				} else if (isset($savedReportSettings[0]['report_css'])) {
					$_POST['report_css'] = $savedReportSettings[0]['report_css'];
				} else {
					unset($_POST['report_css']);
				}
			}
		}
		
		if (!empty($_POST['hm_psr_debug'])) {
			error_reporting(E_ALL);
			ini_set('display_errors', 1);
		}
		
		// Map new (1.6.8) product category checklist onto old field name
		if (isset($_POST['tax_input']['product_cat'])) {
			$_POST['product_cats'] = $_POST['tax_input']['product_cat'];
			unset($_POST['tax_input']);
		}
		
		$newSettings = array_intersect_key($_POST, trow_psr_default_report_settings());
		
		// Also update checkbox fields in preset-save
		foreach (array(
			'limit_on', 'include_nil', 'include_shipping', 'include_unpublished', 'include_header', 'include_totals',
			'format_amounts', 'exclude_free', 'order_meta_filter_on', 'customer_meta_filter_on', 'product_tag_filter_on', 'product_meta_filter_on', 'refunds', 'report_title_on', 'report_unfiltered', 'hm_psr_debug', 'object_caching_disable'
			) as $checkboxField) {
			
			if (!isset($newSettings[$checkboxField])) {
				$newSettings[$checkboxField] = 0;
			}
		}
		
		/*foreach ($newSettings as $key => $value)
			if (!is_array($value))
				$newSettings[$key] = $value;*/
		
		// Update the saved report settings
		$savedReportSettings[0] = $newSettings;

		/*if (TROW_GET) {
			HM_Product_Sales_Report_Pro::savePreset($savedReportSettings);
		}*/

		update_option('hm_psr_report_settings', $savedReportSettings);
		
		// Check if no fields are selected
		if (empty($_POST['fields']))
			return;

		list($start_date, $end_date) = trow_psr_get_report_dates();
		
		$titleVars = array(
			'now' => time(),
			'preset' => (empty($_POST['preset_name']) ? 'Product Sales' : $_POST['preset_name'])
		);
		
		if ($_POST['report_time'] != 'all') {
			$titleVars['start'] = $start_date;
			$titleVars['end'] = $end_date;
		}
		
		// Assemble the filename for the report download
		$filename = (empty($_POST['filename']) ? 'Product Sales' : trow_psr_dynamic_title(str_replace(array('/', '\\'), '_', $_POST['filename']), $titleVars)).'.'.($_POST['format'] == 'html-enhanced' ? 'html' : (in_array($_POST['format'], array('xlsx', 'xls', 'html')) ? $_POST['format'] : 'csv'));
		
		if ($_REQUEST['hm_psr_action'] == 'email') {
			if (empty($_POST['email_to'])) return;
			update_option('hm_psr_last_email_to', $_POST['email_to']);
			$filepath = get_temp_dir() . DIRECTORY_SEPARATOR . $filename;
		} else {
			// Send headers
			if ($_POST['format'] == 'xlsx')
				header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			else if ($_POST['format'] == 'xls')
				header('Content-Type: application/vnd.ms-excel');
			else if ($_POST['format'] == 'csv')
				header('Content-Type: text/csv');
			if ($_POST['format'] != 'html' && $_POST['format'] != 'html-enhanced')
				header('Content-Disposition: attachment; filename="'.$filename.'"');
			$filepath = 'php://output';
		}

		if ($_POST['format'] == 'xlsx' || $_POST['format'] == 'xls') {
			include_once(dirname(__FILE__).'/HM_XLS_Export.php');
			$dest = new HM_XLS_Export();
		} else if ($_POST['format'] == 'html') {
			include_once(dirname(__FILE__).'/HM_HTML_Export.php');
			$out = fopen($filepath, 'w');
			$dest = new HM_HTML_Export($out, $_POST['report_css']);
		} else if ($_POST['format'] == 'html-enhanced') {
			include_once(dirname(__FILE__).'/HM_HTML_Enhanced_Export.php');
			$out = fopen($filepath, 'w');
			$dest = new HM_HTML_Enhanced_Export($out, $_POST['report_css']);
		} else {
			include_once(dirname(__FILE__).'/HM_CSV_Export.php');
			$out = fopen($filepath, 'w');
			$dest = new HM_CSV_Export($out, array(
				'delimiter' => $_POST['format_csv_delimiter'],
				'surround' => $_POST['format_csv_surround'],
				'escape' => $_POST['format_csv_escape'],
			));
		}
		
		if (!empty($_POST['report_title_on'])) {
			$dest->putTitle(trow_psr_dynamic_title($_POST['report_title'], $titleVars));
		}
		
		if (!empty($_POST['include_header']))
			trow_sbp_export_header($dest);
		trow_sbp_export_body($dest, $start_date, $end_date);
		
		if ($_POST['format'] == 'xlsx')
			$dest->outputXLSX($filepath);
		else if ($_POST['format'] == 'xls')
			$dest->outputXLS($filepath);
		else {
			// Call destructor, if any
			$dest = null;
			
			fclose($out);
		}
		

		if ($_REQUEST['hm_psr_action'] == 'email') {
			$message = 'A Transaction reports for WooCommerce for '.get_bloginfo('name').' is attached.

Product Category: '.(!empty($_POST['cat']) && is_numeric($_POST['cat']) ? $cat->name : 'All Categories').'
Start Date: '.date('F j, Y', $start_date).'
End Date: '.date('F j, Y', $end_date);

			$hm_psr_email_result = wp_mail($_POST['email_to'], get_bloginfo('name').' Transaction reports for WooCommerce', $message, '', $filepath);
				
			unlink($filepath);
		} else {
			exit;
		}
	}
}

function trow_psr_get_report_dates() {
	// Calculate report start and end dates (timestamps)
	switch ($_POST['report_time']) {
		case '0d':
			$end_date = strtotime('midnight', current_time('timestamp'));
			$start_date = $end_date;
			break;
		case '1d':
			$end_date = strtotime('midnight', current_time('timestamp')) - 86400;
			$start_date = $end_date;
			break;
		case '7d':
			$end_date = strtotime('midnight', current_time('timestamp')) - 86400;
			$start_date = $end_date - (86400 * 6);
			break;
		case '1cm':
			$start_date = strtotime(date('Y-m', current_time('timestamp')).'-01 midnight -1month');
			$end_date = strtotime('+1month', $start_date) - 86400;
			break;
		case '0cm':
			$start_date = strtotime(date('Y-m', current_time('timestamp')).'-01 midnight');
			$end_date = strtotime('+1month', $start_date) - 86400;
			break;
		case '+1cm':
			$start_date = strtotime(date('Y-m', current_time('timestamp')).'-01 midnight +1month');
			$end_date = strtotime('+1month', $start_date) - 86400;
			break;
		case '+7d':
			$start_date = strtotime('midnight', current_time('timestamp')) + 86400;
			$end_date = $start_date + (86400 * 6);
			break;
		case '+30d':
			$start_date = strtotime('midnight', current_time('timestamp')) + 86400;
			$end_date = $start_date + (86400 * 29);
			break;
		case 'custom':
			if (!empty($_POST['report_start_dynamic'])) {
				$_POST['report_start'] = date('Y-m-d', strtotime($_POST['report_start_dynamic'], current_time('timestamp')));
			}
			if (!empty($_POST['report_end_dynamic'])) {
				$_POST['report_end'] = date('Y-m-d', strtotime($_POST['report_end_dynamic'], current_time('timestamp')));
			}
			$end_date = strtotime($_POST['report_end_time'], strtotime($_POST['report_end']));
			$start_date = strtotime($_POST['report_start_time'], strtotime($_POST['report_start']));
			break;
		default: // 30 days is the default
			$end_date = strtotime('midnight', current_time('timestamp')) - 86400;
			$start_date = $end_date - (86400 * 29);
	}
	return array($start_date, $end_date);
}

// This function outputs the report header row
function trow_sbp_export_header($dest) {
	$header = array();
	
	foreach ($_POST['fields'] as $field) {
		$header[] = $_POST['field_names'][$field];
	}
	
	$dest->putRow($header, true);
}

// This function generates and outputs the report body rows
function trow_sbp_export_body($dest, $start_date, $end_date) {
	global $woocommerce, $wpdb;
	
	// Set time limit
	if (is_numeric($_POST['time_limit'])) {
		set_time_limit($_POST['time_limit']);
	}
	
	// Check order statuses
	if (empty($_POST['order_statuses']))
		return;
	$_POST['order_statuses'] = array_intersect($_POST['order_statuses'], array_keys(wc_get_order_statuses()));
	if (empty($_POST['order_statuses']))
		return;
	
	// Disable cache?
	if (!empty($_POST['object_caching_disable'])) {
		wp_suspend_cache_addition(true);
	}
	
	// Validate input
	/*$groupbyFields = trow_psr_get_groupby_fields();
	if (!empty($_POST['groupby']) && !isset($groupbyFields[$_POST['groupby']])) {
		unset($_POST['groupby']);
	}*/
	
	if ($_POST['products'] == 'ids') {
		$product_ids = array();
		foreach (explode(',', $_POST['product_ids']) as $productId) {
			$productId = trim($productId);
			if (is_numeric($productId))
				$product_ids[] = $productId;
		}
	}
	$productsFiltered = ($_POST['products'] == 'cats' || !empty($_POST['product_tag_filter_on']) || !empty($_POST['product_meta_filter_on']) || empty($_POST['include_unpublished']));
	if ($productsFiltered || !empty($_POST['include_nil'])) {
		$params = array(
			'post_type' => 'product',
			'nopaging' => true,
			'fields' => 'ids',
			'ignore_sticky_posts' => true,
			'tax_query' => array()
		);
		
		if (isset($product_ids)) {
			$params['post__in'] = $product_ids;
		}
		if ($_POST['products'] == 'cats') {
			$cats = array();
			foreach ($_POST['product_cats'] as $cat)
				if (is_numeric($cat))
					$cats[] = $cat;
			$params['tax_query'][] = array(
				'taxonomy' => 'product_cat',
				'terms' => $cats
			);
		}
		if (!empty($_POST['product_tag_filter_on'])) {
			$tags = array();
			foreach (explode(',', $_POST['product_tag_filter']) as $tag) {
				$tag = trim($tag);
				if (!empty($tag))
					$tags[] = $tag;
			}
			$params['tax_query'][] = array(
				'taxonomy' => 'product_tag',
				'field' => 'name',
				'terms' => $tags
			);
		}
		
		if (count($params['tax_query']) > 1) {
			$params['tax_query']['relation'] = 'AND';
		}
		
		// Product meta field filtering
		if (!empty($_POST['product_meta_filter_on'])) {
			if (in_array($_POST['product_meta_filter_op'], array('=','!=','<','<=','>','>=','BETWEEN'))) {
				$params['meta_query'] = array(array(
					'key' => $_POST['product_meta_filter_key'],
					'compare' => $_POST['product_meta_filter_op'],
					'value' => ($_POST['product_meta_filter_op'] == 'BETWEEN' ? array($_POST['product_meta_filter_value'], $_POST['product_meta_filter_value_2']) : $_POST['product_meta_filter_value'])
				));
				if (is_numeric($_POST['product_meta_filter_value']) &&
						($_POST['product_meta_filter_op'] != 'BETWEEN' || is_numeric($_POST['product_meta_filter_value_2']))) {
					$params['meta_query'][0]['type'] = 'NUMERIC';
				}
			}
		}
		
		if (!empty($_POST['include_unpublished'])) {
			$params['post_status'] = 'any';
		}
		
		/*if ($_POST['orderby'] == 'product_id') {
			$params['orderby'] = 'ID';
			$params['order'] = ($_POST['orderdir'] == 'desc' ? 'DESC' : 'ASC');
		}*/
		
		$product_ids = get_posts($params);
	}
	if (!isset($product_ids)) {
		$product_ids = null;
	} else if ($_POST['products'] == 'ids') {
		$productsFiltered = true;
	}
	
	/*
	$product_ids = array();
	if ($_POST['products'] == 'cats') {
		if (empty($_POST['product_cats'])) {
			$product_ids = array();
		} else {
			$cats = array();
			foreach ($_POST['product_cats'] as $cat)
				if (is_numeric($cat))
					$cats[] = $cat;
			$product_ids = get_objects_in_term($cats, 'product_cat');
		}
	} else if ($_POST['products'] == 'ids') {
		foreach (explode(',', $_POST['product_ids']) as $productId) {
			$productId = trim($productId);
			if (is_numeric($productId))
				$product_ids[] = $productId;
		}
	} else if (!empty($_POST['include_nil']) || !empty($_POST['product_tag_filter_on']) || !empty($_POST['product_meta_filter_on'])) { // All products
		$args = array('nopaging' => true, 'posts_per_page' => -1, 'post_type' => 'product', 'fields' => 'ids', 'post_status' => (empty($_POST['include_unpublished']) ? 'publish' : 'any'));
		if ($_POST['orderby'] == 'product_id') {
			$args['orderby'] = 'ID';
			$args['order'] = ($_POST['orderdir'] == 'desc' ? 'DESC' : 'ASC');
		}
		
		// Product tag filtering
		if (!empty($_POST['product_tag_filter_on'])) {
			$tags = array();
			foreach (explode(',', $_POST['product_tag_filter']) as $tag) {
				$tag = trim($tag);
				if (!empty($tag))
					$tags[] = $tag;
			}
			if (!empty($tags)) {
				$args['tax_query'] = array(
					array(
						'taxonomy' => 'product_tag',
						'field' => 'name',
						'terms' => $tags
					)
				);
			}
		}
		
		// Product meta field filtering
		if (!empty($_POST['product_meta_filter_on'])) {
			$customFilterFields = HM_Product_Sales_Report_Pro::getCustomFieldNames(false, true);
			if (in_array($_POST['product_meta_filter_key'], $customFilterFields)
					&& in_array($_POST['product_meta_filter_op'], array('=','!=','<','<=','>','>=','BETWEEN'))) {
				$args['meta_key'] = $_POST['product_meta_filter_key'];
				$args['meta_compare'] = $_POST['product_meta_filter_op'];
				$args['meta_value'] = ($_POST['product_meta_filter_op'] == 'BETWEEN' ? array($_POST['product_meta_filter_value'], $_POST['product_meta_filter_value_2']) : $_POST['product_meta_filter_value']);
			}
		}
		
		$product_ids = get_posts($args);
	}
	*/
	
	/*if (!empty($_POST['product_meta_filter_on'])) {
		$customFilterFields = HM_Product_Sales_Report_Pro::getCustomFieldNames(false, true);
		if (in_array($_POST['product_meta_filter_key'], $customFilterFields)) {
			foreach ($product_ids as $i => $product_id) {
				$metaValue = get_post_meta($product_id, $_POST['product_meta_filter_key']);
				if (count($metaValue)) {
					$metaValue = $metaValue[0];
					switch ($_POST['product_meta_filter_op']) {
						case '=':
							if ($metaValue != $_POST['product_meta_filter_value'])
								unset($product_ids[$i]);
							break;
						case '!=':
							if ($metaValue == $_POST['product_meta_filter_value'])
								unset($product_ids[$i]);
							break;
						case '<':
							if ($metaValue >= $_POST['product_meta_filter_value'])
								unset($product_ids[$i]);
							break;
						case '<=':
							if ($metaValue > $_POST['product_meta_filter_value'])
								unset($product_ids[$i]);
							break;
						case '>':
							if ($metaValue <= $_POST['product_meta_filter_value'])
								unset($product_ids[$i]);
							break;
						case '>=':
							if ($metaValue < $_POST['product_meta_filter_value'])
								unset($product_ids[$i]);
							break;
						case 'BETWEEN':
							if ($metaValue < $_POST['product_meta_filter_value'] || $metaValue > $_POST['product_meta_filter_value_2'])
								unset($product_ids[$i]);
							break;
						default:
							unset($product_ids[$i]);
						
					}
				} else {
					unset($product_ids[$i]);
				}
			}
		}
	}*/
	
	// Assemble order by string
	//$orderby = (in_array($_POST['orderby'], array('product_id', 'gross', 'gross_after_discount')) ? $_POST['orderby'] : 'quantity');
	//$orderby .= ' '.($_POST['orderdir'] == 'asc' ? 'ASC' : 'DESC');
	
	// Remove existing filters if the unfiltered option is on
	if (!empty($_POST['report_unfiltered'])) {
		remove_all_filters('woocommerce_reports_get_order_report_data_args');
		remove_all_filters('woocommerce_reports_order_statuses');
		remove_all_filters('woocommerce_reports_get_order_report_query');
		remove_all_filters('woocommerce_reports_get_order_report_data');
	}
	
	
	// Avoid max join size error
	$wpdb->query('SET SQL_BIG_SELECTS=1');
	
	// Filter order statuses
	add_filter('woocommerce_reports_order_statuses', 'trow_psr_report_order_statuses', 9999);
	
	// Filter report query
	add_filter('woocommerce_reports_get_order_report_query', 'trow_psr_filter_report_query');
	
	// Create a new WC_Admin_Report object
	include_once($woocommerce->plugin_path().'/includes/admin/reports/class-wc-admin-report.php');
	$wc_report = new WC_Admin_Report();
	$wc_report->start_date = $start_date;
	$wc_report->end_date = $end_date;
	
	// Override WC tax location, if necessary for a report field
	if (in_array('product_price_with_tax', $_POST['fields'])) {
		add_filter('woocommerce_get_tax_location', 'trow_psr_override_tax_location');
	}
	
	// Initialize totals array
	if (empty($_POST['include_totals']) || empty($_POST['total_fields'])) {
		$totals = array();
	} else {
		$totals = array_combine($_POST['total_fields'], array_fill(0, count($_POST['total_fields']), 0));
	}
	
	$rows = array();
	$orderIndex = array_search($_POST['orderby'], $_POST['fields']);
	
	if ($product_ids === null || !empty($product_ids)) { // Do not run the report if product_ids is empty and not null
		
		// Get report data
		$sold_products = HM_Product_Sales_Report_Pro::getReportData($wc_report, ($productsFiltered ? $product_ids : null), $start_date, $end_date);
		
		// Handle refunds
		$hasRefundFields = count(array_intersect(array('refund_quantity', 'refund_gross', 'refund_with_tax', 'refund_taxes'), $_POST['fields'])) > 0;
		if (!empty($_POST['refunds']) || $hasRefundFields) {
			$refunded_products = HM_Product_Sales_Report_Pro::getReportData($wc_report, ($productsFiltered ? $product_ids : null), $start_date, $end_date, true);
			$sold_products = trow_psr_process_refunds($sold_products, $refunded_products);
		}
		
		
		
		foreach ($sold_products as $product) {
			$row = trow_sbp_get_product_row($product, $_POST['fields'], $totals);
			if (isset($rows[$row[$orderIndex]])) {
				$rows[$row[$orderIndex]][] = $row;
			} else {
				$rows[$row[$orderIndex]] = array($row);
			}
		}
		
		if (!empty($_POST['include_nil'])) {
			foreach (trow_sbp_get_nil_products($product_ids, $sold_products, $dest, $totals) as $row) {
				if (isset($rows[$row[$orderIndex]])) {
					$rows[$row[$orderIndex]][] = $row;
				} else {
					$rows[$row[$orderIndex]] = array($row);
				}
			}
		}
	}
	
	if (!empty($_POST['include_shipping'])) {
		$hasTaxFields = (count(array_intersect(array('builtin::taxes', 'builtin::total_with_tax', 'builtin::refund_with_tax', 'builtin::refund_taxes', 'taxes', 'total_with_tax', 'refund_with_tax', 'refund_taxes'), $_POST['fields'])) > 0);
		$shippingResult = HM_Product_Sales_Report_Pro::getShippingReportData($wc_report, $start_date, $end_date, $hasTaxFields);
		if (!empty($_POST['refunds']) || $hasRefundFields) {
			$shippingRefundResult = HM_Product_Sales_Report_Pro::getShippingReportData($wc_report, $start_date, $end_date, $hasTaxFields, true);
			$shippingResult = trow_psr_process_refunds($shippingResult, $shippingRefundResult);
		}
		foreach ($shippingResult as $shipping) {
			$row = trow_sbp_get_shipping_row($shipping, $_POST['fields'], $totals);
			if (isset($rows[$row[$orderIndex]])) {
				$rows[$row[$orderIndex]][] = $row;
			} else {
				$rows[$row[$orderIndex]] = array($row);
			}
		}
	}
	
	if ($_POST['orderdir'] == 'desc') {
		krsort($rows);
	} else {
		ksort($rows);
	}
	
	foreach ($rows as $filterValueRows) {
		foreach ($filterValueRows as $row) {
			$dest->putRow($row);
		}
	}
		
	
	if (!empty($_POST['include_totals'])) {
		$dest->putRow(trow_sbp_get_totals_row($totals, $_POST['fields']), false, true);
	}
	
	// Remove report order statuses filter
	remove_filter('woocommerce_reports_order_statuses', 'trow_psr_report_order_statuses', 9999);
	
	// Remove report query filter
	remove_filter('woocommerce_reports_get_order_report_query', 'trow_psr_filter_report_query');
}

function trow_psr_override_tax_location() {
	// Copied from get_tax_location() in WooCommerce includes/class-wc-tax.php
	return array(
		WC()->countries->get_base_country(),
		WC()->countries->get_base_state(),
		WC()->countries->get_base_postcode(),
		WC()->countries->get_base_city()
	);
}

function trow_psr_process_refunds($sold_products, $refunded_products) {

	$fieldsToAdjust = array(
		'quantity',
		'gross',
		'gross_after_discount',
		'taxes'
	);
	
	foreach ($refunded_products as $refunded_product) {
		$product = false;
		foreach ($sold_products as $sold_product) {
			if ($sold_product->product_id == $refunded_product->product_id &&
				((empty($sold_product->variation_id) && empty($refunded_product->variation_id)) || $sold_product->variation_id == $refunded_product->variation_id)) {
				
				$product = $sold_product;
				break;
			}
		}
			
		if ($product === false) {
			$product = clone $refunded_product;
			$product->is_refund_only = true;
			if (empty($_POST['refunds'])) {
				foreach ($fieldsToAdjust as $field) {
					$product->$field = 0;
				}
			} else {
				foreach ($fieldsToAdjust as $field) {
					$product->$field = abs($product->$field) * -1;
				}
			}
			
			$sold_products[] = $product;
		} else if (!empty($_POST['refunds'])) {
			foreach ($fieldsToAdjust as $field) {
				if (isset($product->$field)) {
					$product->$field += (abs($refunded_product->$field) * -1);
				}
			}
		}
			
		$product->refund_quantity = abs($refunded_product->quantity);
		$product->refund_gross = $refunded_product->gross * -1;
		$product->refund_taxes = (isset($refunded_product->taxes) ? $refunded_product->taxes * -1 : 0);
		
	}
	
	// Make sure refund fields are set on all products
	foreach ($sold_products as $sold_product) {
		if (!isset($sold_product->refund_quantity)) {
			$sold_product->refund_quantity = 0;
			$sold_product->refund_gross = 0;
			$sold_product->refund_taxes = 0;
		}
	}
	
	return $sold_products;
}

function trow_sbp_get_product_row($product, $fields, &$totals) {
	$row = array();
	
	$addonFields = HM_Product_Sales_Report_Pro::getAddonFields();
	$formatAmounts = !empty($_POST['format_amounts']);
		
	foreach ($fields as $field) {
		if (isset($addonFields[$field]['cb'])) {
			$row[] = call_user_func($addonFields[$field]['cb'], $product, null);
		} else {
			$isBuiltIn = (substr($field, 0, 9) == 'builtin::');
			if (!$isBuiltIn) {
				if (substr($field, 0, 18) == 'order_item_total::') {
					$fieldName = 'order_item_total__'.substr($field, 18);
					if (isset($product->$fieldName)) {
						$row[] = $product->$fieldName;
						if (isset($totals[$field])) {
							$totals[$field] += $product->$fieldName;
						} else {
							$totals[$field] = $product->$fieldName;
						}
					} else {
						$row[] = 0;
					}
				} else {
					$row[] = trow_psr_get_custom_field_value($product->product_id, $field, (empty($product->variation_id) ? null : $product->variation_id));
				}
			} else {
			
				// Add builtin:: prefix, for compatibility with pre-1.6.9 presets
				if (!$isBuiltIn) {
					$field = 'builtin::'.$field;
				}
				
				switch ($field) {
					case 'builtin::product_id':
						$row[] = $product->product_id;
						break;
					case 'builtin::product_sku':
						$row[] = get_post_meta($product->product_id, '_sku', true);
						break;
					case 'builtin::product_name':
						// Following code provided by and copyright Daniel von Mitschke, released under GNU General Public License (GPL) version 2 or later, used under GPL version 3 or later (see license/LICENSE.TXT)
					    $name = html_entity_decode(get_the_title($product->product_id));
					    // Handle deleted products
					    if(empty($name)) {
					        $name = $product->product_name;
                        }
						$row[] = $name;
						// End code provided by Daniel von Mitschke
						break;
					case 'builtin::quantity_sold':
						$row[] = $product->quantity;
						//$totals['builtin::quantity_sold'] = (isset($totals['builtin::quantity_sold']) ? $totals['builtin::quantity_sold'] + $product->quantity : $product->quantity);
						break;
					case 'builtin::gross_sales':
						$row[] = $formatAmounts ? number_format($product->gross, 2, '.', '') : $product->gross;
						//$totals['builtin::gross_sales'] = (isset($totals['builtin::gross_sales']) ? $totals['builtin::gross_sales'] + $product->gross : $product->gross);
						break;
					case 'builtin::gross_after_discount':
						$row[] = $formatAmounts ? number_format($product->gross_after_discount, 2, '.', '') : $product->gross_after_discount;
						//$totals['builtin::gross_after_discount'] = (isset($totals['builtin::gross_after_discount']) ? $totals['builtin::gross_after_discount'] + $product->gross_after_discount : $product->gross_after_discount);
						break;
					case 'builtin::product_categories':
						$row[] = trow_psr_get_custom_field_value($product->product_id, 'taxonomy::product_cat');
						break;
					case 'builtin::product_price':
						if (!isset($wc_product)) {
							$wc_product = wc_get_product(empty($product->variation_id) ? $product->product_id : $product->variation_id);
						}
						if (empty($wc_product)) {
							$row[] = '';
						} else {
							$price = $wc_product->get_price();
							$row[] = ($formatAmounts && is_numeric($price)) ? number_format($price, 2, '.', '') : $price;
						}
						break;
					case 'builtin::product_price_with_tax':
						if (!isset($wc_product)) {
							$wc_product = wc_get_product(empty($product->variation_id) ? $product->product_id : $product->variation_id);
						}
						if (empty($wc_product)) {
							$row[] = '';
						} else {
							$price = $wc_product->get_price_including_tax();
							$row[] = ($formatAmounts && is_numeric($price)) ? number_format($price, 2, '.', '') : $price;
						}
						break;
					case 'builtin::product_stock':
						if (empty($product->variation_id))
							$stock = get_post_meta($product->product_id, '_stock', true);
						else
							$stock = get_post_meta($product->variation_id, '_stock', true);
						$row[] = $stock;
						break;
					case 'builtin::taxes':
						$row[] = $formatAmounts ? number_format($product->taxes, 2, '.', '') :  $product->taxes;
						//$totals['builtin::taxes'] = (isset($totals['builtin::taxes']) ? $totals['builtin::taxes'] + $product->taxes : $product->taxes);
						break;
					case 'builtin::discount':
						$discount = $product->gross - $product->gross_after_discount;
						$row[] = $formatAmounts ? number_format($discount, 2, '.', '') : $discount;
						//$totals['builtin::discount'] = (isset($totals['builtin::discount']) ? $totals['builtin::discount'] + $discount : $discount);
						break;
					case 'builtin::total_with_tax':
						$total = $product->gross_after_discount + $product->taxes;
						$row[] = $formatAmounts ? number_format($total, 2, '.', '') :  $total;
						//$totals['builtin::total_with_tax'] = (isset($totals['builtin::total_with_tax']) ? $totals['builtin::total_with_tax'] + $total : $total);
						break;
					case 'builtin::variation_id':
						$row[] = (empty($product->variation_id) ? '' : $product->variation_id);
						break;
					case 'builtin::variation_sku':
						$row[] = (empty($product->variation_id) ? '' : get_post_meta($product->variation_id, '_sku', true));
						break;
					case 'builtin::variation_attributes':
						$row[] = (TROW_GET ? HM_Product_Sales_Report_Pro::getFormattedVariationAttributes($product) : '');
						break;
					case 'builtin::publish_time':
						$row[] = get_the_time('Y-m-d H:i:s', $product->product_id);
						break;
					case 'builtin::refund_quantity':
						$row[] = $product->refund_quantity;
						//$totals['builtin::refund_quantity'] = (isset($totals['builtin::refund_quantity']) ? $totals['builtin::refund_quantity'] + $product->refund_quantity : $product->refund_quantity);
						break;
					case 'builtin::refund_gross':
						$row[] = $formatAmounts ? number_format($product->refund_gross, 2, '.', '') : $product->refund_gross;
						//$totals['builtin::refund_gross'] = (isset($totals['builtin::refund_gross']) ? $totals['builtin::refund_gross'] + $product->refund_gross : $product->refund_gross);
						break;
					case 'builtin::refund_with_tax':
						$total = $product->refund_gross + $product->refund_taxes;
						$row[] = $formatAmounts ? number_format($total, 2, '.', '') :  $total;
						//$totals['builtin::refund_with_tax'] = (isset($totals['builtin::refund_with_tax']) ? $totals['builtin::refund_with_tax'] + $total : $total);
						break;
					case 'builtin::refund_taxes':
						$row[] = $formatAmounts ? number_format($product->refund_taxes, 2, '.', '') :  $product->refund_taxes;
						//$totals['builtin::refund_taxes'] = (isset($totals['builtin::refund_taxes']) ? $totals['builtin::refund_taxes'] + $product->refund_taxes : $product->refund_taxes);
						break;
					case 'builtin::groupby_field':
						if ($_POST['groupby'] == 'i_builtin::item_price') {
							$row[] = $formatAmounts ? number_format($product->gross / $product->quantity, 2, '.', '') : $product->gross / $product->quantity;
						} else {
							$row[] = $product->groupby_field;
						}
						break;
					default:
						$row[] = '';
				}
				
			}
		}
		
		if (isset($totals[$field])) {
			$totals[$field] += end($row);
		}
	}
	
	return $row;
}

function trow_sbp_get_nil_product_row($productId, $fields, $variationId=null, &$totals) {
	$row = array();

	$addonFields = HM_Product_Sales_Report_Pro::getAddonFields();
	$formatAmounts = !empty($_POST['format_amounts']);
	
	foreach ($fields as $field) {
		if (isset($addonFields[$field]['cb'])) {
			$row[] = call_user_func($addonFields[$field]['cb'], $productId, 'nil');
		} else {
			switch ($field) {
				case 'builtin::product_id':
					$row[] = $productId;
					break;
				case 'builtin::product_sku':
					$row[] = get_post_meta($productId, '_sku', true);
					break;
				case 'builtin::product_name':
					$row[] = html_entity_decode(get_the_title($productId));
					break;
				case 'builtin::quantity_sold':
				case 'builtin::refund_quantity':
					$row[] = 0;
					break;
				case 'builtin::gross_sales':
				case 'builtin::gross_after_discount':
				case 'builtin::taxes':
				case 'builtin::discount':
				case 'builtin::total_with_tax':
				case 'builtin::refund_gross':
				case 'builtin::refund_with_tax':
				case 'builtin::refund_taxes':
					$row[] = $formatAmounts ? '0.00' : 0;
					break;
				case 'builtin::groupby_field':
					$row[] = '';
					break;
				case 'builtin::product_categories':
					$row[] = trow_psr_get_custom_field_value($productId, 'taxonomy::product_cat');
					break;
				case 'builtin::product_price':
					if (!empty($variationId))
						$price = get_post_meta($variationId, '_price', true);
					if (!isset($price) || (empty($price) && $price !== 0))
						$price = get_post_meta($productId, '_price', true);
					$row[] = ($formatAmounts && is_numeric($price)) ? number_format($price, 2, '.', '') : $price;
					break;
				case 'builtin::product_price_with_tax':
					$wc_product = wc_get_product(empty($variationId) ? $productId : $variationId);
					if (empty($wc_product)) {
						$row[] = '';
					} else {
						$price = $wc_product->get_price_including_tax();
						$row[] = ($formatAmounts && is_numeric($price)) ? number_format($price, 2, '.', '') : $price;
					}
					break;
				case 'builtin::product_stock':
					if (!empty($variationId))
						$stock = get_post_meta($variationId, '_stock', true);
					else
						$stock = get_post_meta($productId, '_stock', true);
					$row[] = $stock;
					break;
				case 'builtin::variation_id':
					$row[] = (empty($variationId) ? '' : $variationId);
					break;
				case 'builtin::variation_sku':
					$row[] = (empty($variationId) ? '' : get_post_meta($variationId, '_sku', true));
					break;
				case 'builtin::variation_attributes':
					$row[] = (empty($variationId) ? '' : HM_Product_Sales_Report_Pro::getFormattedVariationAttributes($variationId));
					break;
				case 'builtin::publish_time':
					$row[] = get_the_time('Y-m-d H:i:s', $productId);
					break;
				default:
					if (substr($field, 0, 18) != 'order_item_total::') {
						$row[] = trow_psr_get_custom_field_value($productId, $field, (empty($variationId) ? null : $variationId));
					} else {
						$row[] = '';
					}
			}
		}
		
		if (isset($totals[$field])) {
			$totals[$field] += end($row);
		}
	}
	
	return $row;
}

function trow_sbp_get_shipping_row($shipping, $fields, &$totals) {
	global $woocommerce;
	
	$formatAmounts = !empty($_POST['format_amounts']);
	$addonFields = HM_Product_Sales_Report_Pro::getAddonFields();
	
	$row = array();
	foreach ($fields as $field) {
		if (isset($addonFields[$field]['cb'])) {
			$row[] = call_user_func($addonFields[$field]['cb'], $shipping, 'shipping');
		} else {
			switch ($field) {
				case 'builtin::product_id':
					$row[] = $shipping->product_id;
					break;
				case 'builtin::quantity_sold':
					$row[] = $shipping->quantity;
					break;
				case 'builtin::gross_sales':
					$row[] = $formatAmounts ? number_format($shipping->gross, 2, '.', '') : $shipping->gross;
					break;
				case 'builtin::gross_after_discount':
					$row[] = $formatAmounts ? number_format($shipping->gross, 2, '.', '') : $shipping->gross;
					break;
				case 'builtin::product_name':
					$woocommerce->shipping->load_shipping_methods();
					$shippingMethods = $woocommerce->shipping->get_shipping_methods();
					if (!empty($shippingMethods[$shipping->product_id]->method_title))
						$row[] = 'Shipping - '.$shippingMethods[$shipping->product_id]->method_title;
					else
						$row[] = 'Shipping - '.$shipping->product_id;
					break;
				case 'builtin::taxes':
					$row[] = $formatAmounts ? number_format($shipping->taxes, 2, '.', '') :  $shipping->taxes;
					break;
				case 'builtin::total_with_tax':
					$total = $shipping->gross + $shipping->taxes;
					$row[] = $formatAmounts ? number_format($total, 2, '.', '') :  $total;
					break;
				case 'builtin::refund_gross':
					$row[] = $formatAmounts ? number_format($shipping->refund_gross, 2, '.', '') : $shipping->refund_gross;
					break;
				case 'builtin::refund_with_tax':
					$total = $shipping->refund_gross + $shipping->refund_taxes;
					$row[] = $formatAmounts ? number_format($total, 2, '.', '') :  $total;
					break;
				case 'builtin::refund_taxes':
					$row[] = $formatAmounts ? number_format($shipping->refund_taxes, 2, '.', '') :  $shipping->refund_taxes;
					break;
				case 'builtin::groupby_field':
					if ($_POST['groupby'] == 'i_builtin::item_price') {
						$row[] = $formatAmounts ? number_format($shipping->gross / $shipping->quantity, 2, '.', '') : $shipping->gross / $shipping->quantity;
					} else {
						$row[] = $shipping->groupby_field;
					}
					break;
				default:
					$row[] = '';
			}
		}
		
		if (isset($totals[$field])) {
			$totals[$field] += end($row);
		}
	}
	return $row;
}

function trow_sbp_get_totals_row($totals, $fields) {
	$row = array();
	
	$formatAmounts = !empty($_POST['format_amounts']);
	
	foreach ($fields as $field) {
		if (!isset($totals[$field]) && $field != 'builtin::product_name') {
			$row[] = '';
		} else {
			switch ($field) {
				case 'builtin::product_name':
					$row[] = 'TOTALS';
					break;
				case 'builtin::gross_sales':
				case 'builtin::gross_after_discount':
				case 'builtin::taxes':
				case 'builtin::discount':
				case 'builtin::total_with_tax':
				case 'builtin::refund_gross':
				case 'builtin::refund_with_tax':
				case 'builtin::refund_taxes':
					$row[] = $formatAmounts ? number_format($totals[$field], 2, '.', '') : $totals[$field];
					break;
				default:
					$row[] = $totals[$field];
			}
		}
	}
	
	return $row;
}

function trow_psr_get_custom_field_value($productId, $field, $variationId=null) {
	if (strlen($field) > 10 && substr($field, 0, 10) == 'taxonomy::') {
		$terms = get_the_terms($productId, substr($field, 10));
		if (empty($terms)) {
			return '';
		} else {
			$termNames = array();
			foreach ($terms as $term)
				$termNames[] = $term->name;
			return implode(', ', $termNames);
		}
	} else if (strlen($field) > 11 && substr($field, 0, 11) == 'variation::') {
		$value = (empty($variationId) ? '' : get_post_meta($variationId, substr($field, 11), true));
	} else {
		$value = get_post_meta($productId, $field, true);
	}
	return (is_array($value) ? trow_psr_array_string($value) : $value);
}

function trow_psr_array_string($arr) {
	// Determine whether the array is indexed or associative
	$isIndexedArray = true;
	for ($i = 0; $i < count($arr); ++$i) {
		if (!isset($arr[$i])) {
			$isIndexedArray = false;
			break;
		}
	}
	// Process associative array
	if (!$isIndexedArray) {
		foreach ($arr as $key => $value) {
			$arr[$key] = $key.': '.(is_array($value) ? '('.trow_psr_array_string($value).')' : $value);
		}
	}
	return implode(', ', $arr);
}

add_action('admin_enqueue_scripts', 'trow_psr_admin_enqueue_scripts');
function trow_psr_admin_enqueue_scripts() {
	wp_enqueue_style('hm_psr_admin_style', plugins_url('css/hm-product-sales-report.css', __FILE__), array(), TROW_VERSION);
	wp_enqueue_script('jquery-ui-sortable');
}


// Schedulable email report hook
add_filter('pp_wc_get_schedulable_email_reports', 'trow_psr_add_schedulable_email_reports');
function trow_psr_add_schedulable_email_reports($reports) {
	
	$myReports = array('last' => 'Last used settings');
	$savedReportSettings = get_option('hm_psr_report_settings', array());
	if (!empty($savedReportSettings)) {
		$updated = false;
		foreach ($savedReportSettings as $i => $settings) {
			if ($i == 0)
				continue;
			if (empty($settings['key'])) {
				$chars = 'abcdefghijklmnopqrstuvwxyz123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
				$numChars = strlen($chars);
				while (true) {
					$key = '';
					for ($j = 0; $j < 32; ++$j)
						$key .= $chars[rand(0, $numChars-1)];
					$unique = true;
					foreach ($savedReportSettings as $settings2)
						if (isset($settings2['key']) && $settings2['key'] == $key)
							$unique = false;
					if ($unique)
						break;
				}
				$savedReportSettings[$i]['key'] = $key;
				$updated = true;
			}
			$myReports[$savedReportSettings[$i]['key']] = $settings['preset_name'];
		}
		
		if ($updated)
			update_option('hm_psr_report_settings', $savedReportSettings);
	}

	$reports['hm_psrp'] = array(
		'name' => 'Transaction reports for WooCommerce',
		'callback' => 'trow_psr_run_scheduled_report',
		'fields_callback' => 'trow_psr_get_scheduled_report_fields',
		'reports' => $myReports
	);
	return $reports;
}

function trow_psr_run_scheduled_report($reportId, $start, $end, $args=array(), $output=false) {
	$savedReportSettings = get_option('hm_psr_report_settings');
	if (!isset($savedReportSettings[0]))
		return false;
	
	if ($reportId == 'last') {
		$presetIndex = 0;
	} else {
		foreach ($savedReportSettings as $i => $settings) {
			if (isset($settings['key']) && $settings['key'] == $reportId) {
				$presetIndex = $i;
				break;
			}
		}
	}
	if (!isset($presetIndex))
		return false;
	
	$prevPost = $_POST;
	$_POST = array_merge(trow_psr_default_report_settings(), $savedReportSettings[$presetIndex]);
	if ($start === null && $end === null) {
		list($start, $end) = trow_psr_get_report_dates();
	} else {
		// Add one day to end since we're setting the time to midnight
		$end += 86400;
		
		$_POST['report_time'] = 'custom';
		$_POST['report_start'] = date('Y-m-d', $start);
		$_POST['report_start_time'] = '12:00:00 AM';
		$_POST['report_end'] = date('Y-m-d', $end);
		$_POST['report_end_time'] = '12:00:00 AM';
	}
	$_POST = array_merge($_POST, array_intersect_key($args, $_POST));
	
	if ($_POST['format'] != 'array') {
		$titleVars = array(
			'now' => time(),
			'preset' => (empty($_POST['preset_name']) ? 'Product Sales' : $_POST['preset_name'])
		);
		
		if ($_POST['report_time'] != 'all') {
			$titleVars['start'] = $start;
			$titleVars['end'] = $end;
		}
		
		// Assemble the filename for the report download
		$filepath = get_temp_dir().'/'.(empty($_POST['filename']) ? 'Product Sales' : trow_psr_dynamic_title(str_replace(array('/', '\\'), '_', $_POST['filename']), $titleVars)).'.'.($_POST['format'] == 'html-enhanced' ? 'html' : (in_array($_POST['format'], array('xlsx', 'xls', 'html')) ? $_POST['format'] : 'csv'));
	}
	
	if ($_POST['format'] == 'xlsx' || $_POST['format'] == 'xls') {
		include_once(dirname(__FILE__).'/HM_XLS_Export.php');
		$dest = new HM_XLS_Export();
	} else if ($_POST['format'] == 'html') {
		include_once(dirname(__FILE__).'/HM_HTML_Export.php');
		$out = fopen($output ? 'php://output' : $filepath, 'w');
		$dest = new HM_HTML_Export($out, $_POST['report_css']);
	} else if ($_POST['format'] == 'html-enhanced') {
		include_once(dirname(__FILE__).'/HM_HTML_Enhanced_Export.php');
		$out = fopen($output ? 'php://output' : $filepath, 'w');
		$dest = new HM_HTML_Enhanced_Export($out, $_POST['report_css']);
	} else if ($_POST['format'] == 'array') {
		include_once(dirname(__FILE__).'/HM_Array_Export.php');
		$dest = new HM_Array_Export();
	} else {
		include_once(dirname(__FILE__).'/HM_CSV_Export.php');
		$out = fopen($output ? 'php://output' : $filepath, 'w');
		$dest = new HM_CSV_Export($out);
	}
	
	if (!empty($_POST['report_title_on'])) {
		$dest->putTitle(trow_psr_dynamic_title($_POST['report_title'], $titleVars));
	}
	
	if (!empty($_POST['include_header']))
		trow_sbp_export_header($dest);
	trow_sbp_export_body($dest, $start, $end);
	
	if ($_POST['format'] == 'xlsx') {
		$dest->outputXLSX($filepath);
		if ($output) {
			readfile($filepath);
			unlink($filepath);
		}
	} else if ($_POST['format'] == 'xls') {
		$dest->outputXLS($filepath);
		if ($output) {
			readfile($filepath);
			unlink($filepath);
		}
	} else if ($_POST['format'] != 'array') {
		$dest->close();
		unset($dest);
		fclose($out);
	}
	
	$_POST = $prevPost;
	
	return (isset($_POST['format']) && $_POST['format'] == 'array' ? $dest->getData() : $filepath);
}

function trow_psr_get_scheduled_report_fields($reportId) {
	$savedReportSettings = get_option('hm_psr_report_settings');
	if (!isset($savedReportSettings[0]))
		return false;
	
	if ($reportId == 'last') {
		$presetIndex = 0;
	} else {
		foreach ($savedReportSettings as $i => $settings) {
			if (isset($settings['key']) && $settings['key'] == $reportId) {
				$presetIndex = $i;
				break;
			}
		}
	}
	if (!isset($presetIndex))
		return false;
	
	return array_combine($savedReportSettings[$presetIndex]['fields'], $savedReportSettings[$presetIndex]['field_names']);
}

function trow_sbp_is_variable_product($product_id) {
	// Based on get_product_class() in WooCommerce includes/class-wc-product-factory.php
	$product_type = get_the_terms($product_id, 'product_type');
	return (!empty($product_type) && $product_type[0]->name == 'variable');
}

function trow_sbp_get_variation_ids($product_id) {
	return array_keys(get_children(array(
		'post_parent' => $product_id, 
		'post_type' => 'product_variation',
		'post_status' => 'publish'
	), ARRAY_N));
}

function trow_sbp_get_nil_products($product_ids, $sold_products, $dest, &$totals) {
	$sold_product_ids = array();
	$rows = array();
	
	if (empty($_POST['variations'])) { // Variations together
		foreach ($sold_products as $product) {
			$sold_product_ids[] = $product->product_id;
		}
		foreach (array_diff($product_ids, $sold_product_ids) as $product_id) {
			$rows[] = trow_sbp_get_nil_product_row($product_id, $_POST['fields'], null, $totals);
		}
		
	} else { // Variations separately
	
		$sold_variation_ids = array();
		foreach ($sold_products as $product) {
			$sold_product_ids[] = $product->product_id;
			if (!empty($product->variation_id))
				$sold_variation_ids[] = $product->variation_id;
		}
		
		foreach ($product_ids as $product_id) {
			if (trow_sbp_is_variable_product($product_id)) {
				$variation_ids = trow_sbp_get_variation_ids($product_id);
				foreach (array_diff($variation_ids, $sold_variation_ids) as $variation_id) {
					$rows[] = trow_sbp_get_nil_product_row($product_id, $_POST['fields'], $variation_id, $totals);
				}
			} else if (array_search($product_id, $sold_product_ids) === false) { // Not variable
				$rows[] = trow_sbp_get_nil_product_row($product_id, $_POST['fields'], null, $totals);
			}
		}
	
	}
	
	return $rows;
}

function trow_psr_get_groupby_fields() {
	global $hm_psr_groupby_fields;
	if (!isset($hm_psr_groupby_fields)) {
		global $wpdb;
		
		$fields = $wpdb->get_col('SELECT DISTINCT meta_key FROM (
									SELECT meta_key
									FROM '.$wpdb->prefix.'postmeta
									JOIN '.$wpdb->prefix.'posts ON (post_id=ID)
									WHERE post_type="shop_order"
									ORDER BY ID DESC
									LIMIT 10000
								) fields');
		sort($fields);
		foreach ($fields as $field) {
			$hm_psr_groupby_fields['o_'.$field] = $field;
		}
		$hm_psr_groupby_fields['o_builtin::order_date'] = 'Order Date';
		$hm_psr_groupby_fields['o_builtin::order_day'] = 'Order Day';
		$hm_psr_groupby_fields['o_builtin::order_month'] = 'Order Month';
		$hm_psr_groupby_fields['o_builtin::order_quarter'] = 'Order Quarter';
		$hm_psr_groupby_fields['o_builtin::order_year'] = 'Order Year';
		
		$fields = $wpdb->get_col('SELECT DISTINCT meta_key FROM (
									SELECT meta_key
									FROM '.$wpdb->prefix.'woocommerce_order_itemmeta
									WHERE meta_key NOT IN ("_product_id", "_variation_id")
									ORDER BY order_item_id DESC
									LIMIT 10000
								) fields');
		sort($fields);
		foreach ($fields as $field) {
			$hm_psr_groupby_fields['i_'.$field] = $field;
		}
		
		
		$hm_psr_groupby_fields['i_builtin::item_price'] = 'Item Price';
	}
	return $hm_psr_groupby_fields;
}


function trow_psr_report_order_statuses() {
	// We are now doing our own status filtering, so disable WooCommerce's status filtering
	return false;
	
	/*$wcOrderStatuses = wc_get_order_statuses();
	$orderStatuses = array();
	if (!empty($_POST['order_statuses'])) {
		foreach ($_POST['order_statuses'] as $orderStatus) {
			if (isset($wcOrderStatuses[$orderStatus]))
				$orderStatuses[] = substr($orderStatus, 3);
		}
	}*/
	return $orderStatuses;
}

function trow_psr_filter_report_query($sql, $refundOrders=false) {
	// Add on any extra SQL
	global $hm_wc_report_extra_sql;
	if (!empty($hm_wc_report_extra_sql)) {
		foreach ($hm_wc_report_extra_sql as $key => $extraSql) {
			if (isset($sql[$key])) {
				$sql[$key] .= ' '.$extraSql;
			}
		}
	}
	
	return $sql;
}

add_action('wp_ajax_hm_psr_calc_dynamic_date', 'trow_psr_calc_dynamic_date');
function trow_psr_calc_dynamic_date() {
	if (empty($_POST['date'])) {
		wp_send_json_error();
	}
	$result = strtotime($_POST['date'], current_time('timestamp'));
	if (empty($result)) {
		wp_send_json_error();
	}
	wp_send_json_success(date('Y-m-d', $result));
}

function trow_psr_dynamic_title($title, $vars) {
	global $hm_psr_dt_vars;
	$hm_psr_dt_vars = $vars;
	$title = preg_replace_callback('/\[([a-z_]+)( .+)?\]/U', 'trow_psr_dynamic_title_cb', $title);
	unset($hm_psr_dt_vars);
	return $title;
}

function trow_psr_dynamic_title_cb($field) {
	global $hm_psr_dt_vars;
	switch ($field[1]) {
		case 'preset':
			return $hm_psr_dt_vars['preset'];
		case 'start':
			if (!isset($hm_psr_dt_vars['start'])) {
				return '(all time)';
			}
			$date = $hm_psr_dt_vars['start'];
			break;
		case 'end':
			if (!isset($hm_psr_dt_vars['end'])) {
				return '(all time)';
			}
			$date = $hm_psr_dt_vars['end'];
			break;
		case 'created':
			$date = $hm_psr_dt_vars['now'];
			break;
		default:
			return $field[0];
	}
	
	// Field is a date
	return date((empty($field[2]) ? get_option('date_format') : substr($field[2], 1)), $date);
}

?>