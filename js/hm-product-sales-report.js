jQuery(document).ready(function($) {
	$('#hm_sbp_field_report_time').change(function() {
			$('.hm_sbp_custom_time').toggle(this.value == 'custom');
	});
	$('#hm_sbp_field_report_time').change();

	$('#hm_sbp_field_report_preset').change(function() {
		$(this).closest('form').submit();
	});

	$('#hm-psr-button-add-field').click(function() {
		var $fieldsSelect = $('#hm_psr_custom_field');
		var $selectedOption = $fieldsSelect.find('option:selected:first');
		var isOtherOption = $selectedOption.hasClass('hm-psr-select-other-option');
		if (isOtherOption) {
			var otherFieldName = $fieldsSelect.siblings('.hm-psr-select-other-field:first').val();
			if (!otherFieldName.length) {
				return;
			}
		}
		hm_psr_add_custom_field($selectedOption.val(), isOtherOption ? otherFieldName : $selectedOption.html());
		if (isOtherOption) {
			$fieldsSelect.val($fieldsSelect.find('option:first').val()).change();
		}
	});
	
	$('.hm_psr_variations_fld').change(function() {
		if ($(this).val() == 0) {
			$('#hm_psr_report_fields .hm_psr_variation_field input[type="checkbox"]').prop('checked', false).prop('disabled', true);
			$('#hm_psr_custom_field .hm_psr_variation_field').prop('disabled', true);
		} else {
			$('#hm_psr_report_fields .hm_psr_variation_field input[type="checkbox"]').prop('disabled', false);
			$('#hm_psr_custom_field .hm_psr_variation_field').prop('disabled', false);
		}
	});
	$('.hm_psr_variations_fld:checked').change();
	
	$('#hm_psr_order_meta_filter_op').change(function() {
		switch ($(this).val()) {
			case 'NOTEXISTS':
				$('#hm_psr_order_meta_filter_value')
					.hide()
					.val('');
				$('#hm_psr_order_meta_filter_value_2')
					.hide()
					.find('input')
					.val('');
				break;
			case 'BETWEEN':
				$('#hm_psr_order_meta_filter_value,#hm_psr_order_meta_filter_value_2')
					.show();
				break;
			default:
				$('#hm_psr_order_meta_filter_value')
					.show();
				$('#hm_psr_order_meta_filter_value_2')
					.hide()
					.find('input')
					.val('');
		}
	});
	$('#hm_psr_order_meta_filter_op').change();
	
	
	$('#hm_psr_customer_meta_filter_op').change(function() {
		switch ($(this).val()) {
			case 'NOTEXISTS':
				$('#hm_psr_customer_meta_filter_value')
					.hide()
					.val('');
				$('#hm_psr_customer_meta_filter_value_2')
					.hide()
					.find('input')
					.val('');
				break;
			case 'BETWEEN':
				$('#hm_psr_customer_meta_filter_value,#hm_psr_customer_meta_filter_value_2')
					.show();
				break;
			default:
				$('#hm_psr_customer_meta_filter_value')
					.show();
				$('#hm_psr_customer_meta_filter_value_2')
					.hide()
					.find('input')
					.val('');
		}
	});
	$('#hm_psr_customer_meta_filter_op').change();
	
	
	$('#hm_psr_product_meta_filter_op').change(function() {
		$('#hm_psr_product_meta_filter_value_2').toggle($(this).val() == 'BETWEEN');
	});
	$('#hm_psr_product_meta_filter_op').change();
	
	$('#hm_psr_report_fields').sortable({
		update: hm_psr_update_sort_options
	});
	
	$('#hm_psr_field_groupby').change(function() {
		if ($(this).val() == '') {
			jQuery('#hm_psr_report_fields .hm_psr_groupby_field').css('display', 'none').children('input[type="hidden"]').remove();
		} else {
			jQuery('#hm_psr_report_fields .hm_psr_groupby_field').remove();
			hm_psr_add_custom_field('builtin::groupby_field', $('#hm_psr_field_groupby option:selected').html());
		}
	});
	
	$('#hm_psr_tabs > a').click(function() {
		$('#hm_psr_tab_panels > table').hide();
		$('#hm_psr_tabs > a').removeClass('nav-tab-active');
		$('#' + $(this).attr('id') + '_panel').show();
		$(this).addClass('nav-tab-active');
	});
	if (location.hash.length > 1 && $('#hm_psr_tab_' + location.hash.substring(1)).length) {
		$('#hm_psr_tab_' + location.hash.substring(1)).click();
	} else {
		$('#hm_psr_tabs > a:first-child').click();
	}
	
	$('#hm_psr_product_tag_filter_select').change(function() {
		var thisTag = $(this).val();
		if (thisTag != '') {
			var currentTags = $('#hm_psr_product_tag_filter').val();
			$('#hm_psr_product_tag_filter').val((currentTags == '' ? '' : currentTags + ', ') + thisTag);
		}
		$(this).val('');
	});
	
	$('#hm_psr_field_include_totals').change(function() {
		if ($(this).is(':checked')) {
			$('.hm_psr_field_cb').change();
		} else {
			$('.hm_psr_total_field').hide();
		}
	});
	$('#hm_psr_field_include_totals').change();
	$('#hm_psr_report_fields').on('change', '.hm_psr_field_cb', null, function() {
		if ($('#hm_psr_field_include_totals').is(':checked')) {
			$(this).siblings('.hm_psr_total_field').css('display', ($(this).is(':checked') ? 'block' : 'none'));
		}
	});
	
	$('.hm-psr-date-dynamic-toggle').click(function() {
		var $this = $(this);
		var $dynamicField = $this.siblings('.hm-psr-date-dynamic-field');
		if ($dynamicField.hasClass('hidden')) {
			$dynamicField.siblings('input').prop('disabled', true);
			$dynamicField.val('today').removeClass('hidden').change();
			$this.text('fixed date');
		} else {
			$dynamicField.addClass('hidden').val('');
			$dynamicField.siblings('input').prop('disabled', false);
			$this.text('dynamic date');
		}
	});
	
	$('.hm-psr-date-dynamic-field').change(function() {
		var $this = $(this);
		$.post(ajaxurl, {
			'action': 'trow_psr_calc_dynamic_date',
			'date': $this.val()
		}, function(result) {
			if (result.success && result.data) {
				$this.siblings('input').val(result.data);
			} else {
				alert('Unable to calculate date from expression: ' + $this.val());
			}
		}).fail(function() {
			alert('Unable to calculate date from expression: ' + $this.val());
		});
	});
	
	$('#hm_psr_field_format').change(function() {
		$('.hm_psr_format_options').hide();
		$('#hm_psr_format_options_' + $(this).val()).show();
	});
	$('#hm_psr_field_format').change();
	
	hm_psr_update_sort_options();
	
	$('#hm_psr_tab_panels .hm-psr-select-other')
		.append($('<option>').html('Other...').addClass('hm-psr-select-other-option'))
		.closest('select')
		.change(function() {
			var $this = $(this);
			var $selectedOtherOption = $this.find('option.hm-psr-select-other-option:selected')
			if ($this.hasClass('hm-psr-select-other-selected')) {
					$this.removeClass('hm-psr-select-other-selected').siblings('.hm-psr-select-other-field').remove();
					$selectedOtherOption.html('Other...');
			}
			if ($selectedOtherOption.length) {
				var selectName = $this.attr('name');
				var $optGroup = $selectedOtherOption.closest('optgroup');
				var $otherField = $('<input>')
									.attr({placeholder: '(enter value)', type: 'text'})
									.addClass('hm-psr-select-other-field')
									.change(function() {
										var $this = $(this);
										var thisValue = $(this).val();
										var otherFieldPrefix = $optGroup.data('hm-psr-other-field-prefix');
										$selectedOtherOption.val((otherFieldPrefix ? otherFieldPrefix : '') + thisValue);
										
										// Special handling for the Group By field
										if ($this.siblings('#hm_psr_field_groupby').length) {
											$('#hm_psr_report_fields .hm_psr_groupby_field .hm_psr_field_name').val(thisValue);
										}
									});
				
				$selectedOtherOption.text(($optGroup.length ? $optGroup.attr('label') + ' - ' : '') + 'Other:');
				
				$this
					.addClass('hm-psr-select-other-selected')
					.after($otherField);
			}
		});
});

