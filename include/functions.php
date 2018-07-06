<?php

function is_between($data)
{
	$out = false;

	$value_min = $data['value'][0];
	$value_max = isset($data['value'][1]) ? $data['value'][1] : "";
	$compare_min = $data['compare'][0];
	$compare_max = $data['compare'][1];

	if($value_min >= $compare_min && $value_min <= $compare_max)
	{
		$out = true;
	}

	else if($value_max != '' && $value_max >= $compare_min && $value_max <= $compare_max)
	{
		$out = true;
	}

	if(isset($data['value'][1]))
	{
		$value_max = $data['value'][1];

		if($compare_min >= $value_min && $compare_min <= $value_max)
		{
			$out = true;
		}

		else if($compare_max >= $value_min && $compare_max <= $value_max)
		{
			$out = true;
		}
	}

	return $out;
}

if(!function_exists('get_list_navigation'))
{
	function get_list_navigation($resultPagination)
	{
		global $wpdb, $intLimitAmount, $strSearch;

		$out = "";

		$rowsPagination = $wpdb->num_rows;

		if($rowsPagination > $intLimitAmount || $strSearch != '')
		{
			$out .= "<form method='post' action='".preg_replace("/\&paged\=\d+/", "", $_SERVER['REQUEST_URI'])."'>
				<p class='search-box'>"
					//."<input type='search' name='s' value='".$strSearch."'>"
					.show_textfield(array('type' => 'search', 'name' => 's', 'value' => $strSearch, 'placeholder' => __("Search for", 'lang_webshop'), 'xtra' => " autocomplete='off'"))
					.show_button(array('text' => __("Search", 'lang_webshop'), 'class' => "button"))
				."</p>
			</form>";
		}

		if($rowsPagination > 0)
		{
			$pagination_obj = new pagination();

			$out .= $pagination_obj->show(array('result' => $resultPagination));
		}

		return $out;
	}
}

function init_webshop()
{
	global $wpdb;

	if(!session_id())
	{
		@session_start();
	}

	$name_products = get_option_or_default('setting_webshop_replace_products', __("Products", 'lang_webshop'));
	$name_categories = get_option_or_default('setting_webshop_replace_categories', __("Categories", 'lang_webshop'));
	$name_doc_types = get_option_or_default('setting_webshop_replace_doc_types', __("Filters", 'lang_webshop'));

	$name_location = __("Location", 'lang_webshop');
	$name_customers = __("Customers", 'lang_webshop');
	$name_delivery_type = __("Delivery Type", 'lang_webshop');

	$slug_categories = get_option_or_default('setting_webshop_replace_categories_slug', "c");
	$slug_products = get_option_or_default('setting_webshop_replace_products_slug', "w");

	$labels = array(
		'name' => _x($name_categories, 'post type general name'),
		'menu_name' => $name_categories
	);

	$args = array(
		'labels' => $labels,
		'public' => true,
		'show_in_menu' => false,
		'show_in_nav_menus' => false,
		'exclude_from_search' => true,
		'supports' => array('title', 'editor', 'excerpt', 'page-attributes'),
		'hierarchical' => true,
		'has_archive' => false,
		'rewrite' => array(
			'slug' => $slug_categories,
		),
	);

	register_post_type('mf_categories', $args);

	$labels = array(
		'name' => _x($name_products, 'post type general name'),
		'menu_name' => $name_products
	);

	$args = array(
		'labels' => $labels,
		'public' => true,
		'show_in_menu' => false,
		'show_in_nav_menus' => false,
		'supports' => array('title', 'editor', 'excerpt', 'author'),
		'hierarchical' => true,
		'has_archive' => false,
		'rewrite' => array(
			'slug' => $slug_products,
		),
	);

	register_post_type('mf_products', $args);

	$labels = array(
		'name' => _x($name_doc_types, 'post type general name'),
		'menu_name' => $name_doc_types
	);

	$args = array(
		'labels' => $labels,
		'public' => true,
		'show_in_menu' => false,
		'show_in_nav_menus' => false,
		'exclude_from_search' => true,
		'supports' => array('title', 'page-attributes'),
		'hierarchical' => true,
		'has_archive' => false,
	);

	register_post_type('mf_document_type', $args);

	$labels = array(
		'name' => _x($name_location, 'post type general name'),
		'menu_name' => $name_location
	);

	$args = array(
		'labels' => $labels,
		'public' => true,
		'show_in_menu' => false,
		'show_in_nav_menus' => false,
		'exclude_from_search' => true,
		'supports' => array('title', 'page-attributes'),
		'hierarchical' => true,
		'has_archive' => false,
	);

	register_post_type('mf_location', $args);

	$labels = array(
		'name' => _x($name_customers, 'post type general name'),
		'menu_name' => $name_customers
	);

	$args = array(
		'labels' => $labels,
		'public' => false,
		'exclude_from_search' => true,
		'supports' => array('title'),
		'hierarchical' => true,
		'has_archive' => false,
		'show_in_menu' => false,
	);

	register_post_type('mf_customers', $args);

	$labels = array(
		'name' => _x($name_delivery_type, 'post type general name'),
		'menu_name' => $name_delivery_type
	);

	$args = array(
		'labels' => $labels,
		'public' => false,
		'exclude_from_search' => true,
		'supports' => array('title'),
		'hierarchical' => true,
		'has_archive' => false,
		'show_in_menu' => false,
	);

	register_post_type('mf_delivery_type', $args);

	flush_rewrite_rules();
}

function uninit_webshop()
{
	@session_destroy();
}

function widgets_webshop()
{
	register_widget('widget_webshop_form');
	register_widget('widget_webshop_list');
	register_widget('widget_webshop_favorites');
	register_widget('widget_webshop_recent');
}

function count_orders_webshop($id = 0)
{
	global $wpdb;

	$count_message = "";

	$last_viewed = get_user_meta(get_current_user_id(), 'meta_orders_viewed', true);

	$result = $wpdb->get_results($wpdb->prepare("SELECT orderID FROM ".$wpdb->prefix."webshop_order WHERE orderCreated > %s", $last_viewed));
	$rows = $wpdb->num_rows;

	if($rows > 0)
	{
		$count_message = "&nbsp;<span class='update-plugins' title='".__("Unread orders", 'lang_webshop')."'>
			<span>".$rows."</span>
		</span>";
	}

	return $count_message;
}

function get_map_visibility_for_select()
{
	return array(
		'everywhere' => __("Everywhere", 'lang_webshop'),
		'search' => __("Only in search view", 'lang_webshop'),
		'single' => __("Only on single page", 'lang_webshop'),
		'nowhere' => __("Nowhere", 'lang_webshop'),
	);
}

