<?php


if (!defined('ABSPATH')) exit;

// Print header
echo('
	<div class="wrap">
		<h2>Transaction reports for WooCommerce</h2>
');

// Check for WooCommerce
if (!class_exists('WooCommerce')) {
	echo('<div class="notice notice-error"><p>This plugin requires that WooCommerce is installed and activated.</p></div></div>');
	return;
} else if (!function_exists('wc_get_order_types')) {
	echo('<div class="notice notice-error"><p>The Transaction reports for WooCommerce plugin requires WooCommerce 2.2 or higher. Please update your WooCommerce install.</p></div></div>');
	return;
}

if (isset($hm_psr_email_result)) {
	if ($hm_psr_email_result) {
		echo('<div class="notice notice-success"><p>The report has been emailed to <strong>'.htmlspecialchars($_POST['email_to']).'</strong>.</p></div>');
	} else {
		echo('<div class="notice notice-error"><p>An error occurred while emailing the report. Please try again.</p></div>');
	}
}



// Check for license
if (TROW_GET && !HM_Product_Sales_Report_Pro::licenseCheck())
	return;


// Print form

//HM_Product_Sales_Report_Pro::loadPresetField($savedReportSettings);

$canEditReportCSS = current_user_can('edit_theme_options');
$orderBy = (in_array($reportSettings['orderby'], array('product_id', 'quantity', 'gross', 'gross_after_discount')) ? 'builtin::'.$reportSettings['orderby'] : $reportSettings['orderby']);

echo('<form action="" method="post" id="hm_psr_form">
			<div id="hm_psr-current-preset">
				<input type="text" name="preset_name" placeholder="Preset Name"'.(isset($_GET['preset']) && isset($savedReportSettings[$_GET['preset']]['preset_name']) ? ' value="'.esc_attr($savedReportSettings[$_GET['preset']]['preset_name']).'"' : '').' />
'.(isset($_GET['preset']) ? '<button class="button-primary" name="hm_psr_action" value="preset-save" onclick="jQuery(this).closest(\'form\').attr(\'target\', \'\'); return true;">Save Changes</button>
							<button class="button-secondary" type="button" onclick="location.href=\'?page=hm_sbp\';">Close Preset</button>' : '').'
				<button class="button-secondary" name="hm_psr_action" value="preset-create" onclick="jQuery(this).closest(\'form\').attr(\'target\', \'\'); return true;">Create New Preset</button>
			</div>
			
			<h2 id="hm_psr_tabs" class="nav-tab-wrapper">
				'.(count($savedReportSettings) > 1 ? '<a id="hm_psr_tab_presets" class="nav-tab" href="#presets">Presets</a>' : '').'
				<a id="hm_psr_tab_orders" class="nav-tab" href="#orders">Order Filtering</a>
				<a id="hm_psr_tab_products" class="nav-tab" href="#products">Product Filtering</a>
				<a id="hm_psr_tab_groupsort" class="nav-tab" href="#groupsort">Grouping &amp; Sorting</a>
				<a id="hm_psr_tab_fields" class="nav-tab" href="#fields">Report Fields</a>
				<a id="hm_psr_tab_display" class="nav-tab" href="#display">Display &amp; Format</a>
				<a id="hm_psr_tab_advanced" class="nav-tab" href="#advanced">Advanced</a>
			</h2>
			
			<div id="hm_psr_tab_panels">
			
			<table id="hm_psr_tab_presets_panel">
				<tbody>');
	$runNonce = wp_create_nonce('hm-psr-run');
	foreach ($savedReportSettings as $presetId => $preset) {
		if ($presetId == 0) continue;
		echo('<tr>
			<td>'.esc_html($preset['preset_name']).'</td>
			<td>
				<a href="?page=hm_sbp&amp;hm_psr_action=run&amp;preset='.$presetId.'&amp;hm-psr-nonce='.esc_attr($runNonce).'" target="_blank" class="dashicons dashicons-controls-play" />
				<a href="?page=hm_sbp&amp;preset='.$presetId.'#orders" class="dashicons dashicons-edit" />
				<a href="?page=hm_sbp&amp;hm_psr_action=preset-del&amp;preset='.$presetId.'" class="dashicons dashicons-trash" onclick="return confirm(\'Are you sure that you want to delete this preset?\');" />
			</td>
		</tr>');
	}
echo('
			</tbody>
		</table>
			
			<table id="hm_psr_tab_orders_panel" class="form-table">
				<tr valign="top">
					<th scope="row">
						<label for="hm_sbp_field_report_time">Order Period:</label>
					</th>
					<td>
						<select name="report_time" id="hm_sbp_field_report_time">
							<option value="0d"'.($reportSettings['report_time'] == '0d' ? ' selected="selected"' : '').'>Today</option>
							<option value="1d"'.($reportSettings['report_time'] == '1d' ? ' selected="selected"' : '').'>Yesterday</option>
							<option value="7d"'.($reportSettings['report_time'] == '7d' ? ' selected="selected"' : '').'>Previous 7 days (excluding today)</option>
							<option value="30d"'.($reportSettings['report_time'] == '30d' ? ' selected="selected"' : '').'>Previous 30 days (excluding today)</option>
							<option value="0cm"'.($reportSettings['report_time'] == '0cm' ? ' selected="selected"' : '').'>Current calendar month</option>
							<option value="1cm"'.($reportSettings['report_time'] == '1cm' ? ' selected="selected"' : '').'>Previous calendar month</option>
							<option value="+7d"'.($reportSettings['report_time'] == '+7d' ? ' selected="selected"' : '').'>Next 7 days (future dated orders)</option>
							<option value="+30d"'.($reportSettings['report_time'] == '+30d' ? ' selected="selected"' : '').'>Next 30 days (future dated orders)</option>
							<option value="+1cm"'.($reportSettings['report_time'] == '+1cm' ? ' selected="selected"' : '').'>Next calendar month (future dated orders)</option>
							<option value="all"'.($reportSettings['report_time'] == 'all' ? ' selected="selected"' : '').'>All time</option>
							<option value="custom"'.($reportSettings['report_time'] == 'custom' ? ' selected="selected"' : '').'>Custom date range</option>
						</select>
					</td>
				</tr>
				<tr valign="top" class="hm_sbp_custom_time">
					<th scope="row">
						<label for="hm_sbp_field_report_start">Start Date &amp; Time:</label>
					</th>
					<td>
						<div class="alignleft">
							<input type="date" name="report_start" id="hm_sbp_field_report_start" value="'.(empty($reportSettings['report_start_dynamic']) ? $reportSettings['report_start'] : date('Y-m-d', strtotime($reportSettings['report_start_dynamic'], current_time('timestamp')))).'"'.(empty($reportSettings['report_start_dynamic']) ? '' : ' disabled="disabled"').' />
							<input type="text" class="hm-psr-date-dynamic-field'.(empty($reportSettings['report_start_dynamic']) ? ' hidden' : '').'" name="report_start_dynamic" value="'.esc_attr($reportSettings['report_start_dynamic']).'" placeholder="e.g. -7 days" />
							<a href="javascript:void(0);" class="hm-psr-date-dynamic-toggle">'.(empty($reportSettings['report_start_dynamic']) ? 'dynamic' : 'fixed').' date</a>
						</div>
						<input type="text" name="report_start_time" id="hm_sbp_field_report_start_time" value="'.$reportSettings['report_start_time'].'" />
					</td>
				</tr>
				<tr valign="top" class="hm_sbp_custom_time">
					<th scope="row">
						<label for="hm_sbp_field_report_end">End Date &amp; Time:</label>
					</th>
					<td>
						<div class="alignleft">
							<input type="date" name="report_end" id="hm_sbp_field_report_end" value="'.(empty($reportSettings['report_end_dynamic']) ? $reportSettings['report_end'] : date('Y-m-d', strtotime($reportSettings['report_end_dynamic'], current_time('timestamp')))).'"'.(empty($reportSettings['report_end_dynamic']) ? '' : ' disabled="disabled"').' />
							<input type="text" class="hm-psr-date-dynamic-field'.(empty($reportSettings['report_end_dynamic']) ? ' hidden' : '').'" name="report_end_dynamic" value="'.esc_attr($reportSettings['report_end_dynamic']).'" placeholder="e.g. -7 days" />
							<a href="javascript:void(0);" class="hm-psr-date-dynamic-toggle">'.(empty($reportSettings['report_end_dynamic']) ? 'dynamic' : 'fixed').' date</a>
						</div>
						<input type="text" name="report_end_time" id="hm_sbp_field_report_end_time" value="'.$reportSettings['report_end_time'].'" />
						<p class="description">Enter dates in the format YYYY-MM-DD and times in the format hour:minute:second (AM/PM optional). The end time is exclusive.</p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label>Include Orders With Status:</label>
					</th>
					<td>');
foreach (wc_get_order_statuses() as $status => $statusName) {
	echo('<label><input type="checkbox" name="order_statuses[]"'.(in_array($status, $reportSettings['order_statuses']) ? ' checked="checked"' : '').' value="'.$status.'" /> '.$statusName.'</label><br />');
}
			echo('</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label>
							<input type="checkbox" name="order_meta_filter_on" value="1"'.(empty($reportSettings['order_meta_filter_on']) ? '' : ' checked="checked"').' />
							Only Orders With Field:
						</label>
					</th>
					<td>
						<select name="order_meta_filter_key" class="hm-psr-select-other">
			');
			foreach (HM_Product_Sales_Report_Pro::getOrderFieldNames() as $orderField) {
				$orderFieldHtml = htmlspecialchars($orderField);
				$orderFieldFound = $orderFieldFound || $reportSettings['order_meta_filter_key'] == $orderField;
				echo("<option value=\"$orderFieldHtml\"".($reportSettings['order_meta_filter_key'] == $orderField ? ' selected="selected"' : '').">$orderFieldHtml</option>");
			}
			if (empty($orderFieldFound)) {
				echo('<option value="'.esc_attr($reportSettings['order_meta_filter_key']).'" selected>'.esc_html($reportSettings['order_meta_filter_key']).'</option>');
			}
			echo('
						</select>
						<select id="hm_psr_order_meta_filter_op" name="order_meta_filter_op">
							<option value="="'.($reportSettings['order_meta_filter_op'] == '=' ? ' selected="selected"' : '').'>equal to</option>
							<option value="!="'.($reportSettings['order_meta_filter_op'] == '!=' ? ' selected="selected"' : '').'>not equal to</option>
							<option value="&lt;"'.($reportSettings['order_meta_filter_op'] == '<' ? ' selected="selected"' : '').'>less than</option>
							<option value="&lt;="'.($reportSettings['order_meta_filter_op'] == '<=' ? ' selected="selected"' : '').'>less than or equal to</option>
							<option value="&gt;"'.($reportSettings['order_meta_filter_op'] == '>' ? ' selected="selected"' : '').'>greater than</option>
							<option value="&gt;="'.($reportSettings['order_meta_filter_op'] == '>=' ? ' selected="selected"' : '').'>greater than or equal to</option>
							<option value="BETWEEN"'.($reportSettings['order_meta_filter_op'] == 'BETWEEN' ? ' selected="selected"' : '').'>between</option>
							<option value="NOTEXISTS"'.($reportSettings['order_meta_filter_op'] == 'NOTEXISTS' ? ' selected="selected"' : '').'>does not exist</option>
						</select>
						<input type="text" id="hm_psr_order_meta_filter_value" name="order_meta_filter_value" value="'.htmlspecialchars($reportSettings['order_meta_filter_value']).'" />
						<span id="hm_psr_order_meta_filter_value_2" style="display: none;">
							and
							<input type="text" name="order_meta_filter_value_2" value="'.htmlspecialchars($reportSettings['order_meta_filter_value_2']).'" />
						</span>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label>Include Orders by Customer Role:</label>
					</th>
					<td>
						<select name="customer_role">
							<option value="0"'.(empty($reportSettings['customer_role']) ? ' selected="selected"' : '').'>(All Customers)</option>
							<option value="-1"'.($reportSettings['customer_role'] == -1 ? ' selected="selected"' : '').'>(Guest Customers)</option>');
							foreach ($wp_roles->roles as $roleId => $role) {
								echo('<option value="'.htmlspecialchars($roleId).'"'.($reportSettings['customer_role'] === $roleId ? ' selected="selected"' : '').'>'.htmlspecialchars($role['name']).'</option>');
							}
echo('						</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label>
							<input type="checkbox" name="customer_meta_filter_on" value="1"'.(empty($reportSettings['customer_meta_filter_on']) ? '' : ' checked="checked"').' />
							Only Orders from Customers With Field:
						</label>
					</th>
					<td>
						<select name="customer_meta_filter_key" class="hm-psr-select-other">
			');
			foreach (HM_Product_Sales_Report_Pro::getCustomerFieldNames() as $customerField) {
				$customerFieldHtml = htmlspecialchars($customerField);
				$customerFieldFound = $customerFieldFound || $reportSettings['customer_meta_filter_key'] == $customerField;
				echo("<option value=\"$customerFieldHtml\"".($reportSettings['customer_meta_filter_key'] == $customerField ? ' selected="selected"' : '').">$customerFieldHtml</option>");
			}
			if (empty($customerFieldFound)) {
				echo('<option value="'.esc_attr($reportSettings['customer_meta_filter_key']).'" selected>'.esc_html($reportSettings['customer_meta_filter_key']).'</option>');
			}
			echo('
						</select>
						<select id="hm_psr_customer_meta_filter_op" name="customer_meta_filter_op">
							<option value="="'.($reportSettings['customer_meta_filter_op'] == '=' ? ' selected="selected"' : '').'>equal to</option>
							<option value="!="'.($reportSettings['customer_meta_filter_op'] == '!=' ? ' selected="selected"' : '').'>not equal to</option>
							<option value="&lt;"'.($reportSettings['customer_meta_filter_op'] == '<' ? ' selected="selected"' : '').'>less than</option>
							<option value="&lt;="'.($reportSettings['customer_meta_filter_op'] == '<=' ? ' selected="selected"' : '').'>less than or equal to</option>
							<option value="&gt;"'.($reportSettings['customer_meta_filter_op'] == '>' ? ' selected="selected"' : '').'>greater than</option>
							<option value="&gt;="'.($reportSettings['customer_meta_filter_op'] == '>=' ? ' selected="selected"' : '').'>greater than or equal to</option>
							<option value="BETWEEN"'.($reportSettings['customer_meta_filter_op'] == 'BETWEEN' ? ' selected="selected"' : '').'>between</option>
							<option value="NOTEXISTS"'.($reportSettings['customer_meta_filter_op'] == 'NOTEXISTSs' ? ' selected="selected"' : '').'>does not exist</option>
						</select>
						<input type="text" id="hm_psr_customer_meta_filter_value" name="customer_meta_filter_value" value="'.htmlspecialchars($reportSettings['customer_meta_filter_value']).'" />
						<span id="hm_psr_customer_meta_filter_value_2" style="display: none;">
							and
							<input type="text" name="customer_meta_filter_value_2" value="'.htmlspecialchars($reportSettings['customer_meta_filter_value_2']).'" />
						</span>
					</td>
				</tr>
			</table>
			
			
			<table id="hm_psr_tab_products_panel" class="form-table">
				<tr valign="top">
					<th scope="row">
						<label>Include Products:</label>
					</th>
					<td>
						<label><input type="radio" name="products" value="all"'.($reportSettings['products'] == 'all' ? ' checked="checked"' : '').' /> All products</label><br />
						<label><input type="radio" name="products" value="cats"'.($reportSettings['products'] == 'cats' ? ' checked="checked"' : '').' /> Products in categories:</label><br />
						<ul id="hm-psr-product-cats">
					');
					wp_terms_checklist(0, array('selected_cats' => $reportSettings['product_cats'], 'taxonomy' => 'product_cat', 'checked_ontop' => false));
			echo('
						</ul>
						<label><input type="radio" name="products" value="ids"'.($reportSettings['products'] == 'ids' ? ' checked="checked"' : '').' /> Product ID(s):</label> 
						<input type="text" name="product_ids" style="width: 400px;" placeholder="Use commas to separate multiple product IDs" value="'.htmlspecialchars($reportSettings['product_ids']).'" /><br />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label>
							<input type="checkbox" name="product_tag_filter_on" value="1"'.(empty($reportSettings['product_tag_filter_on']) ? '' : ' checked="checked"').' />
							Only Products Tagged:
						</label>
					</th>
					<td>
						<input type="text" id="hm_psr_product_tag_filter" name="product_tag_filter" value="'.htmlspecialchars($reportSettings['product_tag_filter']).'" style="width: 400px;" />
						<div style="margin-top: 5px;">
							<select id="hm_psr_product_tag_filter_select">
								<option value="">Select tag...</option>');
							foreach (get_terms(array('taxonomy' => 'product_tag', 'hide_empty' => false, 'fields' => 'names')) as $term) {
								$term = htmlspecialchars($term);
								echo('<option value="'.$term.'">'.$term.'</option>');
							}
						echo('</select>
						</div>
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row">
						<label>
							<input type="checkbox" name="product_meta_filter_on" value="1"'.(empty($reportSettings['product_meta_filter_on']) ? '' : ' checked="checked"').' />
							Only Products With Field:
						</label>
					</th>
					<td>
						<select name="product_meta_filter_key" class="hm-psr-select-other">
			');
			foreach (HM_Product_Sales_Report_Pro::getCustomFieldNames(false, true) as $customField) {
				$customFieldHtml = htmlspecialchars($customField);
				$productFieldFound = $productFieldFound || $reportSettings['product_meta_filter_key'] == $customField;
				echo("<option value=\"$customFieldHtml\"".($reportSettings['product_meta_filter_key'] == $customField ? ' selected="selected"' : '').">$customFieldHtml</option>");
			}
			if (empty($productFieldFound)) {
				echo('<option value="'.esc_attr($reportSettings['product_meta_filter_key']).'" selected>'.esc_html($reportSettings['product_meta_filter_key']).'</option>');
			}
			echo('
						</select>
						<select id="hm_psr_product_meta_filter_op" name="product_meta_filter_op">
							<option value="="'.($reportSettings['product_meta_filter_op'] == '=' ? ' selected="selected"' : '').'>equal to</option>
							<option value="!="'.($reportSettings['product_meta_filter_op'] == '!=' ? ' selected="selected"' : '').'>not equal to</option>
							<option value="&lt;"'.($reportSettings['product_meta_filter_op'] == '<' ? ' selected="selected"' : '').'>less than</option>
							<option value="&lt;="'.($reportSettings['product_meta_filter_op'] == '<=' ? ' selected="selected"' : '').'>less than or equal to</option>
							<option value="&gt;"'.($reportSettings['product_meta_filter_op'] == '>' ? ' selected="selected"' : '').'>greater than</option>
							<option value="&gt;="'.($reportSettings['product_meta_filter_op'] == '>=' ? ' selected="selected"' : '').'>greater than or equal to</option>
							<option value="BETWEEN"'.($reportSettings['product_meta_filter_op'] == 'BETWEEN' ? ' selected="selected"' : '').'>between</option>
						</select>
						<input type="text" name="product_meta_filter_value" value="'.htmlspecialchars($reportSettings['product_meta_filter_value']).'" />
						<span id="hm_psr_product_meta_filter_value_2" style="display: none;">
							and
							<input type="text" name="product_meta_filter_value_2" value="'.htmlspecialchars($reportSettings['product_meta_filter_value_2']).'" />
						</span>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label>Product Variations:</label>
					</th>
					<td>
						<label>
							<input type="radio" name="variations" value="0"'.(empty($reportSettings['variations']) ? ' checked="checked"' : '').' class="hm_psr_variations_fld" />
							Group product variations together
						</label><br />
						<label>
							<input type="radio" name="variations" value="1"'.(empty($reportSettings['variations']) ? '' : ' checked="checked"').(TROW_GET ? '' : ' disabled="disabled"').' class="hm_psr_variations_fld" />
							Report on each variation separately
						</label>
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row" colspan="2" class="th-full">
						<label>
							<input type="checkbox" name="include_nil" value="1"'.(empty($reportSettings['include_nil']) ? '' : ' checked="checked"').' />
							Include products with no sales matching the filtering criteria
						</label>
					</th>
				</tr>
				
				<tr valign="top">
					<th scope="row" colspan="2" class="th-full">
						<label>
							<input type="checkbox" name="include_unpublished" value="1"'.(empty($reportSettings['include_unpublished']) ? '' : ' checked="checked"').' />
							Include unpublished products
						</label>
					</th>
				</tr>
				<tr valign="top">
					<th scope="row" colspan="2" class="th-full">
						<label>
							<input type="checkbox" name="exclude_free" value="1"'.(empty($reportSettings['exclude_free']) ? '' : ' checked="checked"').' />
							Exclude free products
						</label>
						<p class="description">If checked, order line items with a total amount of zero (after discounts) will be excluded from the report calculations.</p>
					</th>
				</tr>
				<tr valign="top">
					<th scope="row" colspan="2" class="th-full">
						<label>
							<input type="checkbox" name="include_shipping" value="1"'.(empty($reportSettings['include_shipping']) ? '' : ' checked="checked"').' />
							Include shipping
						</label>
					</th>
				</tr>
				<tr valign="top">
					<th scope="row" colspan="2" class="th-full">
						<label>
							<input type="checkbox" name="refunds" value="1"'.(empty($reportSettings['refunds']) ? '' : ' checked="checked"').' />
							Include line-item refunds
						</label>
						<p class="description">If checked, sales amounts and quantities will include deductions for refunds <span style="text-decoration: underline;">entered</span> during the report date range (regardless of the original order date).<br />
						If unchecked, line-item refunds will not be reflected in the report (except in specifically refund-related fields such as &quot;Quantity Refunded&quot;).<br />
						In either case, only refunds with &quot;Completed&quot; status (made on orders with the statuses defined in the Order Filtering tab) are included in the applicable data.</p>
					</th>
				</tr>
			</table>
			
			
			<table id="hm_psr_tab_groupsort_panel" class="form-table">
				<tr valign="top">
					<th scope="row">
						<label for="hm_psr_field_groupby">Group By:</label>
					</th>
					<td>
						<select name="groupby" id="hm_psr_field_groupby">
							<option value="">(None)</option>
							<optgroup label="Order" class="hm-psr-select-other" data-hm-psr-other-field-prefix="o_">');
			foreach (trow_psr_get_groupby_fields() as $fieldId => $fieldName) {
				if ($fieldId[0] == 'i' && empty($isOrderItemField)) {
					if (empty($foundGroupByField) && !empty($reportSettings['groupby']) && $reportSettings['groupby'][0] == 'o') {
						$foundGroupByField = true;
						echo('<option value="'.htmlspecialchars($reportSettings['groupby']).'" selected>'.htmlspecialchars(substr($reportSettings['groupby'], 2)).'</option>');
					}
					echo('</optgroup><optgroup label="Order Line Item" class="hm-psr-select-other" data-hm-psr-other-field-prefix="i_">');
					$isOrderItemField = true;
				}
				$foundGroupByField = $foundGroupByField || $reportSettings['groupby'] == $fieldId;
				echo('<option value="'.htmlspecialchars($fieldId).'"'.($reportSettings['groupby'] == $fieldId ? ' selected="selected"' : '').'>'.htmlspecialchars($fieldName).'</option>');
			}
			if (empty($foundGroupByField) && !empty($reportSettings['groupby'])) {
				echo('<option value="'.htmlspecialchars($reportSettings['groupby']).'" selected>'.htmlspecialchars(substr($reportSettings['groupby'], 2)).'</option>');
			}
			echo('			</optgroup>
						</select>
						<p class="description">Important: Do not choose a field that is already included in the report.<br>The Group By field will also appear in the report. You can change its label and position in the Report Fields tab.<br>Field names containing spaces or dashes (-) are not supported.</p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="hm_sbp_field_orderby">Sort By:</label>
					</th>
					<td>
						<select name="orderby" id="hm_sbp_field_orderby">
							<option value="'.esc_attr($orderBy).'">'.esc_html($orderBy).'</option>
						</select>
						<select name="orderdir">
							<option value="asc"'.($reportSettings['orderdir'] == 'asc' ? ' selected="selected"' : '').'>ascending</option>
							<option value="desc"'.($reportSettings['orderdir'] == 'desc' ? ' selected="selected"' : '').'>descending</option>
						</select>
					</td>
				</tr>
			</table>
			
			
			<table id="hm_psr_tab_fields_panel" class="form-table">
				<tr valign="top">
					<th scope="row">
						<label>Report Fields:</label>
					</th>
					<td id="hm_psr_report_field_selection"><div id="hm_psr_report_fields">');
$customFields = HM_Product_Sales_Report_Pro::getCustomFieldNames(true);
$addonFields = HM_Product_Sales_Report_Pro::getAddonFields();
$noTotalFields = array('builtin::product_id', 'builtin::product_sku', 'builtin::product_name', 'builtin::variation_id', 'builtin::variation_sku', 'builtin::variation_attributes',
						'builtin::product_categories', 'order_id', 'order_status', 'order_date', 'billing_name', 'billing_phone',
						'builtin::publish_time');
foreach ($reportSettings['fields'] as $fieldId) {
	if (!isset($fieldOptions[$fieldId]) && !isset($customFields[$fieldId]) && !isset($addonFields[$fieldId]) && $fieldId != 'builtin::groupby_field') {
		
		// Compatibility with pre-1.6.9 versions that didn't have the builtin:: prefix
		if (isset($fieldOptions['builtin::'.$fieldId])) {
			if (isset($reportSettings['field_names'][$fieldId])) {
				$reportSettings['field_names']['builtin::'.$fieldId] = $reportSettings['field_names'][$fieldId];
			}
			$fieldId = 'builtin::'.$fieldId;
		}
		
	}
	$fieldIdHtml = htmlspecialchars($fieldId);
	echo('<div'.(in_array($fieldId, array('builtin::variation_id', 'builtin::variation_sku', 'builtin::variation_attributes')) || substr($fieldId, 0, 11) == 'variation::' ? ' class="hm_psr_variation_field"' : ($fieldId == 'builtin::groupby_field' ? ' class="hm_psr_groupby_field"' : '')).'>
			<input type="hidden" name="fields[]" value="'.$fieldIdHtml.'" />
			<button type="button" onclick="hm_psr_remove_field(this);"><span class="dashicons dashicons-no"></span></button>
			<input type="text" class="hm_psr_field_name" name="field_names['.$fieldIdHtml.']" value="'.(isset($reportSettings['field_names'][$fieldId]) ? htmlspecialchars($reportSettings['field_names'][$fieldId]) : (isset($fieldOptions[$fieldId]) ? $fieldOptions[$fieldId] : $fieldIdHtml)).'" />
			<label class="hm_psr_total_field'.(in_array($fieldId, $noTotalFields) ? ' no-total' : '').'"><span>Total</span><input type="checkbox" name="total_fields[]" value="'.$fieldIdHtml.'"'.(in_array($fieldId, $reportSettings['total_fields']) ? ' checked="checked"' : '').' /></label>'
			.'</div>');
}

echo('</div><strong>Add Field:</strong> <select id="hm_psr_custom_field">');
foreach (array_merge(array('Built-in Fields' => $fieldOptions), $customFields) as $fieldGroupName => $fields) {
	switch ($fieldGroupName) {
		case 'Product Variation':
			$fieldGroupPrefix = 'variation::';
			break;
		case 'Order Item':
			$fieldGroupPrefix = 'order_item_total::';
			break;
	}
	echo('<optgroup label="'.$fieldGroupName.'"'.($fieldGroupName == 'Built-in Fields' || $fieldGroupName == 'Product Taxonomies' ? '' : ' class="hm-psr-select-other"'.(isset($fieldGroupPrefix) ? ' data-hm-psr-other-field-prefix="'.$fieldGroupPrefix.'"' : '')).'>');
	foreach ($fields as $fieldId => $fieldDisplay) {
		$fieldClasses = '';
		if (in_array($fieldId, array('builtin::variation_id', 'builtin::variation_sku', 'builtin::variation_attributes')) || substr($fieldId, 0, 11) == 'variation::') {
			$fieldClasses = 'hm_psr_variation_field';
		}
		if (in_array($fieldId, $noTotalFields)) {
			$fieldClasses .= (empty($fieldClasses) ? '' : ' ').'no-total-field';
		}
		echo('<option value="'.htmlspecialchars($fieldId).'"'.(empty($fieldClasses) ? '' : ' class="'.$fieldClasses.'"').'>'.htmlspecialchars($fieldDisplay).'</option>');
	}
	echo('</optgroup>');
}
$addonFields = array_diff_key($addonFields, $fieldOptions, $customFields);
if (!empty($addonFields)) {
	echo('<optgroup label="Addon Fields">');
	foreach ($addonFields as $fieldId => $fieldData) {
		echo('<option value="'.htmlspecialchars($fieldId).'">'.htmlspecialchars($fieldData['label']).'</option>');
	}
	echo('</optgroup>');
}
echo('</select> <button type="button" class="button-secondary" id="hm-psr-button-add-field">Add</button>
		<p class="description">Order Item field names containing spaces or dashes (-) are not supported.</p>
');


			echo('</td>
				</tr>
			</table>
			
			
			<table id="hm_psr_tab_display_panel" class="form-table">
				<tr valign="top">
					<th scope="row" colspan="2" class="th-full">
						<label>
							<input type="checkbox" name="limit_on" value="1"'.(empty($reportSettings['limit_on']) ? '' : ' checked="checked"').' />
							Show only the first
							<input type="number" name="limit" value="'.esc_attr($reportSettings['limit']).'" min="0" step="1" class="small-text" />
							products
						</label>
					</th>
				</tr>
				<tr valign="top">
					<th scope="row" colspan="2" class="th-full">
						<label>
							<input type="checkbox" name="report_title_on" value="1"'.(empty($reportSettings['report_title_on']) ? '' : ' checked="checked"').' />
							Include title:
							<input type="text" name="report_title" class="hm-psr-field-300" value="'.esc_attr($reportSettings['report_title']).'" />
						</label>
						<p class="description">Dynamic field examples: [preset] [start] [start Y-m-d H:i:s] [end] [created]</p>
					</th>
				</tr>
				<tr valign="top">
					<th scope="row" colspan="2" class="th-full">
						<label>
							<input type="checkbox" name="include_header" value="1"'.(empty($reportSettings['include_header']) ? '' : ' checked="checked"').' />
							Include header row
						</label>
						<p class="description">If checked, the first row of the report will contain the field names.</p>
					</th>
				</tr>
				<tr valign="top">
					<th scope="row" colspan="2" class="th-full">
						<label>
							<input type="checkbox" id="hm_psr_field_include_totals" name="include_totals" value="1"'.(empty($reportSettings['include_totals']) ? '' : ' checked="checked"').' />
							Include totals row
						</label>
						<p class="description">If checked, the last row of the report will contain totals.</p>
					</th>
				</tr>
				<tr valign="top">
					<th scope="row" colspan="2" class="th-full">
						<label>
							<input type="checkbox" name="format_amounts" value="1"'.(empty($reportSettings['format_amounts']) ? '' : ' checked="checked"').' />
							Display amounts with two decimal places
						</label>
						<p class="description">All calculations are done based on the amounts stored in the WooCommerce database without intermediate rounding, so selecting this option may introduce small rounding variances.</p>
					</th>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="hm_psr_field_format">Format:</label>
					</th>
					<td>
						<select name="format" id="hm_psr_field_format">
							<option value="csv"'.($reportSettings['format'] == 'csv' ? ' selected="selected"' : '').'>CSV</option>
							<option value="xlsx"'.($reportSettings['format'] == 'xlsx' ? ' selected="selected"' : '').'>XLSX</option>
							<option value="xls"'.($reportSettings['format'] == 'xls' ? ' selected="selected"' : '').'>XLS</option>
							<option value="html"'.($reportSettings['format'] == 'html' ? ' selected="selected"' : '').'>HTML</option>
							<option value="html-enhanced"'.($reportSettings['format'] == 'html-enhanced' ? ' selected="selected"' : '').'>HTML (enhanced)</option>
						</select>
						<div id="hm_psr_format_options_csv" class="hm_psr_format_options">
							<label>
								Separate fields with:
								<input type="text" name="format_csv_delimiter" maxlength="1"'.(empty($reportSettings['format_csv_delimiter']) ? '' : ' value="'.esc_attr($reportSettings['format_csv_delimiter']).'"').'>
							</label><br>
							<label>
								Surround fields with:
								<input type="text" name="format_csv_surround" maxlength="1"'.(empty($reportSettings['format_csv_surround']) ? '' : ' value="'.esc_attr($reportSettings['format_csv_surround']).'"').'>
							</label><br>
							<label>
								Escape surround character with:
								<input type="text" name="format_csv_escape" maxlength="1"'.(empty($reportSettings['format_csv_escape']) ? '' : ' value="'.esc_attr($reportSettings['format_csv_escape']).'"').'>
							</label>
						</div>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="hm_psr_field_filename">Filename:</label>
					</th>
					<td>
						<input type="text" name="filename" id="hm_psr_field_filename" class="hm-psr-field-300" value="'.esc_attr($reportSettings['filename']).'" />
						<p class="description">An extension (e.g. &quot;.csv&quot;) will be added automatically. See the &quot;Include title&quot; setting for dynamic field examples.</p>
					</td>
				</tr>
			</table>
			
			
			<table id="hm_psr_tab_advanced_panel" class="form-table">
				<tr valign="top">
					<th scope="row">
						<label for="hm_psr_field_time_limit">Time Limit:</label>
					</th>
					<td>
						<label>
							Allow report to run for up to
							<input type="number" id="hm_psr_field_time_limit" name="time_limit" class="small-text" min="0" step="1" value="'.esc_attr($reportSettings['time_limit']).'" />
							seconds
						</label>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="hm_psr_field_time_limit">Sort Buffer Size:</label>
					</th>
					<td>
						<label>
							Attempt to set MySQL sort buffer size to
							<input type="number" id="hm_psr_field_time_limit" name="db_sort_buffer_size" class="small-text" min="0" step="1" value="'.esc_attr($reportSettings['db_sort_buffer_size']).'" />
							K
						</label>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label>Plugin Conflicts:</label>
					</th>
					<td>
						<label>
							<input type="checkbox" name="report_unfiltered"'.(empty($reportSettings['report_unfiltered']) ? '' : ' checked="checked"').'>
							Attempt to prevent other plugins or code from changing the export query or output
						</label>
						<p class="description">Enabling this option can help resolve issues caused by conflicting plugins, but be sure to verify the accuracy and completeness of the export output when using this option.</p>
					</th>
				</tr>
				<tr valign="top">
					<th scope="row" colspan="2" class="th-full">
						<label>
							<input type="checkbox" name="object_caching_disable" value="1"'.(empty($reportSettings['object_caching_disable']) ? '' : ' checked="checked"').' />
							Disable WordPress object caching
						</label>
						<p class="description">Enable this option if you are encountering memory limit errors while running reports.</p>
					</th>
				</tr>
				<tr valign="top">
					<th scope="row" colspan="2" class="th-full">
						<label>
							<input type="checkbox" name="hm_psr_debug" value="1"'.(empty($reportSettings['hm_psr_debug']) ? '' : ' checked="checked"').' />
							Enable debug mode
						</label>
					</th>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="hm_psr_field_report_css">Report CSS:</label>
					</th>
					<td>
						<textarea id="hm_psr_field_report_css" name="report_css"'.($canEditReportCSS ? '' : ' disabled="disabled"').'>'.esc_html($reportSettings['report_css']).'</textarea>
						<p class="description">Applies to HTML report formats only.'.($canEditReportCSS ? '' : '<br />You do not have the necessary permissions to edit the report preset\'s CSS.').'</p>
					</td>
				</tr>
			</table>
			
			</div> <!-- /hm_psr_tab_panels -->');
			
			//HM_Product_Sales_Report_Pro::savePresetField();
			
			wp_nonce_field('hm-psr-run', 'hm-psr-nonce');
			
			echo('<p class="submit">
				<button type="submit" class="button-primary" name="hm_psr_action" value="run" style="margin-right: 30px;" onclick="jQuery(this).closest(\'form\').attr(\'target\', \'_blank\'); return true;">Download Report</button>

				<input type="email" name="email_to" placeholder="Email address" value="'.htmlspecialchars(get_option('hm_psr_last_email_to', get_bloginfo('admin_email'))).'" />
				<button type="submit" class="button-primary" name="hm_psr_action" value="email" onclick="jQuery(this).closest(\'form\').attr(\'target\', \'\'); return true;">Email Report</button>
			</p>

		</form>');
		
		HM_Product_Sales_Report_Pro::pluginCreditBox();
		

		
echo('
	</div>
	
	<script type="text/javascript" src="'.plugins_url('../js/hm-product-sales-report.js?v='.TROW_VERSION, __FILE__).'"></script>
');
?>