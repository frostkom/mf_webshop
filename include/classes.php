<?php

class mf_webshop
{
	function __construct()
	{
		$this->meta_prefix = "mf_ws_";

		$this->range_min = $this->range_max = "";
		$this->interval_amount = $this->interval_count = 0;
		$this->arr_interval_type_data = $this->post_name_for_type = array();

		$this->post_type_categories = 'mf_category';
		$this->post_type_products = 'mf_product';
		$this->post_type_custom_categories = 'mf_cust_cat';
		$this->post_type_document_type = 'mf_doc_type';
		$this->post_type_location = 'mf_location';
		$this->post_type_customers = 'mf_customer';
		$this->post_type_delivery_type = 'mf_delivery';

		$this->option_type = '';

		// Needs to be here because Poedit does not pick up this from below
		$arr_localize = array(
			__("Show all", 'lang_webshop'),
		);
	}

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

	function get_document_types_for_select($data = array())
	{
		global $wpdb;

		if(!isset($data['add_choose_here'])){	$data['add_choose_here'] = true;}
		if(!isset($data['include'])){			$data['include'] = '';}

		$query_where_key = $query_where_value = "";

		if($data['include'] != '')
		{
			$query_where_key = "meta_value = %s";
			$query_where_value = $data['include'];
		}

		$arr_data = array();

		if($data['add_choose_here'] == true)
		{
			$arr_data[''] = "-- ".__("Choose Here", 'lang_webshop')." --";
		}

		$result = $this->get_document_types(array(
			'select' => "ID, post_title",
			'join' => "INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = '".$this->meta_prefix."document_type'",
			'where_key' => $query_where_key,
			'where_value' => $query_where_value,
		));

		foreach($result as $r)
		{
			$arr_data[$r->ID] = $r->post_title;
		}

		return $arr_data;
	}

	function get_sort_for_select($include = array())
	{
		$arr_data = array();

		if(!is_array($include) || count($include) == 0 || in_array('alphabetical', $include))
		{
			$arr_data['alphabetical'] = __("A-Z", 'lang_webshop');
		}

		if(!is_array($include) || count($include) == 0 || in_array('newest', $include) || in_array('latest', $include))
		{
			$arr_data['latest'] = __("Latest", 'lang_webshop');
		}

		if(!is_array($include) || count($include) == 0 || in_array('popular', $include))
		{
			if(is_plugin_active("mf_form/index.php"))
			{
				$arr_data['popular'] = __("Popularity", 'lang_webshop');
			}
		}

		if(!is_array($include) || count($include) == 0 || in_array('random', $include))
		{
			$arr_data['random'] = __("Random", 'lang_webshop');
		}

		if(!is_array($include) || count($include) == 0 || in_array('size', $include))
		{
			if($this->get_post_name_for_type('size') != '')
			{
				$arr_data['size'] = __("Size", 'lang_webshop');
			}
		}

		return $arr_data;
	}

	function get_types_for_select()
	{
		$arr_data = array(
			'group_information' => "-- ".__("Information", 'lang_webshop')." --",
				'description' => __("Description", 'lang_webshop'),
				'heading' => __("Heading", 'lang_webshop'),
				'content' => __("Content", 'lang_webshop'),
				'label' => __("Label", 'lang_webshop'),
			'group_input' => "-- ".__("Input", 'lang_webshop')." --",
				'text' => __("Text", 'lang_webshop'),
				'textarea' => __("Textarea", 'lang_webshop'),
				'date' => __("Date", 'lang_webshop'),
				'email' => __("E-mail", 'lang_webshop'),
				'phone' => __("Phone Number", 'lang_webshop'),
				'url' => __("URL", 'lang_webshop'),
				'clock' => __("Clock", 'lang_webshop'),
				'checkbox' => __("Checkbox", 'lang_webshop'),
			'group_special_input' => "-- ".__("Special input", 'lang_webshop')." --",
				'color' => __("Color Picker", 'lang_webshop'),
				'event' => __("Event", 'lang_webshop'),
				'page' => __("Page", 'lang_webshop'),
				'file_advanced' => __("File", 'lang_webshop'),
				'categories' => get_option_or_default('setting_webshop_replace_categories', __("Categories", 'lang_webshop')),
				'custom_categories' => __("Custom Categories", 'lang_webshop'),
				'social' => __("Social Feed", 'lang_webshop'),
			'group_numbers' => "-- ".__("Numbers", 'lang_webshop')." --",
				'number' => __("Number", 'lang_webshop'),
				'price' => __("Number", 'lang_webshop')." (".__("Price", 'lang_webshop').")",
				'size' => __("Number", 'lang_webshop')." (".__("Size", 'lang_webshop').")",
				'stock' => __("Number", 'lang_webshop')." (".__("Stock", 'lang_webshop').")",
				'interval' => __("Interval", 'lang_webshop'),
			'group_location' => "-- ".__("Location", 'lang_webshop')." --",
				'location' => __("Location", 'lang_webshop'),
				'address' => __("Address", 'lang_webshop'),
				'local_address' => __("Local Address", 'lang_webshop'),
				'gps' => __("Map", 'lang_webshop'),
				//'map' => __("Map", 'lang_webshop'),
			'group_formatting' => "-- ".__("Formatting", 'lang_webshop')." --",
				'divider' => __("Divider", 'lang_webshop'),
				'container_start' => __("Start of container", 'lang_webshop'),
				'container_end' => __("End of container", 'lang_webshop'),
			'group_settings' => "-- ".__("Settings", 'lang_webshop')." --",
				'ghost' => __("Hide information", 'lang_webshop'),
				'global_code' => __("Global code", 'lang_webshop'),
		);

		//$arr_data = array_sort(array('array' => $arr_data, 'on' => 0, 'order' => 'asc', 'keep_index' => true));

		return $arr_data;
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

	function has_categories()
	{
		global $wpdb;

		$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = 'publish' LIMIT 0, 1", $this->post_type_categories.$this->option_type));

		return ($wpdb->num_rows > 0);
	}

	function get_option_types()
	{
		$this->arr_option_types = array('');

		$setting_webshop_option_types = get_option('setting_webshop_option_types');

		if($setting_webshop_option_types != '')
		{
			$this->arr_option_types = array_merge($this->arr_option_types, explode(",", $setting_webshop_option_types));
		}
	}

	function get_option_types_for_select()
	{
		$arr_data = array();

		$this->get_option_types();

		foreach($this->arr_option_types as $option_type)
		{
			$this->option_type = ($option_type != '' ? "_".$option_type : '');

			$arr_data[$option_type] = get_option_or_default('setting_webshop_replace_webshop'.$this->option_type, __("Webshop", 'lang_webshop'));
		}

		return $arr_data;
	}

	function check_if_previous_post_types_is_saved($option)
	{
		global $wpdb;

		$check_option = "setting_webshop_replace_webshop_";

		$result = $wpdb->get_results($wpdb->prepare("SELECT option_name FROM ".$wpdb->options." WHERE option_name LIKE %s", $check_option."%"));

		if($wpdb->num_rows > 0)
		{
			$arr_options = explode(",", $option);

			foreach($result as $r)
			{
				$option_name = str_replace($check_option, "", $r->option_name);

				if($option_name != '')
				{
					if(!in_array($option_name, $arr_options))
					{
						$arr_options[] = $option_name;
					}
				}

				else
				{
					do_log(sprintf(__("An option for %s exists in the DB", 'lang_webshop'), $r->option_name));
				}
			}

			$option = trim(implode(",", $arr_options), ",");
		}

		return $option;
	}

	function init()
	{
		global $wpdb;

		if(!session_id())
		{
			@session_start();
		}

		$this->get_option_types();

		foreach($this->arr_option_types as $option_type)
		{
			$this->option_type = ($option_type != '' ? "_".$option_type : '');

			$name_categories = get_option_or_default('setting_webshop_replace_categories'.$this->option_type, __("Categories", 'lang_webshop'));

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
					'slug' => get_option_or_default('setting_webshop_replace_categories_slug'.$this->option_type, 'c'),
				),
			);

			register_post_type($this->post_type_categories.$this->option_type, $args);

			$name_products = get_option_or_default('setting_webshop_replace_products'.$this->option_type, __("Products", 'lang_webshop'));

			$arr_supports = array('title', 'excerpt', 'author');

			if($this->get_post_name_for_type('content') == '')
			{
				$arr_supports[] = 'editor';
			}

			$labels = array(
				'name' => _x($name_products, 'post type general name'),
				'menu_name' => $name_products
			);

			$args = array(
				'labels' => $labels,
				'public' => true,
				'show_in_menu' => false,
				'show_in_nav_menus' => false,
				'supports' => $arr_supports,
				'hierarchical' => true,
				'has_archive' => false,
				'rewrite' => array(
					'slug' => get_option_or_default('setting_webshop_replace_products_slug'.$this->option_type, 'w'),
				),
			);

			register_post_type($this->post_type_products.$this->option_type, $args);

			$name_custom_categories = __("Custom Categories", 'lang_webshop');

			$labels = array(
				'name' => _x($name_custom_categories, 'post type general name'),
				'menu_name' => $name_custom_categories
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

			register_post_type($this->post_type_custom_categories.$this->option_type, $args);

			$name_doc_types = get_option_or_default('setting_webshop_replace_doc_types'.$this->option_type, __("Filters", 'lang_webshop'));

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

			register_post_type($this->post_type_document_type.$this->option_type, $args);

			$name_location = __("Location", 'lang_webshop');

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

			register_post_type($this->post_type_location.$this->option_type, $args);

			$name_customers = __("Customers", 'lang_webshop');

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

			register_post_type($this->post_type_customers.$this->option_type, $args);

			$name_delivery_type = __("Delivery Type", 'lang_webshop');

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

			register_post_type($this->post_type_delivery_type.$this->option_type, $args);
		}

		flush_rewrite_rules();
	}

	function settings_webshop()
	{
		global $wpdb;

		$options_area_orig = __FUNCTION__;

		$this->get_option_types();

		// Webshop
		############################
		$options_area = $options_area_orig."_parent";

		add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

		$arr_settings = array(
			'setting_webshop_option_types' => __("Types", 'lang_webshop'),
		);

		foreach($this->arr_option_types as $option_type)
		{
			$arr_settings['setting_webshop_replace_webshop|'.$option_type] = sprintf(__("Title for '%s'", 'lang_webshop'), ($option_type != '' ? $option_type : __("default", 'lang_webshop')));
		}

		$arr_settings['setting_webshop_local_storage'] = __("Local Storage", 'lang_webshop');

		show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
		############################

		foreach($this->arr_option_types as $option_type)
		{
			$this->option_type = ($option_type != '' ? "_".$option_type : '');

			$ghost_post_name = $this->get_post_name_for_type('ghost');

			$name_product = get_option_or_default('setting_webshop_replace_product'.$this->option_type, __("Product", 'lang_webshop'));

			// Generic
			############################
			$options_area = $options_area_orig;

			add_settings_section($options_area.'|'.$option_type, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

			$arr_settings = array(
				'setting_webshop_replace_product|'.$option_type => __("Replace Text", 'lang_webshop'),
				'setting_webshop_replace_products|'.$option_type => __("Replace Text", 'lang_webshop'),
				'setting_webshop_replace_categories|'.$option_type => __("Replace Text", 'lang_webshop'),
				'setting_webshop_replace_doc_types|'.$option_type => __("Replace Text", 'lang_webshop'),

				'setting_webshop_replace_products_slug|'.$option_type => sprintf(__("Replace %s slug with", 'lang_webshop'), strtolower($name_product)),
			);

			if($this->has_categories() > 0)
			{
				$name_categories = get_option_or_default('setting_webshop_replace_categories'.$this->option_type, __("Categories", 'lang_webshop'));

				$arr_settings['setting_show_categories|'.$option_type] = sprintf(__("Show %s on site", 'lang_webshop'), $name_categories);
				$arr_settings['setting_webshop_replace_categories_slug|'.$option_type] = sprintf(__("Replace %s slug with", 'lang_webshop'), strtolower($name_categories));
			}

			if(is_plugin_active("mf_form/index.php") && $option_type == '')
			{
				$arr_settings['setting_webshop_payment_form|'.$option_type] = __("Payment Form", 'lang_webshop');
			}

			show_settings_fields(array('area' => $options_area.'|'.$option_type, 'object' => $this, 'settings' => $arr_settings));
			############################

			//Search
			############################
			$options_area = $options_area_orig."_search";

			add_settings_section($options_area.'|'.$option_type, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

			$arr_settings = array(
				'setting_webshop_display_sort|'.$option_type => __("Display Sort", 'lang_webshop'),
				'setting_webshop_sort_default|'.$option_type => __("Sort Default", 'lang_webshop'),

				'setting_webshop_display_filter|'.$option_type => __("Display Filter", 'lang_webshop'),
				'setting_webshop_replace_filter_products|'.$option_type => __("Replace Text", 'lang_webshop'),

				'setting_search_max|'.$option_type => __("Max results to send quote", 'lang_webshop'),
				'setting_replace_search_result_info|'.$option_type => __("Replace Text", 'lang_webshop'),

				'setting_webshop_replace_choose_product|'.$option_type => __("Replace Text", 'lang_webshop'),

				'setting_webshop_switch_icon_on|'.$option_type => __("Switch Icon", 'lang_webshop')." (".__("On", 'lang_webshop').")",
				'setting_webshop_switch_icon_off|'.$option_type => __("Switch Icon", 'lang_webshop')." (".__("Off", 'lang_webshop').")",
			);

			if(is_plugin_active("mf_form/index.php"))
			{
				$arr_settings['setting_quote_form|'.$option_type] = __("Form for quote request", 'lang_webshop');
			}

			$arr_settings['setting_show_all_min|'.$option_type] = __("Min results to show number", 'lang_webshop');
			$arr_settings['setting_range_min_default|'.$option_type] = __("Default range minimum", 'lang_webshop');
			$arr_settings['setting_range_choices|'.$option_type] = __("Custom range choices", 'lang_webshop');
			$arr_settings['setting_require_search|'.$option_type] = __("Require user to make some kind of search", 'lang_webshop');
			$arr_settings['setting_webshop_replace_none_checked|'.$option_type] = __("Replace Text", 'lang_webshop');

			$arr_settings['setting_replace_send_request_for_quote|'.$option_type] = __("Replace Text", 'lang_webshop');
			$arr_settings['setting_replace_quote_request|'.$option_type] = __("Replace Text", 'lang_webshop');

			if(get_option('setting_require_search') == 'yes')
			{
				$arr_settings['setting_webshop_replace_too_many|'.$option_type] = __("Replace Text", 'lang_webshop');
			}

			show_settings_fields(array('area' => $options_area.'|'.$option_type, 'object' => $this, 'settings' => $arr_settings));
			############################

			/* Favorites */
			############################
			$options_area = $options_area_orig."_favorites";

			add_settings_section($options_area.'|'.$option_type, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

			$arr_settings = array(
				'setting_webshop_replace_favorites_info|'.$option_type => __("Replace Text", 'lang_webshop'),
				'setting_webshop_replace_email_favorites|'.$option_type => __("Replace Text", 'lang_webshop'),
				'setting_webshop_share_email_subject|'.$option_type => __("Email Subject", 'lang_webshop'),
				'setting_webshop_share_email_content|'.$option_type => __("Email Content", 'lang_webshop'),
			);

			show_settings_fields(array('area' => $options_area.'|'.$option_type, 'object' => $this, 'settings' => $arr_settings));
			############################

			/* Product */
			############################
			$options_area = $options_area_orig."_product";

			add_settings_section($options_area.'|'.$option_type, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

			$arr_settings = array(
				'setting_webshop_display_breadcrumbs|'.$option_type => __("Display Breadcrumbs", 'lang_webshop'),
				'setting_webshop_force_individual_contact|'.$option_type => __("Force Individual Contact", 'lang_webshop'),

				'setting_replace_add_to_search|'.$option_type => __("Replace Text", 'lang_webshop'),
				'setting_replace_remove_from_search|'.$option_type => __("Replace Text", 'lang_webshop'),
				'setting_replace_return_to_search|'.$option_type => __("Replace Text", 'lang_webshop'),
				'setting_replace_search_for_another|'.$option_type => __("Replace Text", 'lang_webshop'),
			);

			if(is_plugin_active("mf_form/index.php")) // && get_option('setting_webshop_force_individual_contact') == 'yes' //Don't even think about this, because it is still used when new vistors arrive at the product page
			{
				$arr_settings['setting_quote_form_single|'.$option_type] = __("Form for quote request (single)", 'lang_webshop');
			}

			show_settings_fields(array('area' => $options_area.'|'.$option_type, 'object' => $this, 'settings' => $arr_settings));
			############################

			//Map
			############################
			$options_area = $options_area_orig."_map";

			add_settings_section($options_area.'|'.$option_type, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

			$arr_settings = array();

			if(!is_plugin_active('mf_maps/index.php'))
			{
				$arr_settings['setting_gmaps_api|'.$option_type] = __("API key", 'lang_webshop');
			}

			$arr_settings['setting_map_visibility|'.$option_type] = __("Map visibility", 'lang_webshop');
			$arr_settings['setting_map_visibility_mobile|'.$option_type] = __("Map visibility", 'lang_webshop')." (".__("Mobile", 'lang_webshop').")";
			$arr_settings['setting_webshop_symbol_inactive_image|'.$option_type] = __("Symbol inactive image", 'lang_webshop');
			$arr_settings['setting_webshop_symbol_active_image|'.$option_type] = __("Symbol active image", 'lang_webshop');

			if($ghost_post_name != '')
			{
				$arr_settings['setting_ghost_inactive_image|'.$option_type] = __("Ghost symbol inactive image", 'lang_webshop');
				$arr_settings['setting_ghost_active_image|'.$option_type] = __("Ghost symbol active image", 'lang_webshop');
			}

			if(get_option('setting_webshop_symbol_active_image'.$this->option_type) == '')
			{
				$arr_settings['setting_webshop_symbol_inactive|'.$option_type] = __("Symbol inactive color", 'lang_webshop');
				$arr_settings['setting_webshop_symbol_active|'.$option_type] = __("Symbol active color", 'lang_webshop');
			}

			$arr_settings['setting_webshop_replace_show_map|'.$option_type] = __("Replace Text", 'lang_webshop');
			$arr_settings['setting_replace_hide_map|'.$option_type] = __("Replace Text", 'lang_webshop');
			$arr_settings['setting_map_info|'.$option_type] = __("Map Information", 'lang_webshop');

			$arr_settings['setting_webshop_color_info|'.$option_type] = __("Info color", 'lang_webshop');
			$arr_settings['setting_webshop_text_color_info|'.$option_type] = __("Info text color", 'lang_webshop');

			show_settings_fields(array('area' => $options_area.'|'.$option_type, 'object' => $this, 'settings' => $arr_settings));
			############################
		}

		$this->option_type = '';
	}

	function settings_webshop_parent_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$setting_webshop_replace_webshop = __("Webshop", 'lang_webshop');

		echo settings_header($setting_key, $setting_webshop_replace_webshop);
	}

		function setting_webshop_option_types_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option($setting_key);

			$option = $this->check_if_previous_post_types_is_saved($option);

			echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => 'cars,planes,boats,bikes'));
		}

		function setting_webshop_replace_webshop_callback($args = array())
		{
			$setting_key = get_setting_key(__FUNCTION__, $args);
			$option = get_option($setting_key);

			echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Webshop", 'lang_webshop')));
		}

		function setting_webshop_local_storage_callback()
		{
			echo "<div class='form_buttons'>"
				.show_button(array('type' => 'button', 'name' => 'btnLocalStorageClear', 'text' => __("Clear", 'lang_webshop'), 'class' => 'button'))
			."</div>
			<div id='storage_response'></div>";
		}

	function get_settings_header_type($args)
	{
		if(isset($args['id']) && preg_match("/\|/", $args['id']))
		{
			list($parent, $option_type) = explode("|", $args['id']);
		}

		else
		{
			$option_type = '';
		}

		return $option_type;
	}

	function settings_webshop_callback($args = array())
	{
		$option_type = $this->get_settings_header_type($args);

		$setting_key = get_setting_key(__FUNCTION__, array('child' => $option_type));
		$setting_webshop_replace_webshop = get_option_or_default('setting_webshop_replace_webshop'.($option_type != '' ? "_".$option_type : ''), __("Webshop", 'lang_webshop'));

		echo settings_header($setting_key, $setting_webshop_replace_webshop);
	}

	function settings_webshop_map_callback($args = array())
	{
		$option_type = $this->get_settings_header_type($args);

		$setting_key = get_setting_key(__FUNCTION__, array('child' => $option_type));
		$setting_webshop_replace_webshop = get_option_or_default('setting_webshop_replace_webshop'.($option_type != '' ? "_".$option_type : ''), __("Webshop", 'lang_webshop'));

		echo settings_header($setting_key, $setting_webshop_replace_webshop." - ".__("Map", 'lang_webshop'));
	}

	function settings_webshop_favorites_callback($args = array())
	{
		$option_type = $this->get_settings_header_type($args);

		$setting_key = get_setting_key(__FUNCTION__, array('child' => $option_type));
		$setting_webshop_replace_webshop = get_option_or_default('setting_webshop_replace_webshop'.($option_type != '' ? "_".$option_type : ''), __("Webshop", 'lang_webshop'));

		echo settings_header($setting_key, $setting_webshop_replace_webshop." - ".__("Favorites", 'lang_webshop'));
	}

	function settings_webshop_product_callback($args = array())
	{
		$option_type = $this->get_settings_header_type($args);

		$setting_key = get_setting_key(__FUNCTION__, array('child' => $option_type));
		$setting_webshop_replace_webshop = get_option_or_default('setting_webshop_replace_webshop'.($option_type != '' ? "_".$option_type : ''), __("Webshop", 'lang_webshop'));
		$setting_webshop_replace_product = get_option_or_default('setting_webshop_replace_product'.($option_type != '' ? "_".$option_type : ''), __("Product", 'lang_webshop'));

		echo settings_header($setting_key, $setting_webshop_replace_webshop." - ".$setting_webshop_replace_product);
	}

	function settings_webshop_search_callback($args = array())
	{
		$option_type = $this->get_settings_header_type($args);

		$setting_key = get_setting_key(__FUNCTION__, array('child' => $option_type));
		$setting_webshop_replace_webshop = get_option_or_default('setting_webshop_replace_webshop'.($option_type != '' ? "_".$option_type : ''), __("Webshop", 'lang_webshop'));

		echo settings_header($setting_key, $setting_webshop_replace_webshop." - ".__("Search", 'lang_webshop'));
	}

	function setting_webshop_replace_product_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Product", 'lang_webshop')));
	}