function settings_webshop()
{
	global $wpdb;

	$options_area_orig = $options_area = __FUNCTION__;

	$obj_webshop = new mf_webshop();
	$ghost_post_name = $obj_webshop->get_post_name_for_type('ghost');

	$name_product = get_option_or_default('setting_webshop_replace_product', __("Product", 'lang_webshop'));

	//Generic
	############################
	add_settings_section($options_area, "", $options_area."_callback", BASE_OPTIONS_PAGE);

	$arr_settings = array();
	$arr_settings['setting_webshop_replace_webshop'] = __("Replace Text", 'lang_webshop');
	$arr_settings['setting_webshop_replace_product'] = __("Replace Text", 'lang_webshop');
	$arr_settings['setting_webshop_replace_products'] = __("Replace Text", 'lang_webshop');
	$arr_settings['setting_webshop_replace_categories'] = __("Replace Text", 'lang_webshop');
	$arr_settings['setting_webshop_replace_doc_types'] = __("Replace Text", 'lang_webshop');

	//$arr_settings['setting_product_default_image'] = __("Default Image", 'lang_webshop');
	$arr_settings['setting_webshop_replace_products_slug'] = sprintf(__("Replace %s slug with", 'lang_webshop'), strtolower($name_product));

	$wpdb->get_results("SELECT ID FROM ".$wpdb->posts." WHERE post_type = 'mf_categories' AND post_status = 'publish' LIMIT 0, 1");

	if($wpdb->num_rows > 0)
	{
		$name_categories = get_option_or_default('setting_webshop_replace_categories', __("Categories", 'lang_webshop'));

		$arr_settings['setting_show_categories'] = sprintf(__("Show %s on site", 'lang_webshop'), $name_categories);
		$arr_settings['setting_webshop_replace_categories_slug'] = sprintf(__("Replace %s slug with", 'lang_webshop'), strtolower($name_categories));
	}

	if(is_plugin_active("mf_form/index.php"))
	{
		$arr_settings['setting_webshop_payment_form'] = __("Payment Form", 'lang_webshop');

		/*if(get_option('setting_webshop_payment_form') > 0)
		{
			$arr_settings['setting_webshop_require_payment'] = __("Require Payment Below", 'lang_webshop');
		}*/
	}

	$arr_settings['setting_local_storage'] = __("Local Storage", 'lang_webshop');

	show_settings_fields(array('area' => $options_area, 'settings' => $arr_settings));
	############################

	//Search
	############################
	$options_area = $options_area_orig."_search";

	add_settings_section($options_area, "", $options_area."_callback", BASE_OPTIONS_PAGE);

	$arr_settings = array();
	$arr_settings['setting_webshop_display_sort'] = __("Display Sort", 'lang_webshop');

	$setting_webshop_display_sort = get_option('setting_webshop_display_sort');

	/*if($setting_webshop_display_sort == 'yes' || is_array($setting_webshop_display_sort) && count($setting_webshop_display_sort) > 1)
	{*/
		$arr_settings['setting_webshop_sort_default'] = __("Sort Default", 'lang_webshop');
	//}

	$arr_settings['setting_webshop_display_filter'] = __("Display Filter", 'lang_webshop');
	$arr_settings['setting_webshop_replace_filter_products'] = __("Replace Text", 'lang_webshop');

	$arr_settings['setting_search_max'] = __("Max results to send quote", 'lang_webshop');
	$arr_settings['setting_replace_search_result_info'] = __("Replace Text", 'lang_webshop');

	$arr_settings['setting_webshop_replace_choose_product'] = __("Replace Text", 'lang_webshop');

	$arr_settings['setting_webshop_switch_icon_on'] = __("Switch Icon", 'lang_webshop')." (".__("On", 'lang_webshop').")";
	$arr_settings['setting_webshop_switch_icon_off'] = __("Switch Icon", 'lang_webshop')." (".__("Off", 'lang_webshop').")";

	if(is_plugin_active("mf_form/index.php"))
	{
		$arr_settings['setting_quote_form'] = __("Form for quote request", 'lang_webshop');
	}

	$arr_settings['setting_show_all_min'] = __("Min results to show number", 'lang_webshop');
	$arr_settings['setting_range_min_default'] = __("Default range minimum", 'lang_webshop');
	$arr_settings['setting_range_choices'] = __("Custom range choices", 'lang_webshop');
	$arr_settings['setting_require_search'] = __("Require user to make some kind of search", 'lang_webshop');
	$arr_settings['setting_webshop_replace_none_checked'] = __("Replace Text", 'lang_webshop');

	$arr_settings['setting_replace_send_request_for_quote'] = __("Replace Text", 'lang_webshop');
	$arr_settings['setting_replace_quote_request'] = __("Replace Text", 'lang_webshop');

	if(get_option('setting_require_search') == 'yes')
	{
		$arr_settings['setting_webshop_replace_too_many'] = __("Replace Text", 'lang_webshop');
	}

	/*if($ghost_post_name != '')
	{
		$arr_settings['setting_ghost_title'] = __("Title for hidden", 'lang_webshop')." ".strtolower($name_product);
		$arr_settings['setting_ghost_image'] = __("Image for hidden", 'lang_webshop')." ".strtolower($name_product);
		$arr_settings['setting_ghost_text'] = __("Text for hidden", 'lang_webshop')." ".strtolower($name_product);
	}*/

	show_settings_fields(array('area' => $options_area, 'settings' => $arr_settings));
	############################

	/* Favorites */
	############################
	$options_area = $options_area_orig."_favorites";

	add_settings_section($options_area, "", $options_area."_callback", BASE_OPTIONS_PAGE);

	$arr_settings = array();
	$arr_settings['setting_webshop_replace_favorites_info'] = __("Replace Text", 'lang_webshop');

	$arr_settings['setting_webshop_replace_email_favorites'] = __("Replace Text", 'lang_webshop');
	$arr_settings['setting_webshop_share_email_subject'] = __("Email Subject", 'lang_webshop');
	$arr_settings['setting_webshop_share_email_content'] = __("Email Content", 'lang_webshop');

	show_settings_fields(array('area' => $options_area, 'settings' => $arr_settings));
	############################

	/* Product */
	############################
	$options_area = $options_area_orig."_product";

	add_settings_section($options_area, "", $options_area."_callback", BASE_OPTIONS_PAGE);

	$arr_settings = array();
	$arr_settings['setting_webshop_display_breadcrumbs'] = __("Display Breadcrumbs", 'lang_webshop');
	$arr_settings['setting_webshop_force_individual_contact'] = __("Force Individual Contact", 'lang_webshop');

	$arr_settings['setting_replace_add_to_search'] = __("Replace Text", 'lang_webshop');
	$arr_settings['setting_replace_remove_from_search'] = __("Replace Text", 'lang_webshop');
	$arr_settings['setting_replace_return_to_search'] = __("Replace Text", 'lang_webshop');
	$arr_settings['setting_replace_search_for_another'] = __("Replace Text", 'lang_webshop');

	if(is_plugin_active("mf_form/index.php")) // && get_option('setting_webshop_force_individual_contact') == 'yes' //Don't even think about this because it is still used when new vistors arrive at the product page
	{
		$arr_settings['setting_quote_form_single'] = __("Form for quote request (single)", 'lang_webshop');
	}

	show_settings_fields(array('area' => $options_area, 'settings' => $arr_settings));
	############################

	//Text
	############################
	/*$options_area = $options_area_orig."_text";

	add_settings_section($options_area, "", $options_area."_callback", BASE_OPTIONS_PAGE);

	$arr_settings = array();

	show_settings_fields(array('area' => $options_area, 'settings' => $arr_settings));*/
	############################

	//Color
	############################
	/*$options_area = $options_area_orig."_color";

	add_settings_section($options_area, "", $options_area."_callback", BASE_OPTIONS_PAGE);

	$arr_settings = array();
	$arr_settings['setting_webshop_color_button'] = __("Button color", 'lang_webshop');
	$arr_settings['setting_webshop_text_color_button'] = __("Button text color", 'lang_webshop')." (".__("Primary", 'lang_webshop').")";
	$arr_settings['setting_webshop_color_button_2'] = __("Button color", 'lang_webshop')." (".__("Secondary", 'lang_webshop').")";
	$arr_settings['setting_color_button_negative'] = __("Button color", 'lang_webshop')." (".__("Negative", 'lang_webshop').")";

	show_settings_fields(array('area' => $options_area, 'settings' => $arr_settings));*/
	############################

	//Map
	############################
	$options_area = $options_area_orig."_map";

	add_settings_section($options_area, "", $options_area."_callback", BASE_OPTIONS_PAGE);

	$arr_settings = array();

	if(!is_plugin_active('mf_maps/index.php'))
	{
		$arr_settings['setting_gmaps_api'] = __("API key", 'lang_webshop');
	}

	$arr_settings['setting_map_visibility'] = __("Map visibility", 'lang_webshop');
	$arr_settings['setting_map_visibility_mobile'] = __("Map visibility", 'lang_webshop')." (".__("Mobile", 'lang_webshop').")";
	$arr_settings['setting_webshop_symbol_inactive_image'] = __("Symbol inactive image", 'lang_webshop');
	$arr_settings['setting_webshop_symbol_active_image'] = __("Symbol active image", 'lang_webshop');

	if($ghost_post_name != '')
	{
		$arr_settings['setting_ghost_inactive_image'] = __("Ghost symbol inactive image", 'lang_webshop');
		$arr_settings['setting_ghost_active_image'] = __("Ghost symbol active image", 'lang_webshop');
	}

	if(get_option('setting_webshop_symbol_active_image') == '')
	{
		$arr_settings['setting_webshop_symbol_inactive'] = __("Symbol inactive color", 'lang_webshop');
		$arr_settings['setting_webshop_symbol_active'] = __("Symbol active color", 'lang_webshop');
	}

	$arr_settings['setting_webshop_replace_show_map'] = __("Show Map", 'lang_webshop');
	$arr_settings['setting_replace_hide_map'] = __("Hide Map", 'lang_webshop');
	$arr_settings['setting_map_info'] = __("Map Information", 'lang_webshop');

	$arr_settings['setting_webshop_color_info'] = __("Info color", 'lang_webshop');
	$arr_settings['setting_webshop_text_color_info'] = __("Info text color", 'lang_webshop');

	show_settings_fields(array('area' => $options_area, 'settings' => $arr_settings));
	############################
}

