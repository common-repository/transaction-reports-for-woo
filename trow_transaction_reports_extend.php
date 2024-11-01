<?php


class HM_Product_Sales_Report_Pro {

	private static $customFieldNames, $addonFields, $orderFieldNames, $customerFieldNames;

	public static function getReportData($wc_report, $product_ids, $startDate=null, $endDate=null, $refundOrders=false) {
		global $wpdb, $hm_wc_report_extra_sql;
		$hm_wc_report_extra_sql = array();
		
		
		// Based on woocoommerce/includes/admin/reports/class-wc-report-sales-by-product.php
		$dataParams = array(
			'_product_id' => array(
				'type' => 'order_item_meta',
				'order_item_type' => 'line_item',
				'function' => '',
				'name' => 'product_id'
			),
            'order_item_name' => array(
                'type' => 'order_item',
                'function' => 'GROUP_CONCAT',
                'distinct' => true,
                'name' => 'product_name'
            ),
			'_qty' => array(
				'type' => 'order_item_meta',
				'order_item_type' => 'line_item',
				'function' => 'SUM',
				'name' => 'quantity'
			),
			'_line_subtotal' => array(
				'type' => 'order_item_meta',
				'order_item_type' => 'line_item',
				'function' => 'SUM',
				'name' => 'gross'
			),
			'_line_total' => array(
				'type' => 'order_item_meta',
				'order_item_type' => 'line_item',
				'function' => 'SUM',
				'name' => 'gross_after_discount'
			),
			'_line_tax' => array(
				'type' => 'order_item_meta',
				'order_item_type' => 'line_item',
				'function' => 'SUM',
				'name' => 'taxes'
			)
		);
		if (!empty($_POST['variations'])) {
			$dataParams['_variation_id'] = array(
				'type' => 'order_item_meta',
				'order_item_type' => 'line_item',
				'function' => '',
				'name' => 'variation_id'
			);
		}
		foreach ($_POST['fields'] as $field) {
			if (substr($field, 0, 18) == 'order_item_total::') {
				$fieldName = str_replace(array(' ', '-'), '', esc_sql(substr($field, 18))); // Remove spaces for security
				
				$dataParams[$fieldName] = array(
					'type' => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function' => 'SUM',
					'join_type' => 'LEFT',
					'name' => 'order_item_total__'.$fieldName
				);
			} else if ($field == 'builtin::groupby_field' && !empty($_POST['groupby']) && $_POST['groupby'] != 'i_builtin::item_price') {
				if (in_array($_POST['groupby'], array('o_builtin::order_month', 'o_builtin::order_quarter', 'o_builtin::order_year', 'o_builtin::order_date', 'o_builtin::order_day'))) {
					switch ($_POST['groupby']) {
						case 'o_builtin::order_month':
							$sqlFunction = 'MONTH';
							break;
						case 'o_builtin::order_quarter':
							$sqlFunction = 'QUARTER';
							break;
						case 'o_builtin::order_year':
							$sqlFunction = 'YEAR';
							break;
						case 'o_builtin::order_day':
							$sqlFunction = 'DAY';
							break;
						default:
							$sqlFunction = 'DATE';
					}
					$dataParams['post_date'] = array(
						'type' => 'post_data',
						'order_item_type' => 'line_item',
						'function' => $sqlFunction,
						'join_type' => 'LEFT',
						'name' => 'groupby_field'
					);
				} else {
					$fieldName = esc_sql(substr($_POST['groupby'], 2));
					$dataParams[$fieldName] = array(
						'type' => ($_POST['groupby'][0] == 'i' ? 'order_item_meta' : 'meta'),
						'order_item_type' => 'line_item',
						'function' => '',
						'join_type' => 'LEFT',
						'name' => 'groupby_field'
					);
				}
			}
		}
		
		$where = array();
		$where_meta = array();
		if ($product_ids != null) {
			// If there are more than 10,000 product IDs, they should not be filtered in the SQL query
			if (count($product_ids) > 10000) {
				$productIdsPostFilter = true;
			} else {
				$where_meta[] = array(
					'type' => 'order_item_meta',
					'meta_key' => '_product_id',
					'operator' => 'IN',
					'meta_value' => $product_ids
				);
			}
		}
		if (!empty($_POST['exclude_free'])) {
			$where_meta[] = array(
				'meta_key' => '_line_total',
				'meta_value' => 0,
				'operator' => '!=',
				'type' => 'order_item_meta'
			);
		}
		
		if (!empty($_POST['order_meta_filter_on'])) {
			if (in_array($_POST['order_meta_filter_op'], array('=', '!=', '<', '<=', '>', '>=', 'BETWEEN'))) {
				
				$metaValue = (is_numeric($_POST['order_meta_filter_value']) ? $_POST['order_meta_filter_value'] : '\''.esc_sql($_POST['order_meta_filter_value']).'\'');
				if ($_POST['order_meta_filter_op'] == 'BETWEEN') {
					if (is_numeric($_POST['order_meta_filter_value_2'])) {
						$metaValue .= ' AND '.$_POST['order_meta_filter_value_2'];
					} else {
						$metaValue .= ' AND \''.esc_sql($_POST['order_meta_filter_value_2']).'\'';
					}
				}
				
				$hm_wc_report_extra_sql['where'] = (isset($hm_wc_report_extra_sql['where']) ? $hm_wc_report_extra_sql['where'] : '').' AND EXISTS(
					SELECT 1 FROM '.$wpdb->postmeta.' WHERE post_id=posts.'.($refundOrders ? 'post_parent' : 'ID').' AND meta_key=\''.esc_sql($_POST['order_meta_filter_key']).'\' AND meta_value'.(is_numeric($_POST['order_meta_filter_value']) ? '*1' : '').' '.$_POST['order_meta_filter_op'].' '.$metaValue.')';
					
				/*$where_meta[] = array(
					'type' => 'order_meta',
					'meta_key' => esc_sql($_POST['order_meta_filter_key']), // Not safe
					'operator' => $_POST['order_meta_filter_op'],
					'meta_value' => $metaValue
				);*/
			} else if ($_POST['order_meta_filter_op'] == 'NOTEXISTS') {
				$hm_wc_report_extra_sql['where'] = (isset($hm_wc_report_extra_sql['where']) ? $hm_wc_report_extra_sql['where'] : '').' AND NOT EXISTS(
					SELECT 1 FROM '.$wpdb->postmeta.' WHERE post_id=posts.'.($refundOrders ? 'post_parent' : 'ID').' AND meta_key=\''.esc_sql($_POST['order_meta_filter_key']).'\')';
			}
		}
		
		// Customer meta filtering
		$customerMetaFilterSql = self::getCustomerMetaFilterSql($refundOrders);
		if (!empty($customerMetaFilterSql)) {
			$hm_wc_report_extra_sql['where'] = (isset($hm_wc_report_extra_sql['where']) ? $hm_wc_report_extra_sql['where'] : '').$customerMetaFilterSql;
		}
		
		if (!empty($_POST['customer_role'])) {
			/*$where_meta[] = array(
				'type' => 'order_meta',
				'meta_key' => '_customer_user',
				'operator' => ($_POST['customer_role'] == -1 ? '=' : 'IN'),
				'meta_value' => ($_POST['customer_role'] == -1 ? 0 : get_users(array('role' => esc_sql($_POST['customer_role']), 'fields' => 'ID')))
			);*/
			
			if ($_POST['customer_role'] != -1) {
				$userIds = get_users(array('role' => esc_sql($_POST['customer_role']), 'fields' => 'ID'));
			}
			$hm_wc_report_extra_sql['where'] = (isset($hm_wc_report_extra_sql['where']) ? $hm_wc_report_extra_sql['where'] : '').' AND EXISTS(
				SELECT 1 FROM '.$wpdb->postmeta.' WHERE post_id=posts.'.($refundOrders ? 'post_parent' : 'ID').' AND meta_key=\'_customer_user\' AND '.($_POST['customer_role'] == -1 ? 'meta_value = 0' : (empty($userIds) ? 'FALSE' : 'meta_value IN ('.implode(',', $userIds).')')).')';
		}
		
		if ($_POST['report_time'] == 'custom') {
			$where[] = array(
				'key' => 'post_date',
				'operator' => '>=',
				'value' => date('Y-m-d H:i:s', $startDate)
			);
			$where[] = array(
				'key' => 'post_date',
				'operator' => '<',
				'value' => date('Y-m-d H:i:s', $endDate)
			);
		}
		
		$groupBy = 'product_id';
		if (!empty($_POST['variations'])) {
			$groupBy .= ',IF(variation_id="",0,variation_id)';
		}
		if (!empty($_POST['groupby'])) {
			switch ($_POST['groupby']) {
				case 'i_builtin::item_price':
					$groupBy .= ',ROUND(order_item_meta__line_subtotal.meta_value / order_item_meta__qty.meta_value, 2)';
					break;
				default:
					$groupBy .= ',groupby_field';
			}
		}
		
		
		$reportOptions = array(
			'data' => $dataParams,
			'nocache' => true, // added by JH 2019-12-17
			'query_type' => 'get_results',
			'group_by' => $groupBy,
			'limit' => (!empty($_POST['limit_on']) && is_numeric($_POST['limit']) ? $_POST['limit'] : ''),
			'filter_range' => ($_POST['report_time'] != 'all' && $_POST['report_time'] != 'custom'),
			'order_types' => array($refundOrders ? 'shop_order_refund' : 'shop_order'),
			/*'order_status' => $orderStatuses,*/ // Order status filtering is set via filter
			'where_meta' => $where_meta
		);
		
		if (!empty($_POST['hm_psr_debug'])) {
			$reportOptions['debug'] = true;
		}
		
		if (!empty($where)) {
			$reportOptions['where'] = $where;
		}
		
		// Order status filtering
		// Order status array has been sanitized and checked non-empty in trow_sbp_export_body() function
		$statusesStr = '';
		foreach ($_POST['order_statuses'] as $i => $orderStatus) {
			$statusesStr .= ($i ? ',\'' : '\'').esc_sql($orderStatus).'\'';
		}
		$hm_wc_report_extra_sql['where'] = (isset($hm_wc_report_extra_sql['where']) ? $hm_wc_report_extra_sql['where'] : '').' AND posts.post_status'.
			($refundOrders ? '=\'wc-completed\' AND EXISTS(SELECT 1 FROM '.$wpdb->posts.' WHERE ID=posts.post_parent AND post_status IN('.$statusesStr.'))' :
			' IN('.$statusesStr.')');
		
		@$wpdb->query('SET SESSION sort_buffer_size='.absint($_POST['db_sort_buffer_size']*1000));
		
		//ob_start();
		$result = $wc_report->get_order_report_data($reportOptions);
		//file_put_contents(__DIR__.'/debug.log', ob_get_clean());
		
		// Do post-query product ID filtering, if necessary
		if (!empty($result) && !empty($productIdsPostFilter)) {
			foreach ($result as $key => $product) {
				if (!in_array($product->product_id, $product_ids)) {
					unset($result[$key]);
				}
			}
		}
		
		return $result;
		
	}
	