	function setting_webshop_replace_products_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Products", 'lang_webshop')));
	}

	function setting_webshop_replace_products_slug_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => "w", 'description' => ($option != '' ? get_site_url()."/<strong>".$option."</strong>/abc" : "")));
	}

	function setting_show_categories_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key, 'no');

		echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
	}

	function setting_webshop_replace_categories_slug_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => "c", 'description' => ($option != '' ? get_site_url()."/".$option."/abc" : "")));
	}

	function setting_webshop_replace_categories_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Categories", 'lang_webshop')));
	}

	function setting_webshop_replace_doc_types_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Filters", 'lang_webshop')));
	}

	function setting_webshop_replace_filter_products_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Filter amongst %s products", 'lang_webshop'), 'description' => __("Disable by adding any single character", 'lang_webshop')));
	}

	function setting_replace_search_result_info_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Your search matches %s products", 'lang_webshop'), 'description' => __("Disable by adding any single character", 'lang_webshop')));
	}

	function setting_webshop_replace_favorites_info_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textarea(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Here are your %s saved products", 'lang_webshop'), 'description' => __("Disable by adding any single character", 'lang_webshop')));
	}

	function setting_replace_send_request_for_quote_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Send request for quote", 'lang_webshop')));
	}

	function setting_webshop_replace_choose_product_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Choose", 'lang_webshop')));
	}

	function setting_webshop_switch_icon_on_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => "fa fa-check green"));
	}

	function setting_webshop_switch_icon_off_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => "fa fa-times red"));
	}

	function setting_replace_add_to_search_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Add to Search", 'lang_webshop')));
	}

	function setting_replace_remove_from_search_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Remove from Search", 'lang_webshop')));
	}

	function setting_replace_return_to_search_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Return to Search", 'lang_webshop')));
	}

	function setting_replace_search_for_another_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Search for Another", 'lang_webshop')));
	}

	function setting_replace_quote_request_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		$name_products = get_option_or_default('setting_webshop_replace_products', __("Products", 'lang_webshop'));

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Send request for quote to", 'lang_webshop')." %s ".strtolower($name_products)));
	}

	function setting_webshop_replace_none_checked_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("You have to choose at least one product to proceed", 'lang_webshop')));
	}

	function setting_webshop_replace_email_favorites_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Email Your Products", 'lang_webshop')));
	}

	function setting_webshop_replace_too_many_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("In order to send a quote you have to be specific what you want by filtering", 'lang_webshop')));
	}

	function setting_webshop_share_email_subject_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key, __("I would like to share these products that I like", 'lang_webshop'));

		echo show_textfield(array('name' => $setting_key, 'value' => $option));
	}

	function setting_webshop_share_email_content_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key, sprintf(__("Here are my favorites (%s)", 'lang_webshop'), "[url]"));

		echo show_wp_editor(array('name' => $setting_key, 'value' => $option,
			'class' => "hide_media_button hide_tabs",
			'mini_toolbar' => true,
			'editor_height' => 100,
			//'statusbar' => false,
		));
	}

	function setting_gmaps_api_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		$suffix = ($option == '' ? "<a href='//developers.google.com/maps/documentation/javascript/get-api-key'>".__("Get yours here", 'lang_webshop')."</a>" : "");

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'suffix' => $suffix));
	}

	function setting_map_visibility_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_select(array('data' => $this->get_map_visibility_for_select(), 'name' => $setting_key, 'value' => $option));
	}

	function setting_map_visibility_mobile_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_select(array('data' => $this->get_map_visibility_for_select(), 'name' => $setting_key, 'value' => $option));
	}

	function setting_webshop_replace_show_map_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Show Map", 'lang_webshop')));
	}

	function setting_replace_hide_map_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Hide Map", 'lang_webshop')));
	}

	function setting_map_info_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_wp_editor(array('name' => $setting_key, 'value' => $option,
			'class' => "hide_media_button hide_tabs",
			'mini_toolbar' => true,
			'editor_height' => 100,
			//'statusbar' => false,
		));
	}

	function setting_range_min_default_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key, 10);

		echo show_textfield(array('type' => 'number', 'name' => $setting_key, 'value' => $option, 'xtra' => "min='0' max='100'", 'suffix' => "%", 'description' => __("If no lower value is entered in a range, this percentage is used to calculate the lower end of the range", 'lang_webshop')));
	}

	function setting_range_choices_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => '1-50,50-100,1000+'));
	}

	function setting_webshop_display_sort_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		if($option == 'yes')
		{
			$option = array('latest', 'random', 'alphabetical', 'size');
		}

		echo show_select(array('data' => $this->get_sort_for_select(), 'name' => $setting_key."[]", 'value' => $option));
	}

	function setting_webshop_sort_default_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option_or_default($setting_key, 'size');

		echo show_select(array('data' => $this->get_sort_for_select(get_option('setting_webshop_display_sort')), 'name' => $setting_key, 'value' => $option));
	}

	function setting_webshop_display_filter_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option_or_default($setting_key, 'yes');

		$arr_data = array(
			'yes' => __("Yes", 'lang_webshop'),
			'button' => __("Yes", 'lang_webshop')." (".__("Hidden behind a button", 'lang_webshop').")",
			'no' => __("No", 'lang_webshop'),
		);

		echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option));
	}

	function setting_search_max_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option_or_default($setting_key, 50);

		echo show_textfield(array('type' => 'number', 'name' => $setting_key, 'value' => $option, 'xtra' => "min='10' max='100'"));
	}

	function setting_show_all_min_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option_or_default($setting_key, 30);

		echo show_textfield(array('type' => 'number', 'name' => $setting_key, 'value' => $option, 'xtra' => "min='0' max='100'", 'suffix' => sprintf(__("%d will hide the link in the form", 'lang_webshop'), 0)));
	}

	function setting_require_search_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option_or_default($setting_key, 'yes');

		echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
	}

	function setting_webshop_color_info_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key, "#eeeeee");

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'type' => 'color'));
	}

	function setting_webshop_text_color_info_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key, "#000000");

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'type' => 'color'));
	}

	function setting_webshop_symbol_active_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key, "#b8c389");

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'type' => 'color'));
	}

	function setting_webshop_symbol_inactive_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key, "#c78e91");

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'type' => 'color'));
	}

	function setting_webshop_symbol_inactive_image_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo get_media_library(array('name' => $setting_key, 'value' => $option, 'type' => 'image'));
	}

	function setting_webshop_symbol_active_image_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo get_media_library(array('name' => $setting_key, 'value' => $option, 'type' => 'image'));
	}

	function setting_ghost_inactive_image_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo get_media_library(array('name' => $setting_key, 'value' => $option, 'type' => 'image'));
	}

	function setting_ghost_active_image_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo get_media_library(array('name' => $setting_key, 'value' => $option, 'type' => 'image'));
	}

	function setting_quote_form_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		$obj_form = new mf_form();

		echo show_select(array('data' => $obj_form->get_for_select(), 'name' => $setting_key, 'value' => $option, 'suffix' => "<a href='".admin_url("admin.php?page=mf_form/create/index.php")."'><i class='fa fa-plus-circle fa-lg'></i></a>"));
	}

	function setting_quote_form_single_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		$obj_form = new mf_form();

		echo show_select(array('data' => $obj_form->get_for_select(), 'name' => $setting_key, 'value' => $option, 'suffix' => "<a href='".admin_url("admin.php?page=mf_form/create/index.php")."'><i class='fa fa-plus-circle fa-lg'></i></a>"));
	}

	function setting_webshop_display_breadcrumbs_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key, 'no');

		echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
	}

	function setting_webshop_force_individual_contact_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key, 'yes');

		echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option, 'description' => __("This will allow visitors to send individual quote requests all the time, otherwise it is only for first time visitors coming directly to the page that have this option", 'lang_webshop')));
	}

	function setting_webshop_payment_form_callback($args = array())
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		$obj_form = new mf_form();

		echo show_select(array('data' => $obj_form->get_for_select(array('local_only' => true, 'force_has_page' => false)), 'name' => $setting_key, 'value' => $option, 'suffix' => "<a href='".admin_url("admin.php?page=mf_form/create/index.php")."'><i class='fa fa-plus-circle fa-lg'></i></a>"));
	}

	function combined_head()
	{
		$obj_theme_core = new mf_theme_core();
		$obj_theme_core->get_params();

		$setting_mobile_breakpoint = isset($this->options['mobile_breakpoint']) ? $this->options['mobile_breakpoint'] : 600;

		$setting_gmaps_api = get_option('setting_gmaps_api');
		$symbol_active_image = get_option('setting_webshop_symbol_active_image');
		$symbol_active = get_option('setting_webshop_symbol_active');

		$plugin_base_url = plugins_url();
		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		mf_enqueue_style('style_webshop', $plugin_include_url."style.php", $plugin_version);
		mf_enqueue_style('style_bb', $plugin_base_url."/mf_base/include/backbone/style.css", $plugin_version);

		wp_enqueue_script('script_gmaps_api', "//maps.googleapis.com/maps/api/js?v=3.exp&libraries=places&key=".$setting_gmaps_api, array(), $plugin_version);
		mf_enqueue_script('script_webshop', $plugin_include_url."script.js", array(
			'here_i_am' => __("Here I am", 'lang_webshop'),
			'plugins_url' => $plugin_base_url,
			'read_more' => __("Read More", 'lang_webshop'),
			'symbol_active_image' => $symbol_active_image,
			'symbol_active' => trim($symbol_active, "#"),
			'mobile_breakpoint' => $setting_mobile_breakpoint,
			'product_missing' => get_option_or_default('setting_webshop_replace_none_checked', __("You have to choose at least one product to proceed", 'lang_webshop')), //__("You have to select at least one before you submit", 'lang_webshop')
		), $plugin_version);
	}

	function admin_init()
	{
		$this->combined_head();

		global $pagenow;

		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		if($pagenow == 'options-general.php' && check_var('page') == 'settings_mf_base')
		{
			mf_enqueue_script('script_storage', plugins_url()."/mf_base/include/jquery.Storage.js", $plugin_version);
			mf_enqueue_script('script_webshop_wp', $plugin_include_url."script_wp.js", array('cleared_message' => __("Local Storage was successfully cleared on this device", 'lang_webshop')), $plugin_version);
		}

		if($pagenow == 'admin.php' && check_var('page') == 'mf_webshop/stats/index.php')
		{
			mf_enqueue_script('jquery-flot', plugins_url()."/mf_base/include/jquery.flot.min.0.7.js", $plugin_version);
		}
	}

	function updated_option($option_name, $old_value, $option_value)
	{
		if($option_name == 'setting_webshop_option_types')
		{
			$updated = false;
			$max_length = 8;

			$arr_options = explode(",", $option_value);

			foreach($arr_options as $key => $value)
			{
				if(strlen($value) > $max_length)
				{
					$arr_options[$key] = substr($value, 0, $max_length);

					$updated = true;
				}
			}

			if($updated == true)
			{
				update_option('setting_webshop_option_types', trim(implode(",", $arr_options), ","));
			}
		}
	}

	function add_policy($content)
	{
		$name_webshop = get_option_or_default('setting_webshop_replace_webshop', __("Webshop", 'lang_webshop'));

		$content .= "<h3>".$name_webshop."</h3>
		<p>"
			.__("When searching we store information in the so called 'localStorage' in the visiting browser. This data is only used on the site to remember what was last saved and not sent forward to the server unless the visitor fullfills an inquiry. Then the information is sent along with the form to distribute the inquiry to the correct recipients.", 'lang_webshop')
		."</p>";

		return $content;
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

	function wp_head()
	{
		$this->combined_head();

		$plugin_base_include_url = plugins_url()."/mf_base/include/";
		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		mf_enqueue_script('underscore');
		mf_enqueue_script('backbone');
		mf_enqueue_script('script_storage', $plugin_base_include_url."jquery.Storage.js", $plugin_version);
		mf_enqueue_script('script_base_plugins', $plugin_base_include_url."backbone/bb.plugins.js", $plugin_version);
		//mf_enqueue_script('script_webshop_plugins', $plugin_include_url."backbone/bb.plugins.js", array('plugin_url' => $plugin_include_url), $plugin_version);
		mf_enqueue_script('script_webshop_router', $plugin_include_url."backbone/bb.router.js", $plugin_version);
		mf_enqueue_script('script_webshop_models', $plugin_include_url."backbone/bb.models.js", array('plugin_url' => $plugin_include_url), $plugin_version);
		mf_enqueue_script('script_webshop_views', $plugin_include_url."backbone/bb.views.js", array(
			'site_url' => get_site_url(),
			'force_individual_contact' => get_option('setting_webshop_force_individual_contact'),
			'symbol_inactive' => get_option_or_default('setting_webshop_symbol_inactive_image', $this->get_map_marker_url('setting_webshop_symbol_inactive')),
			'symbol_active' => get_option_or_default('setting_webshop_symbol_active_image', $this->get_map_marker_url('setting_webshop_symbol_active')),
			'ghost_inactive' => get_option('setting_ghost_inactive_image'),
			'ghost_active' => get_option('setting_ghost_active_image'),
			'search_max' => get_option_or_default('setting_search_max', 50),
			'show_all_min' => get_option_or_default('setting_show_all_min', 30),
			'require_search' => get_option('setting_require_search'),
		), $plugin_version);
		mf_enqueue_script('script_base_init', $plugin_base_include_url."backbone/bb.init.js", $plugin_version);
	}

	function get_option_type_from_post_id($post_id)
	{
		$post_type = get_post_type($post_id);
		$option_type = str_replace($this->post_type_products, "", $post_type);
		$this->option_type = ($option_type != '' ? "_".$option_type : '');
	}

	function wp_head_single_product()
	{
		global $wpdb, $post;

		$post_id = $post->ID;

		if($post_id > 0 && get_the_excerpt() == '')
		{
			$this->get_option_type_from_post_id($post_id);

			$size_post_name = $this->get_post_name_for_type('description');
			$product_description = get_post_meta($post_id, $this->meta_prefix.$size_post_name, true);

			if($product_description != '')
			{
				echo "<meta name='description' content='".esc_attr($product_description)."'>";
			}
		}
	}

	function widgets_init()
	{
		register_widget('widget_webshop_search');
		register_widget('widget_webshop_map');
		register_widget('widget_webshop_form');
		register_widget('widget_webshop_list');
		register_widget('widget_webshop_favorites');
		register_widget('widget_webshop_recent');
	}

	function uninit()
	{
		@session_destroy();
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
				$post_id = $this->get_document_types(array('select' => "ID", 'where_key' => "post_name = %s", 'where_value' => $data['key'], 'limit' => "0, 1"));

				if($post_id > 0)
				{
					return true;
				}
			}
		}

		return false;
	}

	function filter_form_after_fields($out)
	{
		global $wpdb;

		$out_left = $out_right = "";

		$setting_search_max = get_option_or_default('setting_search_max', 50);
		$name_choose = get_option_or_default('setting_webshop_replace_choose_product', __("Choose", 'lang_webshop'));
		$address_post_name = $this->get_post_name_for_type('address');

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
						$query_join = " LEFT JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = '".$this->meta_prefix.$address_post_name."'";
					}

					$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_excerpt, post_content FROM ".$wpdb->posts.$query_join." WHERE post_type = %s AND post_status = 'publish' AND ID = '%d'", $this->post_type_products, $product_id));

					foreach($result as $r)
					{
						$arr_product = array();

						$this->get_product_data(array('product' => $r, 'single_image' => true, 'show_location_in_data' => false), $arr_product);

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
								<div class='product_image_container'>"
									.$arr_product['product_image'];

									if($arr_product['product_data'] != '')
									{
										$out_left .= "<div class='product_data'>".$arr_product['product_data']."</div>";
									}

								$out_left .= "</div>";

								if(get_option('setting_webshop_payment_form') > 0)
								{
									$out_left .= show_checkbox(array('name' => $key.'[]', 'value' => $product_id, 'text' => $name_choose, 'compare' => $product_id, 'switch' => true, 'switch_icon_on' => get_option('setting_webshop_switch_icon_on'), 'switch_icon_off' => get_option('setting_webshop_switch_icon_off'), 'xtra_class' => 'color_button_2'));
								}

								$out_left .= "<ul class='product_meta product_column'>";

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

			else if($this->is_a_webshop_meta_value(array('key' => $key, 'value' => $value)))
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

	function filter_form_on_submit($data)
	{
		global $wpdb, $error_text;

		$obj_form = new mf_form();

		$answer_text = "";

		$arr_product_ids = array();

		foreach($_REQUEST as $key => $value)
		{
			if($this->is_a_webshop_meta_value(array('key' => $key, 'value' => $value)))
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

					$result = $this->get_document_types(array('select' => "ID, post_title", 'where_key' => "post_name = %s", 'where_value' => $key));

					foreach($result as $r)
					{
						$post_id = $r->ID;
						$key = $r->post_title;

						$post_custom_type = get_post_meta($post_id, $this->meta_prefix.'document_type', true);

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
				$email_post_name = $this->get_post_name_for_type('email');

				$product_email = get_post_meta($product_id, $this->meta_prefix.$email_post_name, true);

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

				$this->insert_sent(array('product_id' => $product_id, 'answer_id' => $obj_form->answer_id));
			}
		}

		if($answer_text != '')
		{
			$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_answer SET answerID = '%d', form2TypeID = '0', answerText = %s", $data['obj_form']->answer_id, $answer_text));
		}

		$data['obj_form']->arr_email_content = $arr_mail_content_temp;

		return $data;
	}

	function shortcode_back_to_search()
	{
		return "<div class='form_button alignleft'>
			<a href='#' id='mf_back_to_search' class='button button-primary hide'><i class='fa fa-chevron-left'></i> ".__("Continue Search", 'lang_webshop')."</a>
		</div>";
	}

	function set_interval_amount($result)
	{
		foreach($result as $r)
		{
			$post_id = $r->ID;

			$post_custom_type = get_post_meta($post_id, $this->meta_prefix.'document_type', true);

			if($post_custom_type == 'interval')
			{
				$this->interval_amount++;
			}
		}
	}

	function increase_count()
	{
		$this->interval_count++;
	}

	function add_interval_type($key, $value)
	{
		$this->arr_interval_type_data[$key] = $value;
	}

	function has_equal_amount($post_title, $name_choose_here)
	{
		$out = "";

		if($this->interval_count == $this->interval_amount)
		{
			$arr_data = array(
				'' => $name_choose_here,
			);

			$setting_range_choices = get_option('setting_range_choices');

			if($setting_range_choices != '')
			{
				foreach(array_map('trim', explode(",", $setting_range_choices)) as $option)
				{
					//$option = trim($option);

					$arr_data[str_replace("+", "-", $option)] = $option;
				}
			}

			else
			{
				$this->calculate_range($arr_data);
			}

			$out = show_select(array('data' => $arr_data, 'name' => 'interval_range', 'text' => $post_title, 'value' => check_var('interval_range', 'char')));

			if(count($this->arr_interval_type_data) > 1)
			{
				$out .= show_select(array('data' => $this->arr_interval_type_data, 'name' => 'interval_type', 'text' => implode("/", $this->arr_interval_type_data), 'value' => check_var('interval_type', 'char')));
			}

			else
			{
				$out .= input_hidden(array('name' => 'interval_type', 'value' => $this->arr_interval_type_data[0][0]));
			}
		}

		return $out;
	}

	function calculate_range(&$arr_data)
	{
		$range_steps = 8;

		$range_step = ceil(($this->range_max - $this->range_min) / $range_steps / 10) * 10;

		for($i = 0; $i < $range_steps; $i++)
		{
			$start = $i * $range_step;
			$end = $start + $range_step;
			$range_span = $start." - ".$end;

			$is_last = $i == ($range_steps - 1);

			$arr_data[str_replace(" ", "", $range_span)] = $range_span.($is_last ? "+" : "");
		}
	}

	function set_range($value_min)
	{
		if($this->range_min == '' || $value_min < $this->range_min)
		{
			$this->range_min = $value_min;
		}

		if($this->range_max == '' || $value_min > $this->range_max)
		{
			$this->range_max = $value_min;
		}
	}

	function get_location_order($data = array())
	{
		global $wpdb;

		if(!isset($data['parent'])){	$data['parent'] = 0;}

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_parent = %s AND ID IN('".implode("','", $data['array'])."')", $this->post_type_location, $data['parent']));

		foreach($result as $r)
		{
			$location_id = $r->ID;

			$post_meta = get_post_meta($location_id, $this->meta_prefix.'location_hidden', true);

			if($post_meta != 'yes')
			{
				$this->arr_locations[] = $location_id;
			}

			$this->get_location_order(array('parent' => $location_id, 'array' => $data['array']));
		}
	}

	function sort_location($data)
	{
		if(!isset($data['array'])){		$data['array'] = array();}
		if(!isset($data['reverse'])){	$data['reverse'] = false;}

		$this->arr_locations = array();

		$this->get_location_order($data);

		if($data['reverse'] == true)
		{
			$this->arr_locations = array_reverse($this->arr_locations);
		}

		return $this->arr_locations;
	}

	/* Admin */
	function admin_menu_payment_page()
	{
		global $wpdb;

		$form_id = get_option('setting_webshop_payment_form'.$this->option_type);

		$obj_form = new mf_form($form_id);

		echo "<div class='wrap'>
			<h1>".$obj_form->get_form_name()."</h1>"
			.apply_filters('the_content', "[mf_form id=".$form_id."]")
		."</div>";
	}

	function confirm_payment($data = array())
	{
		global $wpdb;

		if(!isset($data['paid'])){		$data['paid'] = 0;}

		if(!isset($data['user_id']))
		{
			if(get_current_user_id() > 0)
			{
				$data['user_id'] = get_current_user_id();
			}

			else
			{
				$obj_form = new mf_form();

				$data['user_id'] = $obj_form->get_meta(array('id' => $data['answer_id'], 'meta_key' => 'user_id'));
			}
		}

		if($data['paid'] > 0 && $data['user_id'] > 0)
		{
			$amount = $data['paid'];

			if($amount > 0)
			{
				$meta_key = 'profile_webshop_payment';
				$meta_value = get_the_author_meta($meta_key, $data['user_id']);

				if($meta_value > date('Y-m-d'))
				{
					$meta_value = date('Y-m-d', strtotime($meta_value." +12 month"));
				}

				else
				{
					$meta_value = date('Y-m-d', strtotime("+12 month"));
				}

				update_user_meta($data['user_id'], $meta_key, $meta_value);
			}

			else
			{
				do_log(__("The payment wasn't done correctly", 'lang_webshop')." (".var_export($data, true).")");
			}
		}

		else
		{
			$name_webshop = get_option_or_default('setting_webshop_replace_webshop', __("Webshop", 'lang_webshop'));

			do_log(sprintf(__("Something was missing when a user paid for access to %s (%s)", 'lang_webshop'), $name_webshop, var_export($data, true)));
		}
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
			$count_message = "&nbsp;<span class='update-plugins' title='".__("Unread Orders", 'lang_webshop')."'>
				<span>".$rows."</span>
			</span>";
		}

		return $count_message;
	}

	function admin_menu()
	{
		global $wpdb;

		$this->get_option_types();

		foreach($this->arr_option_types as $option_type)
		{
			$this->option_type = ($option_type != '' ? "_".$option_type : '');

			$menu_root = 'mf_webshop/';
			$menu_start = "edit.php?post_type=".$this->post_type_products.$this->option_type;
			$menu_capability = override_capability(array('page' => $menu_start, 'default' => 'edit_posts'));

			$name_webshop = get_option_or_default('setting_webshop_replace_webshop'.$this->option_type, __("Webshop", 'lang_webshop'));

			if(IS_EDITOR)
			{
				$name_products = get_option_or_default('setting_webshop_replace_products'.$this->option_type, __("Products", 'lang_webshop'));

				$count_message = "";

				$result = $wpdb->get_results("SELECT orderID FROM ".$wpdb->prefix."webshop_order LIMIT 0, 1");
				$rows_order = $wpdb->num_rows;

				$result = $wpdb->get_results("SELECT answerID FROM ".$wpdb->prefix."webshop_sent LIMIT 0, 1");
				$rows_stats = $wpdb->num_rows;

				if($rows_order > 0)
				{
					$count_message = $this->count_orders_webshop();
				}

				add_menu_page($name_webshop, $name_webshop.$count_message, $menu_capability, $menu_start, '', 'dashicons-cart', 21);
				add_submenu_page($menu_start, $name_products, $name_products, $menu_capability, $menu_start);

				$menu_title = get_option_or_default('setting_webshop_replace_categories'.$this->option_type, __("Categories", 'lang_webshop'));
				add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, "edit.php?post_type=".$this->post_type_categories.$this->option_type);

				if($this->get_post_name_for_type('custom_categories') != '')
				{
					$menu_title = __("Custom Categories", 'lang_webshop');
					add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, "edit.php?post_type=".$this->post_type_custom_categories.$this->option_type);
				}

				if($this->get_post_name_for_type('location') != '')
				{
					$menu_title = __("Location", 'lang_webshop');
					add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, "edit.php?post_type=".$this->post_type_location.$this->option_type);
				}

				$menu_title = get_option_or_default('setting_webshop_replace_doc_types'.$this->option_type, __("Filters", 'lang_webshop'));
				add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, "edit.php?post_type=".$this->post_type_document_type.$this->option_type);

				if($rows_order > 0)
				{
					$menu_title = __("Orders", 'lang_webshop');
					add_submenu_page($menu_start, $menu_title, $menu_title.$count_message, $menu_capability, $menu_root.'orders/index.php');
				}

				/*if($this->get_post_name_for_type('price') != '')
				{
					$menu_title = __("Customers", 'lang_webshop');
					add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, "edit.php?post_type=".$this->post_type_customers.$this->option_type);

					$menu_title = __("Delivery Type", 'lang_webshop');
					add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, "edit.php?post_type=".$this->post_type_delivery_type.$this->option_type);
				}

				$menu_title = __("Import", 'lang_webshop');
				add_submenu_page($menu_start, $menu_title, $menu_title." ".strtolower($name_products), $menu_capability, $menu_root.'import/index.php');*/

				if($rows_stats > 0)
				{
					$menu_title = __("Statistics", 'lang_webshop');
					add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_root.'stats/index.php');
				}

				if($this->option_type == '' && is_plugin_active("mf_group/index.php") && $this->get_post_name_for_type('email') != '')
				{
					$menu_title = __("Send e-mail to all", 'lang_webshop')." ".strtolower($name_products);
					add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_root.'group/index.php');
				}

				if(get_option('setting_webshop_payment_form'.$this->option_type) > 0 && get_the_author_meta('profile_webshop_payment', get_current_user_id()) < date('Y-m-d'))
				{
					$menu_title = sprintf(__("Pay to Access %s", 'lang_webshop'), $name_webshop);
					add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, 'admin_menu_payment_page', array($this, 'admin_menu_payment_page'));
				}
			}

			else
			{
				if(get_option('setting_webshop_payment_form'.$this->option_type) > 0 && get_the_author_meta('profile_webshop_payment', get_current_user_id()) < date('Y-m-d'))
				{
					$menu_title = sprintf(__("Pay to Access %s", 'lang_webshop'), $name_webshop);

					add_menu_page($menu_title, $menu_title, $menu_capability, $menu_start, array($this, 'admin_menu_payment_page'), 'dashicons-cart', 21);
				}

				else
				{
					add_menu_page($name_webshop, $name_webshop, $menu_capability, $menu_start, '', 'dashicons-cart', 21);
				}
			}
		}

		$this->option_type = '';
	}

	function get_symbols_for_select()
	{
		$obj_font_icons = new mf_font_icons();

		$arr_icons = $obj_font_icons->get_array(array('allow_optgroup' => false));

		$arr_data = array(
			'' => "-- ".__("Choose Here", 'lang_webshop')." --",
		);

		foreach($arr_icons as $icon)
		{
			$arr_data[$icon] = $icon;
		}

		return $arr_data;
	}

	function rwmb_meta_boxes($meta_boxes)
	{
		global $wpdb;

		$this->get_option_types();

		foreach($this->arr_option_types as $option_type)
		{
			$this->option_type = ($option_type != '' ? "_".$option_type : '');

			//Default values for type
			################
			$post_id = $this->get_document_types(array('select' => "ID", 'order' => "post_date DESC", 'limit' => "0, 1"));

			$default_type = get_post_meta($post_id, $this->meta_prefix.'document_type', true);
			$default_searchable = get_post_meta($post_id, $this->meta_prefix.'document_searchable', true);
			$default_public = get_post_meta($post_id, $this->meta_prefix.'document_public', true);
			$default_public_single = get_post_meta($post_id, $this->meta_prefix.'document_public_single', true);
			$default_quick = get_post_meta($post_id, $this->meta_prefix.'document_quick', true);
			$default_property = get_post_meta($post_id, $this->meta_prefix.'document_property', true);
			################

			// Products
			####################################
			$arr_categories = array();
			get_post_children(array('post_type' => $this->post_type_categories.$this->option_type), $arr_categories);

			$fields_info = $fields_settings = $fields_quick = $fields_searchable = $fields_public = $fields_single = $fields_properties = array();

			if(count($arr_categories) > 0)
			{
				$name_categories = get_option_or_default('setting_webshop_replace_categories'.$this->option_type, __("Categories", 'lang_webshop'));

				$fields_settings[] = array(
					'name' => $name_categories,
					'id' => $this->meta_prefix.'category',
					'type' => 'select',
					'options'  => $arr_categories,
					'multiple' => true,
				);
			}

			$fields_settings[] = array(
				'name' => __("Image", 'lang_webshop'),
				'id'   => $this->meta_prefix.'product_image',
				'type' => 'file_advanced',
			);

			$arr_doc_types_ignore = array('heading', 'label', 'categories', 'divider', 'container_start', 'container_end'); //, 'text', 'description'

			$result = $this->get_document_types(array('select' => "ID, post_title, post_name, post_parent", 'order' => "menu_order ASC"));

			foreach($result as $r)
			{
				$post_id = $r->ID;
				$post_title = $r->post_title;
				$post_name = $r->post_name;
				$post_parent = $r->post_parent;

				// This is already displayed in settings
				/*if($post_name != 'category') //Set in arr_doc_types_ignore above as 'categories'
				{*/
					$arr_attributes = array();

					if($post_parent > 0)
					{
						$parent_name = $this->get_document_types(array('select' => "post_name", 'where_key' => "ID = '%d'", 'where_value' => $post_parent, 'limit' => "0, 1"));

						$arr_attributes['connect_to'] = $this->meta_prefix.$parent_name;
					}

					$post_custom_type = get_post_meta($post_id, $this->meta_prefix.'document_type', true);

					if($post_custom_type != '' && substr($post_custom_type, 0, 6) != 'group_' && !in_array($post_custom_type, $arr_doc_types_ignore))
					{
						$post_document_searchable = get_post_meta($post_id, $this->meta_prefix.'document_searchable', true);
						$post_document_public = get_post_meta($post_id, $this->meta_prefix.'document_public', true);
						$post_document_public_single = get_post_meta($post_id, $this->meta_prefix.'document_public_single', true);
						$post_document_quick = get_post_meta($post_id, $this->meta_prefix.'document_quick', true);
						$post_document_property = get_post_meta($post_id, $this->meta_prefix.'document_property', true);
						$post_document_default = get_post_meta($post_id, $this->meta_prefix.'document_default', true);
						$post_document_display_on_categories = get_post_meta($post_id, $this->meta_prefix.'document_display_on_categories', false);

						if(is_array($post_document_display_on_categories) && count($post_document_display_on_categories) > 0)
						{
							$arr_attributes['condition_type'] = 'show_this_if';
							$arr_attributes['condition_selector'] = $this->meta_prefix.'category';
							$arr_attributes['condition_value'] = $post_document_display_on_categories;
						}

						switch($post_custom_type)
						{
							case 'content':
								$post_document_max_length = get_post_meta($post_id, $this->meta_prefix.'document_max_length', true);

								if($post_document_max_length > 0)
								{
									$arr_attributes['maxlength'] = $post_document_max_length;
								}
							break;
						}

						$fields_array = array(
							'name' => $post_title,
							'id' => $this->meta_prefix.$post_name,
							'type' => $post_custom_type,
							'std' => $post_document_default,
							'attributes' => $arr_attributes,
						);

						if($post_custom_type == 'location')
						{
							$arr_locations = array();
							get_post_children(array('post_type' => $this->post_type_location.$this->option_type, 'post_status' => ''), $arr_locations);

							$fields_array['options'] = $arr_locations;
							$fields_array['multiple'] = true;
						}

						if($post_document_public_single == 'yes')
						{
							$fields_single[] = $fields_array;
						}

						else if($post_document_property == 'yes')
						{
							$fields_properties[] = $fields_array;
						}

						else if($post_document_quick == 'yes')
						{
							$fields_quick[] = $fields_array;
						}

						else if($post_document_searchable == 'yes')
						{
							$fields_searchable[] = $fields_array;
						}

						else if($post_document_public == 'yes')
						{
							$fields_public[] = $fields_array;
						}

						else
						{
							$fields_info[] = $fields_array;
						}
					}
				//}
			}

			if(count($fields_info) > 0)
			{
				$meta_boxes[] = array(
					'id' => $this->meta_prefix.'information',
					'title' => __("Information", 'lang_webshop'),
					'post_types' => array($this->post_type_products.$this->option_type),
					//'context' => 'side',
					'priority' => 'low',
					'fields' => $fields_info
				);
			}

			if(count($fields_properties) > 0)
			{
				$meta_boxes[] = array(
					'id' => $this->meta_prefix.'properties',
					'title' => __("Properties", 'lang_webshop'),
					'post_types' => array($this->post_type_products.$this->option_type),
					//'context' => 'side',
					'priority' => 'low',
					'fields' => $fields_properties
				);
			}

			if(count($fields_settings) > 0)
			{
				$meta_boxes[] = array(
					'id' => $this->meta_prefix.'settings',
					'title' => __("Settings", 'lang_webshop'),
					'post_types' => array($this->post_type_products.$this->option_type),
					'context' => 'side',
					'priority' => 'low',
					'fields' => $fields_settings
				);
			}

			if(count($fields_searchable) > 0)
			{
				$meta_boxes[] = array(
					'id' => $this->meta_prefix.'searchable',
					'title' => __("Searchable", 'lang_webshop'),
					'post_types' => array($this->post_type_products.$this->option_type),
					'context' => 'side',
					'priority' => 'low',
					'fields' => $fields_searchable
				);
			}

			if(count($fields_public) > 0)
			{
				$meta_boxes[] = array(
					'id' => $this->meta_prefix.'public',
					'title' => __("Results", 'lang_webshop'),
					'post_types' => array($this->post_type_products.$this->option_type),
					'context' => 'side',
					'priority' => 'low',
					'fields' => $fields_public
				);
			}

			if(count($fields_single) > 0)
			{
				$meta_boxes[] = array(
					'id' => $this->meta_prefix.'single',
					'title' => __("Contact Info", 'lang_webshop'),
					'post_types' => array($this->post_type_products.$this->option_type),
					'context' => 'side',
					'priority' => 'low',
					'fields' => $fields_single
				);
			}

			if(count($fields_quick) > 0)
			{
				$meta_boxes[] = array(
					'id' => $this->meta_prefix.'quick',
					'title' => __("Quick Info", 'lang_webshop'),
					'post_types' => array($this->post_type_products.$this->option_type),
					//'context' => 'side',
					'priority' => 'low',
					'fields' => $fields_quick
				);
			}
			####################################

			// Document Types
			####################################
			$arr_yes_no = get_yes_no_for_select();

			$arr_fields = array(
				array(
					'name' => __("Type", 'lang_webshop'),
					'id' => $this->meta_prefix.'document_type',
					'type' => 'select',
					'options' => $this->get_types_for_select(),
					'std' => $default_type,
				),
				array(
					'name' => __("Max Length", 'lang_webshop'),
					'id' => $this->meta_prefix.'document_max_length',
					'type' => 'number',
					'attributes' => array(
						'condition_type' => 'show_this_if',
						'condition_selector' => $this->meta_prefix.'document_type',
						'condition_value' => 'content',
					),
				),
				array(
					'type' => 'divider',
				),
				array(
					'name' => __("Make Searchable", 'lang_webshop'),
					'id' => $this->meta_prefix.'document_searchable',
					'type' => 'select',
					'options' => $arr_yes_no,
					'std' => $default_searchable,
					'attributes' => array(
						'condition_type' => 'show_if',
						'condition_value' => 'yes',
						'condition_field' => $this->meta_prefix.'document_searchable_required',
						'condition_default' => 'no',
					),
				),
				array(
					'name' => " - ".__("Make Required", 'lang_webshop'),
					'id' => $this->meta_prefix.'document_searchable_required',
					'type' => 'select',
					'options' => $arr_yes_no,
					'std' => 'no',
				),
				array(
					'name' => __("Display in Results", 'lang_webshop'),
					'id' => $this->meta_prefix.'document_public',
					'type' => 'select',
					'options' => $arr_yes_no,
					'std' => $default_public,
				),
				array(
					'name' => __("Display as Contact Info", 'lang_webshop'),
					'id' => $this->meta_prefix.'document_public_single',
					'type' => 'select',
					'options' => $arr_yes_no,
					'std' => $default_public_single,
				),
				array(
					'name' => __("Display as Quick Info", 'lang_webshop'),
					'id' => $this->meta_prefix.'document_quick',
					'type' => 'select',
					'options' => $arr_yes_no,
					'std' => $default_quick,
				),
				array(
					'name' => __("Display as Property", 'lang_webshop'),
					'id' => $this->meta_prefix.'document_property',
					'type' => 'select',
					'options'  => $arr_yes_no,
					'std' => $default_property,
				),
			);

			$arr_categories = array();
			get_post_children(array('post_type' => $this->post_type_categories.$this->option_type, 'add_choose_here' => false, 'post_status' => 'publish'), $arr_categories);

			if(count($arr_categories) > 1)
			{
				$arr_fields[] = array(
					'type' => 'divider',
				);

				$arr_fields[] = array(
					'name' => __("Display on Categories", 'lang_webshop'),
					'id' => $this->meta_prefix.'document_display_on_categories',
					'type' => 'select',
					'options' => $arr_categories,
					'multiple' => true,
					'attributes' => array(
						'condition_type' => 'hide_this_if',
						'condition_selector' => $this->meta_prefix.'document_type',
						'condition_value' => 'categories',
					),
				);
			}

			$arr_fields[] = array(
				'type' => 'divider',
			);

			$arr_data_symbols = $this->get_symbols_for_select();

			if(count($arr_data_symbols) > 1)
			{
				$arr_fields[] = array(
					'name' => __("Symbol", 'lang_webshop')." (<a href='//fortawesome.github.io/Font-Awesome/icons/'>".__("Ref", 'lang_webshop')."</a>)",
					'id' => $this->meta_prefix.'document_symbol',
					'type' => 'select',
					'options' => $arr_data_symbols,
					//'multiple' => false,
				);
			}

			else
			{
				$arr_fields[] = array(
					'name' => __("Symbol", 'lang_webshop')." (<a href='//fortawesome.github.io/Font-Awesome/icons/'>".__("Ref", 'lang_webshop')."</a>)",
					'id' => $this->meta_prefix.'document_symbol',
					'type' => 'text',
				);
			}

			$arr_fields[] = array(
				'name' => __("Alternate text", 'lang_webshop'),
				'id' => $this->meta_prefix.'document_alt_text',
				'type' => 'text',
			);

			$arr_fields[] = array(
				'name' => __("Custom class", 'lang_webshop'),
				'id' => $this->meta_prefix.'custom_class',
				'type' => 'text',
			);

			$arr_fields[] = array(
				'name' => __("Default value", 'lang_webshop'),
				'id' => $this->meta_prefix.'document_default',
				'type' => 'textarea',
			);

			$meta_boxes[] = array(
				'id' => $this->meta_prefix.'settings',
				'title' => __("Settings", 'lang_webshop'),
				'post_types' => array($this->post_type_document_type.$this->option_type),
				//'context' => 'side',
				'priority' => 'low',
				'fields' => $arr_fields,
			);

			$meta_boxes[] = array(
				'id' => $this->meta_prefix.'order',
				'title' => __("Settings", 'lang_webshop'),
				'post_types' => array($this->post_type_document_type.$this->option_type),
				'context' => 'side',
				'priority' => 'low',
				'fields' => array(
					array(
						'name' => __("Order number", 'lang_webshop'),
						'id' => $this->meta_prefix.'document_type_order',
						'type' => 'number',
					),
				)
			);
			####################################

			// Categories
			####################################
			$name_product = get_option_or_default('setting_webshop_replace_product'.$this->option_type, __("Product", 'lang_webshop'));

			$last_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s ORDER BY post_date DESC LIMIT 0, 1", $this->post_type_categories.$this->option_type));
			$default_value = get_post_meta($last_id, $this->meta_prefix.'connect_new_products', true);

			$meta_boxes[] = array(
				'id' => $this->meta_prefix.'categories',
				'title' => __("Settings", 'lang_webshop'),
				'post_types' => array($this->post_type_categories.$this->option_type),
				'context' => 'side',
				'priority' => 'low',
				'fields' => array(
					array(
						'name' => __("Icon", 'lang_webshop'),
						'id' => $this->meta_prefix.'category_icon',
						'type' => 'select',
						'options'  => $this->get_symbols_for_select(),
					),
					array(
						'name' => sprintf(__("Connect to new %s", 'lang_webshop'), strtolower($name_product)),
						'id' => $this->meta_prefix.'connect_new_products',
						'type' => 'select',
						'options'  => $arr_yes_no,
						'std' => $default_value,
					),
				)
			);
			####################################

			// Custom Categories
			####################################
			$meta_boxes[] = array(
				'id' => $this->meta_prefix.'custom_categories',
				'title' => __("Settings", 'lang_webshop'),
				'post_types' => array($this->post_type_custom_categories.$this->option_type),
				'context' => 'normal',
				'priority' => 'low',
				'fields' => array(
					array(
						'name' => get_option_or_default('setting_webshop_replace_doc_types', __("Filters", 'lang_webshop')),
						'id' => $this->meta_prefix.'document_type',
						'type' => 'select',
						'options'  => $this->get_document_types_for_select(array('include' => 'custom_categories')),
					),
				)
			);
			####################################

			// Location
			####################################
			$meta_boxes[] = array(
				'id' => $this->meta_prefix.'location',
				'title' => __("Settings", 'lang_webshop'),
				'post_types' => array($this->post_type_location.$this->option_type),
				'context' => 'side',
				'priority' => 'low',
				'fields' => array(
					array(
						'name' => __("Hidden", 'lang_webshop'),
						'id' => $this->meta_prefix.'location_hidden',
						'type' => 'select',
						'options'  => $arr_yes_no,
						'std' => 'no',
					),
				)
			);
			####################################

			// Customers
			####################################
			$meta_boxes[] = array(
				'id' => '',
				'title' => __("Settings", 'lang_webshop'),
				'post_types' => array($this->post_type_customers.$this->option_type),
				//'context' => 'side',
				'priority' => 'low',
				'fields' => array(
					array(
						'name' => __("Customer No", 'lang_webshop'),
						'id' => $this->meta_prefix.'customer_no',
						'type' => 'number',
					),
				)
			);
			####################################
		}

		return $meta_boxes;
	}

	function rwmb_enqueue_scripts()
	{
		$setting_range_min_default = get_option_or_default('setting_range_min_default', 10);

		mf_enqueue_script('script_webshop_meta', plugin_dir_url(__FILE__)."script_meta.js", array('range_min_default' => $setting_range_min_default), get_plugin_version(__FILE__));
	}

	function restrict_manage_posts()
	{
		global $post_type, $wpdb;

		if(substr($post_type, 0, strlen($this->post_type_products)) == $this->post_type_products)
		{
			$location_post_name = $this->get_post_name_for_type('location');

			if($location_post_name != '')
			{
				//$strFilterLocation = get_or_set_table_filter(array('key' => 'strFilterLocation', 'save' => true));
				$strFilterLocation = check_var('strFilterLocation');

				$arr_data = array();
				get_post_children(array('post_type' => $this->post_type_location, 'post_status' => '', 'add_choose_here' => true), $arr_data);

				if(count($arr_data) > 2)
				{
					echo show_select(array('data' => $arr_data, 'name' => 'strFilterLocation', 'value' => $strFilterLocation));
				}
			}
		}

		else if(substr($post_type, 0, strlen($this->post_type_document_type)) == $this->post_type_document_type)
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

	function pre_get_posts($wp_query)
	{
		global $post_type, $pagenow;

		if($pagenow == 'edit.php')
		{
			if(substr($post_type, 0, strlen($this->post_type_products)) == $this->post_type_products)
			{
				$location_post_name = $this->get_post_name_for_type('location');

				if($location_post_name != '')
				{
					//$strFilterLocation = get_or_set_table_filter(array('key' => 'strFilterLocation'));
					$strFilterLocation = check_var('strFilterLocation');

					if($strFilterLocation != '')
					{
						$wp_query->query_vars['meta_query'] = array(
							array(
								'key' => $this->meta_prefix.$location_post_name,
								'value' => $strFilterLocation,
								'compare' => '=',
							),
						);
					}
				}
			}

			else if(substr($post_type, 0, strlen($this->post_type_document_type)) == $this->post_type_document_type)
			{
				//$strFilterPlacement = get_or_set_table_filter(array('key' => 'strFilterPlacement'));
				$strFilterPlacement = check_var('strFilterPlacement');

				if($strFilterPlacement != '')
				{
					$wp_query->query_vars['meta_query'] = array(
						array(
							'key' => $this->meta_prefix.'document_'.$strFilterPlacement,
							'value' => 'yes',
							'compare' => '=',
						),
					);
				}
			}
		}
	}

	function column_header($cols, $post_type)
	{
		$this->get_option_types();

		foreach($this->arr_option_types as $option_type)
		{
			$this->option_type = ($option_type != '' ? "_".$option_type : '');

			if($post_type == $this->post_type_categories.$this->option_type)
			{
				unset($cols['date']);

				$cols['category_icon'] = __("Icon", 'lang_webshop');
				$cols['products'] = get_option_or_default('setting_webshop_replace_products'.$this->option_type, __("Products", 'lang_webshop'));
				$cols['connect_new_products'] = sprintf(__("Connect to new %s", 'lang_webshop'), strtolower(get_option_or_default('setting_webshop_replace_product'.$this->option_type, __("Product", 'lang_webshop'))));

				break;
			}

			else if($post_type == $this->post_type_products.$this->option_type)
			{
				unset($cols['date']);

				if(get_post_children(array('post_type' => $this->post_type_categories.$this->option_type, 'count' => true, 'limit' => 1)) > 0)
				{
					$cols['category'] = get_option_or_default('setting_webshop_replace_categories'.$this->option_type, __("Categories", 'lang_webshop'));
				}

				$arr_columns = array('ghost', 'location', 'local_address', 'email', 'phone'); //address
				$arr_columns_admin = array('email', 'phone');

				foreach($arr_columns as $column)
				{
					if(!in_array($column, $arr_columns_admin) || IS_ADMIN)
					{
						$result = $this->get_post_type_info(array('type' => $column));

						if(isset($result->post_title))
						{
							$column_title = $result->post_title;

							$column_icon = get_post_meta($result->ID, $this->meta_prefix.'document_symbol', true);

							if($column_icon != '')
							{
								$column_title = "<i class='fa fa-".$column_icon." fa-lg' title='".$column_title."'></i>";
							}

							$cols[$column] = $column_title;
						}
					}
				}

				break;
			}

			else if($post_type == $this->post_type_custom_categories.$this->option_type)
			{
				unset($cols['date']);

				$cols['document_type'] = get_option_or_default('setting_webshop_replace_doc_types'.$this->option_type, __("Filters", 'lang_webshop'));

				break;
			}

			else if($post_type == $this->post_type_document_type.$this->option_type)
			{
				unset($cols['date']);

				$cols['type'] = __("Type", 'lang_webshop');
				$cols['searchable'] = __("Make Searchable", 'lang_webshop');
				$cols['public'] = __("Display in Results", 'lang_webshop');
				$cols['public_single'] = __("Display as Contact Info", 'lang_webshop');
				$cols['quick'] = __("Display as Quick Info", 'lang_webshop');
				$cols['property'] = __("Display as Property", 'lang_webshop');

				$arr_categories = array();
				get_post_children(array('post_type' => $this->post_type_categories.$this->option_type, 'add_choose_here' => false, 'post_status' => 'publish'), $arr_categories);

				if(count($arr_categories) > 1)
				{
					$cols['display_on_categories'] = __("Display on Categories", 'lang_webshop');
				}

				break;
			}

			else if($post_type == $this->post_type_location.$this->option_type)
			{
				unset($cols['date']);

				$cols['location_hidden'] = "<i class='fa fa-eye-slash fa-lg'></i>";
				$cols['products'] = get_option_or_default('setting_webshop_replace_products'.$this->option_type, __("Products", 'lang_webshop'));

				break;
			}
		}

		return $cols;
	}

	function column_cell($col, $id)
	{
		global $wpdb;

		$post_type = get_post_type($id);

		$this->get_option_types();

		foreach($this->arr_option_types as $option_type)
		{
			$this->option_type = ($option_type != '' ? "_".$option_type : '');

			if($post_type == $this->post_type_categories.$this->option_type)
			{
				switch($col)
				{
					case 'category_icon':
						$post_meta = get_post_meta($id, $this->meta_prefix.$col, true);

						if($post_meta != '')
						{
							$obj_font_icons = new mf_font_icons();

							echo $obj_font_icons->get_symbol_tag($post_meta);
						}
					break;

					case 'products':
						$product_amount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(post_id) FROM ".$wpdb->postmeta." WHERE meta_key = '".$this->meta_prefix."category' AND meta_value = '%d'", $id));

						echo $product_amount;
					break;

					case 'connect_new_products':
						$post_meta = get_post_meta($id, $this->meta_prefix.$col, true);

						echo "<i class='".($post_meta == "yes" ? "fa fa-check green" : "fa fa-times red")." fa-lg'></i>";
					break;
				}

				break;
			}

			else if($post_type == $this->post_type_products.$this->option_type)
			{
				switch($col)
				{
					case 'category':
						$post_meta = get_post_meta($id, $this->meta_prefix.$col, false);
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
						$post_name = $this->get_post_name_for_type($col);
						$post_meta = get_post_meta($id, $this->meta_prefix.$post_name, true);

						if($post_meta == true)
						{
							echo "<i class='".($post_meta == true ? "fa fa-eye-slash" : "fa fa-eye")." fa-lg'></i>";
						}
					break;

					case 'location':
						$post_name = $this->get_post_name_for_type($col);
						$post_meta = get_post_meta($id, $this->meta_prefix.$post_name, false);
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
						$post_name = $this->get_post_name_for_type($col);
						$post_meta = get_post_meta($id, $this->meta_prefix.$post_name, true);

						echo $post_meta;
					break;

					case 'email':
						$post_name = $this->get_post_name_for_type($col);
						$post_meta = get_post_meta($id, $this->meta_prefix.$post_name, true);

						echo "<a href='mailto:".$post_meta."'>".$post_meta."</a>";
					break;

					case 'phone':
						$post_name = $this->get_post_name_for_type($col);
						$post_meta = get_post_meta($id, $this->meta_prefix.$post_name, true);

						echo "<a href='".format_phone_no($post_meta)."'>".$post_meta."</a>";
					break;
				}

				break;
			}

			else if($post_type == $this->post_type_custom_categories.$this->option_type)
			{
				switch($col)
				{
					case 'document_type':
						$post_meta = get_post_meta($id, $this->meta_prefix.$col, true);

						if($post_meta > 0)
						{
							echo get_post_title($post_meta);
						}
					break;
				}

				break;
			}

			else if($post_type == $this->post_type_document_type.$this->option_type)
			{
				switch($col)
				{
					case 'type':
						$post_meta = get_post_meta($id, $this->meta_prefix.'document_'.$col, true);

						if($post_meta != '')
						{
							echo $this->get_types_for_select()[$post_meta];
						}
					break;

					case 'searchable':
						$post_meta = get_post_meta($id, $this->meta_prefix.'document_'.$col, true);

						echo "<i class='".($post_meta == "yes" ? "fa fa-check green" : "fa fa-times red")." fa-lg'></i>";

						$post_meta = get_post_meta($id, $this->meta_prefix.'document_searchable_required', true);

						if($post_meta == 'yes')
						{
							echo " <i class='fa fa-asterisk fa-lg red' title='".__("Required", 'lang_webshop')."'></i>";
						}
					break;

					case 'public':
					case 'public_single':
					case 'quick':
					case 'property':
						$post_meta = get_post_meta($id, $this->meta_prefix.'document_'.$col, true);

						echo "<i class='".($post_meta == "yes" ? "fa fa-check green" : "fa fa-times red")." fa-lg'></i>";
					break;

					case 'display_on_categories':
						$post_meta = get_post_meta($id, $this->meta_prefix.'document_'.$col, false);

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

				break;
			}

			else if($post_type == $this->post_type_location.$this->option_type)
			{
				switch($col)
				{
					case 'location_hidden':
						$post_meta = get_post_meta($id, $this->meta_prefix.$col, true);

						if($post_meta == 'yes')
						{
							echo "<i class='fa fa-eye-slash fa-lg'></i>";
						}
					break;

					case 'products':
						$result = $this->get_products_from_location($id);

						$count_temp = count($result);

						if($count_temp > 0)
						{
							echo "<a href='".admin_url("edit.php?s&post_type=".$this->post_type_products.$this->option_type."&strFilterLocation=".$id)."'>".$count_temp."</a>";
						}
					break;
				}

				break;
			}
		}
	}

	function save_post($post_id, $post, $update)
	{
		global $wpdb;

		$this->get_option_type_from_post_id($post_id);

		if($post->post_type == $this->post_type_products.$this->option_type)
		{
			if($update == true)
			{
				// Clear Cache?
			}

			else
			{
				$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND post_status = 'publish' AND meta_key = '".$this->meta_prefix."connect_new_products' AND meta_value = 'yes'", $this->post_type_categories.$this->option_type));

				foreach($result as $r)
				{
					$category_id = $r->ID;

					add_post_meta($post_id, $this->meta_prefix.'category', $category_id);
				}
			}
		}
	}

	function rwmb_before_save_post($post_id)
	{
		global $wpdb, $post;

		$this->get_option_type_from_post_id($post_id);

		if($post->post_type == $this->post_type_categories.$this->option_type)
		{
			$post_meta_new = check_var($this->meta_prefix.'connect_new_products');
			$post_meta_old = get_post_meta($post_id, $this->meta_prefix.'connect_new_products', false);

			if($post_meta_new == 'yes' && $post_meta_new != $post_meta_old)
			{
				$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = 'publish'", $this->post_type_products.$this->option_type));

				foreach($result as $r)
				{
					$product_id = $r->ID;

					$meta_key = $this->meta_prefix.'category';

					$arr_product_categories = get_post_meta($product_id, $meta_key, false);

					if(!in_array($post_id, $arr_product_categories))
					{
						add_post_meta($product_id, $meta_key, $post_id);
					}
				}
			}
		}
	}

	function manage_users_columns($cols)
	{
		if(get_option('setting_webshop_payment_form') > 0)
		{
			unset($cols['posts']);

			$name_products = get_option_or_default('setting_webshop_replace_products', __("Products", 'lang_webshop'));

			$cols['profile_webshop_payment'] = sprintf(__("Paid for %s until", 'lang_webshop'), $name_products);
		}

		return $cols;
	}

	function manage_users_custom_column($value, $col, $id)
	{
		switch($col)
		{
			case 'profile_webshop_payment':
				$post_meta = get_the_author_meta($col, $id);

				if($post_meta != '')
				{
					return $post_meta;
				}
			break;
		}

		return $value;
	}

	function save_register($user_id, $password = "", $meta = array())
	{
		if(IS_ADMIN && get_option('setting_webshop_payment_form') > 0)
		{
			$meta_key = 'profile_webshop_payment';
			$meta_value = check_var($meta_key);

			update_user_meta($user_id, $meta_key, $meta_value);
		}
	}

	function show_profile($user)
	{
		if(IS_ADMIN && get_option('setting_webshop_payment_form') > 0)
		{
			$out = "";

			$name_products = get_option_or_default('setting_webshop_replace_products', __("Products", 'lang_webshop'));

			$meta_key = 'profile_webshop_payment';
			$meta_value = get_the_author_meta($meta_key, $user->ID);
			$meta_text = sprintf(__("Paid for %s until", 'lang_webshop'), $name_products);

			$out .= "<tr class='".str_replace("_", "-", $meta_key)."-wrap'>
				<th><label for='".$meta_key."'>".$meta_text."</label></th>
				<td>".show_textfield(array('type' => 'date', 'name' => $meta_key, 'value' => $meta_value, 'xtra' => "class='regular-text'"))."</td>
			</tr>";

			if($out != '')
			{
				echo "<table class='form-table'>".$out."</table>";
			}
		}
	}

	function save_profile($user_id)
	{
		if(current_user_can('edit_user', $user_id))
		{
			$this->save_register($user_id);
		}
	}

	/* Public */
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
				$text = get_option_or_default('setting_webshop_replace_filter_products'.$this->option_type, __("Filter amongst %s products", 'lang_webshop'));
			break;

			case 'matches':
				$text = get_option_or_default('setting_replace_search_result_info'.$this->option_type, __("Your search matches %s products", 'lang_webshop'));
			break;

			case 'favorites':
				$text = get_option_or_default('setting_webshop_replace_favorites_info'.$this->option_type, __("Here are your %s saved products", 'lang_webshop'));
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

		if(get_option('setting_quote_form'.$this->option_type) > 0 && in_array('quote', $data['include']))
		{
			$name_quote_request = get_option('setting_replace_quote_request'.$this->option_type);
			$name_quote_none_checked = get_option_or_default('setting_webshop_replace_none_checked'.$this->option_type, __("You have to choose at least one product to proceed", 'lang_webshop'));
			$name_quote_too_many = get_option_or_default('setting_webshop_replace_too_many'.$this->option_type, __("In order to send a quote you have to be specific what you want by filtering", 'lang_webshop'));

			if($name_quote_request == '')
			{
				$name_products = get_option_or_default('setting_webshop_replace_products'.$this->option_type, __("Products", 'lang_webshop'));

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
			$setting_webshop_replace_email_favorites = get_option_or_default('setting_webshop_replace_email_favorites'.$this->option_type, __("Email Your Products", 'lang_webshop'));
			$setting_webshop_share_email_subject = get_option('setting_webshop_share_email_subject'.$this->option_type);
			$setting_webshop_share_email_content = get_option('setting_webshop_share_email_content'.$this->option_type);

			$out .= "<a href='mailto:?subject=".$setting_webshop_share_email_subject."&body=".$setting_webshop_share_email_content."' class='show_if_results button'><i class='fa fa-envelope'></i>".$setting_webshop_replace_email_favorites."</a>";
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

		$setting_replace_show_map = get_option_or_default('setting_webshop_replace_show_map'.$this->option_type, __("Show Map", 'lang_webshop'));
		$setting_replace_hide_map = get_option_or_default('setting_replace_hide_map'.$this->option_type, __("Hide Map", 'lang_webshop'));
		$setting_map_info = get_option('setting_map_info'.$this->option_type);

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

			$out .= input_hidden(array('name' => 'webshop_map_coords', 'allow_empty' => true))
			.input_hidden(array('name' => 'webshop_map_bounds', 'allow_empty' => true))
			."</div>";

		return $out;
	}

	function get_webshop_search()
	{
		global $wpdb;

		$obj_font_icons = new mf_font_icons();

		$name_choose_here = "-- ".__("Choose Here", 'lang_webshop')." --";

		$out = "<div id='webshop_search'>";

			$setting_webshop_display_sort = get_option('setting_webshop_display_sort'.$this->option_type);
			$setting_webshop_display_filter = get_option('setting_webshop_display_filter'.$this->option_type);

			if($setting_webshop_display_sort == 'yes')
			{
				$setting_webshop_display_sort = array('latest', 'random', 'alphabetical', 'size');
			}

			if(is_array($setting_webshop_display_sort) && count($setting_webshop_display_sort) > 1)
			{
				$setting_webshop_sort_default = get_option('setting_webshop_sort_default'.$this->option_type, 'alphabetical');

				$out .= show_form_alternatives(array('data' => $this->get_sort_for_select($setting_webshop_display_sort), 'name' => 'order', 'text' => __("Sort By", 'lang_webshop'), 'value' => $setting_webshop_sort_default));
			}

			if($setting_webshop_display_filter != 'no')
			{
				if($setting_webshop_display_filter == 'button')
				{
					$out .= get_toggler_container(array('type' => 'start', 'text' => __("Filter", 'lang_webshop'), 'rel' => 'webshop_filter'));
				}

					$obj_webshop_interval = new mf_webshop();

					$result = $this->get_document_types(array('select' => "ID, post_status, post_title, post_name", 'join' => "INNER JOIN ".$wpdb->postmeta." AS meta1 ON ".$wpdb->posts.".ID = meta1.post_id AND meta1.meta_key = '".$this->meta_prefix."document_searchable' LEFT JOIN ".$wpdb->postmeta." AS meta2 ON ".$wpdb->posts.".ID = meta2.post_id AND meta2.meta_key = '".$this->meta_prefix."document_type_order'", 'where_key' => "meta1.meta_value = %s", 'where_value' => 'yes', 'order' => "meta2.meta_value + 0 ASC, menu_order ASC"));

					$obj_webshop_interval->set_interval_amount($result);

					foreach($result as $r)
					{
						$post_id = $r->ID;
						$post_title = $r->post_title;
						$post_name = $r->post_name;

						$post_custom_type = get_post_meta($post_id, $this->meta_prefix.'document_type', true);
						$post_custom_symbol = get_post_meta($post_id, $this->meta_prefix.'document_symbol', true);
						$post_custom_class = get_post_meta($post_id, $this->meta_prefix.'custom_class', true);
						$post_custom_required = get_post_meta($post_id, $this->meta_prefix.'document_searchable_required', true);
						$post_document_display_on_categories = get_post_meta($post_id, $this->meta_prefix.'document_display_on_categories', false);

						$arr_attributes = array();

						if(is_array($post_document_display_on_categories) && count($post_document_display_on_categories) > 0)
						{
							$arr_attributes['condition_type'] = 'show_this_if';
							$arr_attributes['condition_selector'] = 'category';
							$arr_attributes['condition_value'] = $post_document_display_on_categories;
						}

						$custom_class = " class='".$post_custom_type.($post_custom_class != '' ? " ".$post_custom_class : "")."'";

						$symbol_tag = $obj_font_icons->get_symbol_tag($post_custom_symbol);

						if($symbol_tag != '')
						{
							$post_title = $symbol_tag."&nbsp;".$post_title;
						}

						switch($post_custom_type)
						{
							case 'checkbox':
								$out .= show_checkbox(array('name' => $post_name, 'text' => $post_title, 'value' => 1, 'compare' => (isset($_REQUEST[$post_name]) ? 1 : 0), 'class' => $post_custom_class, 'required' => ($post_custom_required == 'yes')));
							break;

							case 'categories':
								$arr_data = array();
								get_post_children(array('post_type' => $this->post_type_categories.$this->option_type, 'add_choose_here' => true, 'post_status' => 'publish'), $arr_data);

								$out .= show_select(array('data' => $arr_data, 'name' => $post_name, 'text' => $post_title, 'value' => check_var($post_name, 'char'), 'class' => $post_custom_class, 'required' => ($post_custom_required == 'yes')));
							break;

							case 'custom_categories':
								$post_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_name = %s", $this->post_type_document_type.$this->option_type, $post_name));

								$arr_data = array();
								get_post_children(array(
									'add_choose_here' => true,
									'post_type' => $this->post_type_custom_categories.$this->option_type,
									'join' => " INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = '".$this->meta_prefix."document_type'",
									'where' => "meta_value = '".esc_sql($post_id)."'",
									//'debug' => true,
								), $arr_data);

								$out .= show_select(array('data' => $arr_data, 'name' => $post_name, 'text' => $post_title, 'value' => check_var($post_name, 'char'), 'class' => $post_custom_class, 'required' => ($post_custom_required == 'yes')));
							break;

							case 'location':
								$arr_data = array(
									'' => $name_choose_here,
								);

								get_post_children(array('post_type' => $this->post_type_location.$this->option_type, 'post_status' => 'publish'), $arr_data);

								$out .= show_select(array('data' => $arr_data, 'name' => $post_name, 'text' => $post_title, 'value' => check_var($post_name, 'char'), 'class' => $post_custom_class, 'required' => ($post_custom_required == 'yes'), 'attributes' => $arr_attributes));
							break;

							case 'number':
							case 'price':
							case 'size':
							case 'address':
							case 'local_address':
								$is_numeric = in_array($post_custom_type, array('number', 'price', 'size'));

								$arr_data = array(
									'' => $name_choose_here,
								);

								$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = 'publish'", $this->post_type_products.$this->option_type));

								foreach($result as $r)
								{
									$page_id = $r->ID;

									$post_meta = get_post_meta($page_id, $this->meta_prefix.$post_name, true);

									if($is_numeric)
									{
										$arr_data[$post_meta] = $post_meta;

										$this->set_range($post_meta);
									}

									else
									{
										$arr_data[$post_meta] = $post_meta;
									}
								}

								//$arr_data = array_sort(array('array' => $arr_data, 'on' => 0, 'keep_index' => true));

								if($is_numeric)
								{
									if(count($arr_data) > 5)
									{
										$arr_data = array(
											'' => $name_choose_here
										);

										$this->calculate_range($arr_data);
									}
								}

								$out .= show_select(array('data' => $arr_data, 'name' => $post_name, 'text' => $post_title, 'value' => check_var($post_name, 'char'), 'class' => $post_custom_class, 'required' => ($post_custom_required == 'yes'), 'attributes' => $arr_attributes));
							break;

							case 'interval':
								$obj_webshop_interval->increase_count();

								$post_document_alt_text = get_post_meta($post_id, $this->meta_prefix.'document_alt_text', true);

								if($post_document_alt_text != '')
								{
									$post_title = $post_document_alt_text;
								}

								$obj_webshop_interval->add_interval_type($post_name, $post_title);

								$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = 'publish'", $this->post_type_products.$this->option_type));

								foreach($result as $r)
								{
									$page_id = $r->ID;

									$post_meta = get_post_meta($page_id, $this->meta_prefix.$post_name, true);

									list($post_meta_min, $post_meta_max) = $this->get_interval_min($post_meta);

									$obj_webshop_interval->set_range($post_meta_min);
								}

								$has_equal_amount = $obj_webshop_interval->has_equal_amount($post_title, $name_choose_here);

								if($has_equal_amount != '')
								{
									$out .= "<div".$custom_class.">".$has_equal_amount."</div>";
								}
							break;

							case 'heading':
								$out .= "<h3".$custom_class.">".$post_title."</h3>";
							break;

							case 'gps':
								$out .= show_textfield(array('type' => 'range', 'name' => $post_name, 'text' => __("Distance", 'lang_webshop'), 'value' => check_var($post_name, 'char'), 'xtra' => "min='0' max='500'"));
							break;

							case 'label':
								$out .= "<label".$custom_class.">".$post_title."</label>";
							break;

							case 'divider':
								$out .= "<hr".$custom_class.">";
							break;

							case 'container_start':
								$out .= "<div".$custom_class.">";
							break;

							case 'container_end':
								$out .= "</div>";
							break;

							default:
								do_log(sprintf(__("The type '%s' does not have a case", 'lang_webshop'), $post_custom_type)." (search)");
							break;
						}
					}

				if($setting_webshop_display_filter == 'button')
				{
					$out .= get_toggler_container(array('type' => 'end'));
				}
			}

			$out .= input_hidden(array('name' => 'option_type', 'value' => $this->option_type))
		."</div>";

		return $out;
	}

	function get_form_fields_passthru()
	{
		$out = "";

		$setting_quote_form = get_option('setting_quote_form'.$this->option_type);

		if($setting_quote_form > 0)
		{
			$obj_form = new mf_form($setting_quote_form);

			$query_prefix = $obj_form->get_post_info()."_";

			$count_prefix_length = strlen($query_prefix);

			foreach($_REQUEST as $key => $value)
			{
				if(substr($key, 0, $count_prefix_length) == $query_prefix)
				{
					$out .= input_hidden(array('name' => $key, 'value' => $value));
				}
			}
		}

		return $out;
	}

	function get_templates()
	{
		$name_choose = get_option_or_default('setting_webshop_replace_choose_product'.$this->option_type, __("Choose", 'lang_webshop'));

		$obj_base = new mf_base();
		$out = $obj_base->get_templates(array('lost_connection'));

		$out .= "<script type='text/template' id='template_product_message'>
			<li class='info_text'>
				<p>".__("I could not find anything that corresponded to your choices", 'lang_webshop')."</p>
			</li>
		</script>

		<script type='text/template' id='template_product_item'>
			<li id='product_<%= product_id %>'<%= (product_url != '' ? '' : ' class=ghost') %>>
				<div class='product_heading product_column'>
					<h2>
						<% if(product_url != '')
						{ %>
							<a href='<%= product_url %>'><%= product_title %></a>
						<% }

						else
						{ %>
							<%= product_title %>
						<% } %>
					</h2>
					<% if(product_location != '')
					{ %>
						<p class='product_location'><%= product_location %></p>
					<% }

					if(product_clock != '')
					{ %>
						<span class='product_clock'><%= product_clock %></span>
					<% } %>
				</div>

				<div class='product_image_container'>
					<% if(product_url != '')
					{ %>
						<a href='<%= product_url %>'>
					<% } %>

						<%= product_image %>

					<% if(product_url != '')
					{ %>
						</a>
					<% }

					if(product_data != '')
					{ %>
						<div class='product_data'><%= product_data %></div>
					<% } %>
				</div>

				<ul class='product_meta product_column'>
					<% _.each(product_meta, function(meta)
					{ %>
						<li class='<%= meta.class %>'>
							<%= meta.content %>
						</li>
					<% }); %>
				</ul>

				<% if(product_description != '')
				{ %>
					<div class='product_description product_column'>
						<%= product_description %>
					</div>
				<% } %>";

				if(get_option('setting_webshop_payment_form'.$this->option_type) > 0)
				{
					$out .= "<% if(product_has_email == 1 || 1 == 1)
					{ %>"
						.show_checkbox(array('name' => "products[]", 'value' => '<%= product_id %>', 'compare' => 'disabled', 'text' => $name_choose, 'switch' => true, 'switch_icon_on' => get_option('setting_webshop_switch_icon_on'.$this->option_type), 'switch_icon_off' => get_option('setting_webshop_switch_icon_off'.$this->option_type), 'xtra_class' => 'color_button_2')) //, 'compare' => '<%= product_id %>' //This makes it checked by default
					."<% } %>";
				}

				$out .= "<a href='<%= product_url %>' class='product_link product_column'>".__("Read More", 'lang_webshop')."&hellip;</a>"
				.input_hidden(array('value' => "<%= product_map %>", 'xtra' => "class='map_coords' data-id='<%= product_id %>' data-name='<%= product_title %>' data-url='<%= product_url %>'"))
			."</li>
		</script>";

		return $out;
	}

	function get_type_occurrence($data)
	{
		global $wpdb;

		return $wpdb->get_var($wpdb->prepare("SELECT COUNT(meta_key) FROM ".$wpdb->postmeta." WHERE meta_key = %s AND meta_value = %s", $data['key'], $data['value']));
	}

	function get_document_types($data)
	{
		global $wpdb;

		if(!isset($data['select'])){		$data['select'] = "ID";}
		if(!isset($data['join'])){			$data['join'] = "";}
		if(!isset($data['where_key'])){		$data['where_key'] = "";}
		if(!isset($data['where_value'])){	$data['where_value'] = "";}
		if(!isset($data['limit'])){			$data['limit'] = "";}
		if(!isset($data['order'])){			$data['order'] = "";}

		$query_where = "post_type = '".$this->post_type_document_type.$this->option_type."' AND post_status = 'publish'";
		$query_join = $query_order = $query_limit = "";

		if($data['join'] != '')
		{
			$query_join = " ".$data['join'];
		}

		if($data['where_key'] != '' && $data['where_value'] != '')
		{
			$query_where .= ($query_where != '' ? " AND " : "").$data['where_key'];
			$query_args = $data['where_value'];
		}

		if($data['limit'] != '')
		{
			$query_limit = " LIMIT ".$data['limit'];
		}

		if($data['order'] != '')
		{
			$query_order = " ORDER BY ".$data['order'];
		}

		$query = "SELECT ".$data['select']." FROM ".$wpdb->posts.$query_join." WHERE ".$query_where.$query_order.$query_limit;

		if(isset($query_args))
		{
			$query = $wpdb->prepare($query, $query_args);
		}

		if($data['limit'] != '')
		{
			return $wpdb->get_var($query);
		}

		else
		{
			return $wpdb->get_results($query);
		}
	}

	function get_template_url($data = array())
	{
		global $wpdb;

		if(!isset($data['template'])){		$data['template'] = 'template_webshop_search.php';}
		if(!isset($data['location_id'])){	$data['location_id'] = 0;}

		$out = "";

		$post_id = $wpdb->get_var("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE meta_key = '_wp_page_template' AND meta_value = '".$data['template']."'");

		$out = $post_url = get_permalink($post_id);

		if($data['location_id'] > 0)
		{
			$location_post_name = $this->get_post_name_for_type('location');

			$out .= "?".$location_post_name."=".$data['location_id']."#".$location_post_name."=".$data['location_id'];
		}

		return $out;
	}

	function get_products_from_location($id)
	{
		global $wpdb;

		$location_post_name = $this->get_post_name_for_type('location');

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE meta_key = '".$this->meta_prefix.$location_post_name."' AND meta_value = '%d'", $id));

		return $result;
	}

	function get_post_type_info($data)
	{
		global $wpdb;

		if(!isset($data['select'])){	$data['select'] = "ID, post_name, post_title";}
		if(!isset($data['single'])){	$data['single'] = true;}

		$limit = $data['single'] == true ? " LIMIT 0, 1" : "";

		$query = $wpdb->prepare("SELECT ".$data['select']." FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE meta_key = '".$this->meta_prefix."document_type' AND meta_value = %s".$limit, $data['type']);

		$result = $wpdb->get_results($query);

		if($data['single'] == true)
		{
			foreach($result as $r)
			{
				return $r;
			}
		}

		else
		{
			return $result;
		}
	}

	function get_post_name_for_type($type)
	{
		global $wpdb;

		if(!isset($this->post_name_for_type[$this->option_type][$type]))
		{
			$this->post_name_for_type[$this->option_type][$type] = $wpdb->get_var($wpdb->prepare("SELECT post_name FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND post_status = %s AND meta_key = '".$this->meta_prefix."document_type' AND meta_value = %s LIMIT 0, 1", $this->post_type_document_type.$this->option_type, 'publish', $type));
		}

		return $this->post_name_for_type[$this->option_type][$type];
	}

	function get_post_name_from_id($id)
	{
		global $wpdb;

		return $wpdb->get_var($wpdb->prepare("SELECT post_name FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE meta_key = '".$this->meta_prefix."document_type' AND post_id = '%d' LIMIT 0, 1", $id));
	}

	function get_product_name($data)
	{
		global $wpdb;

		$out = "";

		if(isset($data['id']))
		{
			$out .= $wpdb->get_var($wpdb->prepare("SELECT post_title FROM ".$wpdb->posts." WHERE ID = %s", $data['id']));
		}

		else if(isset($data['email']))
		{
			$email_post_type = $this->get_post_name_for_type('email');

			$out .= $wpdb->get_var($wpdb->prepare("SELECT post_title FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE meta_key = '".$this->meta_prefix.$email_post_type."' AND meta_value = %s", $data['email']));
		}

		return $out;
	}

	function gather_product_meta($data)
	{
		if($data['public'] == 'yes' && ($data['meta'] != '' || $data['type'] == 'heading'))
		{
			$class = $data['type'];
			$content = "";

			switch($data['type'])
			{
				case 'checkbox':
					$class .= " type_choice";
				break;

				case 'address':
				case 'local_address':
				case 'email':
				case 'phone':
				case 'text':
				case 'url':
					$class .= " type_text";
				break;

				case 'content':
				case 'description':
				case 'textarea':
					//Do nothing
				break;
			}

			$symbol_code = (isset($data['symbol']) ? $this->obj_font_icons->get_symbol_tag($data['symbol']) : "");

			switch($data['type'])
			{
				case 'email':
					$data['meta'] = "<a href='mailto:".$data['meta']."'>".$data['meta']."</a>";
				break;

				case 'phone':
					$data['meta'] = "<a href='".format_phone_no($data['meta'])."'>".$data['meta']."</a>";
				break;

				case 'url':
					$meta_nice = remove_protocol(array('url' => $data['meta'], 'clean' => true, 'trim' => true));

					$data['meta'] = "<a href='".$data['meta']."'>".$meta_nice."</a>";
				break;
			}

			###################
			switch($data['type'])
			{
				case 'categories':
					if(is_array($data['meta']))
					{
						$content = "<span title='".$data['title']."'>";

							$i = 0;

							foreach($data['meta'] as $meta)
							{
								$content .= ($i > 0 ? ", " : "").get_post_title($meta);
							}

						$content .= "</span>";
					}

					/*else
					{
						do_log(sprintf(__("Wrong meta (%s) when displaying categories", 'lang_webshop'), var_export($data['meta'])));
					}*/
				break;

				case 'description':
				case 'textarea':
					$content = "<p>"
						.$symbol_code.$data['meta']
						."<a href='".$this->product_url."' class='product_link'>".__("Read More", 'lang_webshop')."&hellip;</a>
					</p>";
				break;

				case 'event':
					if($data['meta'] > 0 && is_plugin_active('mf_calendar/index.php'))
					{
						$obj_calendar = new mf_calendar();
						$obj_calendar->get_events(array('feeds' => array($data['meta']), 'limit' => 1));

						$data['meta'] = $obj_calendar->arr_events;

						if(is_array($data['meta']) && count($data['meta']) > 0)
						{
							$obj_calendar = new mf_calendar();
							$content = $obj_calendar->get_next_event(array('array' => $data));

							$has_data = true;
						}
					}
				break;

				case 'file_advanced':
				case 'page':
					$file_suffix = get_file_suffix($data['meta']);

					if($data['type'] == 'file_advanced' && in_array($file_suffix, array('jpg', 'jpeg', 'png', 'gif')))
					{
						$content = "<img src='".$data['meta']."' alt='".$data['title']."'>";

						$class .= " type_image";
					}

					else
					{
						$content = $symbol_code."<a href='".$data['meta']."'>".$data['title']."</a>";
					}
				break;

				case 'global_code':
					$content = $symbol_code.$data['meta'];
				break;

				case 'heading':
					$content = "<h3>".$symbol_code.$data['title']."</h3>";
				break;

				case 'content':
					//Do nothing
				break;

				default:
					$content = "<span title='".$data['title']."'>".$symbol_code.$data['title']."</span><span>".$data['meta']."</span>";
				break;
			}
			###################

			$this->product_meta[] = array(
				'class' => $class,
				'content' => $content,
			);
		}
	}

	function product_init($data)
	{
		global $wpdb;

		$post = $data['post'];

		$this->obj_font_icons = new mf_font_icons();

		$this->product_meta = array();

		$this->product_id = $post->ID;
		$this->product_title = $post->post_title;
		$this->product_description = $post->post_excerpt;

		$this->category_icon = '';

		if($data['single'] == true)
		{
			$this->product_content = apply_filters('the_content', $post->post_content);
		}

		else
		{
			$this->product_url = get_permalink($this->product_id);
		}

		$this->product_image = get_post_meta_file_src(array('post_id' => $this->product_id, 'meta_key' => $this->meta_prefix.'product_image', 'image_size' => 'large', 'single' => $data['single_image']));

		$this->show_in_result = true;
		$this->product_has_email = false;
		$this->number_amount = $this->price_amount = $this->size_amount = 0;

		$this->product_address = $this->product_map = $this->product_social = $this->search_url = "";

		if($data['single'] == true)
		{
			$this->product_form_buy = "";
			$this->arr_product_property = $this->arr_product_quick = $this->slideshow_images = array();

			foreach($this->product_image as $product_image)
			{
				$this->slideshow_images[] = $product_image;
			}
		}

		else
		{
			$this->product_clock = $this->product_data = $this->product_location = "";
		}

		$has_interval = $has_number = false;

		$this->result = $this->get_document_types(array('select' => "ID, post_status, post_title, post_name", 'order' => "menu_order ASC"));

		$rows = $wpdb->num_rows;

		for($i = 0; $i < $rows; $i++)
		{
			$post_id = $this->result[$i]->ID;

			if($data['single'] == true)
			{
				$post_custom_public = get_post_meta($post_id, $this->meta_prefix.'document_public_single', true);
			}

			else
			{
				$post_custom_public = get_post_meta($post_id, $this->meta_prefix.'document_public', true);
			}

			$this->result[$i]->post_custom_public = $post_custom_public;
			$this->result[$i]->post_custom_type = $post_custom_type = get_post_meta($post_id, $this->meta_prefix.'document_type', true);

			if($post_custom_public == 'yes')
			{
				switch($post_custom_type)
				{
					case 'number':
						$this->number_amount++;
					break;

					case 'price':
						$this->price_amount++;
					break;

					case 'size':
						$this->size_amount++;
					break;
				}
			}

			switch($post_custom_type)
			{
				case 'interval':
					$has_interval = true;
				break;

				case 'number':
				case 'price':
				case 'size':
					$has_number = true;
				break;
			}
		}

		if($has_interval == true)
		{
			$this->interval_type = check_var('interval_type', 'char');
			$this->interval_range = check_var('interval_range', 'char');

			if($this->interval_range != '')
			{
				list($this->interval_range_min, $this->interval_range_max) = $this->get_interval_min($this->interval_range);
			}
		}
	}

	function meta_init($data)
	{
		$r = $data['meta'];

		$this->meta_id = $r->ID;
		$this->meta_title = $r->post_title;
		$this->meta_name = $r->post_name;

		$this->meta_type = $r->post_custom_type;
		$this->meta_public = $r->post_custom_public;

		$this->meta_alt_text = get_post_meta($this->meta_id, $this->meta_prefix.'document_alt_text', true);
		$this->meta_symbol = get_post_meta($this->meta_id, $this->meta_prefix.'document_symbol', true);

		if($this->meta_alt_text != '')
		{
			$this->meta_title = $this->meta_alt_text;
		}
	}

	function get_product_data($data, &$json_output)
	{
		global $wpdb;

		$product_single = false;

		$this->product_init(array('post' => $data['product'], 'single' => $product_single, 'single_image' => $data['single_image']));

		foreach($this->result as $r)
		{
			$this->meta_init(array('meta' => $r, 'single' => $product_single));

			$post_search = check_var($this->meta_name, 'char');

			if($this->meta_type == 'file_advanced')
			{
				$post_meta = get_post_meta_file_src(array('post_id' => $this->meta_id, 'meta_key' => $this->meta_prefix.$this->meta_name, 'is_image' => false));
			}

			else if($this->meta_type == 'categories')
			{
				$post_meta = get_post_meta($this->product_id, $this->meta_prefix.'category', false);

				if(is_array($post_meta) && count($post_meta) > 0 && isset($post_meta[0]))
				{
					$this->category_icon = get_post_meta($post_meta[0], $this->meta_prefix.'category_icon', true);
				}

				if($post_search != '' && !in_array($post_search, $post_meta))
				{
					$this->show_in_result = false;

					break;
				}
			}

			else if($this->meta_type == 'location')
			{
				$post_meta = get_post_meta($this->product_id, $this->meta_prefix.$this->meta_name, false);

				if(count($post_meta) == 0)
				{
					if($post_search != '')
					{
						$this->show_in_result = false;

						break;
					}
				}

				else
				{
					if($post_search != '' && !in_array($post_search, $post_meta))
					{
						$this->show_in_result = false;

						break;
					}

					$arr_locations = $this->sort_location(array('array' => $post_meta, 'reverse' => true));

					$str_locations = "";

					foreach($arr_locations as $location_id)
					{
						$str_locations .= ($str_locations != '' ? ", " : "").get_the_title($location_id);
					}

					if($data['show_location_in_data'] == true)
					{
						$this->product_data .= "<span class='".$this->meta_type."'>".$str_locations."</span>";
					}

					else
					{
						$this->product_location .= ($this->product_location != '' ? ", " : "")."<span class='".$this->meta_type."'>".$str_locations."</span>";
					}

					if($this->meta_public == 'no')
					{
						$post_meta = "";
					}
				}
			}

			else
			{
				$post_meta = get_post_meta($this->product_id, $this->meta_prefix.$this->meta_name, true);

				if($post_meta == '')
				{
					if($post_search != '')
					{
						$this->show_in_result = false;

						break;
					}
				}

				else
				{
					switch($this->meta_type)
					{
						case 'address':
							if($post_search != '' && $post_search != $post_meta)
							{
								$this->show_in_result = false;

								break;
							}

							if($data['show_location_in_data'] == true)
							{
								$this->product_data .= "<span class='".$this->meta_type."'>".$post_meta."</span>";
							}

							else
							{
								$this->product_location .= ($this->product_location != '' ? ", " : "")."<span class='".$this->meta_type."'>".$post_meta."</span>";
							}

							if($this->meta_public == 'no')
							{
								$post_meta = "";
							}
						break;

						case 'checkbox':
							if($post_search != '' && $post_search != $post_meta)
							{
								$this->show_in_result = false;

								break;
							}

							$post_meta = $post_meta == 1 ? "<i class='fa fa-check green'></i>" : "";
						break;

						case 'clock':
							if($this->meta_public == 'yes')
							{
								if($this->meta_symbol != '')
								{
									$this->meta_symbol = $this->obj_font_icons->get_symbol_tag($this->meta_symbol);
								}

								$this->product_clock .= $this->meta_symbol.$post_meta;

								$post_meta = "";
							}
						break;

						case 'custom_categories':
							if($post_search != '' && $post_search != $post_meta)
							{
								$this->show_in_result = false;

								break;
							}
						break;

						case 'email':
							$this->product_has_email = true;
						break;

						case 'event':
							/*if(is_plugin_active('mf_calendar/index.php'))
							{
								$obj_calendar = new mf_calendar();
								$obj_calendar->get_events(array('feeds' => array($post_meta), 'limit' => 1));

								$post_meta = $obj_calendar->arr_events;
							}*/
						break;

						case 'gps':
							$this->product_map = $post_meta;

							$post_meta = "";
						break;

						case 'interval':
							if($this->interval_type == $this->meta_name && $this->interval_range != '')
							{
								list($post_meta_min, $post_meta_max) = $this->get_interval_min($post_meta);

								if(!$this->is_between(array('value' => array($post_meta_min, $post_meta_max), 'compare' => array($this->interval_range_min, $this->interval_range_max))))
								{
									$this->show_in_result = false;

									break;
								}
							}
						break;

						case 'local_address':
							if($post_search != '' && $post_search != $post_meta)
							{
								$this->show_in_result = false;

								break;
							}

							if($data['show_location_in_data'] == false)
							{
								$this->product_location .= ($this->product_location != '' ? ", " : "")."<span class='".$this->meta_type."'>".$post_meta."</span>";
							}

							if($this->meta_public == 'no')
							{
								$post_meta = "";
							}
						break;

						case 'number':
						case 'price':
						case 'size':
							if($post_search != '')
							{
								if(strpos($post_search, "-"))
								{
									list($post_search_min, $post_search_max) = explode("-", $post_search);

									if($this->is_between(array('value' => array($post_meta), 'compare' => array($post_search_min, $post_search_max))))
									{
										$this->show_in_result = false;

										break;
									}
								}

								else
								{
									$post_meta_min = $post_meta_max = $post_meta;

									if($post_search < $post_meta_min || $post_search > $post_meta_max)
									{
										$this->show_in_result = false;

										break;
									}
								}
							}

							if($this->meta_type == 'number' && $this->number_amount == 1 || $this->meta_type == 'price' && $this->price_amount == 1 || $this->meta_type == 'size' && $this->size_amount == 1)
							{
								$this->product_data .= "<span class='".$this->meta_type."'>";

									if($this->meta_symbol != '')
									{
										$this->product_data .= $this->obj_font_icons->get_symbol_tag($this->meta_symbol, $this->meta_title);
									}

									else
									{
										$this->product_data .= $this->meta_title;
									}

									$this->product_data .= "&nbsp;".$post_meta
								."</span>";

								$post_meta = "";
							}
						break;

						case 'page':
							$this->meta_title = get_the_title($post_meta);
							$post_meta = get_permalink($post_meta);
						break;

						case 'content':
						case 'description':
						case 'ghost':
						case 'phone':
						case 'social':
						case 'text':
						case 'textarea':
						case 'url':
							//Do nothing
						break;

						default:
							do_log(sprintf(__("The type '%s' does not have a case", 'lang_webshop'), $this->meta_type)." (list)");
						break;
					}
				}
			}

			$this->gather_product_meta(array(
				'public' => $this->meta_public,
				'title' => $this->meta_title,
				'meta' => $post_meta,
				'type' => $this->meta_type,
				'symbol' => $this->meta_symbol,
			));
		}

		if($this->show_in_result == true)
		{
			$ghost_post_name = $this->get_post_name_for_type('ghost');

			if($ghost_post_name != '' && get_post_meta($this->product_id, $this->meta_prefix.$ghost_post_name, true) == true)
			{
				$this->product_url = "";
				$this->product_meta = array(
					array(
						'class' => 'description',
						'content' => $this->product_description,
					)
				);
			}

			if($this->product_image != '')
			{
				$product_image = "<img src='".$this->product_image."' alt='".$this->product_title."'>";
			}

			else if($this->category_icon != '')
			{
				$product_image = $this->obj_font_icons->get_symbol_tag($this->category_icon, '', false);
			}

			else
			{
				$product_image = get_image_fallback();
			}

			$json_output['product_response'][] = array(
				'product_id' => $this->product_id,
				'product_title' => $this->product_title,
				'product_clock' => ($this->product_clock),
				'product_address' => $this->product_address,
				'product_data' => $this->product_data,
				'product_location' => $this->product_location,
				'product_url' => $this->product_url,
				'product_image' => $product_image,
				'product_meta' => $this->product_meta,
				'product_description' => apply_filters('the_content', $this->product_description),
				'product_has_email' => $this->product_has_email,
				'product_map' => $this->product_map,
				'product_timestamp' => date("Y-m-d H:i:s"),
			);
		}
	}

	function get_distance($coords_1, $coords_2)
	{
		list($lat_1, $lon_1) = $this->get_lat_long_from_coords($coords_1);
		list($lat_2, $lon_2) = $this->get_lat_long_from_coords($coords_2);

		/* v1 */
		/*$earth_radius = 6371000; //meters and your distance will be in meters

		$p1 = ($lon_1 - $lon_2) * cos(.5 * ($lat_1 + $lat_2)); //convert lat/lon to radians
		$p2 = ($lat_1 - $lat_2);
		$distance_1 = $earth_radius * sqrt($p1 * $p1 + $p2 * $p2);*/

		/* v2 */
		//$distance_2 = acos(sin($lat_1) * sin($lat_2) + cos($lat_1) * cos($lat_2) * cos($lon_1 - $lon_2));

		/* v3 */
		$theta = $lon_1 - $lon_2;
		$dist = sin(deg2rad($lat_1)) * sin(deg2rad($lat_2)) +  cos(deg2rad($lat_1)) * cos(deg2rad($lat_2)) * cos(deg2rad($theta));
		$dist = acos($dist);
		$dist = rad2deg($dist);
		$distance = $dist * 60 * 1.1515 * 1.609344; //kilometers

		return $distance;
	}

	function get_lat_long_from_coords($coords)
	{
		return explode(", ", str_replace(array("(", ")"), "", $coords));
	}

	function insert_sent($data)
	{
		global $wpdb;

		$wpdb->get_results($wpdb->prepare("SELECT productID FROM ".$wpdb->prefix."webshop_sent WHERE productID = '%d' AND answerID = '%d' LIMIT 0, 1", $data['product_id'], $data['answer_id']));

		if($wpdb->num_rows == 0)
		{
			$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."webshop_sent SET productID = '%d', answerID = '%d'", $data['product_id'], $data['answer_id']));
		}
	}

	function get_widget_list($instance, $result, $rows)
	{
		$out = "<div class='section'>
			<ul class='webshop_item_list".($instance['webshop_show_info'] == 'yes' ? "" : " expand_image_container")." text_columns ".($rows % 3 == 0 || $rows > 4 ? "columns_3" : "columns_2")."'>";

				if($rows > 0)
				{
					foreach($result as $r)
					{
						$post_id = $r->ID;
						$post_title = $r->post_title;
						$post_excerpt = $r->post_excerpt;

						if($post_excerpt == '')
						{
							$size_post_name = $this->get_post_name_for_type('description');
							$post_excerpt = get_post_meta($post_id, $this->meta_prefix.$size_post_name, true);
						}

						$arr_product = array();

						$this->get_product_data(array('product' => $r, 'single_image' => true, 'show_location_in_data' => true), $arr_product);

						if(isset($arr_product['product_response']))
						{
							$arr_product = $arr_product['product_response'][0];

							$out .= "<li>
								<div class='product_image_container'>
									<a href='".$arr_product['product_url']."'>"
										.$arr_product['product_image']
									."</a>";

									if($arr_product['product_data'] != '')
									{
										$out .= "<div class='product_data'>".$arr_product['product_data']."</div>";
									}

								$out .= "</div>";

								if($instance['webshop_show_info'] == 'yes')
								{
									if($instance['webshop_display_border'] == 'yes')
									{
										$out .= "<div class='product_border'></div>";
									}

									$out .= "<div class='product_description'>
										<h4><a href='".$arr_product['product_url']."'>".$post_title."</a></h4>";

										if($instance['webshop_display_category'] == 'yes')
										{
											$arr_categories = get_post_meta($post_id, $this->meta_prefix.'category', false);

											if(count($arr_categories) > 0)
											{
												$out .= "<div class='product_categories'>";

													foreach($arr_categories as $key => $value)
													{
														$out .= "<span>".get_post_title($value)."</span>";
													}

												$out .= "</div>";
											}
										}

										if($post_excerpt != '')
										{
											$out .= apply_filters('the_content', $post_excerpt);
										}

									$out .= "</div>";
								}

							$out .= "</li>";
						}

						else
						{
							do_log("No product response: ".var_export(array('product' => $r, 'single_image' => true, 'show_location_in_data' => true), true));
						}
					}
				}

				else
				{
					$out .= "<li>"._("There is nothing to show here", 'lang_webshop')."</li>";
				}

			$out .= "</ul>
		</div>";

		return $out;
	}

	function get_cart()
	{
		global $wpdb, $sesWebshopCookie, $intCustomerID, $intCustomerNo, $strOrderName, $emlOrderEmail, $strOrderText, $intDeliveryTypeID, $error_text, $done_text;

		$out = get_notification();

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, productAmount FROM ".$wpdb->posts." INNER JOIN ".$wpdb->prefix."webshop_product2user ON ".$wpdb->posts.".ID = ".$wpdb->prefix."webshop_product2user.productID WHERE post_type = %s AND webshopDone = '0' AND (userID = '%d' OR webshopCookie = %s)", $this->post_type_products, get_current_user_id(), $sesWebshopCookie));

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

				$arr_data = get_posts_for_select(array('post_type' => $this->post_type_customers, 'order' => "post_title ASC", 'add_choose_here' => true));

				if(count($arr_data) > 0)
				{
					$out .= show_select(array('data' => $arr_data, 'name' => 'intCustomerID', 'text' => __("Customer", 'lang_webshop'), 'value' => $intCustomerID))
					.show_textfield(array('name' => 'intCustomerNo', 'text' => __("Customer No", 'lang_webshop'), 'value' => $intCustomerNo, 'type' => 'number'));
				}

				$out .= show_textfield(array('name' => 'strOrderName', 'text' => __("Name", 'lang_webshop'), 'value' => $strOrderName, 'required' => true))
				.show_textfield(array('name' => 'emlOrderEmail', 'text' => __("E-mail", 'lang_webshop'), 'value' => $emlOrderEmail, 'required' => true))
				.show_textarea(array('name' => 'strOrderText', 'text' => __("Text", 'lang_webshop'), 'value' => $strOrderText));

				$arr_data = get_posts_for_select(array('post_type' => $this->post_type_delivery_type, 'order' => "post_title ASC"));

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
}

class mf_webshop_import extends mf_import
{
	function get_defaults()
	{
		global $wpdb;

		$this->obj_webshop = new mf_webshop();

		$this->prefix = $wpdb->base_prefix;
		$this->table = "posts";
		$this->post_type = $this->obj_webshop->post_type_products;
		$this->actions = array('import');
		$this->columns = array(
			'post_title' => __("Title", 'lang_webshop'),
			'post_content' => __("Content", 'lang_webshop'),
		);

		$this->arr_type = array(
			'ghost',
			'description',
			'number',
			'price',
			'stock',
			'size',
			'interval',
			//'checkbox',
			'email',
			'url',
			'date',
			'clock',
			'gps',
			'address',
			'local_address',
			'phone',
		);

		foreach($this->arr_type as $type)
		{
			$result = $this->obj_webshop->get_post_type_info(array('type' => $type, 'single' => false));

			foreach($result as $r)
			{
				$this->columns[$r->post_name] = $r->post_title;
			}
		}

		$this->unique_columns = array(
			'post_title',
		);
	}

	function get_external_value(&$strRowField, &$value)
	{
		global $wpdb;

		$saved_option = false;

		//$obj_webshop = new mf_webshop();

		foreach($this->arr_type as $type)
		{
			$result = $this->obj_webshop->get_post_type_info(array('type' => $type, 'single' => false));

			foreach($result as $r)
			{
				if($strRowField == $r->post_name)
				{
					$this->query_option[$this->obj_webshop->meta_prefix.$r->post_name] = $value;

					$saved_option = true;
				}
			}
		}

		if($saved_option == true)
		{
			$value = "";
		}
	}
}

if(class_exists('RWMB_Field'))
{
	class RWMB__Field extends RWMB_Field{}

	class RWMB_Address_Field extends RWMB_Text_Field
	{
		static public function html($meta, $field)
		{
			return "<input type='text' name='".$field['field_name']."' id='".$field['id']."' value='".$meta."' class='rwmb-text rwmb-address'>";
		}
	}

	/*class RWMB_Categories_Field extends RWMB_Field
	{
		static public function html($meta, $field)
		{
			//Do nothing here since this is shown in the UI for mf_products if there are any mf_categories
		}
	}*/

	class RWMB_Custom_Categories_Field extends RWMB_Text_Field
	{
		static public function html($meta, $field)
		{
			global $wpdb;

			$obj_webshop = new mf_webshop();

			$post_name = str_replace($obj_webshop->meta_prefix, "", $field['id']);
			$post_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_name = %s", $obj_webshop->post_type_document_type, $post_name));

			$arr_data = array();
			get_post_children(array(
				'add_choose_here' => true,
				'post_type' => $obj_webshop->post_type_custom_categories,
				'join' => " INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = '".$obj_webshop->meta_prefix."document_type'",
				'where' => "meta_value = '".esc_sql($post_id)."'",
				//'debug' => true,
			), $arr_data);

			return show_select(array('data' => $arr_data, 'name' => $field['field_name'], 'value' => $meta, 'class' => "rwmb-select-wrapper", 'required' => true, 'xtra' => self::render_attributes($field['attributes'])));
		}
	}

	class RWMB_Description_Field extends RWMB_Textarea_Field{}

	class RWMB_Content_Field extends RWMB_Textarea_Field
	{
		static public function html($meta, $field)
		{
			$attributes = self::get_attributes($field, $meta);

			return sprintf("<textarea %s>%s</textarea>", self::render_attributes($attributes), $meta);
			//return show_textarea(array('name' => $field['field_name'], 'value' => $meta, 'class' => "rwmb-content large-text", 'xtra' => self::render_attributes($field['attributes'])));
		}
	}

	class RWMB_Event_Field extends RWMB_Text_Field
	{
		static public function html($meta, $field)
		{
			if(is_plugin_active('mf_calendar/index.php'))
			{
				$arr_data = array();
				get_post_children(array('add_choose_here' => true, 'post_type' => 'mf_calendar'), $arr_data);

				return show_select(array('data' => $arr_data, 'name' => $field['field_name'], 'value' => $meta, 'class' => "rwmb-select-wrapper", 'suffix' => "<a href='".admin_url("post-new.php?post_type=mf_calendar")."'><i class='fa fa-plus-circle fa-lg'></i></a>", 'xtra' => self::render_attributes($field['attributes'])));
			}

			else
			{
				return "<p>".sprintf(__("You have to install the plugin %s first", 'lang_webshop'), "MF Calendar")."</p>";
			}
		}
	}

	class RWMB_Ghost_Field extends RWMB_Checkbox_Field
	{
		static public function html($meta, $field)
		{
			return "<input type='checkbox' name='".$field['field_name']."' id='".$field['id']."' value='1'".checked($meta == 1, true, false)." class='rwmb-checkbox rwmb-ghost'>";
		}
	}

	class RWMB_Interval_Field extends RWMB_Field
	{
		static public function html($meta, $field)
		{
			return sprintf(
				"<input type='text' name='%s' id='%s' value='%s' class='rwmb-text rwmb-interval' pattern='[\d-]*' placeholder='5-25&hellip;'%s>",
				$field['field_name'],
				$field['id'],
				$meta,
				self::render_attributes($field['attributes'])
			);
		}
	}

	class RWMB_Local_Address_Field extends RWMB_Text_Field
	{
		static public function html($meta, $field)
		{
			return "<input type='text' name='".$field['field_name']."' id='".$field['id']."' value='".$meta."' class='rwmb-text rwmb-local_address'>";
		}
	}

	class RWMB_Location_Field extends RWMB_Select_Field{}

	class RWMB_Price_Field extends RWMB_Field
	{
		static public function html($meta, $field)
		{
			return sprintf(
				"<input type='number' name='%s' id='%s' value='%s' class='rwmb-price'%s>",
				$field['field_name'],
				$field['id'],
				$meta,
				self::render_attributes($field['attributes'])
			);
		}
	}

	class RWMB_Size_Field extends RWMB_Field
	{
		static public function html($meta, $field)
		{
			return sprintf(
				"<input type='number' name='%s' id='%s' value='%s' class='rwmb-size'%s>",
				$field['field_name'],
				$field['id'],
				$meta,
				self::render_attributes($field['attributes'])
			);
		}
	}

	if(!class_exists('RWMB_Social_Field'))
	{
		class RWMB_Social_Field extends RWMB_Field
		{
			static public function html($meta, $field)
			{
				if(is_plugin_active('mf_social_feed/index.php'))
				{
					$arr_data = array();
					get_post_children(array('add_choose_here' => true, 'post_type' => 'mf_social_feed'), $arr_data);

					return show_select(array('data' => $arr_data, 'name' => $field['field_name'], 'value' => $meta, 'class' => "rwmb-select-wrapper", 'suffix' => "<a href='".admin_url("post-new.php?post_type=mf_social_feed")."'><i class='fa fa-plus-circle fa-lg'></i></a>", 'xtra' => self::render_attributes($field['attributes'])));
				}

				else
				{
					return "<p>".sprintf(__("You have to install the plugin %s first", 'lang_webshop'), "MF Social Feed")."</p>";
				}
			}
		}
	}

	class RWMB_Stock_Field extends RWMB_Field
	{
		static public function html($meta, $field)
		{
			return sprintf(
				"<input type='number' name='%s' id='%s' value='%s' class='rwmb-stock'%s>",
				$field['field_name'],
				$field['id'],
				$meta,
				self::render_attributes($field['attributes'])
			);
		}
	}
}

class widget_webshop_search extends WP_Widget
{
	function __construct()
	{
		$widget_ops = array(
			'classname' => 'webshop_search',
			'description' => __("Display Search", 'lang_webshop')
		);

		$this->arr_default = array(
			'webshop_heading' => '',
			'webshop_option_type' => '',
		);

		$this->obj_webshop = new mf_webshop();

		parent::__construct('webshop-search-widget', __("Webshop", 'lang_webshop')." (".__("Search", 'lang_webshop').")", $widget_ops);
	}

	function widget($args, $instance)
	{
		extract($args);

		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$this->obj_webshop->option_type = ($instance['webshop_option_type'] != '' ? "_".$instance['webshop_option_type'] : '');

		echo $before_widget;

			if($instance['webshop_heading'] != '')
			{
				echo $before_title
					.$instance['webshop_heading']
				.$after_title;
			}

			echo "<form action='".get_form_url(get_option('setting_quote_form'.$this->obj_webshop->option_type))."' method='post' id='product_form' class='mf_form product_search'>"
				/*."<div class='aside'><div>".$this->obj_webshop->get_webshop_map()."</div></div>"*/
				//."<div>"
					.$this->obj_webshop->get_search_result_info(array('type' => 'filter'))
					.$this->obj_webshop->get_webshop_search()
					.$this->obj_webshop->get_search_result_info(array('type' => 'matches'))
					."<ul id='product_result_search' class='product_list webshop_item_list'><li class='loading'><i class='fa fa-spinner fa-spin fa-3x'></i></li></ul>"
					.$this->obj_webshop->get_quote_button()
					.$this->obj_webshop->get_form_fields_passthru()
				//."</div>"
			."</form>"
			.$this->obj_webshop->get_templates()
		.$after_widget;
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;

		$new_instance = wp_parse_args((array)$new_instance, $this->arr_default);

		$instance['webshop_heading'] = sanitize_text_field($new_instance['webshop_heading']);
		$instance['webshop_option_type'] = sanitize_text_field($new_instance['webshop_option_type']);

		return $instance;
	}

	function form($instance)
	{
		global $wpdb;

		$instance = wp_parse_args((array)$instance, $this->arr_default);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('webshop_heading'), 'text' => __("Heading", 'lang_webshop'), 'value' => $instance['webshop_heading'], 'xtra' => " id='webshop-title'"))
			.show_select(array('data' => $this->obj_webshop->get_option_types_for_select(), 'name' => $this->get_field_name('webshop_option_type'), 'text' => __("Type", 'lang_webshop'), 'value' => $instance['webshop_option_type']))
		."</div>";
	}
}