function settings_webshop_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$setting_webshop_replace_webshop = get_option_or_default('setting_webshop_replace_webshop', __("Webshop", 'lang_webshop'));

	echo settings_header($setting_key, $setting_webshop_replace_webshop);
}

/*function settings_webshop_color_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$setting_webshop_replace_webshop = get_option_or_default('setting_webshop_replace_webshop', __("Webshop", 'lang_webshop'));

	echo settings_header($setting_key, $setting_webshop_replace_webshop." - ".__("Colors", 'lang_webshop'));
}*/

function settings_webshop_map_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$setting_webshop_replace_webshop = get_option_or_default('setting_webshop_replace_webshop', __("Webshop", 'lang_webshop'));

	echo settings_header($setting_key, $setting_webshop_replace_webshop." - ".__("Map", 'lang_webshop'));
}

function settings_webshop_favorites_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$setting_webshop_replace_webshop = get_option_or_default('setting_webshop_replace_webshop', __("Webshop", 'lang_webshop'));

	echo settings_header($setting_key, $setting_webshop_replace_webshop." - ".__("Favorites", 'lang_webshop'));
}

function settings_webshop_product_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$setting_webshop_replace_webshop = get_option_or_default('setting_webshop_replace_webshop', __("Webshop", 'lang_webshop'));
	$setting_webshop_replace_product = get_option_or_default('setting_webshop_replace_product', __("Product", 'lang_webshop'));

	echo settings_header($setting_key, $setting_webshop_replace_webshop." - ".$setting_webshop_replace_product);
}

function settings_webshop_search_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$setting_webshop_replace_webshop = get_option_or_default('setting_webshop_replace_webshop', __("Webshop", 'lang_webshop'));

	echo settings_header($setting_key, $setting_webshop_replace_webshop." - ".__("Search", 'lang_webshop'));
}

/*function settings_webshop_text_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$setting_webshop_replace_webshop = get_option_or_default('setting_webshop_replace_webshop', __("Webshop", 'lang_webshop'));

	echo settings_header($setting_key, $setting_webshop_replace_webshop." - ".__("Text", 'lang_webshop'));
}*/

function setting_webshop_replace_webshop_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Webshop", 'lang_webshop')));
}

function setting_webshop_replace_product_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Product", 'lang_webshop')));
}

function setting_webshop_replace_products_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Products", 'lang_webshop')));
}

function setting_webshop_replace_products_slug_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => "w", 'description' => ($option != '' ? get_site_url()."/<strong>".$option."</strong>/abc" : "")));
}

function setting_show_categories_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key, 'no');

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
}

function setting_webshop_replace_categories_slug_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => "c", 'description' => ($option != '' ? get_site_url()."/".$option."/abc" : "")));
}

function setting_webshop_replace_categories_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Categories", 'lang_webshop')));
}

function setting_webshop_replace_doc_types_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Filters", 'lang_webshop')));
}

function setting_webshop_replace_filter_products_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Filter amongst %s products", 'lang_webshop'), 'description' => __("Disable by adding any single character", 'lang_webshop')));
}

function setting_replace_search_result_info_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Your search matches %s products", 'lang_webshop'), 'description' => __("Disable by adding any single character", 'lang_webshop')));
}

function setting_webshop_replace_favorites_info_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textarea(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Here are your %s saved products", 'lang_webshop'), 'description' => __("Disable by adding any single character", 'lang_webshop')));
}

function setting_replace_send_request_for_quote_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Send request for quote", 'lang_webshop')));
}

function setting_webshop_replace_choose_product_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Choose", 'lang_webshop')));
}

function setting_webshop_switch_icon_on_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option));
}

function setting_webshop_switch_icon_off_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option));
}

function setting_replace_add_to_search_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Add to Search", 'lang_webshop')));
}

function setting_replace_remove_from_search_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Remove from Search", 'lang_webshop')));
}

function setting_replace_return_to_search_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Return to Search", 'lang_webshop')));
}

function setting_replace_search_for_another_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Search for Another", 'lang_webshop')));
}

function setting_replace_quote_request_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	$name_products = get_option_or_default('setting_webshop_replace_products', __("Products", 'lang_webshop'));

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Send request for quote to", 'lang_webshop')." %s ".strtolower($name_products)));
}

function setting_webshop_replace_none_checked_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("You have to choose at least one product to proceed", 'lang_webshop')));
}

function setting_webshop_replace_email_favorites_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Email Your Products", 'lang_webshop')));
}

function setting_webshop_replace_too_many_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("In order to send a quote you have to be specific what you want by filtering", 'lang_webshop')));
}

function setting_webshop_share_email_subject_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key, __("I would like to share these products that I like", 'lang_webshop'));

	echo show_textfield(array('name' => $setting_key, 'value' => $option));
}

function setting_webshop_share_email_content_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key, sprintf(__("Here are my favorites (%s)", 'lang_webshop'), "[url]"));

	echo show_wp_editor(array('name' => $setting_key, 'value' => $option,
		'class' => "hide_media_button hide_tabs",
		'mini_toolbar' => true,
		'textarea_rows' => 5,
		//'statusbar' => false,
	));
}

if(!function_exists('setting_gmaps_api_callback'))
{
	function setting_gmaps_api_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		$suffix = ($option == '' ? "<a href='//developers.google.com/maps/documentation/javascript/get-api-key'>".__("Get yours here", 'lang_webshop')."</a>" : "");

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'suffix' => $suffix));
	}
}

function setting_map_visibility_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_select(array('data' => get_map_visibility_for_select(), 'name' => $setting_key, 'value' => $option));
}

function setting_map_visibility_mobile_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_select(array('data' => get_map_visibility_for_select(), 'name' => $setting_key, 'value' => $option));
}

function setting_webshop_replace_show_map_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Show Map", 'lang_webshop')));
}

function setting_replace_hide_map_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Hide Map", 'lang_webshop')));
}

function setting_map_info_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_wp_editor(array('name' => $setting_key, 'value' => $option,
		'class' => "hide_media_button hide_tabs",
		'mini_toolbar' => true,
		'textarea_rows' => 5,
		//'statusbar' => false,
	));
}

function setting_product_default_image_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo get_media_library(array('name' => $setting_key, 'value' => $option, 'type' => 'image'));
}