	/*
		The following function contains code copied from from WooCommerce; see license/woocommerce-license.txt for copyright and licensing information
		Modified by Jonathan Hall and/or others 2020-02-13 and earlier
	*/
	public static function getShippingReportData($wc_report, $startDate, $endDate, $taxes=false, $refundOrders=false) {
		global $wpdb, $hm_wc_report_extra_sql;
		$hm_wc_report_extra_sql = array();
	
		// Based on woocoommerce/includes/admin/reports/class-wc-report-sales-by-product.php
		
		$dataParams = array(
			'method_id' => array(
				'type' => 'order_item_meta',
				'order_item_type' => 'shipping',
				'function' => '',
				'name' => 'product_id'
			),
			'cost' => array(
				'type' => 'order_item_meta',
				'order_item_type' => 'shipping',
				'function' => 'SUM',
				'name' => 'gross'
			),
		);
		if (!$refundOrders) {
			$dataParams['order_item_id'] = array(
				'type' => 'order_item',
				'order_item_type' => 'shipping',
				'function' => 'COUNT',
				'name' => 'quantity'
			);
		}
		
		foreach ($_POST['fields'] as $field) {
			if ($field == 'builtin::groupby_field') {
				
				if (!empty($_POST['groupby']) && $_POST['groupby'] != 'i_builtin::item_price') {
					if (in_array($_POST['groupby'], array('o_builtin::order_month', 'o_builtin::order_quarter', 'o_builtin::order_year', 'o_builtin::order_date', 'o_builtin::order_day'))) {
						switch ($_POST['groupby']) {
							case 'o_builtin::order_month':
								$sqlFunction = 'MONTH';
								break;
							case 'o_builtin::order_quarter':
								$sqlFunction = 'QUARTER';
								break;
							case 'o_builtin::order_year':
								$sqlFunction = 'YEAR';
								break;
							case 'o_builtin::order_day':
								$sqlFunction = 'DAY';
								break;
							default:
								$sqlFunction = 'DATE';
						}
						$dataParams['post_date'] = array(
							'type' => 'post_data',
							'order_item_type' => 'shipping',
							'function' => $sqlFunction,
							'join_type' => 'LEFT',
							'name' => 'groupby_field'
						);
					} else {
						$fieldName = esc_sql(substr($_POST['groupby'], 2));
						$dataParams[$fieldName] = array(
							'type' => ($_POST['groupby'][0] == 'i' ? 'order_item_meta' : 'meta'),
							'order_item_type' => 'shipping',
							'function' => '',
							'join_type' => 'LEFT',
							'name' => 'groupby_field'
						);
					}
					break;
				}
			}
		}
		
		$where_meta = array();
		if (!empty($_POST['order_meta_filter_on'])) {
			if (in_array($_POST['order_meta_filter_op'], array('=', '!=', '<', '<=', '>', '>=', 'BETWEEN'))) {
				
				$metaValue = esc_sql($_POST['order_meta_filter_value']);
				if (!is_numeric($_POST['order_meta_filter_value'])) {
					$metaValue = '\''.$metaValue.'\'';
				}
				if ($_POST['order_meta_filter_op'] == 'BETWEEN') {
					if (is_numeric($_POST['order_meta_filter_value_2'])) {
						$metaValue .= ' AND '.$_POST['order_meta_filter_value_2'];
					} else {
						$metaValue .= ' AND \''.esc_sql($_POST['order_meta_filter_value_2']).'\'';
					}
				}
				
				$hm_wc_report_extra_sql['where'] = (isset($hm_wc_report_extra_sql['where']) ? $hm_wc_report_extra_sql['where'] : '').' AND EXISTS(
					SELECT 1 FROM '.$wpdb->postmeta.' WHERE post_id=posts.'.($refundOrders ? 'post_parent' : 'ID').' AND meta_key=\''.esc_sql($_POST['order_meta_filter_key']).'\' AND meta_value '.$_POST['order_meta_filter_op'].' '.$metaValue.')';
				
			} else if ($_POST['order_meta_filter_op'] == 'NOTEXISTS') {
				$hm_wc_report_extra_sql['where'] = (isset($hm_wc_report_extra_sql['where']) ? $hm_wc_report_extra_sql['where'] : '').' AND NOT EXISTS(
					SELECT 1 FROM '.$wpdb->postmeta.' WHERE post_id=posts.'.($refundOrders ? 'post_parent' : 'ID').' AND meta_key=\''.esc_sql($_POST['order_meta_filter_key']).'\')';
			}
			
		}
		
		// Customer meta filtering
		$customerMetaFilterSql = self::getCustomerMetaFilterSql($refundOrders);
		if (!empty($customerMetaFilterSql)) {
			$hm_wc_report_extra_sql['where'] = (isset($hm_wc_report_extra_sql['where']) ? $hm_wc_report_extra_sql['where'] : '').$customerMetaFilterSql;
		}
		
		if (!empty($_POST['customer_role'])) {
			/*$where_meta[] = array(
				'type' => 'order_meta',
				'meta_key' => '_customer_user',
				'operator' => ($_POST['customer_role'] == -1 ? '=' : 'IN'),
				'meta_value' => ($_POST['customer_role'] == -1 ? 0 : get_users(array('role' => esc_sql($_POST['customer_role']), 'fields' => 'ID')))
			);*/
			
			if ($_POST['customer_role'] != -1) {
				$userIds = get_users(array('role' => esc_sql($_POST['customer_role']), 'fields' => 'ID'));
			}
			$hm_wc_report_extra_sql['where'] = (isset($hm_wc_report_extra_sql['where']) ? $hm_wc_report_extra_sql['where'] : '').' AND EXISTS(
				SELECT 1 FROM '.$wpdb->postmeta.' WHERE post_id=posts.'.($refundOrders ? 'post_parent' : 'ID').' AND meta_key=\'_customer_user\' AND '.($_POST['customer_role'] == -1 ? 'meta_value = 0' : (empty($userIds) ? 'FALSE' : 'meta_value IN ('.implode(',', $userIds).')')).')';
		}
		
		$reportParams = array(
			'data' => $dataParams,
			'nocache' => true, // added by JH 2019-12-17
			'query_type' => 'get_results',
			'group_by' => 'product_id'.(empty($_POST['groupby']) ? '' : ($_POST['groupby'] == 'i_builtin::item_price' ? ',(order_item_meta_cost.meta_value * 1)' : ',groupby_field')),
			'filter_range' => ($_POST['report_time'] != 'all' && $_POST['report_time'] != 'custom'),
			'order_types' => array($refundOrders ? 'shop_order_refund' : 'shop_order'),
			'where_meta' => $where_meta
		);
		
		if (!empty($_POST['hm_psr_debug'])) {
			$reportParams['debug'] = true;
		}
		
		if ($_POST['report_time'] == 'custom') {
			$reportParams['where'] = array(
				array(
					'key' => 'post_date',
					'operator' => '>=',
					'value' => date('Y-m-d H:i:s', $startDate)
				),
				array(
					'key' => 'post_date',
					'operator' => '<',
					'value' => date('Y-m-d H:i:s', $endDate)
				)
			);
		}
		
		// Order status filtering
		// Order status array has been sanitized and checked non-empty in trow_sbp_export_body() function
		$statusesStr = '';
		foreach ($_POST['order_statuses'] as $i => $orderStatus) {
			$statusesStr .= ($i ? ',\'' : '\'').esc_sql($orderStatus).'\'';
		}
		$hm_wc_report_extra_sql['where'] = (isset($hm_wc_report_extra_sql['where']) ? $hm_wc_report_extra_sql['where'] : '').' AND posts.post_status'.
			($refundOrders ? '=\'wc-completed\' AND EXISTS(SELECT 1 FROM '.$wpdb->posts.' WHERE ID=posts.post_parent AND post_status IN('.$statusesStr.'))' :
			' IN('.$statusesStr.')');
		
		$result = $wc_report->get_order_report_data($reportParams);
		
		if ($refundOrders) {
			foreach ($result as $shipping) {
				$shipping->quantity = 0;
			}
		}
		
		if ($taxes) {
			
			$hasShippingItemClass = class_exists('WC_Order_Item_Shipping'); // WC 3.0+
			
			$reportParams['data'] = array(
				'method_id' => array(
					'type' => 'order_item_meta',
					'order_item_type' => 'shipping',
					'function' => '',
					'name' => 'product_id'
				)
			);
			if ($hasShippingItemClass) {
				$reportParams['data']['order_item_id'] = array(
					'type' => 'order_item',
					'order_item_type' => 'shipping',
					'function' => '',
					'name' => 'order_item_id'
				);
			} else {
				$reportParams['data']['taxes'] = array(
					'type' => 'order_item_meta',
					'order_item_type' => 'shipping',
					'function' => '',
					'name' => 'taxes'
				);
			}
			$reportParams['group_by'] = '';
			$taxResult = $wc_report->get_order_report_data($reportParams);
			
			foreach ($result as $shipping) {
				$shipping->taxes = 0;
				foreach ($taxResult as $i => $taxes) {
					if ($taxes->product_id == $shipping->product_id) {
						if ($hasShippingItemClass) {
							$oi = new WC_Order_Item_Shipping($taxes->order_item_id);
							$shipping->taxes += $oi->get_total_tax();
						} else {
							$taxArray = @unserialize($taxes->taxes);
							if (!empty($taxArray)) {
								foreach ($taxArray as $taxItem) {
									$shipping->taxes += $taxItem;
								}
							}
						}
						unset($taxResult[$i]);
					}
				}
			}
		}
		
		return $result;
		
	}
	