class widget_webshop_map extends WP_Widget
{
	function __construct()
	{
		$widget_ops = array(
			'classname' => 'webshop_map',
			'description' => __("Display Map", 'lang_webshop')
		);

		$this->arr_default = array(
			'webshop_heading' => '',
			'webshop_option_type' => '',
		);

		$this->obj_webshop = new mf_webshop();

		parent::__construct('webshop-map-widget', __("Webshop", 'lang_webshop')." (".__("Map", 'lang_webshop').")", $widget_ops);
	}

	function widget($args, $instance)
	{
		extract($args);

		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$this->obj_webshop->option_type = ($instance['webshop_option_type'] != '' ? "_".$instance['webshop_option_type'] : '');

		echo $before_widget;

			if($instance['webshop_heading'] != '')
			{
				echo $before_title
					.$instance['webshop_heading']
				.$after_title;
			}

			echo "<div>".$this->obj_webshop->get_webshop_map()."</div>"
		.$after_widget;
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;

		$new_instance = wp_parse_args((array)$new_instance, $this->arr_default);

		$instance['webshop_heading'] = sanitize_text_field($new_instance['webshop_heading']);
		$instance['webshop_option_type'] = sanitize_text_field($new_instance['webshop_option_type']);

		return $instance;
	}

	function form($instance)
	{
		global $wpdb;

		$instance = wp_parse_args((array)$instance, $this->arr_default);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('webshop_heading'), 'text' => __("Heading", 'lang_webshop'), 'value' => $instance['webshop_heading'], 'xtra' => " id='webshop-title'"))
			.show_select(array('data' => $this->obj_webshop->get_option_types_for_select(), 'name' => $this->get_field_name('webshop_option_type'), 'text' => __("Type", 'lang_webshop'), 'value' => $instance['webshop_option_type']))
		."</div>";
	}
}