function setting_range_min_default_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key, 10);

	echo show_textfield(array('type' => 'number', 'name' => $setting_key, 'value' => $option, 'xtra' => "min='0' max='100'", 'suffix' => "%", 'description' => __("If no lower value is entered in a range, this percentage is used to calculate the lower end of the range", 'lang_webshop')));
}

function setting_range_choices_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => '1-50,50-100,1000+'));
}

function setting_webshop_display_sort_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	$obj_webshop = new mf_webshop();

	if($option == 'yes')
	{
		$option = array('latest', 'random', 'alphabetical', 'size');
	}

	echo show_select(array('data' => $obj_webshop->get_sort_for_select(), 'name' => $setting_key."[]", 'value' => $option));
	//echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
}

function setting_webshop_sort_default_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, 'size');

	$obj_webshop = new mf_webshop();

	echo show_select(array('data' => $obj_webshop->get_sort_for_select(get_option('setting_webshop_display_sort')), 'name' => $setting_key, 'value' => $option));
}

function setting_webshop_display_filter_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, 'yes');

	$arr_data = array(
		'yes' => __("Yes", 'lang_webshop'),
		'button' => __("Yes", 'lang_webshop')." (".__("Hidden behind a button", 'lang_webshop').")",
		'no' => __("No", 'lang_webshop'),
	);

	echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option));
}

function setting_search_max_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, 50);

	echo show_textfield(array('type' => 'number', 'name' => $setting_key, 'value' => $option, 'xtra' => "min='10' max='100'"));
}

function setting_show_all_min_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, 30);

	echo show_textfield(array('type' => 'number', 'name' => $setting_key, 'value' => $option, 'xtra' => "min='0' max='100'", 'suffix' => sprintf(__("%d will hide the link in the form", 'lang_webshop'), 0)));
}

function setting_require_search_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, 'yes');

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
}

/*function setting_webshop_color_button_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key, "#ddb27f");

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'type' => 'color'));
}

function setting_webshop_text_color_button_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key, "#ffffff");

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'type' => 'color'));
}

function setting_webshop_color_button_2_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key, "#c78e91");

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'type' => 'color'));
}

function setting_color_button_negative_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key, "#e47676");

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'type' => 'color'));
}*/

function setting_webshop_color_info_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key, "#eeeeee");

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'type' => 'color'));
}

function setting_webshop_text_color_info_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key, "#000000");

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'type' => 'color'));
}

function setting_webshop_symbol_active_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key, "#b8c389");

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'type' => 'color'));
}

function setting_webshop_symbol_inactive_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key, "#c78e91");

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'type' => 'color'));
}

function setting_webshop_symbol_inactive_image_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo get_media_library(array('name' => $setting_key, 'value' => $option, 'type' => 'image'));
}

function setting_webshop_symbol_active_image_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo get_media_library(array('name' => $setting_key, 'value' => $option, 'type' => 'image'));
}

function setting_ghost_inactive_image_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo get_media_library(array('name' => $setting_key, 'value' => $option, 'type' => 'image'));
}

function setting_ghost_active_image_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo get_media_library(array('name' => $setting_key, 'value' => $option, 'type' => 'image'));
}

function setting_ghost_title_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Hidden", 'lang_webshop')));
}

function setting_ghost_image_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo get_media_library(array('name' => $setting_key, 'value' => $option, 'type' => 'image'));
}

function setting_ghost_text_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("This is hidden", 'lang_webshop')));
}

function setting_quote_form_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	$obj_form = new mf_form();

	echo show_select(array('data' => $obj_form->get_for_select(), 'name' => $setting_key, 'value' => $option, 'suffix' => "<a href='".admin_url("admin.php?page=mf_form/create/index.php")."'><i class='fa fa-lg fa-plus'></i></a>"));
}

function setting_quote_form_single_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	$obj_form = new mf_form();

	echo show_select(array('data' => $obj_form->get_for_select(), 'name' => $setting_key, 'value' => $option, 'suffix' => "<a href='".admin_url("admin.php?page=mf_form/create/index.php")."'><i class='fa fa-lg fa-plus'></i></a>"));
}

function setting_webshop_display_breadcrumbs_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key, 'no');

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
}

function setting_webshop_force_individual_contact_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key, 'yes');

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option, 'description' => __("This will allow visitors to send individual quote requests all the time, otherwise it is only for first time visitors coming directly to the page that have this option", 'lang_webshop')));
}

function setting_webshop_payment_form_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	$obj_form = new mf_form();

	echo show_select(array('data' => $obj_form->get_for_select(array('local_only' => true, 'force_has_page' => false)), 'name' => $setting_key, 'value' => $option, 'suffix' => "<a href='".admin_url("admin.php?page=mf_form/create/index.php")."'><i class='fa fa-lg fa-plus'></i></a>"));
}

/*function setting_webshop_require_payment_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key, 'no');

	echo show_select(array('data' => get_settings_roles(array('no' => true)), 'name' => $setting_key, 'value' => $option)); //'yes' => true, 
}*/

function setting_local_storage_callback()
{
	echo "<div class='form_buttons'>"
		.show_button(array('type' => 'button', 'name' => 'btnLocalStorageClear', 'text' => __("Clear", 'lang_webshop'), 'class' => 'button'))
	."</div>
	<div id='storage_response'></div>";
}

function get_map_marker_url($option_key)
{
	//maps.google.com/mapfiles/ms/icons/green-dot.png
	//maps.google.com/mapfiles/ms/icons/red-dot.png
	//www.googlemapsmarkers.com/v1/009900/
	//www.googlemapsmarkers.com/v1/A/0099FF/
	//www.googlemapsmarkers.com/v1/A/0099FF/FFFFFF/FF0000/

	return "http://googlemapsmarkers.com/v1/".trim(get_option($option_key), "#")."/";
}

function meta_webshop()
{
	global $wpdb, $post;

	$post_id = $post->ID;

	if($post_id > 0 && get_the_excerpt() == '')
	{
		$obj_webshop = new mf_webshop();
		$size_post_name = $obj_webshop->get_post_name_for_type('description');
		$product_description = get_post_meta($post_id, $obj_webshop->meta_prefix.$size_post_name, true);

		/*if($product_description == '')
		{
			$name_product = get_option_or_default('setting_webshop_replace_product', __("Product", 'lang_webshop'));

			$post_title = get_post_title($post_id);

			$product_description = $name_product.": ".$post_title;
		}*/

		if($product_description != '')
		{
			echo "<meta name='description' content='".esc_attr($product_description)."'>";
		}
	}
}

function update_product_amount($intProductID2, $intProductAmount2)
{
	global $wpdb;

	$obj_webshop = new mf_webshop();

	$error_text = "";

	$result = $obj_webshop->get_document_types(array('select' => "ID, post_name", 'where_key' => "ID = '%d'", 'where_value' => $intProductID2, 'order' => "menu_order ASC"));

	foreach($result as $r)
	{
		$post_id = $r->ID;
		$post_name = $r->post_name;

		$post_custom_type = get_post_meta($post_id, $obj_webshop->meta_prefix.'document_type', true);

		if($post_custom_type == 'price')
		{
			$post_meta = get_post_meta($intProductID2, $obj_webshop->meta_prefix.$post_name, true);

			if($post_meta > 0 && $intProductAmount2 > 0)
			{
				$intProductAmount_result = $post_meta - $intProductAmount2 > 0 ? $post_meta - $intProductAmount2 : 0;

				update_post_meta($intProductID2, $obj_webshop->meta_prefix.$post_name, $intProductAmount_result);
			}

			else
			{
				$error_text = __("The amount in stock and in the order is wrong", 'lang_webshop')." (".$post_meta." - ".$intProductAmount2.")";
			}
		}
	}

	return $error_text;
}