function hm_psr_add_custom_field(fieldId, fieldName) {
	var customFieldBox = jQuery('#hm_psr_report_fields > div:last-child').clone().css('display', 'block').removeClass('hm_psr_groupby_field hm_psr_variation_field');
	customFieldBox.children('input[type="hidden"]').attr('value', fieldId);
	customFieldBox.children('input[type="text"]').attr('name', 'field_names[' + fieldId + ']').val(fieldName);
	if (fieldId == 'builtin::groupby_field') {
		customFieldBox.addClass('hm_psr_groupby_field');
	} else if (fieldId == 'builtin::variation_id' || fieldId == 'builtin::variation_sku' || fieldId == 'builtin::variation_attributes' || fieldId.substr(0, 11) == 'variation::') {
		customFieldBox.addClass('hm_psr_variation_field');
	}
	customFieldBox.children('.hm_psr_total_field').toggleClass('no-total', jQuery('#hm_psr_custom_field option[value="' + fieldId.replace('"', '\\"') + '"]').hasClass('no-total-field')).children('input').prop('checked', false).attr('value', fieldId);
	jQuery('#hm_psr_report_fields').append(customFieldBox);
	hm_psr_update_sort_options();
}

function hm_psr_remove_field(btn) {
	var $field = jQuery(btn).parent();
	if ($field.hasClass('hm_psr_groupby_field')) {
		alert('This field is the grouping field. To remove this field, please change the Group By setting in the Grouping & Sorting tab.');
		return;
	}
	$field.remove();
	hm_psr_update_sort_options();
}

function hm_psr_update_sort_options() {
	var $select = jQuery('#hm_sbp_field_orderby');
	var currentValue = $select.val();
	$select.empty();
	
	jQuery('#hm_psr_report_fields > div').each(function() {
		var $field = jQuery(this);
		var fieldId = $field.find('input[type="hidden"]').val();
		jQuery('<option>')
			.attr('value', fieldId)
			.text($field.find('input[type="text"]').val())
			.attr('selected', fieldId == currentValue)
			.appendTo($select);
	});
}