class widget_webshop_form extends WP_Widget
{
	function __construct()
	{
		$widget_ops = array(
			'classname' => 'webshop_form',
			'description' => __("Display start page form", 'lang_webshop')
		);

		$this->arr_default = array(
			'webshop_heading' => '',
			'webshop_action' => 0,
			'webshop_doc_type' => array(),
			'webshop_doc_type_default' => '',
			'webshop_form_button_text' => '',
		);

		$this->obj_webshop = new mf_webshop();

		$name_webshop = get_option_or_default('setting_webshop_replace_webshop', __("Webshop", 'lang_webshop'));
		$this->name_products = get_option_or_default('setting_webshop_replace_products', __("Products", 'lang_webshop'));

		parent::__construct('webshop-widget', $name_webshop." (".__("Form", 'lang_webshop').")", $widget_ops);

		$this->name_doc_types = get_option_or_default('setting_webshop_replace_doc_types', __("Filters", 'lang_webshop'));
	}

	function get_doc_type_input($data)
	{
		global $wpdb;

		if(!isset($data['value'])){		$data['value'] = '';}

		$arr_data = array();
		$out = "";

		$obj_webshop_interval = new mf_webshop();

		$result = $this->obj_webshop->get_document_types(array('select' => "ID, post_title, post_name", 'join' => "INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = '".$this->obj_webshop->meta_prefix."document_searchable'", 'where_key' => "ID = '%d' AND meta_value = 'yes'", 'where_value' => $data['post_id']));

		$obj_webshop_interval->set_interval_amount($result);

		foreach($result as $r)
		{
			$post_title = $r->post_title;
			$post_name = $r->post_name;

			$post_custom_type = get_post_meta($data['post_id'], $this->obj_webshop->meta_prefix.'document_type', true);

			$post_value = check_var($post_name);

			if($post_value != '')
			{
				$data['value'] = $post_value;
			}

			switch($post_custom_type)
			{
				case 'location':
					$arr_data = array(
						'' => $post_title."?"
					);

					get_post_children(array('post_type' => $this->post_type_location), $arr_data);

					$out = show_select(array('data' => $arr_data, 'name' => $post_name, 'value' => $data['value']));
				break;

				case 'categories':
					$arr_data = array(
						'' => $post_title."?"
					);

					get_post_children(array('post_type' => $this->obj_webshop->post_type_categories), $arr_data);

					$out = show_select(array('data' => $arr_data, 'name' => $post_name, 'value' => $data['value']));
				break;

				case 'number':
				case 'price':
				case 'size':
				case 'address':
				case 'local_address':
					$arr_data = array(
						'' => $post_title."?"
					);

					$is_numeric = in_array($post_custom_type, array('number', 'price', 'size'));

					$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = 'publish'", $this->post_type_products));

					foreach($result as $r)
					{
						$page_id = $r->ID;

						$post_meta = get_post_meta($page_id, $this->obj_webshop->meta_prefix.$post_name, true);

						if($is_numeric)
						{
							$arr_data[$post_meta] = $post_meta;

							$this->obj_webshop->set_range($post_meta);
						}

						else
						{
							$arr_data[$post_meta] = $post_meta;
						}
					}

					if($is_numeric && count($arr_data) > 5)
					{
						$arr_data = array(
							'' => $post_title."?"
						);

						$this->obj_webshop->calculate_range($arr_data);
					}

					$out = show_select(array('data' => $arr_data, 'name' => $post_name, 'value' => $data['value']));
				break;

				case 'interval':
					$obj_webshop_interval->increase_count();

					$post_document_alt_text = get_post_meta($data['post_id'], $this->obj_webshop->meta_prefix.'document_alt_text', true);

					if($post_document_alt_text != '')
					{
						$post_title = $post_document_alt_text;
					}

					$obj_webshop_interval->add_interval_type($post_name, $post_title);

					$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = 'publish'", $this->post_type_products));

					foreach($result as $r)
					{
						$page_id = $r->ID;

						$post_meta = get_post_meta($page_id, $this->obj_webshop->meta_prefix.$post_name, true);

						list($post_meta_min, $post_meta_max) = $this->get_interval_min($post_meta);

						$obj_webshop_interval->set_range($post_meta_min);
					}

					$out = $obj_webshop_interval->has_equal_amount($post_title, $name_choose_here);
				break;

				default:
					do_log(sprintf(__("The type '%s' does not have a case", 'lang_webshop'), $post_custom_type)." (widget)");
				break;
			}
		}

		if($out != '')
		{
			$out = "<li>".$out."</li>";
		}

		return array($arr_data, $out);
	}