function meta_boxes_script_webshop()
{
	$setting_range_min_default = get_option_or_default('setting_range_min_default', 10);

	mf_enqueue_script('script_webshop_meta', plugin_dir_url(__FILE__)."script_meta.js", array('range_min_default' => $setting_range_min_default), get_plugin_version(__FILE__));
}

function get_product_list_item($post_id = 0, $current_post_id = 0)
{
	global $wpdb;

	$out = "";
	$is_ancestor = false;

	$result = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->posts." WHERE post_type = 'mf_categories' AND post_status = 'publish' AND post_parent = '%d' ORDER BY menu_order ASC", $post_id));

	if($wpdb->num_rows > 0)
	{
		$out .= "<ul".($post_id > 0 ? " class='children'" : "").">";

			foreach($result as $r)
			{
				$post_id = $r->ID;
				$post_title = $r->post_title;

				$post_url = get_permalink($r);

				list($list_output, $is_parent) = get_product_list_item($post_id, $current_post_id);

				$class = "";

				if($post_id == $current_post_id)
				{
					$class = "current_page_item";

					$is_ancestor = true;
				}

				else if($is_parent == true)
				{
					$class = "current_page_parent";

					$is_ancestor = true;
				}

				$out .= "<li".($class != '' ? " class='".$class."'" : "").">
					<a href='".$post_url."'>
						<i class='fa fa-caret-right'></i>"
						.$post_title
					."</a>"
					.$list_output
				."</li>";
			}

		$out .= "</ul>";
	}

	return array($out, $is_ancestor);
}

function get_interval_min($value)
{
	if(strpos($value, "-"))
	{
		list($value_min, $value_max) = explode("-", $value);

		if(!($value_max > $value_min))
		{
			$value_max = pow($value_min, 2);
		}
	}

	else
	{
		$setting_range_min_default = get_option_or_default('setting_range_min_default', 10);

		$value_min = $value * ($setting_range_min_default / 100);
		$value_max = $value;
	}

	return array($value_min, $value_max);
}

function get_search_result_info($data)
{
	if(!isset($data['type'])){		$data['type'] = '';}

	switch($data['type'])
	{
		case 'filter':
			$text = get_option_or_default('setting_webshop_replace_filter_products', __("Filter amongst %s products", 'lang_webshop'));
		break;

		case 'matches':
			$text = get_option_or_default('setting_replace_search_result_info', __("Your search matches %s products", 'lang_webshop'));
		break;

		case 'favorites':
			$text = get_option_or_default('setting_webshop_replace_favorites_info', __("Here are your %s saved products", 'lang_webshop'));
		break;

		default:
			$text = '';
		break;
	}

	if(strlen($text) > 2)
	{
		$out = "<div class='search_result_info'>".sprintf($text, "<span>0</span>")."</div>";
	}

	return $out;
}

function get_quote_button($data = array())
{
	if(!isset($data['include'])){	$data['include'] = array('quote');}

	$out = "";

	if(get_option('setting_quote_form') > 0 && in_array('quote', $data['include']))
	{
		$name_quote_request = get_option('setting_replace_quote_request');
		$name_quote_none_checked = get_option_or_default('setting_webshop_replace_none_checked', __("You have to choose at least one product to proceed", 'lang_webshop'));
		$name_quote_too_many = get_option_or_default('setting_webshop_replace_too_many', __("In order to send a quote you have to be specific what you want by filtering", 'lang_webshop'));

		if($name_quote_request == '')
		{
			$name_products = get_option_or_default('setting_webshop_replace_products', __("Products", 'lang_webshop'));

			$name_quote_request = __("Send request for quote to", 'lang_webshop')." %s ".strtolower($name_products);
		}

		$out .= show_button(array('text' => sprintf($name_quote_request, "<span>0</span>"), 'class' => "show_if_results button-primary hide"));
		//$out .= show_button(array('type' => "button", 'text' => __("Choose all", 'lang_webshop'), 'class' => "show_choose_all button-secondary hide"));

		$out .= "<p class='show_if_none_checked info_text hide'>".$name_quote_none_checked."</p>"
		."<p class='show_if_too_many info_text hide'>".$name_quote_too_many."</p>";
	}

	if(in_array('print', $data['include']))
	{
		$out .= show_button(array('type' => 'button', 'text' => "<i class='fa fa-print'></i>".__("Print List", 'lang_webshop'), 'class' => "show_if_results button-primary hide button_print"));
	}

	if(in_array('email', $data['include']))
	{
		$setting_webshop_replace_email_favorites = get_option_or_default('setting_webshop_replace_email_favorites', __("Email Your Products", 'lang_webshop'));
		$setting_webshop_share_email_subject = get_option('setting_webshop_share_email_subject');
		$setting_webshop_share_email_content = get_option('setting_webshop_share_email_content');

		$out .= "<a href='mailto:?subject=".$setting_webshop_share_email_subject."&body=".$setting_webshop_share_email_content."' class='show_if_results button'><i class='fa fa-envelope-o'></i>".$setting_webshop_replace_email_favorites."</a>";
	}

	if($out != '')
	{
		return "<div class='quote_button'>
			<div class='form_button'>"
				.$out
			."</div>
		</div>";
	}
}

function get_webshop_map()
{
	global $wpdb;

	$setting_replace_show_map = get_option_or_default('setting_webshop_replace_show_map', __("Show Map", 'lang_webshop'));
	$setting_replace_hide_map = get_option_or_default('setting_replace_hide_map', __("Hide Map", 'lang_webshop'));
	$setting_map_info = get_option('setting_map_info');

	$out = "<h2 class='is_map_toggler color_button'>
		<span>".$setting_replace_show_map."</span>
		<span>".$setting_replace_hide_map."</span>
	</h2>
	<div class='map_wrapper'>
		<div id='webshop_map'></div>";

		if($setting_map_info != '')
		{
			$out .= "<div class='webshop_map_info'>".nl2br($setting_map_info)."</div>";
		}

		$out .= input_hidden(array('name' => "webshop_map_coords", 'allow_empty' => true))
		.input_hidden(array('name' => "webshop_map_bounds", 'allow_empty' => true))
		."</div>";

	return $out;
}