	public static function getCustomerMetaFilterSql($refundOrders) {
		global $wpdb;
		
		// Customer meta filtering
		
		if (!empty($_POST['customer_meta_filter_on'])) {
			if (in_array($_POST['customer_meta_filter_op'], array('=', '!=', '<', '<=', '>', '>=', 'BETWEEN'))) {
				
				$metaValue = (is_numeric($_POST['customer_meta_filter_value']) ? $_POST['customer_meta_filter_value'] : '\''.esc_sql($_POST['customer_meta_filter_value']).'\'');
				if ($_POST['customer_meta_filter_op'] == 'BETWEEN') {
					if (is_numeric($_POST['customer_meta_filter_value_2'])) {
						$metaValue .= ' AND '.$_POST['customer_meta_filter_value_2'];
					} else {
						$metaValue .= ' AND \''.esc_sql($_POST['customer_meta_filter_value_2']).'\'';
					}
				}
				
				return ' AND EXISTS(
					SELECT 1 FROM '.$wpdb->postmeta.'
					WHERE post_id=posts.'.($refundOrders ? 'post_parent' : 'ID').'
						AND meta_key=\'_customer_user\'
						AND meta_value IN (
							SELECT user_id FROM '.$wpdb->usermeta.'
							WHERE meta_key=\''.esc_sql($_POST['customer_meta_filter_key']).'\'
								AND meta_value'.(is_numeric($_POST['customer_meta_filter_value']) ? '*1' : '').' '.$_POST['customer_meta_filter_op'].' '.$metaValue.'
						)
				)';
					
			} else if ($_POST['customer_meta_filter_op'] == 'NOTEXISTS') {
				return ' AND NOT EXISTS(
					SELECT 1 FROM '.$wpdb->postmeta.'
					WHERE post_id=posts.'.($refundOrders ? 'post_parent' : 'ID').'
						AND meta_key=\'_customer_user\'
						AND meta_value NOT IN (
							SELECT user_id FROM '.$wpdb->usermeta.'
							WHERE meta_key=\''.esc_sql($_POST['customer_meta_filter_key']).'\'
						)
				)';
			}
		}
		
		return '';
	}
	
	public static function getFormattedVariationAttributes($product) {
		if (is_numeric($product)) {
			$varId = $product;
		} else if (empty($product->variation_id)) {
			return '';
		} else {
			$varId = $product->variation_id;
		}
		if (function_exists('wc_get_product_variation_attributes')) {
			$attr = wc_get_product_variation_attributes($varId);
		} else {
			$product = wc_get_product($varId);
			if (empty($product))
				return '';
			$attr = $product->get_variation_attributes();
		}
		foreach ($attr as $i => $v) {
			if ($v === '')
				unset($attr[$i]);
		}
		return urldecode(implode(', ', $attr));
	}
	
	public static function getCustomFieldNames($includeDisplay=false, $productFieldsOnly=false) {
		global $wpdb;
		if (!isset(HM_Product_Sales_Report_Pro::$customFieldNames) || $productFieldsOnly) {
			$customFields = $wpdb->get_col('SELECT DISTINCT meta_key FROM (
												SELECT meta_key
												FROM '.$wpdb->prefix.'postmeta
												JOIN '.$wpdb->prefix.'posts ON (post_id=ID)
												WHERE post_type="product"
												ORDER BY ID DESC
												LIMIT 10000
											) fields', 0);
			if ($productFieldsOnly)
				return $customFields;
			
			HM_Product_Sales_Report_Pro::$customFieldNames = array(
				'Product' => array_combine($customFields, $customFields),
				'Product Taxonomies' => array(),
				'Product Variation' => array(),
				'Order Item' => array()
			);
			
			foreach (get_object_taxonomies('product') as $taxonomy) {
				HM_Product_Sales_Report_Pro::$customFieldNames['Product Taxonomies']['taxonomy::'.$taxonomy] = $taxonomy;
			}
			
			$variationFields = $wpdb->get_col('SELECT DISTINCT meta_key FROM (
													SELECT meta_key
													FROM '.$wpdb->prefix.'postmeta
													JOIN '.$wpdb->prefix.'posts ON (post_id=ID)
													WHERE post_type="product_variation"
													ORDER BY ID DESC
													LIMIT 10000
												) fields', 0);
			foreach ($variationFields as $variationField)
				HM_Product_Sales_Report_Pro::$customFieldNames['Product Variation']['variation::'.$variationField] = 'Variation '.$variationField;
			
			$skipOrderItemFields = array('_product_id', '_qty', '_line_subtotal', '_line_total', '_line_tax', '_variation_id', '_line_tax_data', '_tax_class', '_refunded_item_id');
			$orderItemFields = $wpdb->get_col('SELECT DISTINCT meta_key FROM (
													SELECT meta_key
													FROM '.$wpdb->prefix.'woocommerce_order_itemmeta
													JOIN '.$wpdb->prefix.'woocommerce_order_items USING (order_item_id)
													WHERE order_item_type=\'line_item\'
													ORDER BY order_item_id DESC
													LIMIT 10000
												) fields', 0);
			foreach ($orderItemFields as $orderItemField) {
				if (!in_array($orderItemField, $skipOrderItemFields) && !empty($orderItemField) && strpos($orderItemField, ' ') === false && strpos($orderItemField, '-') === false) {
					HM_Product_Sales_Report_Pro::$customFieldNames['Order Item']['order_item_total::'.$orderItemField] = 'Total Order Item '.$orderItemField;
				}
			}
		}
		return HM_Product_Sales_Report_Pro::$customFieldNames;
	}
	
	/*
		Get fields added by other plugins.
		Plugins hooked to "hm_psr_addon_fields" must add their fields to the array in the following format:
			my_addon_field_id => array(
				'label' => 'My Addon Field',
				'cb' => my_callback_function
			);
		where "my_callback_function" takes the following arguments:
			$product: the product object returned by $wc_report->get_order_report_data(), or the product ID for zero-sales products
			$type: null for regular products, 'shipping' for shipping items, or 'nil' for zero-sales products
		and returns the field value to include in the report for the given product.
	*/
	public static function getAddonFields() {
		if (!isset(HM_Product_Sales_Report_Pro::$addonFields)) {
			HM_Product_Sales_Report_Pro::$addonFields = apply_filters('hm_psr_addon_fields', array());
		}
		return HM_Product_Sales_Report_Pro::$addonFields;
	}
	
	public static function getOrderFieldNames() {
		global $wpdb;
		if (!isset(HM_Product_Sales_Report_Pro::$orderFieldNames)) {
			HM_Product_Sales_Report_Pro::$orderFieldNames = $wpdb->get_col('
					SELECT DISTINCT meta_key FROM (
						SELECT meta_key
						FROM '.$wpdb->prefix.'postmeta
						JOIN '.$wpdb->prefix.'posts ON (post_id=ID)
						WHERE post_type="shop_order"
						ORDER BY ID DESC
						LIMIT 10000
					) fields', 0);
		}
		return HM_Product_Sales_Report_Pro::$orderFieldNames;
	}
	
	public static function getCustomerFieldNames() {
		global $wpdb;
		if (!isset(HM_Product_Sales_Report_Pro::$customerFieldNames)) {
			HM_Product_Sales_Report_Pro::$customerFieldNames = $wpdb->get_col('
					SELECT DISTINCT meta_key FROM (
						SELECT meta_key
						FROM '.$wpdb->usermeta.'
						ORDER BY user_id DESC
						LIMIT 10000
					) fields', 0);
		}
		return HM_Product_Sales_Report_Pro::$customerFieldNames;
	}
	
	public static function loadPresetField($savedReportSettings) {
		echo('<form action="" method="post">
				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<label for="hm_sbp_field_report_preset">Load Preset:</label>
						</th>
						<td>
							<select name="r" id="hm_sbp_field_report_preset">');
		foreach ($savedReportSettings as $i => $reportPreset) {
			echo('<option value="'.$i.'"'.(isset($_POST['r']) && $_POST['r'] == $i ? ' selected="selected"' : '').'>'.($i == 0 ? '[Last used settings]' : htmlspecialchars($reportPreset['preset_name'])).'</option>');
		}
		echo('				</select>
							<button type="submit" name="op" value="preset-del" class="button-primary">Delete Preset</button>
						</td>
					</tr>
				</table>
			</form>');
	}
	
	public static function savePresetField() {
		echo('<table class="form-table">
				<tr valign="top">
					<th scope="row">
						<label for="hm_sbp_field_save_preset">Create New Preset:</label>
					</th>
					<td>
						<input type="text" name="save_preset" id="hm_sbp_field_save_preset" placeholder="Preset Name" />
					</td>
				</tr>
			</table>');
	}
	
	/*public static function savePreset(&$savedReportSettings) {
		if (!empty($_POST['save_preset']))
			$savedReportSettings[] = array_merge($savedReportSettings[0], array('preset_name' => strip_tags($_POST['save_preset'])));
	}*/
	
	
	
	
	
	// Following code copied from Easy Digital Downloads Software Licensing addon - see comment near the top of this file for details - modified by Jonathan Hall and/or others 2019-09-10 and earlier
	
	public static function licenseCheck() {
		if( isset( $_POST['hm_psr_license_deactivate'] )) {
			HM_Product_Sales_Report_Pro::deactivateLicense();
		}
	
		if (get_option('hm_psr_license_status', 'invalid') == 'valid') {
			return true;
		} else {
			if( isset( $_POST['hm_psr_license_activate'] ) && !empty($_POST['hm_psr_license_key']) && ctype_alnum($_POST['hm_psr_license_key']) ) {
				update_option('hm_psr_license_key', trim($_POST['hm_psr_license_key']));
				HM_Product_Sales_Report_Pro::activateLicense();
				if (get_option('hm_psr_license_status', 'invalid') == 'valid')
					return true;
			}
			
			echo('
			<div style="background-color: #fff; border: 1px solid #ccc; padding: 20px; display: inline-block;">
				<form action="" method="post">
			');
			wp_nonce_field( 'hm_psr_license_activate_nonce', 'hm_psr_license_activate_nonce' );
			echo('
					<label for="hm_psr_license_activate" style="display: block; margin-bottom: 10px;">Please enter the license key provided when you purchased the plugin:</label>
					<input type="text" id="hm_psr_license_key" name="hm_psr_license_key" />
					<button type="submit" name="hm_psr_license_activate" value="1" class="button-primary">Activate</button>
				</form>
			</div>
			');
			return false;
		}
	}
	
	public static function activateLicense() {
	
		// run a quick security check
		if( ! check_admin_referer( 'hm_psr_license_activate_nonce', 'hm_psr_license_activate_nonce' ) )
			return; // get out if we didn't click the Activate button

		// retrieve the license
		$license = trim( get_option( 'hm_psr_license_key' ) );

		// data to send in our API request
		$api_params = array(
			'edd_action'=> 'activate_license',
			'license' 	=> $license,
			'item_name' => urlencode( TROW_ITEM_NAME ), // the name of product in EDD
			'url'       => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post( TROW_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
		
		// make sure the response came back okay
		if ( is_wp_error( $response ) )
			return false;

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "valid" or "invalid"

		update_option( 'hm_psr_license_status', $license_data->license );

	}
	
	public static function deactivateLicense() {

		// run a quick security check
		if( ! check_admin_referer( 'hm_psr_license_deactivate_nonce', 'hm_psr_license_deactivate_nonce' ) )
			return; // get out if we didn't click the dectivate button

		// retrieve the license from the database
		$license = trim( get_option( 'hm_psr_license_key' ) );

		// data to send in our API request
		$api_params = array(
			'edd_action'=> 'deactivate_license',
			'license' 	=> $license,
			'item_name' => urlencode( TROW_ITEM_NAME ), // the name product in EDD
			'url'       => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post( TROW_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) )
			return false;

		delete_option( 'hm_psr_license_status' );
		delete_option( 'hm_psr_license_key' );
	}
	
	public static function pluginCreditBox() {
		echo('<b style="background: white; padding: 12px;"><a href="https://sitefreelancing.com/product/transaction-reports-for-woo/">Buy Pro</a> to get quick comparative Insight</b>');
	}
}
	


if( !class_exists( 'HM_PSR_EDD_SL_Plugin_Updater' ) ) {
	// load our custom updater
	include( dirname( __FILE__ ) . '/EDD_SL_Plugin_Updater.php' );
}
function hm_psr_register_option() {
	// creates our settings in the options table
	register_setting('hm_psr_license', 'hm_psr_license_key', 'hm_psr_sanitize_license' );
}
add_action('admin_init', 'hm_psr_register_option');

function hm_psr_plugin_updater() {

	// retrieve our license key from the DB
	$license_key = trim( get_option( 'hm_psr_license_key' ) );

	// setup the updater
	$edd_updater = new HM_PSR_EDD_SL_Plugin_Updater( TROW_STORE_URL, dirname(__FILE__).'/trow_transaction-reports-for-woo.php', array(
			'version' 	=> TROW_VERSION,		// current version number
			'license' 	=> $license_key, 		// license key (used get_option above to retrieve from DB)
			'item_name' => TROW_ITEM_NAME, 	// name of plugin
			'author' 	=> 'walia1' 		// author of this plugin
		)
	);

}
add_action( 'admin_init', 'hm_psr_plugin_updater', 0 );

function hm_psr_sanitize_license( $new ) {
	$old = get_option( 'hm_psr_license_key' );
	if( $old && $old != $new ) {
		delete_option( 'hm_psr_license_status' ); // new license has been entered, so must reactivate
	}
	return $new;
}

// End code copied from Easy Digital Downloads Software Licensing addon
?>