	function widget($args, $instance)
	{
		extract($args);

		$instance = wp_parse_args((array)$instance, $this->arr_default);

		if(is_array($instance['webshop_doc_type']) || $instance['webshop_doc_type'] != '')
		{
			if(!is_array($instance['webshop_doc_type'])){	$instance['webshop_doc_type'] = array($instance['webshop_doc_type']);}

			if(count($instance['webshop_doc_type']) > 0)
			{
				$name_choose_here = "-- ".__("Choose Here", 'lang_webshop')." --";

				echo $before_widget;

					if($instance['webshop_heading'] != '')
					{
						echo $before_title
							.$instance['webshop_heading']
						.$after_title;
					}

					echo "<form method='get' action='".get_permalink($instance['webshop_action'])."' class='mf_form'>
						<ul class='flex_flow'>";

							foreach($instance['webshop_doc_type'] as $post_id)
							{
								list($arr_data, $out_temp) = $this->get_doc_type_input(array('post_id' => $post_id, 'value' => $instance['webshop_doc_type_default']));

								echo $out_temp;
							}

							echo "<li class='form_button'>"
								.show_button(array('text' => ($instance['webshop_form_button_text'] != '' ? $instance['webshop_form_button_text'] : __("Search", 'lang_webshop'))))
							."</li>
						</ul>";

						if(get_option('setting_show_all_min') > 0)
						{
							echo "<p class='webshop_form_link'>
								<a href='".get_permalink($instance['webshop_action'])."'>".__("Show all", 'lang_webshop')."<span></span>".($this->name_products != '' ? " ".strtolower($this->name_products) : "")."</a>
							</p>";
						}

					echo "</form>"
				.$after_widget;
			}
		}
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;

		$new_instance = wp_parse_args((array)$new_instance, $this->arr_default);

		$instance['webshop_heading'] = sanitize_text_field($new_instance['webshop_heading']);
		$instance['webshop_action'] = sanitize_text_field($new_instance['webshop_action']);
		$instance['webshop_doc_type'] = is_array($new_instance['webshop_doc_type']) ? $new_instance['webshop_doc_type'] : array();
		$instance['webshop_doc_type_default'] = sanitize_text_field($new_instance['webshop_doc_type_default']);
		$instance['webshop_form_button_text'] = sanitize_text_field($new_instance['webshop_form_button_text']);

		return $instance;
	}