function get_webshop_cart()
{
	global $wpdb, $sesWebshopCookie, $intCustomerID, $intCustomerNo, $strOrderName, $emlOrderEmail, $strOrderText, $intDeliveryTypeID, $error_text, $done_text;

	$out = get_notification();

	$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, productAmount FROM ".$wpdb->posts." INNER JOIN ".$wpdb->prefix."webshop_product2user ON ".$wpdb->posts.".ID = ".$wpdb->prefix."webshop_product2user.productID WHERE post_type = 'mf_products' AND webshopDone = '0' AND (userID = '%d' OR webshopCookie = %s)", get_current_user_id(), $sesWebshopCookie));

	if($wpdb->num_rows > 0)
	{
		$out .= "<h4>".__("Cart", 'lang_webshop')."</h4>
		<ul>";

			foreach($result as $r)
			{
				$post_id = $r->ID;
				$post_title = $r->post_title;
				$product_amount = $r->productAmount;

				$post_url = get_permalink($post_id);

				$out .= "<li>
					<a href='".$post_url."'>
						<span>".$post_title."</span>
						<em>(".$product_amount.")</em>
					</a>
				</li>";
			}

		$out .= "</ul>
		<form method='post' action='' id='order_proceed' class='mf_form".(isset($_POST['btnOrderConfirm']) ? " hide" : "")."'>
			<div class='form_button'>"
				.show_button(array('name' => 'btnOrderProceed', 'text' => __("Proceed to Checkout", 'lang_webshop'), 'type' => 'button'))
			."</div>
		</form>";

		if(get_current_user_id() > 0 && !($intCustomerID > 0))
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT customerID, orderName, orderEmail FROM ".$wpdb->prefix."webshop_order WHERE userID = '%d' ORDER BY orderCreated DESC LIMIT 0, 1", get_current_user_id()));

			foreach($result as $r)
			{
				$intCustomerID = $r->customerID;
				$strOrderName = $r->orderName;
				$emlOrderEmail = $r->orderEmail;
			}
		}

		$out .= "<form method='post' action='' id='order_confirm' class='mf_form".(isset($_POST['btnOrderConfirm']) ? "" : " hide")."'>
			<h4>".__("Checkout", 'lang_webshop')."</h4>";

			$arr_data = get_posts_for_select(array('post_type' => 'mf_customers', 'order' => "post_title ASC", 'add_choose_here' => true));

			if(count($arr_data) > 0)
			{
				$out .= show_select(array('data' => $arr_data, 'name' => 'intCustomerID', 'text' => __("Customer", 'lang_webshop'), 'value' => $intCustomerID))
				.show_textfield(array('name' => 'intCustomerNo', 'text' => __("Customer No", 'lang_webshop'), 'value' => $intCustomerNo, 'type' => 'number'));
			}

			$out .= show_textfield(array('name' => 'strOrderName', 'text' => __("Name", 'lang_webshop'), 'value' => $strOrderName, 'required' => true))
			.show_textfield(array('name' => 'emlOrderEmail', 'text' => __("E-mail", 'lang_webshop'), 'value' => $emlOrderEmail, 'required' => true))
			.show_textarea(array('name' => 'strOrderText', 'text' => __("Text", 'lang_webshop'), 'value' => $strOrderText));

			$arr_data = get_posts_for_select(array('post_type' => 'mf_delivery_type', 'order' => "post_title ASC"));

			if(count($arr_data) > 0)
			{
				$out .= show_select(array('data' => $arr_data, 'name' => 'intDeliveryTypeID', 'text' => __("Delivery Type", 'lang_webshop'), 'value' => $intDeliveryTypeID));
			}

			$out .= "<div class='form_button'>"
				.show_button(array('name' => 'btnOrderConfirm', 'text' => __("Confirm Order", 'lang_webshop')))
			."</div>"
			.wp_nonce_field('order_confirm', '_wpnonce_order_confirm', true, false)
		."</form>";
	}

	return $out;
}

function is_a_webshop_meta_value($data)
{
	global $wpdb;

	if($data['value'] != '')
	{
		if($data['key'] == "products" && count($data['value']) > 0)
		{
			return true;
		}

		else
		{
			$obj_webshop = new mf_webshop();
			$post_id = $obj_webshop->get_document_types(array('select' => "ID", 'where_key' => "post_name = %s", 'where_value' => $data['key'], 'limit' => "0, 1"));

			if($post_id > 0)
			{
				return true;
			}
		}
	}

	return false;
}

function filter_form_after_fields_webshop($out)
{
	global $wpdb;

	$obj_webshop = new mf_webshop();

	$out_left = $out_right = "";

	$setting_search_max = get_option_or_default('setting_search_max', 50);
	$name_choose = get_option_or_default('setting_webshop_replace_choose_product', __("Choose", 'lang_webshop'));
	$address_post_name = $obj_webshop->get_post_name_for_type('address');

	foreach($_REQUEST as $key => $value)
	{
		if($key == "interval_range" && $value != '')
		{
			$key = $_POST['interval_type'];
		}

		if($key == 'products')
		{
			if(!is_array($value))
			{
				$value = array($value);
			}

			$count_temp = count($value);

			for($i = 0; $i < $count_temp && $i < $setting_search_max; $i++)
			{
				$product_id = $value[$i];

				$query_join = "";

				if($address_post_name != '')
				{
					$query_join = " LEFT JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = '".$obj_webshop->meta_prefix.$address_post_name."'";
				}

				$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_excerpt, post_content FROM ".$wpdb->posts.$query_join." WHERE post_type = 'mf_products' AND post_status = 'publish' AND ID = '%d'", $product_id));

				foreach($result as $r)
				{
					$arr_product = array();

					$obj_webshop->get_product_data(array('product' => $r, 'single_image' => true, 'show_location_in_data' => false), $arr_product);

					/* If the user for some reason succeeds in requesting all the products but still having a filter on to prevent all products to show this is neccesary */
					if(isset($arr_product['product_response'][0]))
					{
						$arr_product = $arr_product['product_response'][0];

						$out_left .= "<li>
							<div class='product_heading product_column'>
								<h2>".$arr_product['product_title']."</h2>";

								if($arr_product['product_location'] != '')
								{
									$out_left .= "<p class='product_location'>".$arr_product['product_location']."</p>";
								}

							$out_left .= "</div>
							<div class='product_image_container'>";

								if($arr_product['product_image'] != '')
								{
									$out_left .= "<img src='".$arr_product['product_image']."' alt='".$arr_product['product_title']."'>";
								}

								else
								{
									$out_left .= get_image_fallback();
								}

								if($arr_product['product_data'] != '')
								{
									$out_left .= "<div class='product_data'>".$arr_product['product_data']."</div>";
								}

							$out_left .= "</div>"
							.show_checkbox(array('name' => $key.'[]', 'value' => $product_id, 'text' => $name_choose, 'compare' => $product_id, 'switch' => true, 'switch_icon_on' => get_option('setting_webshop_switch_icon_on'), 'switch_icon_off' => get_option('setting_webshop_switch_icon_off'), 'xtra_class' => 'color_button_2'))
							."<ul class='product_meta product_column'>";

								foreach($arr_product['product_meta'] as $product_meta)
								{
									$out_left .= "<li class='".$product_meta['class']."'>"
										.$product_meta['content']
									."</li>";
								}

							$out_left .= "</ul>
						</li>";
					}
				}
			}
		}

		else if(is_a_webshop_meta_value(array('key' => $key, 'value' => $value)))
		{
			$out_right .= input_hidden(array('name' => $key, 'value' => $value));
		}
	}

	if($out_left != '' || $out_right != '')
	{
		$out = "<ul id='product_result_form' class='product_list webshop_item_list'>";

			if($out_left != '')
			{
				$out .= $out_left;
			}

		$out .= "</ul>";

		if($out_right != '')
		{
			$out .= $out_right;
		}
	}

	return apply_filters('the_content', $out);
}

function filter_form_on_submit_webshop($data)
{
	global $wpdb, $error_text;

	$obj_form = new mf_form();
	$obj_webshop = new mf_webshop();

	$answer_text = "";

	$arr_product_ids = array();

	foreach($_REQUEST as $key => $value)
	{
		if(is_a_webshop_meta_value(array('key' => $key, 'value' => $value)))
		{
			if($key == 'products')
			{
				if($value > 0)
				{
					$arr_product_ids = $value;
				}
			}

			else
			{
				$answer_text .= $key." -> ".$value.", ";

				$result = $obj_webshop->get_document_types(array('select' => "ID, post_title", 'where_key' => "post_name = %s", 'where_value' => $key));

				foreach($result as $r)
				{
					$post_id = $r->ID;
					$key = $r->post_title;

					$post_custom_type = get_post_meta($post_id, $obj_webshop->meta_prefix.'document_type', true);

					switch($post_custom_type)
					{
						case 'checkbox':
							$value = ($value == 1 ? __("Yes", 'lang_webshop') : __("No", 'lang_webshop'));
						break;

						case 'location':
							$value = get_the_title($value);
						break;
					}
				}

				if(!isset($data['obj_form']->arr_email_content['doc_types']))
				{
					$data['obj_form']->arr_email_content['doc_types'] = array();
				}

				$data['obj_form']->arr_email_content['doc_types'][] = array(
					'label' => $key,
					'value' => $value,
				);
			}
		}
	}

	$arr_mail_content_temp = $data['obj_form']->arr_email_content;

	if(count($arr_product_ids) > 0)
	{
		$result = $wpdb->get_results($wpdb->prepare("SELECT formEmail, formEmailNotifyPage, formEmailName FROM ".$wpdb->base_prefix."form WHERE formID = '%d' AND formDeleted = '0'", $data['obj_form']->id));

		foreach($result as $r)
		{
			$data['obj_form']->email_admin = $r->formEmail;
			$data['obj_form']->email_notify_page = $r->formEmailNotifyPage;
			$data['obj_form']->email_subject = ($r->formEmailName != "" ? $r->formEmailName : $data['obj_form']->form_name);
		}

		$name_product = get_option_or_default('setting_webshop_replace_product', __("Product", 'lang_webshop'));
		$name_products = get_option_or_default('setting_webshop_replace_products', __("Products", 'lang_webshop'));

		$i = 0;

		$arr_mail_content_temp['products'][$i] = array(
			'label' => $name_products,
			'id' => 0,
			'value' => "",
		);

		foreach($arr_product_ids as $product_id)
		{
			$email_post_name = $obj_webshop->get_post_name_for_type('email');

			$product_email = get_post_meta($product_id, $obj_webshop->meta_prefix.$email_post_name, true);

			if($product_email != '')
			{
				$product_title = get_the_title($product_id);

				$arr_mail_content_this = $data['obj_form']->arr_email_content;
				$arr_mail_content_this['products'][0] = array(
					'label' => $name_product,
					'id' => $product_id,
					'value' => $product_title,
				);

				$obj_form->answer_id = $data['obj_form']->answer_id;

				$obj_form->page_content_data = array(
					'page_id' => $data['obj_form']->email_notify_page,
					'mail_to' => $product_email,
					'subject' => $data['obj_form']->email_subject,
					'content' => $arr_mail_content_this,
				);

				$obj_form->mail_data = array(
					'to' => $product_email,
					'type' => "product",
					'subject' => $obj_form->page_content_data['subject'],
					'content' => '',
				);

				$obj_form->mail_data['content'] = $obj_form->get_page_content_for_email();
				//$obj_form->mail_data['subject'] = $obj_form->page_content_data['subject'];

				if($data['obj_form']->email_visitor != '')
				{
					if($data['obj_form']->email_admin != '' && strpos($data['obj_form']->email_admin, "<"))
					{
						$arr_mail_from = explode("<", $data['obj_form']->email_admin);

						$mail_from_name = $arr_mail_from[0];
					}

					else
					{
						$mail_from_name = get_bloginfo('name');
					}

					$obj_form->mail_data['headers'] = "From: ".$mail_from_name." <".$data['obj_form']->email_visitor.">\r\n";
				}

				$obj_form->send_transactional_email();

				$arr_mail_content_temp['products'][$i]['id'] = $product_id;
				$arr_mail_content_temp['products'][$i]['value'] = $product_title;

				$i++;
			}

			$obj_webshop->insert_sent(array('product_id' => $product_id, 'answer_id' => $obj_form->answer_id));
		}
	}

	if($answer_text != '')
	{
		$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_answer SET answerID = '%d', form2TypeID = '0', answerText = %s", $data['obj_form']->answer_id, $answer_text));
	}

	$data['obj_form']->arr_email_content = $arr_mail_content_temp;

	return $data;
}

function post_filter_select_webshop()
{
    global $post_type, $wpdb;

    if($post_type == 'mf_products')
	{
		$obj_webshop = new mf_webshop();
		$location_post_name = $obj_webshop->get_post_name_for_type('location');

		if($location_post_name != '')
		{
			//$strFilterLocation = get_or_set_table_filter(array('key' => 'strFilterLocation', 'save' => true));
			$strFilterLocation = check_var('strFilterLocation');

			$arr_data = array();
			get_post_children(array('post_type' => 'mf_location', 'post_status' => '', 'add_choose_here' => true), $arr_data);

			if(count($arr_data) > 2)
			{
				echo show_select(array('data' => $arr_data, 'name' => 'strFilterLocation', 'value' => $strFilterLocation));
			}
		}
    }

	else if($post_type == 'mf_document_type')
	{
		//$strFilterPlacement = get_or_set_table_filter(array('key' => 'strFilterPlacement', 'save' => true));
		$strFilterPlacement = check_var('strFilterPlacement');

		$arr_data = array(
			'' => "-- ".__("Choose Here", 'lang_webshop')." --",
			'searchable' => __("Make Searchable", 'lang_webshop'),
			'public' => __("Display in Results", 'lang_webshop'),
			'public_single' => __("Display as Contact Info", 'lang_webshop'),
			'quick' => __("Display as Quick Info", 'lang_webshop'),
			'property' => __("Display as Property", 'lang_webshop'),
		);

		echo show_select(array('data' => $arr_data, 'name' => 'strFilterPlacement', 'value' => $strFilterPlacement));
    }
}

function post_filter_query_webshop($wp_query)
{
    global $post_type, $pagenow;

    if($pagenow == 'edit.php')
	{
		$obj_webshop = new mf_webshop();

		if($post_type == 'mf_products')
		{
			$location_post_name = $obj_webshop->get_post_name_for_type('location');

			if($location_post_name != '')
			{
				//$strFilterLocation = get_or_set_table_filter(array('key' => 'strFilterLocation'));
				$strFilterLocation = check_var('strFilterLocation');

				if($strFilterLocation != '')
				{
					$wp_query->query_vars['meta_query'] = array(
						array(
							'key' => $obj_webshop->meta_prefix.$location_post_name,
							'value' => $strFilterLocation,
							'compare' => '=',
						),
					);
				}
			}
		}

		else if($post_type == 'mf_document_type')
		{
			//$strFilterPlacement = get_or_set_table_filter(array('key' => 'strFilterPlacement'));
			$strFilterPlacement = check_var('strFilterPlacement');

			if($strFilterPlacement != '')
			{
				$wp_query->query_vars['meta_query'] = array(
					array(
						'key' => $obj_webshop->meta_prefix.'document_'.$strFilterPlacement,
						'value' => 'yes',
						'compare' => '=',
					),
				);
			}
		}
	}
}

function column_header_products($cols)
{
	unset($cols['date']);

	if(get_post_children(array('post_type' => 'mf_categories', 'count' => true, 'limit' => 1)) > 0)
	{
		$cols['category'] = get_option_or_default('setting_webshop_replace_categories', __("Categories", 'lang_webshop'));
	}

	$obj_webshop = new mf_webshop();

	$arr_columns = array('ghost', 'location', 'local_address', 'email', 'phone'); //address
	$arr_columns_admin = array('email', 'phone');

	foreach($arr_columns as $column)
	{
		if(!in_array($column, $arr_columns_admin) || IS_ADMIN)
		{
			$result = $obj_webshop->get_post_type_info(array('type' => $column));

			if(isset($result->post_title))
			{
				$column_title = $result->post_title;

				$column_icon = get_post_meta($result->ID, $obj_webshop->meta_prefix.'document_symbol', true);

				if($column_icon != '')
				{
					$column_title = "<i class='fa fa-".$column_icon." fa-lg' title='".$column_title."'></i>";
				}

				$cols[$column] = $column_title;
			}
		}
	}

	return $cols;
}