	function form($instance)
	{
		global $wpdb;

		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$arr_data_action = array();
		get_post_children(array(), $arr_data_action);

		$arr_data_doc_type = array();

		$result = $this->obj_webshop->get_document_types(array('select' => "ID, post_title, post_name", 'join' => "INNER JOIN ".$wpdb->postmeta." AS meta1 ON ".$wpdb->posts.".ID = meta1.post_id AND meta1.meta_key = '".$this->obj_webshop->meta_prefix."document_searchable' LEFT JOIN ".$wpdb->postmeta." AS meta2 ON ".$wpdb->posts.".ID = meta2.post_id AND meta2.meta_key = '".$this->obj_webshop->meta_prefix."document_type_order'", 'where_key' => "meta1.meta_value = %s", 'where_value' => 'yes', 'order' => "meta2.meta_value + 0 ASC, menu_order ASC"));

		foreach($result as $r)
		{
			$post_id = $r->ID;
			$post_title = $r->post_title;
			$post_name = $r->post_name;

			$post_custom_type = get_post_meta($post_id, $this->obj_webshop->meta_prefix.'document_type', true);

			if(in_array($post_custom_type, array('number', 'price', 'size', 'address', 'local_address', 'interval', 'location', 'categories')))
			{
				$arr_data_doc_type[$post_id] = $post_title;
			}
		}

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('webshop_heading'), 'text' => __("Heading", 'lang_webshop'), 'value' => $instance['webshop_heading'], 'xtra' => " id='webshop-title'"))
			.show_select(array('data' => $arr_data_action, 'name' => $this->get_field_name('webshop_action'), 'text' => __("Go to after submit", 'lang_webshop'), 'value' => $instance['webshop_action']))
			."<div class='flex_flow'>"
				.show_select(array('data' => $arr_data_doc_type, 'name' => $this->get_field_name('webshop_doc_type')."[]", 'text' => $this->name_doc_types, 'value' => $instance['webshop_doc_type']));

				if(count($instance['webshop_doc_type']) == 1)
				{
					foreach($instance['webshop_doc_type'] as $post_id)
					{
						list($arr_data, $out_temp) = $this->get_doc_type_input(array('post_id' => $post_id));

						echo show_select(array('data' => $arr_data, 'name' => $this->get_field_name('webshop_doc_type_default'), 'text' => sprintf(__("Default %s", 'lang_webshop'), $this->name_doc_types), 'value' => $instance['webshop_doc_type_default']));
					}
				}

			echo "</div>"
			.show_textfield(array('name' => $this->get_field_name('webshop_form_button_text'), 'text' => __("Button text", 'lang_webshop'), 'value' => $instance['webshop_form_button_text']))
		."</div>";
	}
}