function column_cell_products($col, $id)
{
	$obj_webshop = new mf_webshop();

	$post_name = $obj_webshop->get_post_name_for_type($col);

	switch($col)
	{
		case 'category':
			$post_meta = get_post_meta($id, $obj_webshop->meta_prefix.$col, false);
			$count_temp = count($post_meta);

			if($count_temp > 0)
			{
				$category_names = "";

				for($i = 0; $i < $count_temp; $i++)
				{
					$category_names .= ($i > 0 ? ", " : "").get_the_title($post_meta[$i]);
				}

				if($count_temp > 1)
				{
					echo "<span title='".$category_names."'>".$count_temp."</span>";
				}

				else
				{
					echo $category_names;
				}
			}
		break;

		case 'ghost':
			$post_meta = get_post_meta($id, $obj_webshop->meta_prefix.$post_name, true);

			if($post_meta == true)
			{
				echo "<i class='fa fa-lg ".($post_meta == true ? "fa-eye-slash" : "fa-eye")."'></i>";
			}
		break;

		case 'location':
			$post_meta = get_post_meta($id, $obj_webshop->meta_prefix.$post_name, false);
			$count_temp = count($post_meta);

			if($count_temp > 0)
			{
				for($i = 0; $i < $count_temp; $i++)
				{
					echo ($i > 0 ? ", " : "").get_the_title($post_meta[$i]);
				}
			}
		break;

		case 'address':
		case 'local_address':
			$post_meta = get_post_meta($id, $obj_webshop->meta_prefix.$post_name, true);

			echo $post_meta;
		break;

		case 'email':
			$post_meta = get_post_meta($id, $obj_webshop->meta_prefix.$post_name, true);

			echo "<a href='mailto:".$post_meta."'>".$post_meta."</a>";
		break;

		case 'phone':
			$post_meta = get_post_meta($id, $obj_webshop->meta_prefix.$post_name, true);

			echo "<a href='".format_phone_no($post_meta)."'>".$post_meta."</a>";
		break;
	}
}

function column_header_categories($cols)
{
	unset($cols['date']);

	$name_products = get_option_or_default('setting_webshop_replace_products', __("Products", 'lang_webshop'));
	$name_product = get_option_or_default('setting_webshop_replace_product', __("Product", 'lang_webshop'));

	$cols['products'] = $name_products;
	$cols['connect_new_products'] = sprintf(__("Connect to new %s", 'lang_webshop'), strtolower($name_product));

	return $cols;
}

function column_cell_categories($col, $id)
{
	global $wpdb;

	$obj_webshop = new mf_webshop();

	switch($col)
	{
		case 'products':
			$product_amount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(post_id) FROM ".$wpdb->postmeta." WHERE meta_key = '".$obj_webshop->meta_prefix."category' AND meta_value = '%d'", $id));

			echo $product_amount;
		break;

		case 'connect_new_products':
			$post_meta = get_post_meta($id, $obj_webshop->meta_prefix.$col, true);

			echo "<i class='fa fa-lg ".($post_meta == "yes" ? "fa-check green" : "fa-close red")."'></i>";
		break;
	}
}

function column_header_document_type($cols)
{
	unset($cols['date']);

	$cols['type'] = __("Type", 'lang_webshop');
	$cols['searchable'] = __("Make Searchable", 'lang_webshop');
	$cols['public'] = __("Display in Results", 'lang_webshop');
	$cols['public_single'] = __("Display as Contact Info", 'lang_webshop');
	$cols['quick'] = __("Display as Quick Info", 'lang_webshop');
	$cols['property'] = __("Display as Property", 'lang_webshop');

	$arr_categories = array();
	get_post_children(array('post_type' => 'mf_categories', 'add_choose_here' => false, 'post_status' => 'publish'), $arr_categories);

	if(count($arr_categories) > 1)
	{
		$cols['display_on_categories'] = __("Display on Categories", 'lang_webshop');
	}

	return $cols;
}

function column_cell_document_type($col, $id)
{
	$obj_webshop = new mf_webshop();

	switch($col)
	{
		case 'type':
			$post_meta = get_post_meta($id, $obj_webshop->meta_prefix.'document_'.$col, true);

			echo $obj_webshop->get_types_for_select()[$post_meta];
		break;

		case 'searchable':
			$post_meta = get_post_meta($id, $obj_webshop->meta_prefix.'document_'.$col, true);

			echo "<i class='fa fa-lg ".($post_meta == "yes" ? "fa-check green" : "fa-close red")."'></i>";

			$post_meta = get_post_meta($id, $obj_webshop->meta_prefix.'document_searchable_required', true);

			if($post_meta == 'yes')
			{
				echo " <i class='fa fa-lg fa-asterisk red' title='".__("Required", 'lang_webshop')."'></i>";
			}
		break;

		case 'public':
		case 'public_single':
		case 'quick':
		case 'property':
			$post_meta = get_post_meta($id, $obj_webshop->meta_prefix.'document_'.$col, true);

			echo "<i class='fa fa-lg ".($post_meta == "yes" ? "fa-check green" : "fa-close red")."'></i>";
		break;

		case 'display_on_categories':
			$post_meta = get_post_meta($id, $obj_webshop->meta_prefix.'document_'.$col, false);

			if(count($post_meta) > 0)
			{
				$i = 0;

				foreach($post_meta as $category_id)
				{
					echo ($i > 0 ? ", " : "").get_post_title($category_id);

					$i++;
				}
			}

			else
			{
				echo __("All", 'lang_webshop');
			}
		break;
	}
}

function column_header_location($cols)
{
	unset($cols['date']);

	$cols['location_hidden'] = "<i class='fa fa-lg fa-eye-slash'></i>";
	$cols['products'] = get_option_or_default('setting_webshop_replace_products', __("Products", 'lang_webshop'));

	return $cols;
}

function column_cell_location($col, $id)
{
	$obj_webshop = new mf_webshop();

	switch($col)
	{
		case 'location_hidden':
			$post_meta = get_post_meta($id, $obj_webshop->meta_prefix.$col, true);

			if($post_meta == 'yes')
			{
				echo "<i class='fa fa-lg fa-eye-slash'></i>";
			}
		break;

		case 'products':
			$result = $obj_webshop->get_products_from_location($id);

			$count_temp = count($result);

			if($count_temp > 0)
			{
				echo "<a href='".admin_url("edit.php?s&post_type=mf_products&strFilterLocation=".$id)."'>".$count_temp."</a>";
			}
		break;
	}
}

function save_post_webshop($post_id, $post, $update)
{
	global $wpdb;

	if($post->post_type == 'mf_products')
	{
		if($update == true)
		{
			/*$obj_cache = new mf_cache();
			$obj_cache->clean_url = remove_protocol(array('url' => plugin_dir_url(__FILE__), 'clean' => true));

			$count_temp = $obj_cache->clear(array('allow_depth' => false));*/
		}

		else
		{
			$obj_webshop = new mf_webshop();

			$result = $wpdb->get_results("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = 'mf_categories' AND post_status = 'publish' AND meta_key = '".$obj_webshop->meta_prefix."connect_new_products' AND meta_value = 'yes'");

			foreach($result as $r)
			{
				$category_id = $r->ID;

				add_post_meta($post_id, $obj_webshop->meta_prefix.'category', $category_id);
			}
		}
	}
}

function before_save_meta_webshop($post_id)
{
	global $wpdb, $post;

	if($post->post_type == 'mf_categories')
	{
		$obj_webshop = new mf_webshop();

		$post_meta_new = check_var($obj_webshop->meta_prefix.'connect_new_products');
		$post_meta_old = get_post_meta($post_id, $obj_webshop->meta_prefix.'connect_new_products', false);

		if($post_meta_new == 'yes' && $post_meta_new != $post_meta_old)
		{
			$result = $wpdb->get_results("SELECT ID FROM ".$wpdb->posts." WHERE post_type = 'mf_products' AND post_status = 'publish'");

			foreach($result as $r)
			{
				$product_id = $r->ID;

				$meta_key = $obj_webshop->meta_prefix.'category';

				$arr_product_categories = get_post_meta($product_id, $meta_key, false);

				if(!in_array($post_id, $arr_product_categories))
				{
					add_post_meta($product_id, $meta_key, $post_id);
				}
			}
		}
	}
}

function shortcode_back_to_search()
{
	return "<div class='form_button alignleft'>
		<a href='#' id='mf_back_to_search' class='button button-primary hide'><i class='fa fa-chevron-left'></i> ".__("Continue Search", 'lang_webshop')."</a>
	</div>";
}