class widget_webshop_list extends WP_Widget
{
	function __construct()
	{
		$widget_ops = array(
			'classname' => 'webshop_list webshop_widget',
			'description' => __("Display start page list", 'lang_webshop')
		);

		$this->arr_default = array(
			'webshop_heading' => "",
			'webshop_action' => 0,
			'webshop_locations' => "",
		);

		$this->obj_webshop = new mf_webshop();

		$name_webshop = get_option_or_default('setting_webshop_replace_webshop', __("Webshop", 'lang_webshop'));

		parent::__construct('webshop-list-widget', $name_webshop." (".__("List", 'lang_webshop').")", $widget_ops);
	}

	function widget($args, $instance)
	{
		global $wpdb;

		extract($args);

		$instance = wp_parse_args((array)$instance, $this->arr_default);

		if(is_array($instance['webshop_locations']))
		{
			echo $before_widget;

				if($instance['webshop_heading'] != '')
				{
					echo $before_title
						.$instance['webshop_heading']
					.$after_title;
				}

				$arr_data = array();
				get_post_children(array('post_type' => $this->obj_webshop->post_type_location), $arr_data);

				echo "<div class='section'>
					<ul class='text_columns columns_3'>"; //".(count($arr_data) % 3 == 0 || count($arr_data) > 4 ? "" : "columns_2")."

						foreach($arr_data as $key => $value)
						{
							if(in_array($key, $instance['webshop_locations']))
							{
								$post_name = $this->obj_webshop->get_post_name_for_type('location');

								echo "<li><a href='".get_permalink($instance['webshop_action'])."?".$post_name."=".$key."#".$post_name."=".$key."'>".trim($value, "&nbsp;")."</a></li>";
							}
						}

					echo "</ul>
				</div>"
			.$after_widget;
		}
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;

		$new_instance = wp_parse_args((array)$new_instance, $this->arr_default);

		$instance['webshop_heading'] = sanitize_text_field($new_instance['webshop_heading']);
		$instance['webshop_action'] = sanitize_text_field($new_instance['webshop_action']);
		$instance['webshop_locations'] = is_array($new_instance['webshop_locations']) ? $new_instance['webshop_locations'] : array();

		return $instance;
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$arr_data = array();
		get_post_children(array(), $arr_data);

		$arr_data_locations = array();
		get_post_children(array('post_type' => $this->obj_webshop->post_type_location), $arr_data_locations);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('webshop_heading'), 'text' => __("Heading", 'lang_webshop'), 'value' => $instance['webshop_heading'], 'xtra' => " id='webshop-title'"))
			.show_select(array('data' => $arr_data, 'name' => $this->get_field_name('webshop_action'), 'text' => __("Go to on click", 'lang_webshop'), 'value' => $instance['webshop_action']))
			.show_select(array('data' => $arr_data_locations, 'name' => $this->get_field_name('webshop_locations')."[]", 'text' => __("Locations", 'lang_webshop'), 'value' => $instance['webshop_locations']))
		."</div>";
	}
}

class widget_webshop_favorites extends WP_Widget
{
	function __construct()
	{
		$widget_ops = array(
			'classname' => 'webshop_favorites webshop_widget',
			'description' => __("Display start page favorites", 'lang_webshop')
		);

		$this->arr_default = array(
			'webshop_heading' => "",
			'webshop_products' => array(),
			'webshop_display_category' => 'no',
			'webshop_show_info' => 'no',
			'webshop_display_border' => 'yes',
		);

		$this->obj_webshop = new mf_webshop();

		$name_webshop = get_option_or_default('setting_webshop_replace_webshop', __("Webshop", 'lang_webshop'));

		parent::__construct('webshop-favorites-widget', $name_webshop." (".__("Favorites", 'lang_webshop').")", $widget_ops);

		$this->name_products = get_option_or_default('setting_webshop_replace_products', __("Products", 'lang_webshop'));
	}

	function widget($args, $instance)
	{
		global $wpdb;

		extract($args);

		$instance = wp_parse_args((array)$instance, $this->arr_default);

		if(count($instance['webshop_products']) > 0)
		{
			echo $before_widget;

				if($instance['webshop_heading'] != '')
				{
					echo $before_title
						.$instance['webshop_heading']
					.$after_title;
				}

				$query_join = "";

				$address_post_name = $this->obj_webshop->get_post_name_for_type('address');

				if($address_post_name != '')
				{
					$query_join = " LEFT JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = '".esc_sql($this->obj_webshop->meta_prefix.$address_post_name)."'";
				}

				$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_excerpt, post_content FROM ".$wpdb->posts.$query_join." WHERE post_type = %s AND post_status = 'publish' AND ID IN ('".implode("','", $instance['webshop_products'])."') ORDER BY menu_order ASC", $this->obj_webshop->post_type_products));
				$rows = $wpdb->num_rows;

				echo $this->obj_webshop->get_widget_list($instance, $result, $rows)
			.$after_widget;
		}
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;

		$new_instance = wp_parse_args((array)$new_instance, $this->arr_default);

		$instance['webshop_heading'] = sanitize_text_field($new_instance['webshop_heading']);
		$instance['webshop_products'] = is_array($new_instance['webshop_products']) ? $new_instance['webshop_products'] : array();
		$instance['webshop_display_category'] = sanitize_text_field($new_instance['webshop_display_category']);
		$instance['webshop_show_info'] = sanitize_text_field($new_instance['webshop_show_info']);
		$instance['webshop_display_border'] = sanitize_text_field($new_instance['webshop_display_border']);

		return $instance;
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$arr_data = array();
		get_post_children(array('post_type' => $this->obj_webshop->post_type_products, 'order_by' => 'post_title'), $arr_data);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('webshop_heading'), 'text' => __("Heading", 'lang_webshop'), 'value' => $instance['webshop_heading'], 'xtra' => " id='webshop-title'"))
			.show_select(array('data' => $arr_data, 'name' => $this->get_field_name('webshop_products')."[]", 'text' => $this->name_products, 'value' => $instance['webshop_products']))
			.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('webshop_display_category'), 'text' => __("Display Category", 'lang_webshop'), 'value' => $instance['webshop_display_category']))
			."<div class='flex_flow'>"
				.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('webshop_show_info'), 'text' => __("Display Info", 'lang_webshop'), 'value' => $instance['webshop_show_info']));

				if($instance['webshop_show_info'] == 'yes')
				{
					echo show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('webshop_display_border'), 'text' => __("Display Border", 'lang_webshop'), 'value' => $instance['webshop_display_border']));
				}

			echo "</div>
		</div>";
	}
}

class widget_webshop_recent extends WP_Widget
{
	function __construct()
	{
		$widget_ops = array(
			'classname' => 'webshop_recent webshop_widget',
			'description' => __("Display recent", 'lang_webshop')
		);

		$this->arr_default = array(
			'webshop_heading' => '',
			'webshop_amount' => 3,
			'webshop_display_category' => 'no',
			'webshop_show_info' => 'no',
			'webshop_display_border' => 'yes',
		);

		$this->obj_webshop = new mf_webshop();

		$name_webshop = get_option_or_default('setting_webshop_replace_webshop', __("Webshop", 'lang_webshop'));

		parent::__construct('webshop-recent-widget', $name_webshop." (".__("Recent", 'lang_webshop').")", $widget_ops);

		$this->name_products = get_option_or_default('setting_webshop_replace_products', __("Products", 'lang_webshop'));
	}

	function widget($args, $instance)
	{
		global $wpdb;

		extract($args);

		$instance = wp_parse_args((array)$instance, $this->arr_default);

		if($instance['webshop_amount'] > 0)
		{
			echo $before_widget;

				if($instance['webshop_heading'] != '')
				{
					echo $before_title
						.$instance['webshop_heading']
					.$after_title;
				}

				$query_join = $query_where = "";

				$address_post_name = $this->obj_webshop->get_post_name_for_type('address');
				$ghost_post_name = $this->obj_webshop->get_post_name_for_type('ghost');

				if($ghost_post_name != '')
				{
					$query_join .= " LEFT JOIN ".$wpdb->postmeta." AS ghost_meta ON ".$wpdb->posts.".ID = ghost_meta.post_id AND ghost_meta.meta_key = '".esc_sql($this->obj_webshop->meta_prefix.$ghost_post_name)."'";
					$query_where .= " AND (ghost_meta.meta_value = '0' OR ghost_meta.meta_value IS null)";
				}

				if($address_post_name != '')
				{
					$query_join .= " LEFT JOIN ".$wpdb->postmeta." AS address_meta ON ".$wpdb->posts.".ID = address_meta.post_id AND address_meta.meta_key = '".esc_sql($this->obj_webshop->meta_prefix.$address_post_name)."'";
				}

				$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_excerpt, post_content FROM ".$wpdb->posts.$query_join." WHERE post_type = %s AND post_status = 'publish'".$query_where." ORDER BY post_date DESC LIMIT 0, ".esc_sql($instance['webshop_amount']), $this->obj_webshop->post_type_products));
				$rows = $wpdb->num_rows;

				echo $this->obj_webshop->get_widget_list($instance, $result, $rows)
			.$after_widget;
		}
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;

		$new_instance = wp_parse_args((array)$new_instance, $this->arr_default);

		$instance['webshop_heading'] = sanitize_text_field($new_instance['webshop_heading']);
		$instance['webshop_amount'] = sanitize_text_field($new_instance['webshop_amount']);
		$instance['webshop_display_category'] = sanitize_text_field($new_instance['webshop_display_category']);
		$instance['webshop_show_info'] = sanitize_text_field($new_instance['webshop_show_info']);
		$instance['webshop_display_border'] = sanitize_text_field($new_instance['webshop_display_border']);

		return $instance;
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('webshop_heading'), 'text' => __("Heading", 'lang_webshop'), 'value' => $instance['webshop_heading'], 'xtra' => " id='webshop-title'"))
			."<div class='flex_flow'>"
				.show_textfield(array('type' => 'number', 'name' => $this->get_field_name('webshop_amount'), 'text' => __("Amount", 'lang_webshop'), 'value' => $instance['webshop_amount']))
				.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('webshop_display_category'), 'text' => __("Display Category", 'lang_webshop'), 'value' => $instance['webshop_display_category']))
			."</div>
			<div class='flex_flow'>"
				.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('webshop_show_info'), 'text' => __("Display Info", 'lang_webshop'), 'value' => $instance['webshop_show_info']));

				if($instance['webshop_show_info'] == 'yes')
				{
					echo show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('webshop_display_border'), 'text' => __("Display Border", 'lang_webshop'), 'value' => $instance['webshop_display_border']));
				}

			echo "</div>
		</div>";
	}
}

if(!class_exists('pagination'))
{
	class pagination
	{
		function __construct()
		{
			$this->range = 5;
			$this->per_page = 20;
			$this->count = 0;
		}

		function show($data)
		{
			global $intLimitStart;

			if(!is_array($data['result']) && $data['result'] > 0)
			{
				$rows = $data['result'];
			}

			else
			{
				$rows = $data['result'] != '' ? count($data['result']) : 0;
			}

			if($rows > $this->per_page)
			{
				$first = 1;
				$last = ceil($rows / $this->per_page);
				$this->current = floor($intLimitStart / $this->per_page) + 1;

				$start = $first < ($this->current - $this->range - 1) ? $this->current - $this->range : $first;
				$stop = $last > ($this->current + $this->range + 1) ? $this->current + $this->range : $last;

				$out = "<div class='tablenav'>
					<div class='tablenav-pages'>";

						if($this->current > $first)
						{
							$out .= $this->button(array('page' => ($this->current - 1), 'text' => "&laquo;&laquo;"));
						}

						if($start != $first)
						{
							$out .= $this->button(array('page' => $first))."<span>...</span>";
						}

						for($i = $start; $i <= $stop; $i++)
						{
							$out .= $this->button(array('page' => $i));
						}

						if($stop != $last)
						{
							$out .= "<span>...</span>".$this->button(array('page' => $last));
						}

						if($this->current < $last)
						{
							$out .= $this->button(array('page' => ($this->current + 1), 'text' => "&raquo;&raquo;"));
						}

					$out .= "</div>
				</div>";

				$this->count++;

				return $out;
			}
		}

		function button($data)
		{
			return "<a href='".preg_replace("/\&paged\=\d+/", "", $_SERVER['REQUEST_URI'])."&paged=".($data['page'] - 1)."'".($this->current == $data['page'] ? " class='disabled'" : "").">"
				.(isset($data['text']) ? $data['text'] : $data['page'])
			."</a>";
		}
	}
}