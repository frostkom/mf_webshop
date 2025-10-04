<?php

class mf_webshop
{
	var $meta_prefix = 'mf_ws_';
	var $cookie_name;
	var $cookie_value;
	var $range_min = "";
	var $range_max = "";
	var $interval_amount = 0;
	var $interval_count = 0;
	var $arr_interval_type_data = [];
	var $post_name_for_type = [];
	var $post_type_categories = 'mf_category';
	var $post_type_products = 'mf_product';
	var $post_type_custom_categories = 'mf_cust_cat';
	var $post_type_document_type = 'mf_doc_type';
	var $post_type_orders = 'mf_webshop_orders';
	var $post_type_location = 'mf_location';
	var $post_type_customers = 'mf_customer';
	var $post_type_delivery_type = 'mf_delivery';
	var $template_used = [];
	var $option_type = '';
	var $event_max_length = 10;
	var $product_id = 0;
	var $event_id = 0;

	var $order_id;
	var $arr_meta_keys = ['first_name', 'last_name', 'contact_phone', 'contact_email', 'address_street', 'address_co', 'address_zip', 'address_city']; //'address_country'
	var $order_details;

	function __construct()
	{
		$this->cookie_name = $this->meta_prefix.'cart'.COOKIEHASH;
		$this->cookie_value = md5($this->meta_prefix.'cart_'.apply_filters('get_current_visitor_ip', ""));
	}

	function set_cookie()
	{
		setcookie($this->cookie_name, $this->cookie_value, strtotime("+1 month"), COOKIEPATH);
		$_COOKIE[$this->cookie_name] = $this->cookie_value;

		return $_COOKIE[$this->cookie_name];
	}

	function get_cookie()
	{
		return (isset($_COOKIE[$this->cookie_name]) ? $_COOKIE[$this->cookie_name] : $this->set_cookie());
	}

	function get_category_colors($data = [])
	{
		global $wpdb;

		if(!isset($data['type'])){	$data['type'] = 'category_icon_color';}

		$result = [];

		$result_temp = $wpdb->get_results($wpdb->prepare("SELECT ID, meta_value FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND meta_key = %s AND meta_value != '' GROUP BY ID", $this->post_type_categories, $this->meta_prefix.$data['type']));

		$result = array_merge($result, $result_temp);

		return $result;
	}

	function create_product_event_connection($post_id = 0)
	{
		global $wpdb;

		if($post_id > 0)
		{
			$post_title = get_the_title($post_id);
			$post_author = mf_get_post_content($post_id, 'post_author');
		}

		else
		{
			$post_title = get_user_info();
			$post_author = get_current_user_id();
		}

		$event_id = 0;

		if(is_plugin_active("mf_calendar/index.php"))
		{
			global $obj_calendar;

			if(!isset($obj_calendar))
			{
				$obj_calendar = new mf_calendar();
			}

			$event_post_name = $this->get_post_name_for_type('event');

			if($event_post_name != '')
			{
				$event_id = get_post_meta($post_id, $this->meta_prefix.$event_post_name, true);

				if(!($event_id > 0))
				{
					$event_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_title = %s AND post_author = '%d' AND post_parent = '0'", $obj_calendar->post_type, $post_title, $post_author));

					if($event_id > 0)
					{
						update_post_meta($post_id, $this->meta_prefix.$event_post_name, $event_id);
					}
				}

				if(!($event_id > 0))
				{
					$event_id = wp_insert_post(array(
						'post_title' => $post_title,
						'post_type' => $obj_calendar->post_type,
						'post_status' => 'publish',
						'post_author' => $post_author,
					));

					update_post_meta($post_id, $this->meta_prefix.$event_post_name, $event_id);
				}
			}
		}
	}

	function is_between($data)
	{
		$out = false;

		$value_min = $data['value'][0];
		$value_max = (isset($data['value'][1]) ? $data['value'][1] : '');
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

	function get_document_types_for_select($data = [])
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

		$arr_data = [];

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

	function get_sort_for_select($include = [])
	{
		$arr_data = [];

		if(!is_array($include) || count($include) == 0 || in_array('alphabetical', $include))
		{
			$arr_data['alphabetical'] = __("A-Z", 'lang_webshop');
		}

		if(!is_array($include) || count($include) == 0 || in_array('newest', $include) || in_array('latest', $include))
		{
			$arr_data['latest'] = __("Latest", 'lang_webshop');
		}

		/*if(!is_array($include) || count($include) == 0 || in_array('popular', $include))
		{
			if(is_plugin_active("mf_form/index.php"))
			{
				$arr_data['popular'] = __("Popularity", 'lang_webshop');
			}
		}*/

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
			'group_special_input' => "-- ".__("Special Input", 'lang_webshop')." --",
				'color' => __("Color Picker", 'lang_webshop'),
				'event' => __("Event", 'lang_webshop'),
				'page' => __("Page", 'lang_webshop'),
				'file_advanced' => __("File", 'lang_webshop'),
				'categories' => __("Categories", 'lang_webshop'),
				'categories_v2' => __("Categories", 'lang_webshop')." (v2)",
				'custom_categories' => __("Custom Categories", 'lang_webshop'),
				'social' => __("Social Feed", 'lang_webshop'),
				'overlay' => __("Overlay", 'lang_webshop'),
			'group_numbers' => "-- ".__("Numbers", 'lang_webshop')." --",
				'number' => __("Number", 'lang_webshop'),
				'price' => __("Number", 'lang_webshop')." (".__("Price", 'lang_webshop').")",
				'size' => __("Number", 'lang_webshop')." (".__("Size", 'lang_webshop').")",
				'stock' => __("Number", 'lang_webshop')." (".__("Stock", 'lang_webshop').")",
				'interval' => __("Interval", 'lang_webshop'),
			'group_location' => "-- ".__("Location", 'lang_webshop')." --",
				'city' => __("City", 'lang_webshop'),
				'location' => __("Location", 'lang_webshop'),
				'address' => __("Address", 'lang_webshop'),
				'local_address' => __("Local Address", 'lang_webshop'),
				'coordinates' => __("Coordinates", 'lang_webshop'),
				'gps' => __("Map", 'lang_webshop'),
			'group_formatting' => "-- ".__("Formatting", 'lang_webshop')." --",
				'divider' => __("Divider", 'lang_webshop'),
				'read_more_button' => __("Read More Button", 'lang_webshop'),
				'container_start' => __("Start of Container", 'lang_webshop'),
				'container_end' => __("End of Container", 'lang_webshop'),
			'group_settings' => "-- ".__("Settings", 'lang_webshop')." --",
				'ghost' => __("Hide Information", 'lang_webshop'),
				'global_code' => __("Global Code", 'lang_webshop'),
		);

		$arr_data = apply_filters('get_webshop_filters_for_select', $arr_data);

		return $arr_data;
	}

	function get_map_visibility_for_select($data = [])
	{
		if(!isset($data['allow_disable'])){		$data['allow_disable'] = false;}

		$arr_data = array(
			'everywhere' => __("Everywhere", 'lang_webshop'),
			'search' => __("Only in search view", 'lang_webshop'),
			'single' => __("Only on single page", 'lang_webshop'),
			'nowhere' => __("Nowhere", 'lang_webshop'),
		);

		/*if($data['allow_disable'] == true)
		{
			$arr_data['disable'] = __("Disable", 'lang_webshop');
		}*/

		return $arr_data;
	}

	function get_map_placement_for_select($data = [])
	{
		return array(
			'above_filter' => __("Above Filter", 'lang_webshop'),
			'below_filter' => __("Below Filter", 'lang_webshop'),
		);
	}

	function get_map_button_placement_for_select($data = [])
	{
		return array(
			'above_map' => __("Above Map", 'lang_webshop'),
			'page_bottom' => __("Page Bottom", 'lang_webshop'),
		);
	}

	function get_symbols_for_select()
	{
		global $obj_font_icons;

		if(!isset($obj_font_icons))
		{
			$obj_font_icons = new mf_font_icons();
		}

		$arr_data = array(
			'' => "-- ".__("Choose Here", 'lang_webshop')." --",
		);

		foreach($obj_font_icons->get_array(array('allow_optgroup' => false)) as $icon)
		{
			$arr_data[$icon] = $icon;
		}

		return $arr_data;
	}

	function get_include_on_for_select()
	{
		return array(
			'products' => __("Products", 'lang_webshop'),
			//'events' => __("Events", 'lang_webshop'),
		);
	}

	function get_categories_result($data = [])
	{
		global $wpdb;

		if(!isset($data['include_on'])){			$data['include_on'] = 'products';}
		if(!isset($data['post_parent'])){			$data['post_parent'] = 0;}
		if(!isset($data['limit'])){					$data['limit'] = 0;}

		$query_limit = "";

		if($data['limit'] > 0)
		{
			$query_limit = " LIMIT 0, ".$data['limit'];
		}

		return $wpdb->get_results($wpdb->prepare("SELECT ID, post_title FROM ".$wpdb->posts." LEFT JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = %s WHERE post_type = %s AND post_status = %s AND post_parent = '%d' AND (meta_value = %s OR meta_value IS null) GROUP BY ID ORDER BY menu_order ASC".$query_limit, $this->meta_prefix.'include_on', $this->post_type_categories, 'publish', $data['post_parent'], $data['include_on']));
	}

	function get_categories_for_select($data = [])
	{
		if(!isset($data['add_choose_here'])){		$data['add_choose_here'] = true;}
		if(!isset($data['display_icons'])){			$data['display_icons'] = false;}

		$arr_data = [];

		if($data['add_choose_here'])
		{
			$arr_data[''] = "-- ".__("Choose Category Here", 'lang_webshop')." --";
		}

		$result = $this->get_categories_result($data);

		if($data['display_icons'])
		{
			global $obj_font_icons;

			if(!isset($obj_font_icons))
			{
				$obj_font_icons = new mf_font_icons();
			}
		}

		foreach($result as $r)
		{
			$post_id = $r->ID;
			$post_title = $r->post_title;

			if($data['display_icons'])
			{
				$category_icon = get_post_meta($post_id, $this->meta_prefix.'category_icon', true);

				$arr_data[$post_id] = "<span>"
					.$obj_font_icons->get_symbol_tag(array(
						'symbol' => $category_icon,
						'class' => "category_".$post_id,
					))
					.$post_title
				."</span>";
			}

			else
			{
				$arr_data[$post_id] = $post_title;
			}
		}

		return $arr_data;
	}

	function has_categories($data = [])
	{
		if(!isset($data['include_on'])){		$data['include_on'] = 'products';}

		$arr_categories = $this->get_categories_for_select(array('include_on' => $data['include_on'], 'add_choose_here' => false, 'limit' => 2));

		return count($arr_categories);
	}

	function get_image_alt_for_select($data = [])
	{
		return array(
			'yes' => __("Yes", 'lang_webshop'),
			'single' => __("Yes", 'lang_webshop')." (".__("but not on Search", 'lang_webshop').")",
			'no' => __("No", 'lang_webshop'),
		);
	}

	function get_doc_types_for_select($data = [])
	{
		if(!isset($data['add_choose_here'])){		$data['add_choose_here'] = true;}

		$arr_data = [];

		if($data['add_choose_here'])
		{
			$arr_data[''] = "-- ".__("Choose Here", 'lang_webshop')." --";
		}

		$arr_data['searchable'] = __("Searchable", 'lang_webshop');
		$arr_data['public'] = __("Results", 'lang_webshop');
		$arr_data['public_single'] = __("Contact Info", 'lang_webshop');
		$arr_data['quick'] = __("Quick Info", 'lang_webshop');
		$arr_data['property'] = __("Properties", 'lang_webshop');

		/*if(is_plugin_active("mf_calendar/index.php"))
		{
			$arr_data['events'] = __("Events", 'lang_webshop');
		}*/

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
					do_log(sprintf("An option for %s exists in the DB", $r->option_name));
				}
			}

			$option = trim(implode(",", $arr_options), ",");
		}

		return $option;
	}

	function cron_base()
	{
		global $wpdb;

		$obj_cron = new mf_cron();
		$obj_cron->start(__CLASS__);

		if($obj_cron->is_running == false)
		{
			// Remove old non-fulfilled orders
			###########
			$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = %s AND post_date < DATE_SUB(NOW(), INTERVAL 1 YEAR)", $this->post_type_orders, 'draft'));

			foreach($result as $r)
			{
				do_log(__FUNCTION__.": Remove the order #".$r->ID." because it is old and not fulfilled");
				//wp_trash_post($r->ID);
			}
			###########

			replace_post_type(array('old' => 'mf_categories', 'new' => 'mf_category'));
			replace_post_type(array('old' => 'mf_products', 'new' => 'mf_product'));
			replace_post_type(array('old' => 'mf_custom_categories', 'new' => 'mf_cust_cat'));
			replace_post_type(array('old' => 'mf_document_type', 'new' => 'mf_doc_type'));
			replace_post_type(array('old' => 'mf_customers', 'new' => 'mf_customer'));
			replace_post_type(array('old' => 'mf_delivery_type', 'new' => 'mf_delivery'));

			mf_uninstall_plugin(array(
				'options' => array('setting_webshop_option_types', 'setting_webshop_display_images', 'setting_webshop_max_file_uploads', 'setting_webshop_user_updated_notification', 'setting_webshop_user_updated_notification_subject', 'setting_webshop_user_updated_notification_content', 'setting_webshop_title_fields_amount', 'setting_webshop_replace_product_title', 'setting_webshop_replace_product_description', 'setting_webshop_replace_title_information', 'setting_webshop_replace_title_settings', 'setting_webshop_replace_title_contact_info', 'setting_webshop_replace_title_quick_info', 'setting_webshop_replace_title_properties', 'setting_local_storage', 'settings_color_button_hover', 'settings_text_color_button_hover', 'settings_color_button_2_hover', 'setting_webshop_mobile_breakpoint', 'setting_webshop_require_payment', 'setting_webshop_allow_individual_contant', 'setting_product_default_image', 'setting_ghost_title', 'setting_ghost_image', 'setting_ghost_text', 'setting_webshop_color_button', 'setting_webshop_text_color_button', 'setting_webshop_color_button_2', 'setting_color_button_negative', 'setting_show_categories', 'setting_webshop_activate_frontend_admin', 'setting_webshop_payment_form', 'setting_webshop_product_template', 'setting_quote_form_single', 'setting_quote_form', 'setting_webshop_replace_categories_slug', 'setting_webshop_replace_products_slug', 'setting_webshop_replace_webshop', 'setting_webshop_replace_doc_types', 'setting_webshop_replace_categories', 'setting_webshop_replace_enter_title_here', 'setting_webshop_replace_products', 'setting_webshop_replace_product', 'setting_webshop_display_breadcrumbs', 'setting_replace_search_result_info', 'setting_webshop_replace_filter_products', 'setting_replace_return_to_search', 'setting_webshop_replace_too_many', 'setting_replace_quote_request', 'setting_webshop_replace_none_checked', 'setting_require_search', 'setting_replace_send_request_for_quote', 'setting_webshop_force_individual_contact', 'setting_replace_search_for_another', 'setting_replace_remove_from_search', 'setting_replace_add_to_search'),
				'tables' => array('webshop_order', 'webshop_product2user', 'webshop_sent'),
			));
		}

		$obj_cron->end();
	}

	function block_resources()
	{
		$this->combined_head();

		$plugin_base_include_url = plugins_url()."/mf_base/include/";
		$plugin_include_url = plugin_dir_url(__FILE__);

		$arr_breakpoints = apply_filters('get_layout_breakpoints', ['tablet' => 1200, 'mobile' => 930, 'suffix' => "px"]);

		mf_enqueue_script('underscore');
		mf_enqueue_script('backbone');
		mf_enqueue_script('script_storage', $plugin_base_include_url."jquery.Storage.js");
		mf_enqueue_script('script_base_plugins', $plugin_base_include_url."backbone/bb.plugins.js");
		mf_enqueue_script('script_webshop_router', $plugin_include_url."backbone/bb.router.js");
		mf_enqueue_script('script_webshop_models', $plugin_include_url."backbone/bb.models.js", array('ajax_url' => admin_url('admin-ajax.php')));
		mf_enqueue_script('script_webshop_views', $plugin_include_url."backbone/bb.views.js", array(
			'site_url' => get_site_url(),
			'symbol_inactive' => get_option_or_default('setting_webshop_symbol_inactive_image', $this->get_map_marker_url('setting_webshop_symbol_inactive')),
			'symbol_active' => get_option_or_default('setting_webshop_symbol_active_image', $this->get_map_marker_url('setting_webshop_symbol_active')),
			'ghost_inactive' => get_option('setting_ghost_inactive_image'),
			'ghost_active' => get_option('setting_ghost_active_image'),
			'search_max' => get_option_or_default('setting_search_max', 50),
			'show_all_min' => get_option_or_default('setting_show_all_min', 30),
			'mobile_breakpoint' => $arr_breakpoints['mobile'],
		));
		mf_enqueue_script('script_base_init', $plugin_base_include_url."backbone/bb.init.js");
	}

	/*function block_render_list_callback($attributes)
	{
		//$attributes['webshop_action'] = sanitize_text_field($new_instance['webshop_action']);
		//$attributes['webshop_locations'] = is_array($new_instance['webshop_locations']) ? $new_instance['webshop_locations'] : [];

		$this->block_resources();

		$out = "";

		//if(is_array($attributes['webshop_locations']))
		//{
			$out .= "<div".parse_block_attributes(array('class' => "widget webshop_widget webshop_list", 'attributes' => $attributes)).">";

				//$arr_data = [];
				//get_post_children(array('post_type' => $this->post_type_location), $arr_data);

				//$out .= "<div>"; // class='section'

					$out .= "<ul class='grid_columns'>"; //text_columns columns_3

						foreach($arr_data as $key => $value)
						{
							if(in_array($key, $attributes['webshop_locations']))
							{
								$post_name = $this->get_post_name_for_type('location');

								$out .= "<li><a href='".get_permalink($attributes['webshop_action'])."?".$post_name."=".$key."#".$post_name."=".$key."'>".trim($value, "&nbsp;")."</a></li>";
							}
						}

					$out .= "</ul>";

				//$out .= "</div>";

			$out .= "</div>";
		//}

		return $out;
	}*/

	function block_render_search_callback($attributes)
	{
		$this->block_resources();

		$out = "<div".parse_block_attributes(array('class' => "widget webshop_widget square webshop_search", 'attributes' => $attributes)).">
			<form action='' method='post' id='product_form' class='mf_form product_search'>";

				//$out .= $this->get_search_result_info(array('type' => 'filter'));
				$out .= $this->get_webshop_search();
				//$out .= $this->get_search_result_info(array('type' => 'matches'));

				/*if(get_option('setting_webshop_map_placement', 'above_filter') == 'below_filter')
				{
					$out .= $this->get_webshop_map(array('container_class' => 'display_on_mobile'));
				}*/

				$out .= "<ul class='product_list webshop_item_list grid_columns'><li class='loading'>".apply_filters('get_loading_animation', '', ['class' => "fa-3x"])."</li></ul>"
				//.$this->get_quote_button()
			."</form>"
			.$this->get_templates(array('type' => 'products'))
		."</div>";

		return $out;
	}

	function block_render_cart_callback($attributes)
	{
		global $wpdb;

		$plugin_include_url = plugin_dir_url(__FILE__);

		$arr_webshop_input_type = array('address', 'city', 'country', 'email', 'first_name', 'last_name', 'telno', 'zip');

		mf_enqueue_script('underscore');
		mf_enqueue_style('style_webshop_cart', $plugin_include_url."style_cart.css");
		mf_enqueue_script('script_webshop_cart', $plugin_include_url."script_cart.js", array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'arr_webshop_input_type' => $arr_webshop_input_type,
		));

		$setting_webshop_tax_display = get_option('setting_webshop_tax_display');

		$arr_header[] = __("Product", 'lang_webshop');
		$arr_header[] = __("Price", 'lang_webshop');

		if($setting_webshop_tax_display == 'yes')
		{
			$arr_header[] = __("Tax", 'lang_webshop');
		}

		$arr_header[] = __("Amount", 'lang_webshop');
		$arr_header[] = __("Subtotal", 'lang_webshop');
		$arr_header[] = "";

		$this->order_id = $this->get_cookie();

		$obj_encryption = new mf_encryption(__CLASS__);
		$this->order_details = [];

		foreach($this->arr_meta_keys as $meta_key)
		{
			$this->order_details[$meta_key] = check_var($meta_key);
		}

		if(isset($_POST['btnWebshopPay']))
		{
			// Send payment
			// Collect answer
			// Set order to published
		}

		else
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = %s AND meta_value = %s WHERE post_type = %s AND post_status = %s ORDER BY post_modified DESC LIMIT 0, 1", $this->meta_prefix.'cart_hash', $this->order_id, $this->post_type_orders, 'draft'));

			foreach($result as $r)
			{
				foreach($this->arr_meta_keys as $meta_key)
				{
					$this->order_details[$meta_key] = get_post_meta($r->ID, $this->meta_prefix.$meta_key, true);

					if($this->order_details[$meta_key] != '')
					{
						$this->order_details[$meta_key] = $obj_encryption->decrypt($this->order_details[$meta_key], md5($this->order_id));
					}
				}
			}
		}

		$out = "<div".parse_block_attributes(array('class' => "widget webshop_cart", 'attributes' => $attributes)).">
			<table class='cart_products widefat striped mf_form'>"
				.show_table_header($arr_header)
				."<tbody>
					<tr>
						<td colspan='".count($arr_header)."' class='loading'>".apply_filters('get_loading_animation', '', ['class' => "fa-3x"])."</td>
					</tr>
				</tbody>
			</table>
			<br>
			<div class='flex_flow hide'>
				<div>
					<h3>".__("Summary", 'lang_webshop')."</h3>
					<table class='cart_totals widefat striped'>
						<tbody>
							<tr>
								<td>".__("Shipping Cost", 'lang_webshop')."</td>
								<td class='shipping_cost'></td>
							</tr>
							<tr>
								<td>".__("Total", 'lang_webshop')."</td>
								<td class='total_sum'></td>
							</tr>
							<tr>
								<td>".__("Tax", 'lang_webshop')."</td>
								<td class='total_tax'></td>
							</tr>
						</tbody>
					</table>
					<br>
					<div class='is-layout-flex wp-block-buttons-is-layout-flex'>";

						$search_post_id = apply_filters('get_block_search', 0, 'mf/webshopsearch');

						if($search_post_id > 0)
						{
							$out .= "<div class='wp-block-button'>
								<a href='".get_the_permalink($search_post_id)."' class='wp-block-button__link'>".__("Continue Shopping", 'lang_webshop')."</a>
							</div>";
						}

					$out .= "</div>
				</div>
				<div class='proceed_to_checkout'>
					<form action='#' method='post' class='order_details mf_form'>
						<h3>".__("Complete Your Purchase", 'lang_webshop')."</h3>
						<div class='flex_flow'>"
							.show_textfield(array('name' => 'first_name', 'text' => __("First Name", 'lang_webshop'), 'value' => $this->order_details['first_name'], 'xtra' => " data-fetch_info='first_name'", 'required' => true))
							.show_textfield(array('name' => 'last_name', 'text' => __("Last Name", 'lang_webshop'), 'value' => $this->order_details['last_name'], 'xtra' => " data-fetch_info='last_name'", 'required' => true))
						."</div>"
						//."<strong>".__("Contact", 'lang_webshop')."</strong>"
						."<div class='flex_flow'>"
							.show_textfield(array('name' => 'contact_email', 'text' => __("E-mail", 'lang_webshop'), 'value' => $this->order_details['contact_email'], 'xtra' => " data-fetch_info='email'", 'required' => true))
							.show_textfield(array('name' => 'contact_phone', 'text' => __("Phone Number", 'lang_webshop'), 'value' => $this->order_details['contact_phone'], 'xtra' => " data-fetch_info='telno'"))
						."</div>"
						//."<strong>".__("Address", 'lang_webshop')."</strong>"
						.show_textfield(array('name' => 'address_street', 'text' => __("Address", 'lang_webshop'), 'value' => $this->order_details['address_street'], 'xtra' => " data-fetch_info='address'", 'required' => true))
						.show_textfield(array('name' => 'address_co', 'text' => __("C/O", 'lang_webshop'), 'value' => $this->order_details['address_co'], 'required' => true))
						."<div class='flex_flow'>"
							.show_textfield(array('type' => 'number', 'name' => 'address_zip', 'text' => __("Zip Code", 'lang_webshop'), 'value' => $this->order_details['address_zip'], 'xtra' => " data-fetch_info='zip'", 'required' => true))
							.show_textfield(array('name' => 'address_city', 'text' => __("City", 'lang_webshop'), 'value' => $this->order_details['address_city'], 'xtra' => " data-fetch_info='city'", 'required' => true))
						."</div>";

						//.show_select(array('data' => $this->get_countries_for_select(), 'name' => 'address_country', 'text' => __("Country", 'lang_webshop'), 'value' => $this->order_details['address_country'], 'xtra' => " data-fetch_info='country'"))

						$setting_webshop_stripe_secret_key = get_option('setting_webshop_stripe_secret_key');

						if($setting_webshop_stripe_secret_key != '')
						{
							$out .= "<p>".__("Card Details", 'lang_webshop')."</p>
							<div class='card_details'>"
								.show_textfield(array('name' => 'payment_card_no', 'placeholder' => __("Card Number", 'lang_webshop'), 'value' => "", 'maxlength' => 19, 'required' => true))
								."<div class='flex_flow'>"
									.show_textfield(array('name' => 'payment_card_expires', 'placeholder' => __("Expires (MM/YY)", 'lang_webshop'), 'value' => "", 'maxlength' => 5, 'required' => true))
									.show_textfield(array('type' => 'number', 'name' => 'payment_card_cvc', 'placeholder' => __("CVC", 'lang_webshop'), 'value' => "", 'maxlength' => 3, 'required' => true))
								."</div>
							</div>";
								
							$out .= "<div".get_form_button_classes().">"
								.show_button(array('name' => 'btnWebshopPay', 'text' => sprintf(__("Pay %s", 'lang_webshop'), "<span class='total_sum'></span>"), 'xtra' => "disabled"))
								.input_hidden(array('name' => 'action', 'value' => 'api_webshop_order_update'))
								//.input_hidden(array('name' => 'order_id', 'value' => $this->order_id, 'allow_empty' => true))
							."</div>";
						}

					$out .= "</form>";

					$setting_webshop_swish_merchant_number = get_option('setting_webshop_swish_merchant_number');

					if($setting_webshop_swish_merchant_number != '')
					{
						$out .= "<p>".__("Swish", 'lang_webshop')."</p>";

						if(isset($_POST['btnPaymentSwish']))
						{
							$base_callback_url = $_SERVER['HTTP_REFERER'];

							/*$paymentRequest = [
								"payeePaymentReference" => "unique_reference_123",
								"callbackUrl" => "https://yourdomain.com/swish_callback",
								"payeeAlias" => "1231181189",          // your Swish merchant number
								"payerAlias" => "46701234567",         // customer's phone number
								"amount" => "100",                     // amount in SEK as string
								"currency" => "SEK",
								"message" => "Order #1234"
							];

							$payload = json_encode($paymentRequest);

							// Paths to your client certificate and private key (PEM or P12 with passphrase)
							$certFile = "/path/to/Swish_Merchant_TestCertificate_1234679304.p12";
							$certPassword = "your_cert_password";
							$keyFile = "/path/to/Swish_Merchant_TestCertificate_1234679304.key";

							// Swish API endpoint for payment requests
							$url = "https://mss.cpc.getswish.net/swish-cpcapi/api/v1/paymentrequests";

							// Initialize cURL
							$ch = curl_init();

							curl_setopt($ch, CURLOPT_URL, $url);
							curl_setopt($ch, CURLOPT_PORT, 443);
							curl_setopt($ch, CURLOPT_SSLCERT, $certFile);
							curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $certPassword);
							curl_setopt($ch, CURLOPT_SSLKEY, $keyFile);
							curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $certPassword);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
							curl_setopt($ch, CURLOPT_POST, true);
							curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
							curl_setopt($ch, CURLOPT_HTTPHEADER, [
								'Content-Type: application/json',
							]);

							// Execute request
							$response = curl_exec($ch);
							$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
							if (curl_errno($ch)) {
								echo 'Curl error: ' . curl_error($ch);
							} else {
								echo 'HTTP status: ' . $httpCode . "n";
								echo 'Response: ' . $response . "n";
							}

							curl_close($ch);

							Callback:
							{
							  "id": "6e633f92-4a2a-47e1-9e10-47f93b9f1123",
							  "paymentRequestId": "6e633f92-4a2a-47e1-9e10-47f93b9f1123",
							  "status": "PAID",
							  "amount": "100",
							  "currency": "SEK",
							  "payeeAlias": "1231181189",
							  "payerAlias": "46701234567",
							  "message": "Order #1234",
							  "created": "2025-08-29T10:00:00.000Z",
							  "updated": "2025-08-29T10:01:00.000Z",
							  "payeePaymentReference": "unique_reference_123",
							  "errorCode": null
							}*/

							$setting_webshop_swish_certificate_root_file = get_option('setting_webshop_swish_certificate_root_file');
							$setting_webshop_swish_certificate_file = get_option('setting_webshop_swish_certificate_file');
							$setting_webshop_swish_certificate_password = get_option('setting_webshop_swish_certificate_password');
							$setting_webshop_swish_key_file = get_option('setting_webshop_swish_key_file');

							$post_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = %s AND meta_value = %s WHERE post_type = %s AND post_status = %s ORDER BY post_modified DESC LIMIT 0, 1", $this->meta_prefix.'cart_hash', $this->order_id, $this->post_type_orders, 'draft'));

							$total_sum = 0; // get total sum like in webshop_cart
							$setting_webshop_currency = get_option('setting_webshop_currency', 'SEK');

							//$action = "https://mss.cpc.getswish.net/swish-cpcapi/api/v1/paymentrequests/";
							$action = "https://cpc.getswish.net/swish-cpcapi/api/v1/paymentrequests";

							$post_data = array(
								'payeePaymentReference' => $this->order_id,
								'callbackUrl' => $base_callback_url."&callback",
								//'payerAlias' => $telno, // This will only return Location and thus we can't send the mobile user to swish://...
								'payeeAlias' => $setting_webshop_swish_merchant_number,
								'amount' => $total_sum,
								'currency' => $setting_webshop_currency,
								'message' => "Order #".$post_id,
							);

							$data = array(
								'url' => $action,
								'content_type' => "application/json",
								'catch_head' => true,
								'post_data' => json_encode($post_data),
								'ca_path' => $setting_webshop_swish_certificate_root_file,
								'ssl_cert_path' => $setting_webshop_swish_certificate_file,
								'ssl_key_path' => $setting_webshop_swish_key_file,
							);

							list($url_content, $headers) = get_url_content($data);

							//do_log("Swish: ".str_replace(array("\n", "\r"), "", var_export($data, true))." -> ".str_replace(array("\n", "\r"), "", var_export($headers, true))." -> ".str_replace(array("\n", "\r"), "", var_export($url_content, true)));

							switch($headers['http_code'])
							{
								case 200:
								case 201:
									//$json = json_decode($url_content, true);

									$json = array();

									foreach(explode("\n", $url_content) as $row)
									{
										@list($row_key, $row_value) = explode(":", $row, 2);

										$json[trim($row_key)] = trim($row_value);
									}

									//$out .= "Successful: ".var_export($json, true)." (".var_export($headers, true).")";

									if(isset($json['PaymentRequestToken']))
									{
										$token = $json['PaymentRequestToken'];
										$callback = $base_callback_url."&accept";

										$action = "swish://paymentrequest?token=".$token."&callbackurl=".urlencode($callback);

										$out .= "<form method='post' action='".$action."'>
											<div".get_form_button_classes().">"
												//."<p>".$action."</p>"
												.show_button(array('text' => __("Open the App", 'lang_webshop'))) //'name' => 'btnPaymentSwish', 
											."</div>
										</form>";
									}

									else
									{
										$error_text = __("I could not find a token in the answer", 'lang_webshop')." (".htmlspecialchars($url_content).")";

										$out .= get_notification();
									}
								break;

								default:
									$error_text = sprintf(__("I am sorry, but I did not get the correct answer from %s to proceed to payment. An admin has been notified about this.", 'lang_webshop'), "Swish")." (".var_export($headers, true)." -> ".$headers['http_code'].")";

									$out .= get_notification();

									do_log("Unsuccessful Swish payment: ".str_replace(array("\n", "\r"), "", var_export($data, true))." -> ".str_replace(array("\n", "\r"), "", var_export($headers, true))." -> ".str_replace(array("\n", "\r"), "", var_export($url_content, true)));
								break;
							}
						}

						else if(isset($_GET['accept']))
						{
							$strPaymentStatus = ''; //$total_sum, $this->order_id

							switch($strPaymentStatus)
							{
								case 'paid':
									$done_text = __("Thank you for ordering!", 'lang_webshop');
								break;

								case 'cancelled':
									$error_text = __("You seam to have cancelled the order...", 'lang_webshop');
								break;

								case 'failed':
									$error_text = __("Something went wrong with the payment. An admin has been notified about this.", 'lang_webshop');
								break;

								default:
									$notice_text = __("There is not yet a status on your payment. Wait a moment...", 'lang_webshop');
								break;
							}

							$out .= get_notification();
						}

						else if(isset($_GET['callback']))
						{
							$response_request = $_REQUEST;
							$response_body = file_get_contents('php://input');

							$json = json_decode($response_body, true);

							switch($json['status'])
							{
								case 'DECLINED':
									if($this->order_id == $json['payeePaymentReference'])
									{
										// The payment was cancelled. Save it in the order
										//$this->order_id
									}

									else
									{
										do_log("Swish Incorrect Reference: ".str_replace(array("\n", "\r"), "", var_export($response_request, true)).", ".str_replace(array("\n", "\r"), "", var_export($response_body, true)));
									}
								break;

								case 'ERROR':
									if($this->order_id == $json['payeePaymentReference'])
									{
										// The payment failed. Save it in the order
										//$this->order_id
									}

									else
									{
										do_log("Swish Incorrect Reference: ".str_replace(array("\n", "\r"), "", var_export($response_request, true)).", ".str_replace(array("\n", "\r"), "", var_export($response_body, true)));
									}
								break;

								case 'PAID':
									if($this->order_id == $json['payeePaymentReference'])
									{
										if((int)$json['amount'] == (int)$total_sum)
										{
											// The paid amount is correct. Save it in the order
											//$this->order_id
										}

										else
										{
											do_log("Swish Incorrect Amount: ".str_replace(array("\n", "\r"), "", var_export($response_request, true)).", ".str_replace(array("\n", "\r"), "", var_export($response_body, true)));
										}
									}

									else
									{
										do_log("Swish Incorrect Reference: ".str_replace(array("\n", "\r"), "", var_export($response_request, true)).", ".str_replace(array("\n", "\r"), "", var_export($response_body, true)));
									}
								break;

								default:
									do_log("Swish Status Unknown (".$json['status']."): ".str_replace(array("\n", "\r"), "", var_export($response_request, true)).", ".str_replace(array("\n", "\r"), "", var_export($response_body, true)));
								break;
							}
						}

						else
						{
							$out .= "<form method='post' action=''>"
								."<div".get_form_button_classes().">"
									.show_button(array('name' => 'btnPaymentSwish', 'text' => sprintf(__("Pay %s", 'lang_webshop'), "<span class='total_sum'></span>")))
								."</div>"
							."</form>";
						}
					}

				$out .= "</div>
			</div>"
			.$this->get_templates(array('type' => 'webshop_cart'))
		."</div>";

		return $out;
	}

	function block_render_buy_button_callback($attributes)
	{
		global $post;

		$plugin_include_url = plugin_dir_url(__FILE__);

		mf_enqueue_style('style_webshop_buy_button', $plugin_include_url."style_buy_button.css");
		mf_enqueue_script('script_webshop_buy_button', $plugin_include_url."script_buy_button.js", array(
			'ajax_url' => admin_url('admin-ajax.php'),
		));

		$out = "<div".parse_block_attributes(array('class' => "widget webshop_buy_button", 'attributes' => $attributes)).">";

			$cart_post_id = apply_filters('get_block_search', 0, 'mf/webshopcart');

			if($cart_post_id > 0)
			{
				$out .= "<div class='is-layout-flex wp-block-buttons-is-layout-flex'>
					<div class='wp-block-button cart_buttons'>
						<a href='".get_the_permalink($cart_post_id)."' class='wp-block-button__link in_cart hide'><span>".sprintf(__("%s in Cart", 'lang_webshop'), "<span></span>")."</span><i class='fa fa-check'></i></a>
						<a href='#' class='wp-block-button__link add_to_cart' rel='".$post->ID."'><span>".__("Add", 'lang_webshop')."</span><i class='fa fa-plus'></i></a>
					</div>
				</div>";
			}

		$out .= "</div>";

		return $out;
	}

	/*function get_filters_for_select()
	{
		return array(
			'order_by' => __("Order by", 'lang_webshop'),
		);
	}

	function get_order_by_for_select()
	{
		$arr_data = array(
			'' => "-- ".__("Choose Here", 'lang_webshop')." --",
			'latest' => __("Latest", 'lang_webshop'),
			'alphabetical' => __("A-Z", 'lang_webshop'),
			'distance' => __("Closest", 'lang_webshop'),
		);

		return $arr_data;
	}*/

	function block_render_products_callback($attributes)
	{
		if(!isset($attributes['webshop_filters'])){					$attributes['webshop_filters'] = [];}
		if(!isset($attributes['webshop_filters_order_by'])){		$attributes['webshop_filters_order_by'] = 'alphabetical';}
		if(!isset($attributes['webshop_filters_order_by_text'])){	$attributes['webshop_filters_order_by_text'] = '';}
		if(!isset($attributes['webshop_text'])){					$attributes['webshop_text'] = '';}
		if(!isset($attributes['webshop_amount'])){					$attributes['webshop_amount'] = 3;}
		if(!isset($attributes['webshop_link_product'])){			$attributes['webshop_link_product'] = 'yes';}
		if(!isset($attributes['webshop_category'])){				$attributes['webshop_category'] = [];}
		if(!isset($attributes['webshop_button_text'])){				$attributes['webshop_button_text'] = '';}

		$this->block_resources();

		$widget_id = "widget_webshop_products_".md5(var_export($attributes, true));

		$out = "";

		/*if($attributes['webshop_amount'] > 0)
		{
			if(!is_array($attributes['webshop_category']))
			{
				if($attributes['webshop_category'] > 0)
				{
					$attributes['webshop_category'] = array($attributes['webshop_category']);
				}
			}*/

			$out .= "<div".parse_block_attributes(array('class' => "widget webshop_widget webshop_filter_products", 'attributes' => $attributes)).">";

				/*if($attributes['webshop_heading'] != '')
				{
					$attributes['webshop_heading'] = apply_filters('widget_title', $attributes['webshop_heading'], $attributes, $this->id_base);

					$category_title = "";

					foreach($attributes['webshop_category'] as $webshop_category)
					{
						$category_title .= ($category_title != '' ? ", " : "").get_the_title($webshop_category);
					}

					$out .= $before_title
						.str_replace("[category]", $category_title, $attributes['webshop_heading'])
					.$after_title;
				}*/

				/*if(count($attributes['webshop_filters']) > 0)
				{
					$out .= "<form action='#' method='post' class='product_filters mf_form'>";

						if(in_array('order_by', $attributes['webshop_filters']))
						{
							$product_filter_order_by = check_var('product_filter_order_by', 'char', true, $attributes['webshop_filters_order_by']);
							$webshop_filters_order_by_text = $attributes['webshop_filters_order_by_text'] != '' ? $attributes['webshop_filters_order_by_text'] : __("Order by", 'lang_webshop');

							$out .= show_select(array('data' => $this->get_order_by_for_select(), 'name' => 'product_filter_order_by', 'text' => $webshop_filters_order_by_text, 'value' => $product_filter_order_by, 'field_class' => "mf_form_field product_filter_order_by"));
						}

					$out .= "</form>";
				}

				if($attributes['webshop_text'] != '')
				{
					$out .= "<div class='widget_text'>".apply_filters('the_content', str_replace("[amount]", "<span></span>", $attributes['webshop_text']))."</div>";
				}*/

				$out .= "<ul id='".$widget_id."' class='widget_list'";

					/*if($attributes['webshop_link_product'] != '')
					{
						$out .= " data-link_product='".$attributes['webshop_link_product']."'";
					}

					if(count($attributes['webshop_category']) > 0)
					{
						$out .= " data-category='".implode(",", $attributes['webshop_category'])."'";
					}*/

				$out .= " data-limit='0' data-amount='".$attributes['webshop_amount']."'>"
					.$this->get_spinner_template(array('tag' => 'li', 'size' => "fa-3x"))
				."</ul>"
			."</div>"
			.$this->get_templates(array('type' => 'filter_products')); //, 'button_text' => $attributes['webshop_button_text']
		//}

		return $out;
	}

	function enqueue_block_editor_assets()
	{
		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		wp_register_script('script_webshop_block_wp', $plugin_include_url."block/script_wp.js", array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-block-editor'), $plugin_version, true);

		/*$arr_data = [];
		get_post_children([], $arr_data);

		$arr_data_locations = [];
		get_post_children(array('post_type' => $this->post_type_location), $arr_data_locations);*/

		wp_localize_script('script_webshop_block_wp', 'script_webshop_block_wp', array(
			//'block_title' => __("Webshop", 'lang_webshop')." - ".__("Locations", 'lang_webshop'),
			//'block_description' => __("Display Locations", 'lang_webshop'),
			//'webshop_location_label' => __("Select", 'lang_webshop'),
			//'webshop_location' => $arr_data_locations,
			'block_title2' => __("Webshop", 'lang_webshop')." - ".__("Search", 'lang_webshop'),
			'block_description2' => __("Display Search", 'lang_webshop'),
			//'block_title3' => __("Webshop", 'lang_webshop')." - ".__("Filtered Products", 'lang_webshop'),
			//'block_description3' => __("Display Filtered Products", 'lang_webshop'),
			'block_title4' => __("Webshop", 'lang_webshop')." - ".__("Cart", 'lang_webshop'),
			'block_description4' => __("Display Cart", 'lang_webshop'),
			'block_title5' => __("Webshop", 'lang_webshop')." - ".__("Buy Button", 'lang_webshop'),
			'block_description5' => __("Display Buy Button", 'lang_webshop'),
		));
	}

	function init()
	{
		load_plugin_textdomain('lang_webshop', false, str_replace("/include", "", dirname(plugin_basename(__FILE__)))."/lang/");

		// Post types
		#######################
		register_post_type($this->post_type_categories, $args = array(
			'labels' => array(
				'name' => __("Categories", 'lang_webshop'),
				'singular_name' => __("Category", 'lang_webshop'),
				'menu_name' => __("Categories", 'lang_webshop'),
				'all_items' => __('List', 'lang_webshop'),
				'edit_item' => __('Edit', 'lang_webshop'),
				'view_item' => __('View', 'lang_webshop'),
				'add_new_item' => __('Add New', 'lang_webshop'),
			),
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => false,
			'show_in_nav_menus' => false,
			'supports' => array('title', 'editor', 'excerpt', 'page-attributes'),
			'hierarchical' => true,
			'has_archive' => false,
		));

		$arr_supports = array('title', 'thumbnail', 'excerpt', 'revisions', 'author');

		if($this->get_post_name_for_type('content') == '')
		{
			$arr_supports[] = 'editor';
		}

		register_post_type($this->post_type_products, array(
			'labels' => array(
				'name' => __("Products", 'lang_webshop'),
				'singular_name' => __("Product", 'lang_webshop'),
				'menu_name' => __("Products", 'lang_webshop'),
				'all_items' => __('List', 'lang_webshop'),
				'edit_item' => __('Edit', 'lang_webshop'),
				'view_item' => __('View', 'lang_webshop'),
				'add_new_item' => __('Add New', 'lang_webshop'),
			),
			'public' => true,
			'show_in_menu' => false,
			'show_in_nav_menus' => false,
			'show_in_rest' => true,
			'supports' => $arr_supports,
			'hierarchical' => true,
			'has_archive' => false,
		));

		register_post_type($this->post_type_custom_categories, array(
			'labels' => array(
				'name' => __("Custom Categories", 'lang_webshop'),
				'singular_name' => __("Custom Category", 'lang_webshop'),
				'menu_name' => __("Custom Categories", 'lang_webshop'),
				'all_items' => __('List', 'lang_webshop'),
				'edit_item' => __('Edit', 'lang_webshop'),
				'view_item' => __('View', 'lang_webshop'),
				'add_new_item' => __('Add New', 'lang_webshop'),
			),
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => false,
			'show_in_nav_menus' => false,
			'supports' => array('title', 'page-attributes'),
			'hierarchical' => true,
			'has_archive' => false,
		));

		register_post_type($this->post_type_document_type, array(
			'labels' => array(
				'name' => __("Filters", 'lang_webshop'),
				'singular_name' => __("Filter", 'lang_webshop'),
				'menu_name' => __("Filters", 'lang_webshop'),
				'all_items' => __('List', 'lang_webshop'),
				'edit_item' => __('Edit', 'lang_webshop'),
				'view_item' => __('View', 'lang_webshop'),
				'add_new_item' => __('Add New', 'lang_webshop'),
			),
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => false,
			'show_in_nav_menus' => false,
			'supports' => array('title', 'page-attributes'),
			'hierarchical' => true,
			'has_archive' => false,
		));

		register_post_type($this->post_type_location, array(
			'labels' => array(
				'name' => __("Locations", 'lang_webshop'),
				'singular_name' => __("Location", 'lang_webshop'),
				'menu_name' => __("Locations", 'lang_webshop'),
				'all_items' => __('List', 'lang_webshop'),
				'edit_item' => __('Edit', 'lang_webshop'),
				'view_item' => __('View', 'lang_webshop'),
				'add_new_item' => __('Add New', 'lang_webshop'),
			),
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => false,
			'show_in_nav_menus' => false,
			'supports' => array('title', 'page-attributes'),
			'hierarchical' => true,
			'has_archive' => false,
		));

		register_post_type($this->post_type_customers, array(
			'labels' => array(
				'name' => __("Customers", 'lang_webshop'),
				'singular_name' => __("Customer", 'lang_webshop'),
				'menu_name' => __("Customers", 'lang_webshop'),
				'all_items' => __('List', 'lang_webshop'),
				'edit_item' => __('Edit', 'lang_webshop'),
				'view_item' => __('View', 'lang_webshop'),
				'add_new_item' => __('Add New', 'lang_webshop'),
			),
			'public' => false,
			'show_in_menu' => false,
			'supports' => array('title'),
			'hierarchical' => true,
			'has_archive' => false,
		));

		register_post_type($this->post_type_delivery_type, array(
			'labels' => array(
				'name' => __("Delivery Types", 'lang_webshop'),
				'singular_name' => __("Delivery Type", 'lang_webshop'),
				'menu_name' => __("Delivery Types", 'lang_webshop'),
				'all_items' => __('List', 'lang_webshop'),
				'edit_item' => __('Edit', 'lang_webshop'),
				'view_item' => __('View', 'lang_webshop'),
				'add_new_item' => __('Add New', 'lang_webshop'),
			),
			'public' => false,
			'show_in_menu' => false,
			'supports' => array('title'),
			'hierarchical' => true,
			'has_archive' => false,
		));

		register_post_type($this->post_type_orders, array(
			'labels' => array(
				'name' => __("Orders", 'lang_webshop'),
				'singular_name' => __("Order", 'lang_webshop'),
				'menu_name' => __("Orders", 'lang_webshop'),
				'all_items' => __('List', 'lang_webshop'),
				'edit_item' => __('Edit', 'lang_webshop'),
				'view_item' => __('View', 'lang_webshop'),
				'add_new_item' => __('Add New', 'lang_webshop'),
			),
			'public' => true,
			'show_in_menu' => false,
			'show_in_nav_menus' => false,
			'show_in_rest' => true,
			//'supports' => $arr_supports,
			'hierarchical' => false,
			'has_archive' => false,
		));

		flush_rewrite_rules();
		#######################

		/*register_block_type('mf/webshoplist', array(
			'editor_script' => 'script_webshop_block_wp',
			'editor_style' => 'style_base_block_wp',
			'render_callback' => array($this, 'block_render_list_callback'),
		));*/

		register_block_type('mf/webshopsearch', array(
			'editor_script' => 'script_webshop_block_wp',
			'editor_style' => 'style_base_block_wp',
			'render_callback' => array($this, 'block_render_search_callback'),
		));

		register_block_type('mf/webshopcart', array(
			'editor_script' => 'script_webshop_block_wp',
			'editor_style' => 'style_base_block_wp',
			'render_callback' => array($this, 'block_render_cart_callback'),
		));

		register_block_type('mf/webshopbuybutton', array(
			'editor_script' => 'script_webshop_block_wp',
			'editor_style' => 'style_base_block_wp',
			'render_callback' => array($this, 'block_render_buy_button_callback'),
		));

		/*register_block_type('mf/webshopproducts', array(
			'editor_script' => 'script_webshop_block_wp',
			'editor_style' => 'style_base_block_wp',
			'render_callback' => array($this, 'block_render_products_callback'),
		));*/
	}

	function settings_webshop()
	{
		$options_area_orig = __FUNCTION__;

		// Webshop
		############################
		$options_area = $options_area_orig."_parent";

		add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

		$arr_settings = array(
			'setting_webshop_currency' => __("Currency", 'lang_webshop'),
			'setting_webshop_tax_rate' => __("VAT", 'lang_webshop'),
			'setting_webshop_tax_enter' => __("Enter Price", 'lang_webshop'),
			'setting_webshop_tax_display' => __("Display Price", 'lang_webshop'),
			'setting_webshop_shipping_cost' => __("Shipping Cost", 'lang_webshop'),
			'setting_webshop_shipping_free_limit' => __("Limit for Free Shipping", 'lang_webshop'),
			'setting_webshop_stripe_secret_key' => __("Stripe", 'lang_webshop')." (".__("Secret Key", 'lang_webshop').")",
			'setting_webshop_swish_merchant_number' => __("Swish", 'lang_webshop')." (".__("Merchant Number", 'lang_webshop').")",
			//'setting_webshop_local_storage' => __("Local Storage", 'lang_webshop'),
		);

		if(get_option('setting_webshop_swish_merchant_number') != '')
		{
			$arr_settings['setting_webshop_swish_certificate_root_file'] = " - ".__("Certificate Root File", 'lang_webshop');
			$arr_settings['setting_webshop_swish_certificate_file'] = " - ".__("Certificate File", 'lang_webshop');
			$arr_settings['setting_webshop_swish_certificate_password'] = " - ".__("Certificate Password", 'lang_webshop');
			$arr_settings['setting_webshop_swish_key_file'] = " - ".__("Key File", 'lang_webshop');
		}

		show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
		############################

		//Search
		############################
		$options_area = $options_area_orig."_parent_search";

		add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

		$arr_settings = array(
			'setting_webshop_display_sort' => __("Display Sort", 'lang_webshop'),
			'setting_webshop_sort_default' => __("Sort Default", 'lang_webshop'),
			'setting_webshop_display_filter' => __("Display Filter", 'lang_webshop'),
		);

		$arr_settings['setting_show_all_min'] = __("Min results to show number", 'lang_webshop');

		if($this->get_post_name_for_type('interval') != '')
		{
			$arr_settings['setting_range_min_default'] = __("Default range minimum", 'lang_webshop');
			$arr_settings['setting_range_choices'] = __("Custom range choices", 'lang_webshop');
		}

		show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
		############################

		//Map
		############################
		/*if(is_plugin_active("mf_maps/index.php"))
		{
			$options_area = $options_area_orig."_parent_map";

			add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

			$arr_settings = array(
				'setting_map_visibility' => __("Map visibility", 'lang_webshop'),
				'setting_map_visibility_mobile' => __("Map visibility", 'lang_webshop')." (".__("Mobile", 'lang_webshop').")",
				'setting_webshop_map_placement' => __("Map Placement", 'lang_webshop'),
				'setting_webshop_map_button_placement' => __("Map Button Placement", 'lang_webshop'),
				'setting_webshop_color_info' => __("Info color", 'lang_webshop'),
				'setting_webshop_text_color_info' => __("Info text color", 'lang_webshop'),
			);

			if(!is_plugin_active("mf_maps/index.php"))
			{
				$arr_settings['setting_gmaps_api'] = __("API key", 'lang_webshop');
			}

			$arr_settings['setting_webshop_replace_show_map'] = __("Replace Text", 'lang_webshop');
			$arr_settings['setting_webshop_replace_hide_map'] = __("Replace Text", 'lang_webshop');

			show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
		}*/
		############################

		//Search
		############################
		/*$options_area = $options_area_orig."_search";

		add_settings_section($options_area.'', "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

		$arr_settings = [];

		if(is_plugin_active("mf_form/index.php"))
		{
			$arr_settings['setting_quote_form'] = __("Form for quote request", 'lang_webshop');

			if(get_option('setting_quote_form') > 0)
			{
				$arr_settings['setting_search_max'] = __("Max results to send quote", 'lang_webshop');
				$arr_settings['setting_webshop_switch_icon_on'] = __("Switch Icon", 'lang_webshop')." (".__("On", 'lang_webshop').")";
				$arr_settings['setting_webshop_switch_icon_off'] = __("Switch Icon", 'lang_webshop')." (".__("Off", 'lang_webshop').")";
			}
		}

		show_settings_fields(array('area' => $options_area.'', 'object' => $this, 'settings' => $arr_settings));*/
		############################

		/* Favorites */
		############################
		/*if(get_option('setting_quote_form') > 0)
		{
			$options_area = $options_area_orig."_favorites";

			add_settings_section($options_area.'', "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

			$arr_settings = array(
				'setting_webshop_replace_favorites_info' => __("Replace Text", 'lang_webshop'),
				'setting_webshop_replace_email_favorites' => __("Replace Text", 'lang_webshop'),
				'setting_webshop_share_email_subject' => __("Email Subject", 'lang_webshop'),
				'setting_webshop_share_email_content' => __("Email Content", 'lang_webshop'),
			);

			show_settings_fields(array('area' => $options_area.'', 'object' => $this, 'settings' => $arr_settings));
		}*/
		############################

		/* Product */
		############################
		$options_area = $options_area_orig."_product";

		add_settings_section($options_area.'', "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

		$arr_settings = [];

		if(is_plugin_active("mf_form/index.php"))
		{
			if($this->has_categories(array('include_on' => 'products')) > 0)
			{
				$arr_settings['setting_webshop_allow_multiple_categories'] = __("Allow Multiple Categories", 'lang_webshop');
			}
		}

		show_settings_fields(array('area' => $options_area.'', 'object' => $this, 'settings' => $arr_settings));
		############################

		//Map
		############################
		/*if(is_plugin_active("mf_maps/index.php")) //$this->get_post_name_for_type('gps') != ''
		{
			$options_area = $options_area_orig."_map";

			add_settings_section($options_area.'', "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

			$arr_settings = [];

			$arr_settings['setting_webshop_symbol_inactive_image'] = __("Symbol inactive image", 'lang_webshop');
			$arr_settings['setting_webshop_symbol_active_image'] = __("Symbol active image", 'lang_webshop');

			$ghost_post_name = $this->get_post_name_for_type('ghost');

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

			$arr_settings['setting_map_info'] = __("Map Information", 'lang_webshop');

			show_settings_fields(array('area' => $options_area.'', 'object' => $this, 'settings' => $arr_settings));
		}*/
		############################
	}

	function pre_update_option($new_value, $old_value)
	{
		$out = "";

		if($new_value != '')
		{
			$obj_encryption = new mf_encryption(__CLASS__);
			$out = $obj_encryption->encrypt($new_value, md5(AUTH_KEY));
		}

		return $out;
	}

	function settings_webshop_parent_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo settings_header($setting_key, __("Webshop", 'lang_webshop'));
	}

		function get_currency_for_select()
		{
			return array(
				'' => "-- ".__("Choose Here", 'lang_webshop')." --",
				//'DKK' => __("Danish Krone", 'lang_form')." (DKK)",
				'EUR' => __("Euro", 'lang_form')." (EUR)",
				'USD' => __("US Dollar", 'lang_form')." (USD)",
				//'GBP' => __("English Pound", 'lang_form')." (GBP)",
				'SEK' => __("Swedish Krona", 'lang_form')." (SEK)",
				//'AUD' => __("Australian Dollar", 'lang_form')." (AUD)",
				//'CAD' => __("Canadian Dollar", 'lang_form')." (CAD)",
				//'ISK' => __("Icelandic Krona", 'lang_form')." (ISK)",
				//'JPY' => __("Japanese Yen", 'lang_form')." (JPY)",
				//'NZD' => __("New Zealand Dollar", 'lang_form')." (NZD)",
				//'NOK' => __("Norwegian Krone", 'lang_form')." (NOK)",
				//'CHF' => __("Swiss Franc", 'lang_form')." (CHF)",
				//'TRY' => __("Turkish Lira", 'lang_form')." (TRY)",
			);
		}

		function setting_webshop_currency_callback($args = [])
		{
			$setting_key = get_setting_key(__FUNCTION__, $args);
			$option = get_option($setting_key, 'SEK');

			echo show_select(array('data' => $this->get_currency_for_select(), 'name' => $setting_key, 'value' => $option));
		}

		function setting_webshop_tax_rate_callback($args = [])
		{
			$setting_key = get_setting_key(__FUNCTION__, $args);
			$option = get_option($setting_key, 25);

			echo show_textfield(array('name' => $setting_key, 'value' => $option, 'suffix' => "%"));
		}

		function setting_webshop_tax_enter_callback($args = [])
		{
			$setting_key = get_setting_key(__FUNCTION__, $args);
			$option = get_option($setting_key, 'yes');

			echo show_select(array('data' => array('yes' => __("Incl. Tax", 'lang_webshop'), 'no' => __("Excl. Tax", 'lang_webshop')), 'name' => $setting_key, 'value' => $option));
		}

		function setting_webshop_tax_display_callback($args = [])
		{
			$setting_key = get_setting_key(__FUNCTION__, $args);
			$option = get_option($setting_key, 'yes');

			echo show_select(array('data' => array('yes' => __("Excl. Tax", 'lang_webshop'), 'no' => __("Incl. Tax", 'lang_webshop')), 'name' => $setting_key, 'value' => $option));
		}

		function setting_webshop_shipping_cost_callback($args = [])
		{
			$setting_key = get_setting_key(__FUNCTION__, $args);
			$option = get_option($setting_key);

			echo show_textfield(array('type' => 'number', 'name' => $setting_key, 'value' => $option));
		}

		function setting_webshop_shipping_free_limit_callback($args = [])
		{
			$setting_key = get_setting_key(__FUNCTION__, $args);
			$option = get_option($setting_key);

			echo show_textfield(array('type' => 'number', 'name' => $setting_key, 'value' => $option));
		}

		function setting_webshop_stripe_secret_key_callback($args = [])
		{
			$setting_key = get_setting_key(__FUNCTION__, $args);
			$option = get_option($setting_key);

			$obj_encryption = new mf_encryption(__CLASS__);
			$option = $obj_encryption->decrypt($option, md5(AUTH_KEY));

			echo show_password_field(array('name' => $setting_key, 'value' => $option, 'xtra' => " autocomplete='new-password'"));
		}

		function setting_webshop_swish_merchant_number_callback($args = [])
		{
			$setting_key = get_setting_key(__FUNCTION__, $args);
			$option = get_option($setting_key);

			echo show_textfield(array('type' => 'number', 'name' => $setting_key, 'value' => $option));
		}

		function setting_webshop_swish_certificate_root_file_callback($args = [])
		{
			$setting_key = get_setting_key(__FUNCTION__, $args);
			$option = get_option($setting_key);

			$description = '';

			if($option == '')
			{
				$description = sprintf(__("The file should be a %s file.", 'lang_webshop'), ".pem")." <a href='#'>".__("Get yours here", 'lang_webshop')."</a>";
			}

			echo get_media_library(array('name' => $setting_key, 'value' => $option, 'description' => $description));
		}

		function setting_webshop_swish_certificate_file_callback($args = [])
		{
			$setting_key = get_setting_key(__FUNCTION__, $args);
			$option = get_option($setting_key);

			$description = '';

			if($option == '')
			{
				$description = sprintf(__("The file should be a %s file.", 'lang_webshop'), ".p12")." <a href='#'>".__("Get yours here", 'lang_webshop')."</a>"; // cert / pem / p12
			}

			echo get_media_library(array('name' => $setting_key, 'value' => $option, 'description' => $description));
		}

		function setting_webshop_swish_certificate_password_callback($args = [])
		{
			$setting_key = get_setting_key(__FUNCTION__, $args);
			$option = get_option($setting_key);

			echo show_password_field(array('name' => $setting_key, 'value' => $option, 'xtra' => " autocomplete='new-password'"));
		}

		function setting_webshop_swish_key_file_callback($args = [])
		{
			$setting_key = get_setting_key(__FUNCTION__, $args);
			$option = get_option($setting_key);

			$description = '';

			if($option == '')
			{
				$description = sprintf(__("The file should be a %s file.", 'lang_webshop'), ".key")." <a href='#'>".__("Get yours here", 'lang_webshop')."</a>";
			}

			echo get_media_library(array('name' => $setting_key, 'value' => $option, 'description' => $description));
		}

		/*function setting_webshop_local_storage_callback()
		{
			echo show_button(array('type' => 'button', 'name' => 'btnLocalStorageClear', 'text' => __("Clear", 'lang_webshop'), 'class' => 'button'))
			."<div id='storage_response'></div>";
		}*/

	function settings_webshop_parent_search_callback($args = [])
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo settings_header($setting_key, __("Webshop", 'lang_webshop')." - ".__("Search", 'lang_webshop'));
	}

		function setting_webshop_display_sort_callback($args = [])
		{
			$setting_key = get_setting_key(__FUNCTION__, $args);
			$option = get_option($setting_key);

			if($option == 'yes')
			{
				$option = array('latest', 'random', 'alphabetical', 'size');
			}

			echo show_select(array('data' => $this->get_sort_for_select(), 'name' => $setting_key."[]", 'value' => $option));
		}

		function setting_webshop_sort_default_callback($args = [])
		{
			$setting_key = get_setting_key(__FUNCTION__, $args);
			$option = get_option_or_default($setting_key, 'size');

			echo show_select(array('data' => $this->get_sort_for_select(get_option('setting_webshop_display_sort')), 'name' => $setting_key, 'value' => $option));
		}

		function setting_webshop_display_filter_callback($args = [])
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

	function settings_webshop_parent_map_callback($args = [])
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo settings_header($setting_key, __("Webshop", 'lang_webshop')." - ".__("Map", 'lang_webshop'));
	}

		function setting_map_visibility_callback($args = [])
		{
			$setting_key = get_setting_key(__FUNCTION__, $args);
			$option = get_option($setting_key);

			echo show_select(array('data' => $this->get_map_visibility_for_select(), 'name' => $setting_key, 'value' => $option));
		}

		function setting_map_visibility_mobile_callback($args = [])
		{
			$setting_key = get_setting_key(__FUNCTION__, $args);
			$option = get_option($setting_key);

			echo show_select(array('data' => $this->get_map_visibility_for_select(), 'name' => $setting_key, 'value' => $option));
		}

		function setting_webshop_map_placement_callback($args = [])
		{
			$setting_key = get_setting_key(__FUNCTION__, $args);
			$option = get_option($setting_key);

			echo show_select(array('data' => $this->get_map_placement_for_select(), 'name' => $setting_key, 'value' => $option));
		}

		function setting_webshop_map_button_placement_callback($args = [])
		{
			$setting_key = get_setting_key(__FUNCTION__, $args);
			$option = get_option($setting_key);

			echo show_select(array('data' => $this->get_map_button_placement_for_select(), 'name' => $setting_key, 'value' => $option));
		}

		function setting_webshop_color_info_callback($args = [])
		{
			$setting_key = get_setting_key(__FUNCTION__, $args);
			$option = get_option($setting_key, "#eeeeee");

			echo show_textfield(array('type' => 'color', 'name' => $setting_key, 'value' => $option));
		}

		function setting_webshop_text_color_info_callback($args = [])
		{
			$setting_key = get_setting_key(__FUNCTION__, $args);
			$option = get_option($setting_key, "#000000");

			echo show_textfield(array('type' => 'color', 'name' => $setting_key, 'value' => $option));
		}

	function settings_webshop_callback($args = [])
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);

		echo settings_header($setting_key, __("Webshop", 'lang_webshop'));
	}

	function settings_webshop_map_callback($args = [])
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);

		echo settings_header($setting_key, __("Webshop", 'lang_webshop')." - ".__("Map", 'lang_webshop'));
	}

	function settings_webshop_favorites_callback($args = [])
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);

		echo settings_header($setting_key, __("Webshop", 'lang_webshop')." - ".__("Favorites", 'lang_webshop'));
	}

	function settings_webshop_product_callback($args = [])
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);

		echo settings_header($setting_key, __("Webshop", 'lang_webshop')." - ".__("Product", 'lang_webshop'));
	}

	function settings_webshop_search_callback($args = [])
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);

		echo settings_header($setting_key, __("Webshop", 'lang_webshop')." - ".__("Search", 'lang_webshop'));
	}

	function setting_webshop_replace_favorites_info_callback($args = [])
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textarea(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Here are your %s saved products", 'lang_webshop'), 'description' => __("Disable by adding any single character", 'lang_webshop')));
	}

	function setting_webshop_switch_icon_on_callback($args = [])
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => "fa fa-check green"));
	}

	function setting_webshop_switch_icon_off_callback($args = [])
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => "fa fa-times red"));
	}

	function setting_webshop_allow_multiple_categories_callback($args = [])
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key, 'yes');

		echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
	}

	function setting_webshop_replace_email_favorites_callback($args = [])
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Email Your Products", 'lang_webshop')));
	}

	function setting_webshop_share_email_subject_callback($args = [])
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key, __("I would like to share these products that I like", 'lang_webshop'));

		echo show_textfield(array('name' => $setting_key, 'value' => $option));
	}

	function setting_webshop_share_email_content_callback($args = [])
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key, sprintf(__("Here are my favorites (%s)", 'lang_webshop'), "[url]"));

		echo show_wp_editor(array('name' => $setting_key, 'value' => $option,
			'class' => "hide_media_button hide_tabs",
			'mini_toolbar' => true,
			'editor_height' => 100,
		));
	}

	function setting_gmaps_api_callback($args = [])
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		$suffix = ($option == '' ? "<a href='//developers.google.com/maps/documentation/javascript/get-api-key'>".__("Get yours here", 'lang_webshop')."</a>" : "");

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'suffix' => $suffix));
	}

	function setting_webshop_replace_show_map_callback($args = [])
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Show Map", 'lang_webshop')));
	}

	function setting_webshop_replace_hide_map_callback($args = [])
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Hide Map", 'lang_webshop')));
	}

	function setting_map_info_callback($args = [])
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_wp_editor(array('name' => $setting_key, 'value' => $option,
			'class' => "hide_media_button hide_tabs",
			'mini_toolbar' => true,
			'editor_height' => 100,
		));
	}

	function setting_range_min_default_callback($args = [])
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key, 10);

		echo show_textfield(array('type' => 'number', 'name' => $setting_key, 'value' => $option, 'xtra' => "min='0' max='100'", 'suffix' => "%", 'description' => __("If no lower value is entered in a range, this percentage is used to calculate the lower end of the range", 'lang_webshop')));
	}

	function setting_range_choices_callback($args = [])
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => '1-50,50-100,1000+'));
	}

	function setting_search_max_callback($args = [])
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option_or_default($setting_key, 50);

		echo show_textfield(array('type' => 'number', 'name' => $setting_key, 'value' => $option, 'xtra' => "min='10' max='100'"));
	}

	function setting_show_all_min_callback($args = [])
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option_or_default($setting_key, 30);

		echo show_textfield(array('type' => 'number', 'name' => $setting_key, 'value' => $option, 'xtra' => "min='0' max='100'", 'suffix' => sprintf(__("%d will hide the link in the form", 'lang_webshop'), 0)));
	}

	function setting_webshop_symbol_active_callback($args = [])
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key, "#b8c389");

		echo show_textfield(array('type' => 'color', 'name' => $setting_key, 'value' => $option));
	}

	function setting_webshop_symbol_inactive_callback($args = [])
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key, "#c78e91");

		echo show_textfield(array('type' => 'color', 'name' => $setting_key, 'value' => $option));
	}

	function setting_webshop_symbol_inactive_image_callback($args = [])
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo get_media_library(array('type' => 'image', 'name' => $setting_key, 'value' => $option));
	}

	function setting_webshop_symbol_active_image_callback($args = [])
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo get_media_library(array('type' => 'image', 'name' => $setting_key, 'value' => $option));
	}

	function setting_ghost_inactive_image_callback($args = [])
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo get_media_library(array('type' => 'image', 'name' => $setting_key, 'value' => $option));
	}

	function setting_ghost_active_image_callback($args = [])
	{
		$setting_key = get_setting_key(__FUNCTION__, $args);
		$option = get_option($setting_key);

		echo get_media_library(array('type' => 'image', 'name' => $setting_key, 'value' => $option));
	}

	function combined_head()
	{
		$arr_breakpoints = apply_filters('get_layout_breakpoints', ['tablet' => 1200, 'mobile' => 930, 'suffix' => "px"]);

		$setting_gmaps_api = get_option('setting_gmaps_api');
		$symbol_active_image = get_option('setting_webshop_symbol_active_image');
		$symbol_active = get_option('setting_webshop_symbol_active');

		$plugin_base_include_url = plugins_url()."/mf_base/include/";
		$plugin_include_url = plugin_dir_url(__FILE__);

		mf_enqueue_style('style_base_grid_columns', $plugin_base_include_url."style_grid_columns.php");
		mf_enqueue_style('style_webshop_buy_button', $plugin_include_url."style_buy_button.css");
		mf_enqueue_style('style_webshop', $plugin_include_url."style.php");
		mf_enqueue_style('style_bb', $plugin_base_include_url."backbone/style.css");

		if($setting_gmaps_api != '')
		{
			$plugin_version = get_plugin_version(__FILE__);

			wp_enqueue_script('script_gmaps_api', "//maps.googleapis.com/maps/api/js?v=3.exp&libraries=places&key=".$setting_gmaps_api, [], $plugin_version);
		}

		if(is_plugin_active('mf_maps/index.php'))
		{
			global $obj_maps;

			$obj_maps->init_maps();
		}

		mf_enqueue_script('script_webshop', $plugin_include_url."script.js", array(
			'plugins_url' => plugins_url(),
			'symbol_active_image' => $symbol_active_image,
			'symbol_active' => trim($symbol_active, "#"),
			'mobile_breakpoint' => $arr_breakpoints['mobile'],
			'product_missing' => __("You have to choose at least one product to proceed", 'lang_webshop'),
		));
	}

	function admin_init()
	{
		global $pagenow;

		$this->combined_head();

		$plugin_base_include_url = plugins_url()."/mf_base/include/";
		$plugin_include_url = plugin_dir_url(__FILE__);

		switch($pagenow)
		{
			case 'options-general.php':
				if(check_var('page') == 'settings_mf_base')
				{
					mf_enqueue_script('script_storage', $plugin_base_include_url."jquery.Storage.js");
					mf_enqueue_script('script_webshop_wp', $plugin_include_url."script_wp.js", array(
						'loading_animation' => apply_filters('get_loading_animation', ''),
						'cleared_message' => sprintf(__("%s was successfully cleared on this device", 'lang_webshop'), "Local Storage"),
					));
				}
			break;

			case 'admin.php':
				if(check_var('page') == 'mf_webshop/stats/index.php')
				{
					mf_enqueue_script('jquery-flot', $plugin_base_include_url."jquery.flot.min.0.7.js");
				}
			break;
		}

		if(function_exists('wp_add_privacy_policy_content'))
		{
			$content = sprintf(__("When searching we store information in the so called %s in the visiting browser. This data is only used on the site to remember what was last saved and not sent forward to the server unless the visitor fullfills an inquiry. Then the information is sent along with the form to distribute the inquiry to the correct recipients.", 'lang_webshop'), 'localStorage');

			wp_add_privacy_policy_content(__("Webshop", 'lang_webshop'), $content);
		}
	}

	function get_map_marker_url($option_key)
	{
		//maps.google.com/mapfiles/ms/icons/green-dot.png
		//maps.google.com/mapfiles/ms/icons/red-dot.png
		//www.googlemapsmarkers.com/v1/009900/
		//www.googlemapsmarkers.com/v1/A/0099FF/
		//www.googlemapsmarkers.com/v1/A/0099FF/FFFFFF/FF0000/

		return ""; //"http://googlemapsmarkers.com/v1/".trim(get_option($option_key), "#")."/"
	}

	function get_option_type_from_post_id($post_id)
	{
		$this->option_type = str_replace($this->post_type_products, "", get_post_type($post_id));
	}

	function filter_is_file_used($arr_used)
	{
		global $wpdb;

		$result = $wpdb->get_results($wpdb->prepare("SELECT post_id FROM ".$wpdb->postmeta." WHERE (meta_key LIKE %s AND meta_value LIKE %s OR meta_key = %s AND meta_value = %s)", $this->meta_prefix.'product_image', "%".$arr_used['id']."%", $this->meta_prefix.'categories', $arr_used['id']));
		$rows = $wpdb->num_rows;

		if($rows > 0)
		{
			$arr_used['amount'] += $rows;

			foreach($result as $r)
			{
				if($arr_used['example'] != '')
				{
					break;
				}

				$arr_used['example'] = admin_url("post.php?action=edit&post=".$r->post_id);
			}
		}

		return $arr_used;
	}

	/*function uninit()
	{
		@session_destroy();
	}*/

	/*function default_content($post_content)
	{
		if($post_content == "[product_default]")
		{
			$post_content = $this->default_template;
		}

		return $post_content;
	}*/

	function is_a_webshop_meta_value($data)
	{
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

	/*function filter_form_after_fields($out)
	{
		global $wpdb;

		$out_left = $out_right = "";

		$products = check_var('products');
		$product_id_first = is_array($products) ? $products[0] : $products;
		$this->get_option_type_from_post_id($product_id_first);

		$setting_search_max = get_option_or_default('setting_search_max', 50);
		$setting_webshop_switch_icon_on = get_option('setting_webshop_switch_icon_on');
		$setting_webshop_switch_icon_off = get_option('setting_webshop_switch_icon_off');

		$address_post_name = $this->get_post_name_for_type('address');

		foreach($_REQUEST as $key => $value)
		{
			$key = check_var($key, 'char', false);
			$value = check_var($value, 'char', false);

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

					$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_excerpt, post_content FROM ".$wpdb->posts.$query_join." WHERE post_type = %s AND post_status = %s AND ID = '%d'", $this->post_type_products, 'publish', $product_id));

					if($wpdb->num_rows > 0)
					{
						foreach($result as $r)
						{
							$arr_product = [];

							$this->get_product_data(array('product' => $r, 'single_image' => true, 'show_location_in_data' => false), $arr_product);

							// If the user for some reason succeeds in requesting all the products but still having a filter on to prevent all products to show this is neccesary
							if(isset($arr_product['product_response'][0]))
							{
								$arr_product = $arr_product['product_response'][0];

								$out_left .= "<li>
									<div class='image'".(IS_ADMINISTRATOR ? " rel='".__FUNCTION__."'" : "").">"
										.$arr_product['product_image'];

										if($arr_product['product_data'] != '')
										{
											$out_left .= "<div class='product_data'>".$arr_product['product_data']."</div>";
										}

									$out_left .= "</div>
									<div class='product_heading'>"
										.$arr_product['product_title'];

										if($arr_product['product_location'] != '')
										{
											$out_left .= "<p class='product_location'>".$arr_product['product_location']."</p>";
										}

									$out_left .= "</div>
									<ul class='product_meta'>";

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

					else
					{
						do_log("No product found (".$wpdb->last_query.")");
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
	}*/

	/*function filter_form_on_submit($data)
	{
		global $wpdb, $error_text, $obj_form;

		if(!isset($obj_form))
		{
			$obj_form = new mf_form();
		}

		$answer_text = "";

		$products = check_var('products');
		$product_id_first = is_array($products) ? $products[0] : $products;
		$this->get_option_type_from_post_id($product_id_first);

		$arr_product_ids = [];

		foreach($_REQUEST as $key => $value)
		{
			$key = check_var($key, 'char', false);
			$value = check_var($value, 'char', false);

			if($this->is_a_webshop_meta_value(array('key' => $key, 'value' => $value)))
			{
				if($key == 'products')
				{
					if(is_array($value) && count($value) > 0)
					{
						$arr_product_ids = $value;
					}

					else if($value > 0)
					{
						$arr_product_ids[] = $value;
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
						$data['obj_form']->arr_email_content['doc_types'] = [];
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
			$result = $wpdb->get_results($wpdb->prepare("SELECT formEmail, formEmailNotifyPage, formEmailName FROM ".$wpdb->base_prefix."form WHERE formID = '%d' AND formDeleted = '0'", $data['obj_form']->id)); //, formEmailNotifyFrom

			foreach($result as $r)
			{
				$data['obj_form']->email_admin = $r->formEmail;
				//$data['obj_form']->email_notify_from = $r->formEmailNotifyFrom;
				$data['obj_form']->email_notify_page = $r->formEmailNotifyPage;
				$data['obj_form']->email_subject = ($r->formEmailName != "" ? $r->formEmailName : $data['obj_form']->form_name);
			}

			$i = 0;

			$arr_mail_content_temp['products'][$i] = array(
				'label' => __("Products", 'lang_webshop'),
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
						'label' => __("Product", 'lang_webshop'),
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

					if(isset($data['obj_form']->answer_data['email']) && $data['obj_form']->answer_data['email'] != '')
					{
						if(isset($data['obj_form']->answer_data['name']) && $data['obj_form']->answer_data['name'] != '')
						{
							$name_temp = $data['obj_form']->answer_data['name'];
						}

						else if($data['obj_form']->email_admin != '' && strpos($data['obj_form']->email_admin, "<"))
						{
							$arr_mail_from = explode("<", $data['obj_form']->email_admin);

							$name_temp = $arr_mail_from[0];
						}

						else
						{
							$name_temp = get_bloginfo('name');
						}

						$obj_form->mail_data['from'] = $data['obj_form']->answer_data['email'];
						$obj_form->mail_data['headers'] = "From: ".$name_temp." <".$data['obj_form']->answer_data['email'].">\r\n";
					}

					$obj_form->send_transactional_email();

					$arr_mail_content_temp['products'][$i]['id'] = $product_id;
					$arr_mail_content_temp['products'][$i]['value'] = $product_title;

					$i++;
				}

				if(!isset($obj_form->answer_id))
				{
					do_log("AnswerID not set: ".var_export($obj_form, true));
				}

				//$this->insert_sent(array('product_id' => $product_id, 'answer_id' => $obj_form->answer_id));
			}
		}

		if($answer_text != '')
		{
			$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_answer SET answerID = '%d', form2TypeID = '0', answerText = %s", $data['obj_form']->answer_id, $answer_text));
		}

		$data['obj_form']->arr_email_content = $arr_mail_content_temp;

		return $data;
	}*/

	/*function get_product_id_from_calendar($post_id)
	{
		global $wpdb;

		$product_id = 0;
		$option_type_out = "";

		$debug = [];

		if(is_plugin_active("mf_calendar/index.php"))
		{
			global $obj_calendar;

			if(!isset($obj_calendar))
			{
				$obj_calendar = new mf_calendar();
			}

			$post_type = get_post_type($post_id);

			switch($post_type)
			{
				case $obj_calendar->post_type_event:
					$post_parent = get_post_meta($post_id, $obj_calendar->meta_prefix.'calendar', true);

					if($post_parent > 0)
					{
						if(!($product_id > 0))
						{
							$debug[] = "Option Type: ";

							$event_post_name = $this->get_post_name_for_type('event');

							$debug[] = "Event Post Name: ".$event_post_name;

							if($event_post_name != '')
							{
								$product_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND post_status = %s AND meta_key = %s AND meta_value = '%d' GROUP BY ID", $this->post_type_products, 'publish', $this->meta_prefix.$event_post_name, $post_parent));

								if($product_id > 0)
								{
									$option_type_out = $this->option_type;

									$debug[] = "Product: ".$product_id;
								}

								else
								{
									$debug[] = "No Product: ".$wpdb->last_query;
								}
							}

							else
							{
								$debug[] = "No Events: ";
							}
						}

						else
						{
							$debug[] = "Already set: ".$product_id;
						}
					}

					else
					{
						$debug[] = "No Parent: ".$post_id;
					}
				break;

				default:
					$debug[] = "Incorrect Post Type: ".$post_type." == ".$obj_calendar->post_type_event;
				break;
			}
		}

		else
		{
			$debug[] = "Not active: MF Calendar";
		}

		$debug[] = "Returning: ".$product_id;

		//do_log("get_product_id_from_calendar: ".var_export($debug, true));

		return array($product_id, $option_type_out);
	}*/

	function filter_fields_array($post_id, &$fields_array, $type)
	{
		switch($fields_array['type'])
		{
			case 'categories':
				$arr_categories = $this->get_categories_for_select(array('include_on' => $type));

				switch($type)
				{
					/*case 'events':
						$fields_array['type'] = 'select';
						$fields_array['multiple'] = false;
					break;*/

					case 'products':
						if(get_option('setting_webshop_allow_multiple_categories', 'yes') == 'yes')
						{
							$fields_array['type'] = 'select';
							$fields_array['multiple'] = true;
							$fields_array['attributes']['size'] = get_select_size(array('count' => count($arr_categories)));
						}

						else
						{
							$fields_array['type'] = 'select';
							$fields_array['multiple'] = false;
						}
					break;
				}

				$fields_array['options'] = $arr_categories;
			break;

			case 'content':
				$post_document_max_length = get_post_meta($post_id, $this->meta_prefix.'document_max_length', true);

				if($post_document_max_length > 0)
				{
					$arr_attributes['maxlength'] = $post_document_max_length;
				}
			break;

			case 'location':
				$arr_locations = [];
				get_post_children(array('post_type' => $this->post_type_location, 'post_status' => ''), $arr_locations);

				$fields_array['options'] = $arr_locations;
				$fields_array['multiple'] = true;
				$fields_array['desc'] = get_post_meta($post_id, $this->meta_prefix.'document_description', true);
				$fields_array['attributes']['size'] = get_select_size(array('count' => count($arr_locations)));
			break;

			case 'select':
				$fields_array['desc'] = get_post_meta($post_id, $this->meta_prefix.'document_description', true);

				if(!isset($fields_array['options']) || !is_array($fields_array['options']))
				{
					do_log("Options are needed: ".var_export($fields_array, true));
				}
			break;

			case 'custom_categories':
			case 'education':
			case 'overlay':
			case 'page':
			case 'select3':
			case 'social':
				$fields_array['desc'] = get_post_meta($post_id, $this->meta_prefix.'document_description', true);
			break;

			case 'description':
			case 'text':
			case 'textarea':
			case 'phone':
			case 'clock':
			case 'checkbox':
			case 'color':
			case 'event':
			case 'file_advanced':
			case 'ghost':
			case 'categories':
			case 'categories_v2':
			case 'number':
			case 'price':
			case 'size':
			case 'stock':
				$fields_array['attributes']['placeholder'] = get_post_meta($post_id, $this->meta_prefix.'document_placeholder', true);
				$fields_array['desc'] = get_post_meta($post_id, $this->meta_prefix.'document_description', true);
			break;

			case 'address':
			case 'local_address':
				$fields_array['attributes']['placeholder'] = get_post_meta_or_default($post_id, $this->meta_prefix.'document_placeholder', true, __("Street 123, City", 'lang_webshop'));
				$fields_array['desc'] = get_post_meta($post_id, $this->meta_prefix.'document_description', true);
			break;

			case 'clock':
				$fields_array['attributes']['placeholder'] = get_post_meta_or_default($post_id, $this->meta_prefix.'document_placeholder', true, "18.00-03.00");
				$fields_array['desc'] = get_post_meta($post_id, $this->meta_prefix.'document_description', true);
			break;

			case 'email':
				$fields_array['attributes']['placeholder'] = get_post_meta_or_default($post_id, $this->meta_prefix.'document_placeholder', true, get_placeholder_email());
				$fields_array['desc'] = get_post_meta($post_id, $this->meta_prefix.'document_description', true);
			break;

			case 'interval':
				$fields_array['attributes']['placeholder'] = get_post_meta_or_default($post_id, $this->meta_prefix.'document_placeholder', true, "5-25");
				$fields_array['desc'] = get_post_meta($post_id, $this->meta_prefix.'document_description', true);
			break;

			case 'phone':
				$fields_array['attributes']['placeholder'] = get_post_meta_or_default($post_id, $this->meta_prefix.'document_placeholder', true, __("001-888-342-324", 'lang_webshop'));
				$fields_array['desc'] = get_post_meta($post_id, $this->meta_prefix.'document_description', true);
			break;

			case 'url':
				$fields_array['attributes']['placeholder'] = get_post_meta_or_default($post_id, $this->meta_prefix.'document_placeholder', true, get_site_url());
				$fields_array['desc'] = get_post_meta($post_id, $this->meta_prefix.'document_description', true);
			break;
		}
	}

	function get_template_path()
	{
		return str_replace(WP_CONTENT_DIR, "", plugin_dir_path(__FILE__))."templates/";
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

	function get_location_order($data = [])
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
		if(!isset($data['array'])){		$data['array'] = [];}
		if(!isset($data['reverse'])){	$data['reverse'] = false;}

		$this->arr_locations = [];

		$this->get_location_order($data);

		if($data['reverse'] == true)
		{
			$this->arr_locations = array_reverse($this->arr_locations);
		}

		return $this->arr_locations;
	}

	/* Admin */
	function confirm_payment($data = [])
	{
		global $obj_form;

		if(!isset($data['paid'])){		$data['paid'] = 0;}

		if(!isset($data['user_id']))
		{
			if(is_user_logged_in())
			{
				$data['user_id'] = get_current_user_id();
			}

			else
			{
				if(!isset($obj_form))
				{
					$obj_form = new mf_form();
				}

				$data['user_id'] = $obj_form->get_meta(array('id' => $data['answer_id'], 'meta_key' => 'user_id'));
			}
		}

		if($data['paid'] > 0 && $data['user_id'] > 0)
		{
			if($data['paid'] > 0)
			{
				$meta_key = 'profile_webshop_payment';
				$meta_value = get_the_author_meta($meta_key, $data['user_id']);

				if($meta_value > date("Y-m-d"))
				{
					$meta_value = date("Y-m-d", strtotime($meta_value." +12 month"));
				}

				else
				{
					$meta_value = date("Y-m-d", strtotime("+12 month"));
				}

				update_user_meta($data['user_id'], $meta_key, $meta_value);
			}

			else
			{
				do_log("The payment wasn't done correctly (".var_export($data, true).")");
			}
		}

		else
		{
			$name_webshop = __("Webshop", 'lang_webshop');

			do_log(sprintf("Something was missing when a user paid for access to %s (%s)", $name_webshop, var_export($data, true)));
		}
	}

	function admin_menu()
	{
		global $wpdb;

		$menu_start = "edit.php?post_type=".$this->post_type_products;
		$menu_capability = 'edit_posts';

		$name_webshop = __("Webshop", 'lang_webshop');

		if(IS_EDITOR)
		{
			add_menu_page($name_webshop, $name_webshop, $menu_capability, $menu_start, '', 'dashicons-cart', 21);
			add_submenu_page($menu_start, __("Products", 'lang_webshop'), __("Products", 'lang_webshop'), $menu_capability, $menu_start);

			$menu_title = __("Categories", 'lang_webshop');
			add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, "edit.php?post_type=".$this->post_type_categories);

			if($this->get_post_name_for_type('custom_categories') != '')
			{
				$menu_title = __("Custom Categories", 'lang_webshop');
				add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, "edit.php?post_type=".$this->post_type_custom_categories);
			}

			if($this->get_post_name_for_type('location') != '')
			{
				$menu_title = __("Location", 'lang_webshop');
				add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, "edit.php?post_type=".$this->post_type_location);
			}

			$menu_title = __("Filters", 'lang_webshop');
			add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, "edit.php?post_type=".$this->post_type_document_type);

			$menu_title = __("Orders", 'lang_webshop');
			add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, "edit.php?post_type=".$this->post_type_orders);
		}

		else
		{
			add_menu_page($name_webshop, $name_webshop, $menu_capability, $menu_start, '', 'dashicons-cart', 21);
		}

		if(IS_EDITOR)
		{
			$menu_title = __("Settings", 'lang_webshop');
			add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, admin_url("options-general.php?page=settings_mf_base#settings_webshop"));
		}
	}

	function filter_sites_table_pages($arr_pages)
	{
		$arr_pages[$this->post_type_products] = array(
			'icon' => "fas fa-shopping-cart",
			'title' => __("Products", 'lang_webshop'),
		);

		return $arr_pages;
	}

	// Same as in MF Health
	function update_rwmb_post_meta($post_id, $meta_key, $meta_value)
	{
		global $wpdb;

		$updated = false;

		/* Delete old connections */
		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->postmeta." WHERE post_id = '%d' AND meta_key = %s AND meta_value NOT IN('".implode("','", $meta_value)."')", $post_id, $meta_key));

		if($wpdb->num_rows > 0)
		{
			$updated = true;
		}

		/* Insert new connections */
		foreach($meta_value as $value)
		{
			$wpdb->get_results($wpdb->prepare("SELECT meta_id FROM ".$wpdb->postmeta." WHERE post_id = '%d' AND meta_key = %s AND meta_value = '%d'", $post_id, $meta_key, $value));

			if($wpdb->num_rows == 0)
			{
				$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->postmeta." SET post_id = '%d', meta_key = %s, meta_value = '%d'", $post_id, $meta_key, $value));

				if($wpdb->num_rows > 0)
				{
					$updated = true;
				}
			}
		}

		return $updated;
	}

	function rwmb_meta_boxes($meta_boxes)
	{
		global $wpdb;

		// Products
		####################################
		$fields_info = $fields_settings = $fields_quick = $fields_searchable = $fields_public = $fields_single = $fields_properties = [];

		$arr_yes_no = get_yes_no_for_select();

		$fields_settings[] = array(
			'name' => __("Searchable", 'lang_webshop'),
			'id' => $this->meta_prefix.'searchable',
			'type' => 'select',
			'options' => $arr_yes_no,
		);

		$setting_webshop_allow_multiple_categories = get_option('setting_webshop_allow_multiple_categories', 'yes');

		$arr_categories = $this->get_categories_for_select(array('include_on' => 'products', 'add_choose_here' => ($setting_webshop_allow_multiple_categories != 'yes')));
		$count_temp = count($arr_categories);

		if($count_temp > 0)
		{
			$meta_description = "";

			$result = $this->get_post_type_info(array('type' => 'categories'));

			if(isset($result->post_title))
			{
				$meta_title = $result->post_title;

				$meta_description = get_post_meta($result->ID, $this->meta_prefix.'document_description', true);
			}

			else
			{
				$meta_title = __("Categories", 'lang_webshop');
			}

			$data_temp = array(
				'name' => $meta_title,
				'id' => $this->meta_prefix.'category',
				'type' => 'select',
				'options' => $arr_categories,
				'multiple' => false,
				'desc' => $meta_description,
				'attributes' => [],
			);

			if($setting_webshop_allow_multiple_categories == 'yes')
			{
				//$data_temp['type'] = 'select3';
				$data_temp['multiple'] = true;
				$data_temp['attributes'] = array(
					'size' => get_select_size(array('count' => $count_temp)),
				);
			}

			$fields_settings[] = $data_temp;
		}

		$meta_description = "";

		$result = $this->get_post_type_info(array('type' => 'file_advanced'));

		if(isset($result->post_title))
		{
			$meta_title = $result->post_title;

			$meta_description = get_post_meta($result->ID, $this->meta_prefix.'document_description', true);
		}

		else
		{
			$meta_title = __("Image", 'lang_webshop');
		}

		$fields_settings[] = array(
			'name' => $meta_title,
			'id' => $this->meta_prefix.'product_image',
			'type' => 'file_advanced',
			'max_file_uploads' => 0,
			'mime_type' => 'image',
			'desc' => $meta_description,
		);

		$arr_doc_types_ignore = array('heading', 'label', 'categories', 'categories_v2', 'divider', 'read_more_button', 'container_start', 'container_end'); //, 'text', 'description'

		$result = $this->get_document_types(array('select' => "ID, post_title, post_name, post_parent", 'order' => "menu_order ASC"));

		foreach($result as $r)
		{
			$post_id = $r->ID;
			$post_title = $r->post_title;
			$post_name = $r->post_name;
			$post_parent = $r->post_parent;

			$post_custom_type = get_post_meta($post_id, $this->meta_prefix.'document_type', true);

			if($post_custom_type != '' && substr($post_custom_type, 0, 6) != 'group_' && !in_array($post_custom_type, $arr_doc_types_ignore))
			{
				$arr_attributes = [];

				if($post_parent > 0)
				{
					$parent_name = $this->get_document_types(array('select' => "post_name", 'where_key' => "ID = '%d'", 'where_value' => $post_parent, 'limit' => "0, 1"));

					$arr_attributes['connect_to'] = $this->meta_prefix.$parent_name;
				}

				$post_document_searchable = get_post_meta($post_id, $this->meta_prefix.'document_searchable', true);
				$post_document_public = get_post_meta($post_id, $this->meta_prefix.'document_public', true);
				$post_document_public_single = get_post_meta($post_id, $this->meta_prefix.'document_public_single', true);
				$post_document_quick = get_post_meta($post_id, $this->meta_prefix.'document_quick', true);
				$post_document_property = get_post_meta($post_id, $this->meta_prefix.'document_property', true);

				$post_document_input_required = get_post_meta($post_id, $this->meta_prefix.'document_input_required', true);

				if($post_document_input_required == 'yes')
				{
					$arr_attributes['required'] = true;
				}

				if(is_plugin_active("mf_calendar/index.php"))
				{
					$post_document_events = get_post_meta($post_id, $this->meta_prefix.'document_events', true);
				}

				$post_document_alt_text = get_post_meta($post_id, $this->meta_prefix.'document_alt_text', true);
				$post_document_default = get_post_meta($post_id, $this->meta_prefix.'document_default', true);
				$post_document_display_on_categories = get_post_meta($post_id, $this->meta_prefix.'document_display_on_categories', false);

				if(is_array($post_document_display_on_categories) && count($post_document_display_on_categories) > 0)
				{
					$arr_attributes['condition_type'] = 'show_this_if';
					$arr_attributes['condition_selector'] = $this->meta_prefix.'category';
					$arr_attributes['condition_value'] = $post_document_display_on_categories;
				}

				$fields_array = array(
					'post_id' => $post_id,
					'name' => $post_title,
					'alt_text' => $post_document_alt_text,
					'id' => $this->meta_prefix.$post_name,
					'type' => $post_custom_type,
					'std' => $post_document_default,
					'attributes' => $arr_attributes,
				);

				$this->filter_fields_array($post_id, $fields_array, 'products');

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

				else if(is_plugin_active("mf_calendar/index.php") && $post_document_events == 'yes')
				{
					// Do nothing. It should be displayed when editing an event instead
				}

				else
				{
					$fields_info[] = $fields_array;
				}
			}
		}

		if(count($fields_info) > 0)
		{
			$meta_boxes[] = array(
				'id' => $this->meta_prefix.'information',
				'title' => __("Information", 'lang_webshop'),
				'post_types' => array($this->post_type_products),
				//'context' => 'side',
				'priority' => 'low',
				'fields' => $fields_info,
			);
		}

		if(count($fields_settings) > 0)
		{
			$meta_boxes[] = array(
				'id' => $this->meta_prefix.'settings',
				'title' => __("Settings", 'lang_webshop'),
				'post_types' => array($this->post_type_products),
				'context' => 'side',
				'priority' => 'low',
				'fields' => $fields_settings,
			);
		}

		if(count($fields_searchable) > 0)
		{
			$meta_boxes[] = array(
				'id' => $this->meta_prefix.'searchable',
				'title' => __("Searchable", 'lang_webshop'),
				'post_types' => array($this->post_type_products),
				'context' => 'side',
				'priority' => 'low',
				'fields' => $fields_searchable,
			);
		}

		if(count($fields_public) > 0)
		{
			$meta_boxes[] = array(
				'id' => $this->meta_prefix.'public',
				'title' => __("Results", 'lang_webshop'),
				'post_types' => array($this->post_type_products),
				'context' => 'side',
				'priority' => 'low',
				'fields' => $fields_public,
			);
		}

		if(count($fields_single) > 0)
		{
			$meta_boxes[] = array(
				'id' => $this->meta_prefix.'single',
				'title' => __("Contact Info", 'lang_webshop'),
				'post_types' => array($this->post_type_products),
				'context' => 'side',
				'priority' => 'low',
				'fields' => $fields_single,
			);
		}

		if(count($fields_quick) > 0)
		{
			$meta_boxes[] = array(
				'id' => $this->meta_prefix.'quick',
				'title' => __("Quick Info", 'lang_webshop'),
				'post_types' => array($this->post_type_products),
				//'context' => 'side',
				'priority' => 'low',
				'fields' => $fields_quick,
			);
		}

		if(count($fields_properties) > 0)
		{
			$meta_boxes[] = array(
				'id' => $this->meta_prefix.'properties',
				'title' => __("Properties", 'lang_webshop'),
				'post_types' => array($this->post_type_products),
				//'context' => 'side',
				'priority' => 'low',
				'fields' => $fields_properties,
			);
		}
		####################################

		// Document Types
		####################################
		$default_type = $default_searchable = $default_public = $default_public_single = $default_quick = $default_property = 'no';

		$post_id = check_var('post');

		if(!($post_id > 0))
		{
			$last_id = $this->get_document_types(array('select' => "ID", 'order' => "post_date DESC", 'limit' => "0, 1"));

			$default_type = get_post_meta_or_default($last_id, $this->meta_prefix.'document_type', true, 'no');
			$default_searchable = get_post_meta_or_default($last_id, $this->meta_prefix.'document_searchable', true, 'no');
			$default_public = get_post_meta_or_default($last_id, $this->meta_prefix.'document_public', true, 'no');
			$default_public_single = get_post_meta_or_default($last_id, $this->meta_prefix.'document_public_single', true, 'no');
			$default_quick = get_post_meta_or_default($last_id, $this->meta_prefix.'document_quick', true, 'no');
			$default_property = get_post_meta_or_default($last_id, $this->meta_prefix.'document_property', true, 'no');
		}

		$condition_value_placement = '"categories_v2", "description", "color", "gps", "read_more_button", "overlay"';

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
				'name' => __("Searchable", 'lang_webshop'),
				'id' => $this->meta_prefix.'document_searchable',
				'type' => 'select',
				'options' => $arr_yes_no,
				'std' => $default_searchable,
				'attributes' => array(
					'condition_type' => 'show_this_if',
					'condition_selector' => $this->meta_prefix.'document_type',
					'condition_value' => '"heading", "checkbox", "categories", "categories_v2", "custom_categories", "event", "number", "price", "size", "stock", "interval", "location", "address", "local_address", "container_start", "container_end"',
				),
			),
			array(
				'name' => " - ".__("Required", 'lang_webshop'),
				'id' => $this->meta_prefix.'document_searchable_required',
				'type' => 'select',
				'options' => $arr_yes_no,
				'std' => 'no',
				'attributes' => array(
					'condition_type' => 'show_this_if',
					'condition_selector' => $this->meta_prefix.'document_searchable',
					'condition_value' => 'yes',
				),
			),
			array(
				'name' => __("Results", 'lang_webshop'),
				'id' => $this->meta_prefix.'document_public',
				'type' => 'select',
				'options' => $arr_yes_no,
				'std' => $default_public,
				'attributes' => array(
					'condition_type' => 'hide_this_if',
					'condition_selector' => $this->meta_prefix.'document_type',
					'condition_value' => '"categories_v2", "description", "color", "gps", "overlay"',
				),
			),
			array(
				'name' => __("Contact Info", 'lang_webshop'),
				'id' => $this->meta_prefix.'document_public_single',
				'type' => 'select',
				'options' => $arr_yes_no,
				'std' => $default_public_single,
				'attributes' => array(
					'condition_type' => 'hide_this_if',
					'condition_selector' => $this->meta_prefix.'document_type',
					'condition_value' => $condition_value_placement,
				),
			),
			array(
				'name' => __("Quick Info", 'lang_webshop'),
				'id' => $this->meta_prefix.'document_quick',
				'type' => 'select',
				'options' => $arr_yes_no,
				'std' => $default_quick,
				'attributes' => array(
					'condition_type' => 'hide_this_if',
					'condition_selector' => $this->meta_prefix.'document_type',
					'condition_value' => $condition_value_placement,
				),
			),
			array(
				'name' => __("Properties", 'lang_webshop'),
				'id' => $this->meta_prefix.'document_property',
				'type' => 'select',
				'options' => $arr_yes_no,
				'std' => $default_property,
				'attributes' => array(
					'condition_type' => 'hide_this_if',
					'condition_selector' => $this->meta_prefix.'document_type',
					'condition_value' => $condition_value_placement,
				),
			),
			array(
				'name' => " - ".__("Required", 'lang_webshop'),
				'id' => $this->meta_prefix.'document_input_required',
				'type' => 'select',
				'options' => $arr_yes_no,
				'std' => 'no',
				'attributes' => array(
					'condition_type' => 'hide_this_if',
					'condition_selector' => $this->meta_prefix.'document_type',
					'condition_value' => '"categories_v2", "description", "color", "gps", "overlay", "heading", "categories", "categories_v2", "event", "container_start", "container_end", "read_more_button", "file_advanced", "event", "coordinates", "divider"', //, "education"
				),
			),
		);

		if(is_plugin_active("mf_calendar/index.php"))
		{
			$default_events = 'no';

			if(!($post_id > 0))
			{
				$default_events = get_post_meta_or_default($last_id, $this->meta_prefix.'document_events', true, 'no');
			}

			$arr_fields[] = array(
				'name' => __("Events", 'lang_webshop'),
				'id' => $this->meta_prefix.'document_events',
				'type' => 'select',
				'options' => $arr_yes_no,
				'std' => $default_events,
				'attributes' => array(
					'condition_type' => 'hide_this_if',
					'condition_selector' => $this->meta_prefix.'document_type',
					'condition_value' => $condition_value_placement,
				),
			);
		}

		$arr_categories = $this->get_categories_for_select(array('include_on' => 'products', 'add_choose_here' => false));
		$count_temp = count($arr_categories);

		if($count_temp > 1)
		{
			$arr_fields[] = array(
				'type' => 'divider',
			);

			$arr_fields[] = array(
				'name' => __("Display on Categories", 'lang_webshop'),
				'id' => $this->meta_prefix.'document_display_on_categories',
				'type' => 'select3',
				'options' => $arr_categories,
				'multiple' => true,
				'attributes' => array(
					'condition_type' => 'hide_this_if',
					'condition_selector' => $this->meta_prefix.'document_type',
					'condition_value' => '"categories", "categories_v2", "description", "container_start", "container_end"',
					'size' => get_select_size(array('count' => $count_temp)),
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
				'name' => __("Symbol", 'lang_webshop')." (<a href='//fontawesome.com/icons'>".__("Ref", 'lang_webshop')."</a>)",
				'id' => $this->meta_prefix.'document_symbol',
				'type' => 'select',
				'options' => $arr_data_symbols,
				'attributes' => array(
					'condition_type' => 'hide_this_if',
					'condition_selector' => $this->meta_prefix.'document_type',
					'condition_value' => '"description", "color", "gps", "container_start", "container_end"',
				),
			);
		}

		else
		{
			$arr_fields[] = array(
				'name' => __("Symbol", 'lang_webshop')." (<a href='//fontawesome.com/icons'>".__("Ref", 'lang_webshop')."</a>)",
				'id' => $this->meta_prefix.'document_symbol',
				'type' => 'text',
				'attributes' => array(
					'condition_type' => 'hide_this_if',
					'condition_selector' => $this->meta_prefix.'document_type',
					'condition_value' => '"description", "color", "gps", "container_start", "container_end"',
				),
			);
		}

		$arr_fields[] = array(
			'name' => __("Alternate text", 'lang_webshop'),
			'id' => $this->meta_prefix.'document_alt_text',
			'type' => 'text',
			'attributes' => array(
				'condition_type' => 'hide_this_if',
				'condition_selector' => $this->meta_prefix.'document_type',
				'condition_value' => '"description", "color", "gps", "container_start", "container_end"',
			),
		);

		$arr_fields[] = array(
			'name' => __("Custom class", 'lang_webshop'),
			'id' => $this->meta_prefix.'custom_class',
			'type' => 'text',
			/* Same as document_searchable */
			'attributes' => array(
				'condition_type' => 'show_this_if',
				'condition_selector' => $this->meta_prefix.'document_type',
				'condition_value' => '"checkbox", "categories", "categories_v2", "custom_categories", "number", "price", "size", "stock", "interval", "location", "address", "local_address", "container_start", "container_end"',
			),
		);

		$arr_fields[] = array(
			'name' => __("Default value", 'lang_webshop'),
			'id' => $this->meta_prefix.'document_default',
			'type' => 'textarea',
			/* Same as document_searchable */
			'attributes' => array(
				'condition_type' => 'show_this_if',
				'condition_selector' => $this->meta_prefix.'document_type',
				'condition_value' => '"checkbox", "categories", "custom_categories", "number", "price", "size", "stock", "interval", "location", "address", "local_address", "container_start", "container_end"',
			),
		);

		$arr_fields[] = array(
			'name' => __("Placeholder", 'lang_webshop'),
			'id' => $this->meta_prefix.'document_placeholder',
			'type' => 'text',
			'attributes' => array(
				'condition_type' => 'hide_this_if',
				'condition_selector' => $this->meta_prefix.'document_type',
				'condition_value' => '"description", "color", "gps", "container_start", "container_end"',
			),
		);

		$arr_fields[] = array(
			'name' => __("Description", 'lang_webshop'),
			'id' => $this->meta_prefix.'document_description',
			'type' => 'text',
			'attributes' => array(
				'condition_type' => 'hide_this_if',
				'condition_selector' => $this->meta_prefix.'document_type',
				'condition_value' => '"description", "color", "gps", "container_start", "container_end"',
			),
		);

		$meta_boxes[] = array(
			'id' => $this->meta_prefix.'settings',
			'title' => __("Settings", 'lang_webshop'),
			'post_types' => array($this->post_type_document_type),
			//'context' => 'side',
			'priority' => 'low',
			'fields' => $arr_fields,
		);

		$meta_boxes[] = array(
			'id' => $this->meta_prefix.'order',
			'title' => __("Settings", 'lang_webshop'),
			'post_types' => array($this->post_type_document_type),
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
		$default_value = 'no';

		if(!($post_id > 0))
		{
			$last_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s ORDER BY post_date DESC LIMIT 0, 1", $this->post_type_categories));

			if($last_id > 0)
			{
				$default_value = get_post_meta_or_default($last_id, $this->meta_prefix.'connect_new_products', true, 'no');
			}
		}

		$arr_fields = array(
			array(
				'name' => __("Background Color", 'lang_webshop'),
				'id' => $this->meta_prefix.'category_background_color',
				'type' => 'color',
			),
			array(
				'name' => __("Icon", 'lang_webshop'),
				'id' => $this->meta_prefix.'category_icon',
				'type' => 'select',
				'options' => $this->get_symbols_for_select(),
			),
			array(
				'name' => __("Color", 'lang_webshop'),
				'id' => $this->meta_prefix.'category_icon_color',
				'type' => 'color',
			),
			array(
				'name' => sprintf(__("Connect to new %s", 'lang_webshop'), strtolower(__("Product", 'lang_webshop'))),
				'id' => $this->meta_prefix.'connect_new_products',
				'type' => 'select',
				'options' => $arr_yes_no,
				'std' => $default_value,
			),
		);

		if(is_plugin_active("mf_calendar/index.php"))
		{
			$arr_include_on = $this->get_include_on_for_select();

			$arr_fields[] = array(
				'name' => __("Include on", 'lang_webshop'),
				'id' => $this->meta_prefix.'include_on',
				'type' => 'select',
				'options' => $arr_include_on,
				'multiple' => true,
				'attributes' => array(
					'size' => get_select_size(array('count' => count($arr_include_on))),
				),
			);

			$arr_fields[] = array(
				'name' => __("Event Max Length", 'lang_webshop'),
				'id' => $this->meta_prefix.'event_max_length',
				'type' => 'number',
				'attributes' => array(
					'min' => 0,
				),
			);
		}

		$meta_boxes[] = array(
			'id' => $this->meta_prefix.'categories',
			'title' => __("Settings", 'lang_webshop'),
			'post_types' => array($this->post_type_categories),
			'context' => 'side',
			'priority' => 'low',
			'fields' => $arr_fields,
		);
		####################################

		// Custom Categories
		####################################
		$meta_boxes[] = array(
			'id' => $this->meta_prefix.'custom_categories',
			'title' => __("Settings", 'lang_webshop'),
			'post_types' => array($this->post_type_custom_categories),
			'context' => 'normal',
			'priority' => 'low',
			'fields' => array(
				array(
					'name' => __("Filters", 'lang_webshop'),
					'id' => $this->meta_prefix.'document_type',
					'type' => 'select',
					'options' => $this->get_document_types_for_select(array('include' => 'custom_categories')),
				),
				array(
					'name' => __("Image", 'lang_webshop'),
					'id' => $this->meta_prefix.'image',
					'type' => 'file_advanced',
					'max_file_uploads' => 1,
					'mime_type' => 'image',
				),
				array(
					'name' => __("Affect Heading", 'lang_webshop'),
					'id' => $this->meta_prefix.'affect_heading',
					'type' => 'select',
					'options' => get_yes_no_for_select(array('add_choose_here' => true)),
				),
			)
		);
		####################################

		// Location
		####################################
		$meta_boxes[] = array(
			'id' => $this->meta_prefix.'location',
			'title' => __("Settings", 'lang_webshop'),
			'post_types' => array($this->post_type_location),
			'context' => 'side',
			'priority' => 'low',
			'fields' => array(
				array(
					'name' => __("Hidden", 'lang_webshop'),
					'id' => $this->meta_prefix.'location_hidden',
					'type' => 'select',
					'options' => $arr_yes_no,
					'std' => 'no',
				),
			)
		);
		####################################

		// Customers
		####################################
		$meta_boxes[] = array(
			'id' => $this->meta_prefix.'settings',
			'title' => __("Settings", 'lang_webshop'),
			'post_types' => array($this->post_type_customers),
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

		return $meta_boxes;
	}

	function rwmb_enqueue_scripts()
	{
		$setting_range_min_default = get_option_or_default('setting_range_min_default', 10);

		mf_enqueue_script('script_webshop_meta', plugin_dir_url(__FILE__)."script_meta.js", array('range_min_default' => $setting_range_min_default));
	}

	function restrict_manage_posts()
	{
		global $post_type;

		if(isset($this->post_type_products) && isset($post_type) && substr($post_type, 0, strlen($this->post_type_products)) == $this->post_type_products)
		{
			$location_post_name = $this->get_post_name_for_type('location');

			if($location_post_name != '')
			{
				$strFilterLocation = check_var('strFilterLocation');

				$arr_data = [];
				get_post_children(array('post_type' => $this->post_type_location, 'post_status' => '', 'add_choose_here' => true), $arr_data);

				if(count($arr_data) > 2)
				{
					echo show_select(array('data' => $arr_data, 'name' => 'strFilterLocation', 'value' => $strFilterLocation));
				}
			}
		}

		else if(isset($this->post_type_document_type) && isset($post_type) && substr($post_type, 0, strlen($this->post_type_document_type)) == $this->post_type_document_type)
		{
			$strFilterPlacement = check_var('strFilterPlacement');

			echo show_select(array('data' => $this->get_doc_types_for_select(), 'name' => 'strFilterPlacement', 'value' => $strFilterPlacement));
		}
	}

	function pre_get_posts($wp_query)
	{
		global $post_type, $pagenow;

		if($pagenow == 'edit.php' && $post_type != '')
		{
			if(substr($post_type, 0, strlen($this->post_type_products)) == $this->post_type_products)
			{
				$location_post_name = $this->get_post_name_for_type('location');

				if($location_post_name != '')
				{
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

	function column_header($columns)
	{
		global $post_type, $obj_font_icons;

		switch($post_type)
		{
			case $this->post_type_categories:
				unset($columns['date']);

				$columns['category_background_color'] = __("Background", 'lang_webshop');
				$columns['category_icon'] = __("Icon", 'lang_webshop');
				$columns['products'] = __("Products", 'lang_webshop');
				$columns['connect_new_products'] = sprintf(__("Connect to new %s", 'lang_webshop'), strtolower(__("Product", 'lang_webshop')));

				if(is_plugin_active("mf_calendar/index.php"))
				{
					$columns['include_on'] = __("Include on", 'lang_webshop');
				}
			break;

			case $this->post_type_products:
				if(!isset($obj_font_icons))
				{
					$obj_font_icons = new mf_font_icons();
				}

				unset($columns['date']);

				if($this->has_categories(array('include_on' => 'products')) > 0)
				{
					$columns['category'] = __("Categories", 'lang_webshop');
				}

				$arr_columns = array('ghost', 'location', 'local_address', 'email', 'phone', 'event'); //address
				$arr_columns_admin = array('email', 'phone');

				foreach($arr_columns as $column)
				{
					if(!in_array($column, $arr_columns_admin) || IS_ADMINISTRATOR)
					{
						$result = $this->get_post_type_info(array('type' => $column));

						if(isset($result->post_title))
						{
							$column_title = $result->post_title;

							$column_icon = get_post_meta($result->ID, $this->meta_prefix.'document_symbol', true);

							if($column_icon != '')
							{
								$column_title = $obj_font_icons->get_symbol_tag(array('symbol' => $column_icon, 'class' => "fa-lg", 'title' => $column_title));
							}

							$columns[$column] = $column_title;
						}
					}
				}
			break;

			case $this->post_type_custom_categories:
				unset($columns['date']);

				$columns['document_type'] = __("Filters", 'lang_webshop');
				$columns['image'] = __("Image", 'lang_webshop');
				$columns['affect_heading'] = __("Affect Heading", 'lang_webshop');
			break;

			case $this->post_type_document_type:
				unset($columns['date']);

				$columns['type'] = __("Type", 'lang_webshop');
				$columns['settings'] = __("Settings", 'lang_webshop');

				if($this->has_categories(array('include_on' => 'products')) > 1)
				{
					$columns['display_on_categories'] = __("Display on Categories", 'lang_webshop');
				}
			break;

			case $this->post_type_orders:
				$columns['products'] = __("Products", 'lang_webshop');
				$columns['details'] = __("Details", 'lang_webshop');
			break;

			case $this->post_type_location:
				unset($columns['date']);

				$columns['location_hidden'] = "<i class='fa fa-eye-slash fa-lg'></i>";

				if($this->get_post_name_for_type('location') != '')
				{
					$columns['products'] = __("Products", 'lang_webshop');
				}
			break;
		}

		return $columns;
	}

	function column_cell($column, $post_id)
	{
		global $wpdb, $post, $obj_font_icons;

		switch($post->post_type)
		{
			case $this->post_type_categories:
				switch($column)
				{
					case 'category_background_color':
						$post_meta = get_post_meta($post_id, $this->meta_prefix.$column, true);

						if($post_meta != '')
						{
							echo "<i class='fas fa-circle fa-lg' style='color: ".$post_meta."'></i>";
						}
					break;

					case 'category_icon':
						$post_meta = get_post_meta($post_id, $this->meta_prefix.$column, true);

						if($post_meta != '')
						{
							if(!isset($obj_font_icons))
							{
								$obj_font_icons = new mf_font_icons();
							}

							echo $obj_font_icons->get_symbol_tag(array('symbol' => $post_meta, 'class' => "category_".$post_id." fa-lg"));
						}
					break;

					case 'products':
						$product_amount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(post_id) FROM ".$wpdb->postmeta." WHERE meta_key = '".$this->meta_prefix."category' AND meta_value = '%d'", $post_id));

						echo $product_amount;
					break;

					case 'connect_new_products':
						$post_meta = get_post_meta($post_id, $this->meta_prefix.$column, true);

						echo "<i class='fa ".($post_meta == 'yes' ? "fa-check green" : "fa-times red")." fa-lg'></i>";
					break;

					case 'include_on':
						$post_meta = get_post_meta($post_id, $this->meta_prefix.$column, false);

						if(is_array($post_meta) && count($post_meta) > 0)
						{
							$arr_include_on = $this->get_include_on_for_select();

							$out_temp = "";

							foreach($post_meta as $value)
							{
								$out_temp .= ($out_temp != '' ? ", " : "").$arr_include_on[$value];
							}

							echo $out_temp;
						}
					break;
				}
			break;

			case $this->post_type_products:
				switch($column)
				{
					case 'category':
						$post_meta = get_post_meta($post_id, $this->meta_prefix.$column, false);
						$count_temp = count($post_meta);

						if($count_temp > 0)
						{
							$count_limit = 3;

							echo "<div class='category_icon nowrap'>";

								if(!isset($obj_font_icons))
								{
									$obj_font_icons = new mf_font_icons();
								}

								for($i = 0; $i < $count_temp; $i++)
								{
									$category_id = $post_meta[$i];
									$category_title = get_the_title($category_id);

									if($i >= $count_limit)
									{
										echo " +".($count_temp - $count_limit);

										break;
									}

									else
									{
										$post_category_icon = get_post_meta($category_id, $this->meta_prefix.'category_icon', true);

										if($post_category_icon != '')
										{
											echo ($i > 0 ? " " : "").$obj_font_icons->get_symbol_tag(array('symbol' => $post_category_icon, 'title' => $category_title, 'class' => "category_".$category_id." fa-lg"));
										}

										else
										{
											echo ($i > 0 ? ", " : "").$category_title;
										}
									}
								}

							echo "</div>";
						}
					break;

					case 'ghost':
						$post_name = $this->get_post_name_for_type($column);
						$post_meta = get_post_meta($post_id, $this->meta_prefix.$post_name, true);

						if($post_meta == true)
						{
							echo "<i class='fa ".($post_meta == true ? "fa-eye-slash" : "fa-eye")." fa-lg'></i>";
						}
					break;

					case 'location':
						$post_name = $this->get_post_name_for_type($column);
						$post_meta = get_post_meta($post_id, $this->meta_prefix.$post_name, false);
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
						$post_name = $this->get_post_name_for_type($column);
						$post_meta = get_post_meta($post_id, $this->meta_prefix.$post_name, true);

						echo $post_meta;
					break;

					case 'email':
						$post_name = $this->get_post_name_for_type($column);
						$post_meta = get_post_meta($post_id, $this->meta_prefix.$post_name, true);

						echo "<a href='mailto:".$post_meta."'>".$post_meta."</a>";
					break;

					case 'phone':
						$post_name = $this->get_post_name_for_type($column);
						$post_meta = get_post_meta($post_id, $this->meta_prefix.$post_name, true);

						echo "<a href='".format_phone_no($post_meta)."'>".$post_meta."</a>";
					break;

					case 'event':
						$post_name = $this->get_post_name_for_type($column);
						$post_meta = get_post_meta($post_id, $this->meta_prefix.$post_name, true);

						if(is_plugin_active("mf_calendar/index.php"))
						{
							$obj_calendar = new mf_calendar();
							echo $obj_calendar->get_amount_of_posts_for_td($post_meta);
						}

						else
						{
							do_log("MF Calendar does not seam to be activated");
						}
					break;
				}
			break;

			case $this->post_type_custom_categories:
				switch($column)
				{
					case 'document_type':
						$post_meta = get_post_meta($post_id, $this->meta_prefix.$column, true);

						if($post_meta > 0)
						{
							echo get_the_title($post_meta);
						}
					break;

					case 'image':
						$post_meta = get_post_meta_file_src(array('post_id' => $post_id, 'meta_key' => $this->meta_prefix.$column, 'image_size' => 'thumbnail', 'single' => true));

						if($post_meta != '')
						{
							echo "<img src='".$post_meta."'>";
						}
					break;

					case 'affect_heading':
						$post_meta = get_post_meta($post_id, $this->meta_prefix.$column, true);

						if($post_meta != '')
						{
							echo "<i class='fa ".($post_meta == 'yes' ? "fa-check green" : "fa-times red")." fa-lg'></i>";
						}
					break;
				}
			break;

			case $this->post_type_document_type:
				switch($column)
				{
					case 'type':
						$post_meta = get_post_meta($post_id, $this->meta_prefix.'document_'.$column, true);

						if($post_meta != '')
						{
							echo $this->get_types_for_select()[$post_meta];
						}
					break;

					case 'settings':
						$arr_doc_types = $this->get_doc_types_for_select(array('add_choose_here' => false));

						foreach($arr_doc_types as $key => $value)
						{
							switch($key)
							{
								case 'searchable':
									$post_icon = "fas fa-search";
								break;

								case 'public':
									$post_icon = "fas fa-list-alt";
								break;

								case 'public_single':
									$post_icon = "far fa-envelope";
								break;

								case 'quick':
									$post_icon = "fas fa-eye";
								break;

								case 'property':
									$post_icon = "fas fa-cog";
								break;

								/*case 'events':
									$post_icon = "far fa-calendar-alt";
								break;*/
							}

							$post_meta = get_post_meta($post_id, $this->meta_prefix.'document_'.$key, true);

							echo "<span class='fa-stack fa-2x' title='".$value."'>
								<i class='".$post_icon." grey fa-stack-1x'></i>
								<i class='fa ".($post_meta == 'yes' ? "fa-check green" : "fa-ban red")." fa-stack-1x'></i>
							</span>";

							switch($key)
							{
								case 'searchable':
									$hide = ($post_meta != 'yes');
									$post_meta = get_post_meta($post_id, $this->meta_prefix.'document_searchable_required', true);

									echo "<span class='fa-stack fa-2x'".($hide ? " style='visibility: hidden'" : "")." title='".__("Required", 'lang_webshop')." (".__("Searchable", 'lang_webshop').")'>
										<i class='fa fa-asterisk grey fa-stack-1x'></i>
										<i class='fa ".($post_meta == 'yes' ? "fa-check green" : "fa-ban red")." fa-stack-1x'></i>
									</span>";
								break;

								case 'property':
									$post_document_type = get_post_meta($post_id, $this->meta_prefix.'document_type', true);

									$hide = (in_array($post_document_type, array('categories_v2', 'description', 'color', 'gps', 'overlay', 'heading', 'categories', 'event', 'container_start', 'container_end', 'read_more_button', 'file_advanced', 'event', 'coordinates', 'divider', 'education')));
									$post_meta = get_post_meta($post_id, $this->meta_prefix.'document_input_required', true);

									echo "<span class='fa-stack fa-2x'".($hide ? " style='visibility: hidden'" : "")." title='".__("Required", 'lang_webshop')." (".__("Input", 'lang_webshop').")'>
										<i class='fa fa-asterisk grey fa-stack-1x'></i>
										<i class='fa ".($post_meta == 'yes' ? "fa-check green" : "fa-ban red")." fa-stack-1x'></i>
									</span>";
								break;
							}
						}
					break;

					case 'display_on_categories':
						$post_meta = get_post_meta($post_id, $this->meta_prefix.'document_'.$column, false);

						if(count($post_meta) > 0)
						{
							$i = 0;

							foreach($post_meta as $category_id)
							{
								echo ($i > 0 ? ", " : "").get_the_title($category_id);

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

			case $this->post_type_orders:
				switch($column)
				{
					case 'products':
						$arr_post_meta = get_post_meta($post_id, $this->meta_prefix.$column, true);

						if(is_array($arr_post_meta) && count($arr_post_meta) > 0)
						{
							foreach($arr_post_meta as $arr_product)
							{
								if(isset($arr_product['id']) && $arr_product['id'] > 0)
								{
									echo "<p>".get_the_title($arr_product['id']).": ".$arr_product['amount']."</p>";
								}

								else
								{
									echo "<p>".__("Nothing in the cart so far", 'lang_webshop')."</p>";
								}
							}
						}
					break;

					case 'details':
						$obj_encryption = new mf_encryption(__CLASS__);
						$this->order_details = [];

						$this->order_id = get_post_meta($post_id, $this->meta_prefix.'cart_hash', true);

						foreach($this->arr_meta_keys as $meta_key)
						{
							$this->order_details[$meta_key] = get_post_meta($post_id, $this->meta_prefix.$meta_key, true);

							if($this->order_details[$meta_key] != '')
							{
								$this->order_details[$meta_key] = $obj_encryption->decrypt($this->order_details[$meta_key], md5($this->order_id));
							}
						}

						if($this->order_details['first_name'] != '' || $this->order_details['last_name'] != '')
						{
							echo "<p>".$this->order_details['first_name']." ".$this->order_details['last_name']."</p>";
						}

						if($this->order_details['address_street'] != '' || $this->order_details['address_zip'] != '' || $this->order_details['address_city'] != '')
						{
							echo "<p>".$this->order_details['address_street'].", ".$this->order_details['address_zip']." ".$this->order_details['address_city']."</p>";
						}
					break;

					default:
						echo $column;
					break;
				}
			break;

			case $this->post_type_location:
				switch($column)
				{
					case 'location_hidden':
						$post_meta = get_post_meta($post_id, $this->meta_prefix.$column, true);

						if($post_meta == 'yes')
						{
							echo "<i class='fa fa-eye-slash fa-lg'></i>";
						}
					break;

					case 'products':
						$result = $this->get_products_from_location($post_id);
						$count_temp = count($result);

						if($count_temp > 0)
						{
							echo "<a href='".admin_url("edit.php?s&post_type=".$this->post_type_products."&strFilterLocation=".$post_id)."'>".$count_temp."</a>";
						}
					break;
				}
			break;
		}
	}

	function get_page_template($post_id)
	{
		global $wpdb;

		return $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE ID = '%d' AND post_type = %s AND meta_key = %s GROUP BY ID LIMIT 0, 1", $post_id, 'page', '_wp_page_template'));
	}

	function display_post_states($post_states, $post)
	{
		/*$page_template = $this->get_page_template($post->ID);

		if($page_template != '')
		{
			$templates_path = $this->get_template_path();

			switch($page_template)
			{
				case 'template_webshop.php':
					$post_states['template_webshop'] = __("Webshop", 'lang_webshop');
				break;

				case 'template_webshop_favorites.php':
					$post_states['template_webshop_favorites'] = __("Webshop", 'lang_webshop')." (".__("Favorites", 'lang_webshop').")";
				break;

				// Can be removed later...
				case $templates_path.'template_webshop.php':
					$post_states['template_webshop'] = __("Webshop", 'lang_webshop');
				break;

				// Can be removed later...
				case $templates_path.'template_webshop_favorites.php':
					$post_states['template_webshop_favorites'] = __("Webshop", 'lang_webshop')." (".__("Favorites", 'lang_webshop').")";
				break;

				default:
					//$post_states['default'] = $page_template;
				break;
			}
		}

		return $post_states;*/

		global $wpdb;

		$arr_page_types = array(
			'mf/webshopsearch' => __("Shop", 'lang_webshop'),
			'mf/webshopcart' => __("Cart", 'lang_webshop'),
		);

		foreach($arr_page_types as $handle => $label)
		{
			if(has_block($handle, $post))
			{
				list($prefix, $type) = explode("/", $handle);

				$post_states[$this->meta_prefix.$type] = $label;
			}
		}

		return $post_states;
	}

	/*function get_template_admin($data)
	{
		global $post, $obj_form, $obj_theme_core;

		switch($data['type'])
		{
			case 'template_webshop':
				get_header();

					if(have_posts())
					{
						if(!isset($obj_theme_core))
						{
							$obj_theme_core = new mf_theme_core();
						}

						echo "<article".(IS_ADMINISTRATOR ? " class='".$data['type']."'" : "").">";

							while(have_posts())
							{
								the_post();

								$post_title = $post->post_title;
								$post_content = apply_filters('the_content', $post->post_content);

								echo "<h1>".$post_title."</h1>";

								if(is_active_sidebar('widget_after_heading') && $obj_theme_core->is_post_password_protected($post->ID) == false)
								{
									ob_start();

									dynamic_sidebar('widget_after_heading');

									$widget_content = ob_get_clean();

									if($widget_content != '')
									{
										echo "<div class='aside after_heading'>"
											.$widget_content
										."</div>";
									}
								}

								echo "<section>".$post_content."</section>";
							}

						echo "</article>";
					}

				get_footer();
			break;

			case 'template_webshop_favorites':
				get_header();

					if(have_posts())
					{
						while(have_posts())
						{
							the_post();

							$post_id = $post->ID;
							$post_title = $post->post_title;
							$post_content = $post->post_content;

							echo "<form action='".$obj_form->get_form_url(get_option('setting_quote_form'))."' method='post' id='product_form' class='mf_form product_search product_favorites'>
								<div class='aside'><div>".$this->get_webshop_map()."</div></div>
								<article".(IS_ADMINISTRATOR ? " class='".$data['type']."'" : "").">
									<h1>".$post_title."</h1>
									<section>
										<div class='favorite_result'>"
											.$this->get_search_result_info(array('type' => 'favorites'))
											.$this->get_quote_button(array('include' => array('quote', 'print', 'email')))
											."<ul class='product_list webshop_item_list'><li class='loading'>".apply_filters('get_loading_animation', '', ['class' => "fa-3x"])."</li></ul>
										</div>
										<div class='favorite_fallback hide'>".apply_filters('the_content', $post_content)."</div>"
									."</section>
								</article>
							</form>"
							.$this->get_templates(array('type' => 'products'));
						}
					}

				get_footer();
			break;
		}
	}*/

	function save_post($post_id, $post, $update)
	{
		global $wpdb;

		$this->get_option_type_from_post_id($post_id);

		if($post->post_type == $this->post_type_products)
		{
			if($update == true)
			{
				delete_user_meta(get_current_user_id(), 'meta_webshop_reminder_sent');
			}

			else
			{
				$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND post_status = %s AND meta_key = '".$this->meta_prefix."connect_new_products' AND meta_value = 'yes' GROUP BY ID", $this->post_type_categories, 'publish'));

				foreach($result as $r)
				{
					$category_id = $r->ID;

					add_post_meta($post_id, $this->meta_prefix.'category', $category_id);
				}
			}
		}
	}

	function wp_trash_post($post_id)
	{
		$post_type = get_post_type($post_id);

		$this->get_option_type_from_post_id($post_id);

		if($post_type == $this->post_type_products)
		{
			$event_post_name = $this->get_post_name_for_type('event');

			if($event_post_name != '')
			{
				$calendar_id = get_post_meta($post_id, $this->meta_prefix.$event_post_name, true);

				if($calendar_id > 0)
				{
					wp_trash_post($calendar_id);
				}
			}
		}
	}

	function get_list($data = [])
	{
		global $wpdb;

		if(!isset($data['output'])){	$data['output'] = 'array';}
		if(!isset($data['select'])){	$data['select'] = "ID";}
		if(!isset($data['order_by'])){	$data['order_by'] = "";}

		$query = $wpdb->prepare("SELECT ".$data['select']." FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = %s".($data['order_by'] != '' ? " ORDER BY ".$data['order_by'] : ""), $this->post_type_products, 'publish');

		switch($data['output'])
		{
			default:
			case 'array':
				return $wpdb->get_results($query);
			break;

			case 'value':
				return $wpdb->get_var($query);
			break;
		}
	}

	function rwmb_before_save_post($post_id)
	{
		global $post;

		$this->get_option_type_from_post_id($post_id);

		if($post->post_type == $this->post_type_categories)
		{
			$post_meta_new = check_var($this->meta_prefix.'connect_new_products');
			$post_meta_old = get_post_meta($post_id, $this->meta_prefix.'connect_new_products', false);

			if($post_meta_new == 'yes' && $post_meta_new != $post_meta_old)
			{
				$result = $this->get_list();

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

	function split_coordinates($in)
	{
		if($in != '')
		{
			return array_map('trim', explode(",", trim(trim($in, "("), ")")));
		}

		else
		{
			return array('', '');
		}
	}

	function rwmb_after_save_post($post_id)
	{
		$this->get_option_type_from_post_id($post_id);

		if(get_post_type($post_id) == $this->post_type_products)
		{
			$coordinates_post_name = $this->get_post_name_for_type('coordinates');

			if($coordinates_post_name != '')
			{
				$post_coordinates = get_post_meta($post_id, $this->meta_prefix.$coordinates_post_name, true);

				if($post_coordinates == '')
				{
					$address_post_name = $this->get_post_name_for_type('local_address');

					if($address_post_name == '')
					{
						$address_post_name = $this->get_post_name_for_type('address');
					}

					if($address_post_name != '')
					{
						$post_location = get_post_meta($post_id, $this->meta_prefix.$address_post_name, true);

						if($post_location != '')
						{
							$post_coordinates = apply_filters('get_coordinates_from_location', $post_location);

							if($post_coordinates != '' && $post_coordinates != $post_location)
							{
								update_post_meta($post_id, $this->meta_prefix.$coordinates_post_name, $post_coordinates);
							}
						}
					}
				}

				if($post_coordinates != '')
				{
					list($latitude, $longitude) = $this->split_coordinates($post_coordinates);

					update_post_meta($post_id, $this->meta_prefix.'latitude', $latitude);
					update_post_meta($post_id, $this->meta_prefix.'longitude', $longitude);
				}
			}
		}
	}

	function get_group_sync_type($arr_data)
	{
		$email_post_name = $this->get_post_name_for_type('email');

		if($email_post_name != '')
		{
			$arr_data['webshop_customers'] = __("Webshop Customers", 'lang_webshop');
		}

		return $arr_data;
	}

	function get_group_sync_addresses($arr_addresses, $sync_type)
	{
		global $wpdb;

		switch($sync_type)
		{
			case 'webshop_customers':
				$email_post_name = $this->get_post_name_for_type('email');

				if($email_post_name != '')
				{
					$result = $wpdb->get_results($wpdb->prepare("SELECT post_title, meta_value FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = %s WHERE post_type = %s AND post_status = %s AND meta_value != '' GROUP BY meta_value", $this->meta_prefix.$email_post_name, $this->post_type_products, 'publish'));

					foreach($result as $r)
					{
						//$product_id = $r->ID;

						$arr_addresses[] = array(
							'email' => $r->meta_value,
							'first_name' => $r->post_title,
							'sur_name' => "",
						);
					}
				}
			break;
		}

		return $arr_addresses;
	}

	function get_tax($data)
	{
		$setting_webshop_tax_enter = get_option('setting_webshop_tax_enter', 'yes');
		$setting_webshop_tax_rate = get_option('setting_webshop_tax_rate', 25);

		if($setting_webshop_tax_enter == 'no')
		{
			$data['price'] = ($data['price'] * ($setting_webshop_tax_rate / 100));
		}

		else
		{
			$data['price'] = ($data['price'] - ($data['price'] / (1 + ($setting_webshop_tax_rate / 100))));
		}

		if($data['suffix'] == true)
		{
			$setting_webshop_currency = get_option('setting_webshop_currency', 'SEK');

			$data['price'] .= "&nbsp;".$setting_webshop_currency;
		}

		return $data['price'];
	}

	function display_price($data)
	{
		if(!isset($data['calculate'])){	$data['calculate'] = true;}
		if(!isset($data['suffix'])){	$data['suffix'] = 'all';}

		if($data['calculate'] == true)
		{
			$setting_webshop_tax_enter = get_option('setting_webshop_tax_enter', 'yes');
			$setting_webshop_tax_display = get_option('setting_webshop_tax_display', 'yes');
			$setting_webshop_tax_rate = get_option('setting_webshop_tax_rate', 25);

			if($setting_webshop_tax_enter == 'no')
			{
				$data['price'] += ($data['price'] * ($setting_webshop_tax_rate / 100));
			}

			if($setting_webshop_tax_display == 'yes')
			{
				$data['price'] -= ($data['price'] - ($data['price'] / (1 + ($setting_webshop_tax_rate / 100))));
			}
		}

		if($data['suffix'] == true)
		{
			if($data['suffix'] == 'currency' || $data['suffix'] == 'all')
			{
				$setting_webshop_currency = get_option('setting_webshop_currency', 'SEK');

				$data['price'] .= "&nbsp;".$setting_webshop_currency;
			}

			if($data['suffix'] == 'tax' || $data['suffix'] == 'all')
			{
				$setting_webshop_tax_display = get_option('setting_webshop_tax_display', 'yes');

				$data['price'] .= "&nbsp;".($setting_webshop_tax_display == 'yes' ? __("excl. tax", 'lang_webshop') : __("incl. tax", 'lang_webshop'));
			}
		}

		return $data['price'];
	}
	
	function get_webshop_cart($json_output)
	{
		global $wpdb;

		$order_id = $this->get_cookie();
		$arr_products = [];
		$total_sum = $total_tax = 0;

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = %s AND meta_value = %s WHERE post_type = %s AND post_status = %s ORDER BY post_modified DESC LIMIT 0, 1", $this->meta_prefix.'cart_hash', $order_id, $this->post_type_orders, 'draft'));

		foreach($result as $r)
		{
			$arr_products = get_post_meta($r->ID, $this->meta_prefix.'products', true);

			if(is_array($arr_products))
			{
				foreach($arr_products as $key => $arr_value)
				{
					$arr_products[$key]['product_title'] = get_the_title($arr_products[$key]['id']);
					$arr_products[$key]['product_url'] = get_the_permalink($arr_products[$key]['id']);

					$total_sum += $this->display_price(array('price' => ($arr_products[$key]['price'] * $arr_products[$key]['amount']), 'suffix' => false));
					$total_tax += $this->get_tax(array('price' => $arr_products[$key]['price'], 'suffix' => false));

					$arr_products[$key]['product_tax'] = $this->get_tax(array('price' => $arr_products[$key]['price'], 'suffix' => true));
					$arr_products[$key]['product_total'] = $this->display_price(array('price' => $arr_products[$key]['price'] * $arr_products[$key]['amount']));
					$arr_products[$key]['price'] = $this->display_price(array('price' => $arr_products[$key]['price']));
				}
			}

			else
			{
				$arr_products = [];
			}
		}

		if(IS_SUPER_ADMIN)
		{
			//$json_output['debug'] .= " (".$wpdb->last_query.")";
		}

		if(is_array($arr_products))
		{
			$setting_webshop_shipping_free_limit = get_option_or_default('setting_webshop_shipping_free_limit', 0);
			$shipping_cost = 0;
			$shipping_comment = "";

			if(!($setting_webshop_shipping_free_limit > 0) || $total_sum < $setting_webshop_shipping_free_limit)
			{
				$shipping_cost = get_option_or_default('setting_webshop_shipping_cost', 0);

				$total_sum += $this->display_price(array('price' => $shipping_cost, 'suffix' => false));
				$total_tax += $this->get_tax(array('price' => $shipping_cost, 'suffix' => false));

				$shipping_comment = " (".sprintf(__("%s left to free shipping", 'lang_webshop'), $this->display_price(array('price' => abs($total_sum - $setting_webshop_shipping_free_limit), 'calculate' => false, 'suffix' => 'currency'))).")";
			}

			$json_output['success'] = true;
			$json_output['response_webshop_cart'] = array(
				//'order_id' => $order_id,
				'products' => $arr_products,
				'shipping_cost' => $this->display_price(array('price' => $shipping_cost, 'calculate' => false)).$shipping_comment,
				'total_sum' => $this->display_price(array('price' => $total_sum, 'calculate' => false)),
				'total_tax' => $this->display_price(array('price' => $total_tax, 'calculate' => false, 'suffix' => 'currency')),
			);
		}

		else
		{
			$json_output['error'] = __("Error", 'lang_webshop');
		}

		return $json_output;
	}

	function api_webshop_call()
	{
		global $wpdb;

		$json_output = array(
			'success' => false,
		);

		$type = check_var('type');
		$type_switch = "";

		if(IS_SUPER_ADMIN)
		{
			//$json_output['debug'] = $type;
		}

		if(substr($type, 0, 1) == "?")
		{
			if(IS_SUPER_ADMIN)
			{
				//$json_output['debug'] .= " has_question_mark";
			}

			$arr_type = explode("&amp;", substr($type, 1));

			foreach($arr_type as $str_type)
			{
				list($key, $value) = explode("=", $str_type);

				if(IS_SUPER_ADMIN)
				{
					//$json_output['debug'] .= " ".$key." = ".$value;
				}

				if($key == 'type')
				{
					$type_switch = $value;
				}

				else
				{
					$$key = $value;
				}
			}
		}

		else
		{
			if(IS_SUPER_ADMIN)
			{
				//$json_output['debug'] .= " has_no_question_mark";
			}

			$arr_type = explode("/", $type);

			$type_switch = $arr_type[0];
		}

		$arr_fields_excluded = array($this->meta_prefix.'searchable');

		switch($type_switch)
		{
			case 'admin':
				if(is_user_logged_in())
				{
					if(isset($arr_type[3]) && in_array($arr_type[3], array('list', 'edit', 'save')))
					{
						$this->option_type = "_".$arr_type[2];
						$arr_type[2] = $arr_type[3];
					}

					else // Just to make sure that option_type isn't carried forward from another loop earlier in the code
					{
						$this->option_type = "";
					}

					$type_temp = $arr_type[1]."/".$arr_type[2];

					switch($type_temp)
					{
						case 'webshop/list':
							$arr_list = [];

							$query_where = "";

							if(1 == 1 || !IS_ADMINISTRATOR)
							{
								$query_where .= " AND post_author = '".get_current_user_id()."'";
							}

							$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_status, post_modified FROM ".$wpdb->posts." WHERE post_type = %s AND (post_status = %s OR post_status = %s)".$query_where, $this->post_type_products, 'publish', 'draft'));

							foreach($result as $r)
							{
								$arr_list[] = array(
									'post_id' => $r->ID,
									'post_title' => $r->post_title.($r->post_status == 'draft' ? " (".__("Draft").")" : ""),
									'post_url' => get_permalink($r->ID),
									'post_modified' => format_date($r->post_modified),
								);
							}

							$json_output['success'] = true;
							$json_output['admin_webshop_response'] = array(
								'type' => $arr_type[0]."_".str_replace("/", "_", $type_temp),
								'list' => $arr_list,
							);
						break;

						case 'webshop/edit':
							$post_id = (isset($arr_type[3]) ? $arr_type[3] : 0);

							$json_output['admin_webshop_response'] = array(
								'type' => $arr_type[0]."_".str_replace("/", "_", $type_temp),
								'post_id' => $post_id,
								'post_title' => "",
								'post_name' => "",
								'meta_boxes' => [],
								'timestamp' => date("Y-m-d H:i:s"),
							);

							if($post_id > 0)
							{
								$this->get_option_type_from_post_id($post_id);

								$arr_meta_boxes = $this->rwmb_meta_boxes([]);

								$query_where = "";

								if(1 == 1 || !IS_ADMINISTRATOR)
								{
									$query_where .= " AND post_author = '".get_current_user_id()."'";
								}

								$result = $wpdb->get_results($wpdb->prepare("SELECT post_title, post_name, post_type, post_author FROM ".$wpdb->posts." WHERE post_type = %s AND (post_status = %s OR post_status = %s) AND ID = '%d'".$query_where, $this->post_type_products, 'publish', 'draft', $post_id));

								foreach($result as $r)
								{
									$json_output['admin_webshop_response']['post_title'] = $post_title = $r->post_title;
									$json_output['admin_webshop_response']['post_name'] = $post_name = $r->post_name;
									$post_type = $r->post_type;
									$post_author = $r->post_author;

									foreach($arr_meta_boxes as $box_id => $arr_meta_box)
									{
										if(!isset($arr_meta_box['context']))
										{
											$arr_meta_boxes[$box_id]['context'] = 'normal';
										}

										if(in_array($post_type, $arr_meta_box['post_types']))
										{
											foreach($arr_meta_box['fields'] as $field_id => $arr_field)
											{
												$arr_meta_boxes[$box_id]['fields'][$field_id]['error'] = $arr_meta_boxes[$box_id]['fields'][$field_id]['class'] = "";
												$arr_children_temp = [];

												$id_temp = $arr_meta_box['fields'][$field_id]['id'];
												$value_temp = "";
												$type_temp = $arr_meta_boxes[$box_id]['fields'][$field_id]['type'];
												$multiple_temp = (isset($arr_meta_box['fields'][$field_id]['multiple']) ? $arr_meta_box['fields'][$field_id]['multiple'] : false);

												$display_temp = $arr_meta_boxes[$box_id]['fields'][$field_id]['display'] = !in_array($id_temp, $arr_fields_excluded);

												if($display_temp)
												{
													// Add options
													switch($type_temp)
													{
														case 'custom_categories':
															$post_name_temp = str_replace($this->meta_prefix, "", $id_temp);
															$post_id_temp = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_name = %s", $this->post_type_document_type, $post_name_temp));

															$arr_data = [];
															get_post_children(array(
																'add_choose_here' => true,
																'post_type' => $this->post_type_custom_categories,
																'join' => " INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = '".$this->meta_prefix."document_type'",
																'where' => "meta_value = '".esc_sql($post_id_temp)."'",
																//'debug' => true,
															), $arr_data);

															$arr_meta_boxes[$box_id]['fields'][$field_id]['options'] = $arr_data;
														break;

														case 'education':
															if(is_plugin_active("mf_education/index.php"))
															{
																$obj_education = new mf_education();

																$arr_data = [];
																get_post_children(array('add_choose_here' => false, 'post_type' => $obj_education->post_type), $arr_data);

																$arr_meta_boxes[$box_id]['fields'][$field_id]['options'] = $arr_data;

																$multiple_temp = true;
															}
														break;

														case 'event':
															if(!is_plugin_active("mf_calendar/index.php"))
															{
																$arr_meta_boxes[$box_id]['fields'][$field_id]['error'] = sprintf(__("You have to install the plugin %s first", 'lang_webshop'), "MF Calendar");
															}
														break;

														case 'location':
														case 'select3':
															$multiple_temp = true;
														break;

														case 'page':
															$arr_data = [];
															get_post_children(array('add_choose_here' => true), $arr_data);

															$arr_meta_boxes[$box_id]['fields'][$field_id]['options'] = $arr_data;
														break;

														case 'social':
															if(is_plugin_active("mf_social_feed/index.php"))
															{
																$obj_social_feed = new mf_social_feed();

																$arr_data = [];
																get_post_children(array('add_choose_here' => true, 'post_type' => $obj_social_feed->post_type), $arr_data);

																$arr_meta_boxes[$box_id]['fields'][$field_id]['options'] = $arr_data;

																$arr_meta_boxes[$box_id]['fields'][$field_id]['class'] .= " has_suffix";
																$arr_meta_boxes[$box_id]['fields'][$field_id]['suffix'] = "<a href='".admin_url("post-new.php?post_type=".$obj_social_feed->post_type)."'><i class='fa fa-plus-circle fa-lg'></i></a>";
															}

															else
															{
																$arr_meta_boxes[$box_id]['fields'][$field_id]['error'] = sprintf(__("You have to install the plugin %s first", 'lang_webshop'), "MF Social Feed");
															}
														break;
													}

													// Add multiple attributes
													switch($type_temp)
													{
														case 'custom_categories':
														case 'education':
														//case 'event':
														case 'location':
														case 'select':
														case 'select3':
															if($multiple_temp)
															{
																$arr_meta_boxes[$box_id]['fields'][$field_id]['class'] = " form_select_multiple";
																$arr_meta_boxes[$box_id]['fields'][$field_id]['attributes']['class'] = "mf_form_field multiselect";
																$arr_meta_boxes[$box_id]['fields'][$field_id]['attributes']['multiple'] = "";
																$arr_meta_boxes[$box_id]['fields'][$field_id]['attributes']['size'] = get_select_size(array('count' => count($arr_meta_boxes[$box_id]['fields'][$field_id]['options'])));

																do_action('init_multiselect');
															}
														break;
													}

													// Get saved value
													switch($type_temp)
													{
														case 'file_advanced':
															$value_temp = [];

															$result_files = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM ".$wpdb->postmeta." WHERE post_id = '%d' AND meta_key = %s", $post_id, $id_temp));

															foreach($result_files as $r_file)
															{
																list($file_name, $file_url) = get_attachment_data_by_id($r_file->meta_value);

																$value_temp[] = $file_name."|".$file_url."|".$r_file->meta_value;
															}
														break;

														case 'custom_categories':
														case 'education':
														//case 'event':
														case 'location':
														case 'select':
														case 'select3':
															$value_temp = get_post_meta($post_id, $id_temp, ($multiple_temp != true));

															if(isset($value_temp[0]) && is_array($value_temp[0]))
															{
																$value_temp = $value_temp[0]; // MB saves as array(0 => array(0, 1)) but we want it to be array(0, 1) when we render it
															}
														break;

														default:
															$value_temp = get_post_meta($post_id, $id_temp, ($multiple_temp == true ? false : true));
														break;
													}

													// Get default value if empty
													if($value_temp == '' || $value_temp == 0)
													{
														switch($type_temp)
														{
															case 'email':
																$user_data = get_userdata($post_author);

																$value_temp = $user_data->user_email;
															break;

															case 'event':
																$this->create_product_event_connection($post_id);
															break;
														}
													}
												}

												$arr_meta_boxes[$box_id]['fields'][$field_id]['value'] = $value_temp;
												$arr_meta_boxes[$box_id]['fields'][$field_id]['multiple'] = $multiple_temp;
												$arr_meta_boxes[$box_id]['fields'][$field_id]['children'] = $arr_children_temp;
											}
										}

										else
										{
											unset($arr_meta_boxes[$box_id]);
										}
									}

									$json_output['success'] = true;
									$json_output['admin_webshop_response']['meta_boxes'] = $arr_meta_boxes;
								}
							}

							else
							{
								$json_output['admin_webshop_response']['post_title'] = get_user_info();
							}

							$json_output['admin_webshop_response']['option_type'] = $this->option_type;
							$json_output['admin_webshop_response']['name_product'] = __("Product", 'lang_webshop');
						break;

						case 'webshop/save':
							$post_id = check_var('post_id', 'int');
							$post_title = check_var('post_title');

							$json_output['admin_webshop_response'] = array(
								'type' => $type,
								'post_id' => $post_id,
								//'debug' => var_export($_REQUEST, true),
							);

							if($post_id > 0)
							{
								$this->get_option_type_from_post_id($post_id);

								$arr_meta_boxes = $this->rwmb_meta_boxes([]);

								$query_where = "";
								$query_where .= " AND post_author = '".get_current_user_id()."'";

								$result = $wpdb->get_results($wpdb->prepare("SELECT post_title, post_name, post_type FROM ".$wpdb->posts." WHERE post_type = %s AND (post_status = %s OR post_status = %s) AND ID = '%d'".$query_where, $this->post_type_products, 'publish', 'draft', $post_id));

								foreach($result as $r)
								{
									$post_title_old = $r->post_title;
									$post_type = $r->post_type;

									$error = $reload = $updated = false;

									$post_data = array(
										'ID' => $post_id,
										'post_status' => 'publish',
										'post_modified' => date("Y-m-d H:i:s"),
										'meta_input' => [],
									);

									if($post_title != $post_title_old)
									{
										$post_data['post_title'] = $post_title;

										$updated = true;
									}

									foreach($arr_meta_boxes as $box_id => $arr_meta_box)
									{
										if(in_array($post_type, $arr_meta_box['post_types']))
										{
											foreach($arr_meta_box['fields'] as $field_id => $arr_field)
											{
												$id_temp = $arr_meta_box['fields'][$field_id]['id'];
												$type_temp = $arr_meta_boxes[$box_id]['fields'][$field_id]['type'];
												$multiple_temp = (isset($arr_meta_box['fields'][$field_id]['multiple']) ? $arr_meta_box['fields'][$field_id]['multiple'] : false);

												if(!in_array($id_temp, $arr_fields_excluded))
												{
													// Prepare multiple
													switch($type_temp)
													{
														case 'education':
														case 'location':
														case 'select3':
															$multiple_temp = true;
														break;
													}

													// Prepare or save values
													switch($type_temp)
													{
														case 'file_advanced':
															$post_value_new = check_var($id_temp, 'char');

															list($arr_files, $arr_ids) = get_attachment_to_send($post_value_new);

															// Use this instead?
															/*if($this->update_rwmb_post_meta($post_id, $id_temp, $arr_ids))
															{
																$updated = true;
															}*/
															#################################
															/* Delete old connections */
															$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->postmeta." WHERE post_id = '%d' AND meta_key = %s AND meta_value NOT IN('".implode("','", $arr_ids)."')", $post_id, $id_temp));

															if($wpdb->num_rows > 0)
															{
																$updated = true;
															}

															/* Insert new connections */
															foreach($arr_ids as $file_id)
															{
																$wpdb->get_results($wpdb->prepare("SELECT meta_id FROM ".$wpdb->postmeta." WHERE post_id = '%d' AND meta_key = %s AND meta_value = '%d'", $post_id, $id_temp, $file_id));

																if($wpdb->num_rows == 0)
																{
																	$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->postmeta." SET post_id = '%d', meta_key = %s, meta_value = '%d'", $post_id, $id_temp, $file_id));

																	if($wpdb->num_rows > 0)
																	{
																		$updated = true;
																	}
																}
															}
															#################################
														break;

														//case 'custom_categories':
														//case 'education':
														//case 'event':
														//case 'location':
														//case 'select':
														case 'select3':
															$post_value_new = check_var($id_temp, ($multiple_temp == true ? 'array' : 'char'));

															if($this->update_rwmb_post_meta($post_id, $id_temp, $post_value_new))
															{
																$updated = true;
															}
														break;

														default:
															$post_value_old = get_post_meta($post_id, $id_temp, ($multiple_temp == true ? false : true));
															$post_value_new = check_var($id_temp, ($multiple_temp == true ? 'array' : 'char'));

															if($post_value_new != $post_value_old)
															{
																$post_data['meta_input'][$id_temp] = $post_value_new;
																//do_log(sprintf("Changed from %s to %s for %s in %s", var_export($post_value_old, true), var_export($post_value_new, true), $id_temp, $post_title));

																$updated = true;
															}
														break;
													}
												}
											}
										}
									}

									if($error == true)
									{
										// Do nothing. $json_output['message'] should be set so the user knows what's gone wrong
									}

									else/* if($updated == true)*/
									{
										if(wp_update_post($post_data) > 0 || $updated == true)
										{
											do_action('rwmb_after_save_post', $post_id);

											$json_output['success'] = true;
											$json_output['message'] = sprintf(__("I have saved the information for you. %sView the page here%s", 'lang_webshop'), "<a href='".get_permalink($post_id)."'>", "</a>");

											if($reload == true)
											{
												$json_output['next_request'] = "admin/webshop/edit/".$post_id;
											}
										}

										else
										{
											$json_output['message'] = __("I could not update the information for you", 'lang_webshop');
										}
									}

									/*else
									{
										$json_output['message'] = __("It does not look like you changed anything, so nothing was saved", 'lang_webshop');
									}*/
								}
							}

							else
							{
								$this->option_type = check_var('option_type');

								$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = %s AND post_title = %s AND post_author = '%d'", $this->post_type_products, 'publish', $post_title, get_current_user_id()));

								if($wpdb->num_rows == 0)
								{
									$post_data = array(
										'post_title' => $post_title,
										'post_type' => $this->post_type_products,
										'post_status' => 'publish',
									);

									$post_id = wp_insert_post($post_data);

									if($post_id > 0)
									{
										do_action('rwmb_after_save_post', $post_id);

										$json_output['success'] = true;
										$json_output['message'] = sprintf(__("I have saved the information for you. %sView the page here%s", 'lang_webshop'), "<a href='".get_permalink($post_id)."'>", "</a>");
										$json_output['next_request'] = "admin/webshop/edit/".$post_id;
										////$json_output['debug'] = "Created: ".$wpdb->last_query;
									}

									else
									{
										$json_output['message'] = __("I could not save the information for you", 'lang_webshop');
									}
								}

								else
								{
									$json_output['message'] = __("One with that title already exists", 'lang_webshop');
								}
							}
						break;
					}
				}

				else
				{
					$json_output['redirect'] = wp_login_url();
				}
			break;

			case 'filter_products':
				$id = check_var('id', 'char');
				$option_type = check_var('option_type', 'char');
				$category = check_var('category', 'char');
				$order_by = check_var('order_by');
				$link_product = check_var('link_product');
				$latitude = check_var('latitude');
				$longitude = check_var('longitude');
				$initial = check_var('initial');
				$limit = check_var('limit', 'int', true, '0');
				$amount = check_var('amount', 'int');

				$json_output = $this->get_filter_products(array(
					'id' => $id,
					'option_type' => $option_type,
					'category' => $category,
					'link_product' => $link_product,
					'order_by' => $order_by,
					'latitude' => $latitude,
					'longitude' => $longitude,
					'initial' => $initial,
					'limit' => $limit,
					'amount' => $amount,
				));
			break;

			case 'add_to_cart':
				$json_output = $this->add_to_cart($json_output, $product_id);
			break;

			case 'webshop_cart':
				$json_output = $this->get_webshop_cart($json_output);
			break;

			case 'amount':
			default:
				$this->option_type = check_var('option_type', 'char');

				$order = check_var('order', 'char', true, get_option('setting_webshop_sort_default', 'alphabetical'));
				$favorites = check_var('favorites', 'char');

				$query_select = $query_join = $query_where = $query_group = $query_order = "";

				$query_join .= " LEFT JOIN ".$wpdb->postmeta." AS searchable ON ".$wpdb->posts.".ID = searchable.post_id AND searchable.meta_key = '".$this->meta_prefix.'searchable'."'";
				$query_where .= " AND (searchable.meta_value IS null OR searchable.meta_value = 'yes')";

				switch($order)
				{
					default:
					case 'alphabetical':
						$query_order .= ($query_order != '' ? ", " : "")."post_title ASC";
					break;

					case 'newest':
					case 'latest':
						$query_order .= ($query_order != '' ? ", " : "")."post_date DESC";
					break;

					case 'random':
						$query_order .= ($query_order != '' ? ", " : "")."RAND()";
					break;

					case 'size':
						$size_post_name = $this->get_post_name_for_type('size');

						if($size_post_name != '')
						{
							$query_join .= " LEFT JOIN ".$wpdb->postmeta." AS meta_size ON ".$wpdb->posts.".ID = meta_size.post_id AND meta_size.meta_key = '".esc_sql($this->meta_prefix.$size_post_name)."'";
							$query_order .= ($query_order != '' ? ", " : "")."(meta_size.meta_value + 0) ASC";
						}
					break;
				}

				if($favorites != '')
				{
					$arr_favorites = explode(",", esc_sql($favorites));

					if(count($arr_favorites) > 0)
					{
						$query_where .= " AND ID IN ('".implode("','", $arr_favorites)."')";
					}
				}

				$json_output['product_response'] = [];

				if($query_group != '')
				{
					$query_group = " GROUP BY ".$query_group;
				}

				if($query_order != '')
				{
					$query_order = " ORDER BY ".$query_order;
				}

				$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_excerpt, post_content".$query_select." FROM ".$wpdb->posts.$query_join." WHERE post_type = %s AND post_status = %s".$query_where.$query_group.$query_order, $this->post_type_products, 'publish'));

				foreach($result as $r)
				{
					$this->get_product_data(array('product' => $r, 'single_image' => true, 'show_location_in_data' => false), $json_output);
				}

				$json_output['success'] = true;

				if($type == 'amount')
				{
					$json_output['product_amount'] = count($json_output['product_response']);

					unset($json_output['product_response']);
				}
			break;
		}

		header('Content-Type: application/json');
		echo json_encode($json_output);
		die();
	}

	function api_webshop_fetch_info()
	{
		$arr_fields = check_var('arr_fields');

		$out = [];

		foreach($arr_fields as $key => $arr_value)
		{
			$value_temp = "";

			switch($arr_value[1])
			{
				case 'address':
					if(is_user_logged_in())
					{
						$value_temp = get_the_author_meta('profile_address_street', get_current_user_id());
					}
				break;

				case 'city':
					if(is_user_logged_in())
					{
						$value_temp = get_the_author_meta('profile_address_city', get_current_user_id());
					}
				break;

				case 'country':
					if(is_user_logged_in())
					{
						$value_temp = get_the_author_meta('profile_country', get_current_user_id());

						if($value_temp > 0 && is_plugin_active("mf_address/index.php"))
						{
							global $obj_address;

							$value_temp = $obj_address->get_countries_for_select()[$value_temp];
						}
					}
				break;

				case 'email':
					if(is_user_logged_in())
					{
						$user_data = get_userdata(get_current_user_id());

						$value_temp = $user_data->user_email;
					}
				break;

				/*case 'name':
					if(is_user_logged_in())
					{
						$user_data = get_userdata(get_current_user_id());

						$value_temp = $user_data->display_name;
					}
				break;*/

				case 'first_name':
					if(is_user_logged_in())
					{
						$user_data = get_userdata(get_current_user_id());

						$value_temp = $user_data->first_name;
					}
				break;

				case 'last_name':
					if(is_user_logged_in())
					{
						$user_data = get_userdata(get_current_user_id());

						$value_temp = $user_data->last_name;
					}
				break;

				//case 'tel':
				case 'telno':
					if(is_user_logged_in())
					{
						$value_temp = get_the_author_meta('profile_phone', get_current_user_id());
					}
				break;

				case 'zip':
					if(is_user_logged_in())
					{
						$value_temp = get_the_author_meta('profile_address_zipcode', get_current_user_id());
					}
				break;
			}

			if($value_temp == '')
			{
				$value_temp = apply_filters('filter_visitor_'.$arr_value[1], '');
			}

			if($value_temp != '')
			{
				$out[] = array('id' => $arr_value[0], 'value' => $value_temp);
			}
		}

		$json_output = array(
			'success' => true,
			'response_fields' => $out,
		);

		header("Content-Type: application/json");
		echo json_encode($json_output);
		die();
	}

	function api_webshop_order_update()
	{
		global $wpdb;

		$json_output = array(
			'success' => false,
		);

		$this->order_id = $this->get_cookie();

		foreach($this->arr_meta_keys as $meta_key)
		{
			$this->order_details[$meta_key] = check_var($meta_key);
		}

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = %s AND meta_value = %s WHERE post_type = %s AND post_status = %s ORDER BY post_modified DESC LIMIT 0, 1", $this->meta_prefix.'cart_hash', $this->order_id, $this->post_type_orders, 'draft'));

		if($wpdb->num_rows > 0)
		{
			$obj_encryption = new mf_encryption(__CLASS__);

			foreach($result as $r)
			{
				$post_id = $r->ID;

				$post_data = array('meta_input' => array());

				foreach($this->arr_meta_keys as $meta_key)
				{
					if($this->order_details[$meta_key] != '')
					{
						$this->order_details[$meta_key] = $obj_encryption->encrypt($this->order_details[$meta_key], md5($this->order_id));
					}

					$post_data['meta_input'][$this->meta_prefix.$meta_key] = $this->order_details[$meta_key];
				}

				$post_data['ID'] = $post_id;
				$post_data['meta_input'] = apply_filters('filter_meta_input', $post_data['meta_input'], $post_data['ID']);

				if(wp_update_post($post_data))
				{
					$json_output['success'] = true;
					$json_output['response_fields'] = [];

					/* Since the data is encrypted we can't do this. Then it has to loop through every order and decrypt the data, and then compare */
					/*if($this->order_details['address_street'] != '')
					{
						$result_address = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = %s AND meta_value = %s WHERE post_type = %s AND post_status != %s AND ID != '%d' ORDER BY post_modified ASC", $this->meta_prefix.'address_street', $this->order_details['address_street'], $this->post_type_orders, 'trash', $post_id));

						foreach($result_address as $r)
						{
							$cart_hash = get_post_meta($r->ID, $this->meta_prefix.'cart_hash', true);

							$order_address_zip = get_post_meta($r->ID, $this->meta_prefix.'address_zip', true);

							if($order_address_zip != '')
							{
								$json_output['response_fields']['address_zip'] = $obj_encryption->decrypt($order_address_zip, md5($cart_hash));
							}
						}
					}*/
				}

				else
				{
					$json_output['error'] = "Not updated";
				}
			}
		}

		else
		{
			$json_output['error'] = "No order found (".$wpdb->last_query.")";
		}

		header('Content-Type: application/json');
		echo json_encode($json_output);
		die();
	}

	function api_webshop_update_product_amount()
	{
		global $wpdb, $obj_base;

		$json_output = array(
			'success' => false,
		);

		$this->order_id = $this->get_cookie();

		$product_name = check_var('product_name');
		$product_amount = check_var('product_amount');

		list($product_rest, $product_id) = explode("_", $product_name);

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = %s AND meta_value = %s WHERE post_type = %s AND post_status = %s ORDER BY post_modified DESC LIMIT 0, 1", $this->meta_prefix.'cart_hash', $this->order_id, $this->post_type_orders, 'draft'));

		if($wpdb->num_rows > 0)
		{
			foreach($result as $r)
			{
				$arr_products = get_post_meta_or_default($r->ID, $this->meta_prefix.'products', true, []);

				foreach($arr_products as $key => $arr_value)
				{
					if(isset($arr_products[$key]['id']) && $arr_products[$key]['id'] == $product_id)
					{
						if($product_amount > 0)
						{
							$arr_products[$key]['amount'] = $product_amount;
						}

						else
						{
							unset($arr_products[$key]);
						}

						break;
					}
				}

				$arr_products = array_values($arr_products);

				$post_data = array(
					'ID' => $r->ID,
					'meta_input' => apply_filters('filter_meta_input', array(
						$this->meta_prefix.'products' => $arr_products,
					)),
				);

				if(wp_update_post($post_data) > 0)
				{
					$json_output['success'] = true;
					$json_output = $this->get_webshop_cart($json_output);
				}

				else
				{
					$json_output['error'] = "I could not update (".$wpdb->last_query.")";
				}
			}
		}

		else
		{
			$json_output['error'] = "No order found (".$wpdb->last_query.")";
		}

		header('Content-Type: application/json');
		echo json_encode($json_output);
		die();
	}

	function api_webshop_buy_button()
	{
		global $wpdb;

		$json_output = array(
			'success' => false,
		);

		$this->order_id = $this->get_cookie();
		$product_id = check_var('product_id');

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = %s AND meta_value = %s WHERE post_type = %s AND post_status = %s ORDER BY post_modified DESC LIMIT 0, 1", $this->meta_prefix.'cart_hash', $this->order_id, $this->post_type_orders, 'draft'));

		foreach($result as $r)
		{
			$arr_products = get_post_meta_or_default($r->ID, $this->meta_prefix.'products', true, []);

			foreach($arr_products as $key => $arr_value)
			{
				if(isset($arr_products[$key]['id']) && $arr_products[$key]['id'] == $product_id)
				{
					$json_output['success'] = true;
					$json_output['product_amount'] = $arr_products[$key]['amount'];
				}
			}
		}

		header('Content-Type: application/json');
		echo json_encode($json_output);
		die();
	}

	function add_to_cart($json_output, $product_id)
	{
		global $wpdb;

		$cart_post_id = apply_filters('get_block_search', 0, 'mf/webshopcart');

		$price_post_name = $this->get_post_name_for_type('price');
		$product_price = get_post_meta($product_id, $this->meta_prefix.$price_post_name, true);

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = %s AND meta_value = %s WHERE post_type = %s AND post_status = %s ORDER BY post_modified DESC LIMIT 0, 1", $this->meta_prefix.'cart_hash', $this->get_cookie(), $this->post_type_orders, 'draft'));

		if($wpdb->num_rows > 0)
		{
			foreach($result as $r)
			{
				$arr_products = get_post_meta($r->ID, $this->meta_prefix.'products', true);

				if(!is_array($arr_products))
				{
					$arr_products = [];
				}

				$amount_temp = 1;
				$was_in_array = false;

				foreach($arr_products as $key => $arr_value)
				{
					if(isset($arr_products[$key]['id']) && $arr_products[$key]['id'] == $product_id)
					{
						$amount_temp = ++$arr_products[$key]['amount'];

						$was_in_array = true;
						break;
					}
				}

				if($was_in_array == false)
				{
					$arr_products[] = array('id' => $product_id, 'price' => $product_price, 'amount' => $amount_temp);
				}

				$post_data = array(
					'ID' => $r->ID,
					'meta_input' => apply_filters('filter_meta_input', array(
						$this->meta_prefix.'products' => $arr_products,
					)),
				);

				if(wp_update_post($post_data) > 0)
				{
					$json_output['success'] = true;
					//$json_output['debug'] .= "Update: ".var_export($post_data, true);

					if($cart_post_id > 0)
					{
						$json_output['response_add_to_cart'] = array(
							'product_id' => $product_id,
							'product_amount' => $amount_temp,
							//'html' => "<a href='".get_the_permalink($cart_post_id)."' class='wp-block-button__link'>".__("In your Cart", 'lang_webshop')." <i class='fa fa-check'></i></a>",
						);
					}

					else
					{
						$json_output['response_add_to_cart'] = array(
							'product_id' => $product_id,
							'product_amount' => $amount_temp,
							//'text' => sprintf(__("Updated to %d in your cart", 'lang_webshop'), (isset($amount_temp) ? $amount_temp : 1)),
						);
					}
				}

				else
				{
					$json_output['response_add_to_cart'] = array(
						'product_id' => $product_id,
						'text' => __("Try Again", 'lang_webshop'),
						'error' => sprintf(__("I could not update your cart with %d of %s", 'lang_webshop'), 1, get_the_title($product_id)),
					);
				}
			}
		}

		else
		{
			$arr_products = [];
			$arr_products[] = array('id' => $product_id, 'price' => $product_price, 'amount' => 1);

			$post_data = array(
				'post_type' => $this->post_type_orders,
				'post_status' => 'draft',
				'post_title' => $this->get_cookie(),
				'meta_input' => apply_filters('filter_meta_input', array(
					$this->meta_prefix.'cart_hash' => $this->get_cookie(),
					$this->meta_prefix.'products' => $arr_products,
				)),
			);

			if(wp_insert_post($post_data) > 0)
			{
				$json_output['success'] = true;
				//$json_output['debug'] .= "Insert: ".var_export($post_data, true);

				if($cart_post_id > 0)
				{
					$json_output['response_add_to_cart'] = array(
						'product_id' => $product_id,
						'product_amount' => $amount_temp,
						//'html' => "<a href='".get_the_permalink($cart_post_id)."' class='wp-block-button__link'>".__("In your Cart", 'lang_webshop')." <i class='fa fa-check'></i></a>",
					);
				}

				else
				{
					$json_output['response_add_to_cart'] = array(
						'product_id' => $product_id,
						'product_amount' => $amount_temp,
						//'text' => sprintf(__("Added %d to your cart", 'lang_webshop'), 1),
					);
				}
			}

			else
			{
				$json_output['response_add_to_cart'] = array(
					'product_id' => $product_id,
					'text' => __("Try Again", 'lang_webshop'),
					'error' => sprintf(__("I could not add %d of %s to your cart", 'lang_webshop'), 1, get_the_title($product_id)),
				);
			}
		}

		return $json_output;
	}

	function api_webshop_add_to_cart()
	{
		$json_output = array(
			'success' => false,
		);

		$product_id = check_var('product_id');

		$json_output = $this->add_to_cart($json_output, $product_id);

		header('Content-Type: application/json');
		echo json_encode($json_output);
		die();
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

			if(!is_numeric($value))
			{
				$value = 1;
			}

			$value_min = ($value * ($setting_range_min_default / 100));
			$value_max = $value;
		}

		return array($value_min, $value_max);
	}

	function get_search_result_info($data)
	{
		if(!isset($data['type'])){		$data['type'] = '';}

		$out = "";

		switch($data['type'])
		{
			case 'filter':
				$text = __("Filter amongst %s products", 'lang_webshop');
			break;

			case 'matches':
				$text = __("Your search matches %s products", 'lang_webshop');
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

	/*function get_quote_button($data = [])
	{
		if(!isset($data['include'])){	$data['include'] = array('quote');}

		$out = "";

		if(get_option('setting_webshop_map_button_placement', 'above_map') == 'page_bottom')
		{
			$setting_replace_show_map = get_option_or_default('setting_webshop_replace_show_map', __("Show Map", 'lang_webshop'));
			$setting_webshop_replace_hide_map = get_option_or_default('setting_webshop_replace_hide_map', __("Hide Map", 'lang_webshop'));

			$out .= "<h2 class='is_map_toggler button'>
				<span>".$setting_replace_show_map."</span>
				<span>".$setting_webshop_replace_hide_map."</span>
			</h2>";
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

			$out .= "<a href='mailto:?subject=".$setting_webshop_share_email_subject."&body=".$setting_webshop_share_email_content."' class='show_if_results button'><i class='fa fa-envelope'></i>".$setting_webshop_replace_email_favorites."</a>";
		}

		if($out != '')
		{
			return "<div class='quote_button'>
				<div".get_form_button_classes().">"
					.$out
				."</div>
			</div>";
		}
	}*/

	function get_webshop_map($data = [])
	{
		if(!isset($data['container_class'])){	$data['container_class'] = "";}

		$setting_maps_controls = get_option_or_default('setting_maps_controls', array('search', 'fullscreen', 'zoom'));
		$setting_map_info = get_option('setting_map_info');

		$out = "<div".get_form_button_classes("webshop_map".($data['container_class'] != '' ? " ".$data['container_class'] : '')).">";

			if(get_option('setting_webshop_map_button_placement', 'above_map') == 'above_map')
			{
				$setting_replace_show_map = get_option_or_default('setting_webshop_replace_show_map', __("Show Map", 'lang_webshop'));
				$setting_webshop_replace_hide_map = get_option_or_default('setting_webshop_replace_hide_map', __("Hide Map", 'lang_webshop'));

				$out .= "<h2 class='is_map_toggler button'>
					<span>".$setting_replace_show_map."</span>
					<span>".$setting_webshop_replace_hide_map."</span>
				</h2>";
			}

			$out .= "<div class='map_wrapper'>";

				if(in_array('search', $setting_maps_controls))
				{
					$out .= show_textfield(array('name' => 'webshop_map_input', 'placeholder' => __("Search for an address and find its position", 'lang_webshop'), 'xtra' => "class='webshop_map_input'")); //, 'value' => $data['input']
				}

				$out .= "<div id='webshop_map' class='webshop_map_container'></div>";

				if($setting_map_info != '')
				{
					$out .= "<div class='webshop_map_info'>".nl2br($setting_map_info)."</div>";
				}

				$out .= input_hidden(array('name' => 'webshop_map_coordinates', 'allow_empty' => true))
				.input_hidden(array('name' => 'webshop_map_bounds', 'allow_empty' => true))
			."</div>
		</div>";

		return $out;
	}

	function get_webshop_search()
	{
		global $wpdb, $obj_font_icons;

		if(!isset($obj_font_icons))
		{
			$obj_font_icons = new mf_font_icons();
		}

		$name_choose_here = "-- ".__("Choose Here", 'lang_webshop')." --";

		$out = "<div id='webshop_search'>";

			$setting_webshop_display_sort = get_option('setting_webshop_display_sort'); //
			$setting_webshop_display_filter = get_option('setting_webshop_display_filter'); //

			if($setting_webshop_display_sort == 'yes')
			{
				$setting_webshop_display_sort = array('latest', 'random', 'alphabetical', 'size');
			}

			if(is_array($setting_webshop_display_sort) && count($setting_webshop_display_sort) > 1)
			{
				$setting_webshop_sort_default = get_option('setting_webshop_sort_default', 'alphabetical');

				$out .= show_form_alternatives(array('data' => $this->get_sort_for_select($setting_webshop_display_sort), 'name' => 'order', 'text' => __("Sort By", 'lang_webshop'), 'value' => $setting_webshop_sort_default));
			}

			if($setting_webshop_display_filter != 'no')
			{
				if($setting_webshop_display_filter == 'button')
				{
					$out .= get_toggler_container(array('type' => 'start', 'text' => __("Filter", 'lang_webshop')));
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

						$arr_attributes = [];

						if(is_array($post_document_display_on_categories) && count($post_document_display_on_categories) > 0)
						{
							$arr_attributes['condition_type'] = 'show_this_if';
							$arr_attributes['condition_selector'] = 'category';
							$arr_attributes['condition_value'] = $post_document_display_on_categories;
						}

						$custom_class = " class='".$post_custom_type.($post_custom_class != '' ? " ".$post_custom_class : "")."'";

						$symbol_tag = $obj_font_icons->get_symbol_tag(array('symbol' => $post_custom_symbol));

						if($symbol_tag != '')
						{
							$post_title = $symbol_tag."&nbsp;".$post_title;
						}

						switch($post_custom_type)
						{
							case 'checkbox':
								$out .= show_checkbox(array('name' => $post_name, 'text' => $post_title, 'value' => 1, 'compare' => isset($_REQUEST[$post_name]), 'class' => $post_custom_class, 'required' => ($post_custom_required == 'yes')));
							break;

							case 'categories':
								$out .= show_select(array('data' => $this->get_categories_for_select(array('include_on' => 'products')), 'name' => $post_name, 'text' => $post_title, 'value' => check_var($post_name, 'char'), 'class' => $post_custom_class, 'required' => ($post_custom_required == 'yes')));
							break;

							case 'categories_v2':
								$out .= show_form_alternatives(array('data' => $this->get_categories_for_select(array('include_on' => 'events', 'display_icons' => true, 'add_choose_here' => false)), 'name' => $post_name, 'value' => check_var($post_name, 'char'), 'class' => $post_custom_class." product_categories category_icon", 'required' => ($post_custom_required == 'yes')));
							break;

							case 'custom_categories':
								$post_id_temp = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_name = %s", $this->post_type_document_type, $post_name));

								$arr_data = [];
								get_post_children(array(
									'add_choose_here' => true,
									'post_type' => $this->post_type_custom_categories,
									'join' => " INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = '".$this->meta_prefix."document_type'",
									'where' => "meta_value = '".esc_sql($post_id_temp)."'",
									//'debug' => true,
								), $arr_data);

								$out .= show_select(array('data' => $arr_data, 'name' => $post_name, 'text' => $post_title, 'value' => check_var($post_name, 'char'), 'class' => $post_custom_class, 'required' => ($post_custom_required == 'yes')));
							break;

							case 'location':
								$arr_data = array(
									'' => $name_choose_here,
								);

								$location_post_name = $this->get_post_name_for_type('location');

								get_post_children(array(
									'post_type' => $this->post_type_location,
									'post_status' => 'publish',
								), $arr_data);

								// Filter those locations that aren't used
								foreach($arr_data as $key => $value)
								{
									if($key > 0)
									{
										$result = $this->get_products_from_location($key);

										if(count($result) == 0)
										{
											unset($arr_data[$key]);
										}
									}
								}

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

								$result = $this->get_list();

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

								$post_title = get_post_meta_or_default($post_id, $this->meta_prefix.'document_alt_text', true, $post_title);

								$obj_webshop_interval->add_interval_type($post_name, $post_title);

								$result = $this->get_list();

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

							case 'coordinates':
							case 'gps':
								$out .= show_textfield(array('type' => 'range', 'name' => $post_name, 'text' => __("Distance", 'lang_webshop'), 'value' => check_var($post_name, 'char'), 'xtra' => "min='0' max='500'"));
							break;

							case 'label':
								$out .= "<label".$custom_class.">".$post_title."</label>";
							break;

							case 'container_start':
								$out .= "<div".$custom_class.">";
							break;

							case 'container_end':
								$out .= "</div>";
							break;

							case 'divider':
								$out .= "<hr".$custom_class.">";
							break;

							case 'read_more_button':
							case 'overlay':
								// Do nothing
							break;

							default:
								do_log(sprintf("The type %s does not have a case", $post_custom_type)." (".$post_id." -> search)");
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

	function get_spinner_template($data)
	{
		return "<".$data['tag']." class='widget_spinner'>"
			.apply_filters('get_loading_animation', '', ['class' => $data['size']])
		."</".$data['tag'].">";
	}

	function get_templates($data)
	{
		global $obj_base;

		if(!isset($obj_base))
		{
			$obj_base = new mf_base();
		}

		if(!isset($data['button_text'])){		$data['button_text'] = '';}

		$out = $obj_base->get_templates(array('lost_connection'));

		if(!isset($this->template_used[$data['type']]) || $this->template_used[$data['type']] == false)
		{
			switch($data['type'])
			{
				case 'filter_products':
					if($data['button_text'] != '')
					{
						$filter_products_load_more_button_text = sprintf($data['button_text']." (%s)", "<%= filter_products_rest %>");
					}

					else
					{
						$filter_products_load_more_button_text = sprintf(__("Display More %s (%s)", 'lang_webshop'), __("Products", 'lang_webshop'), "<%= filter_products_rest %>");
					}

					$out .= "<script type='text/template' id='template_filter_products_spinner'>"
						.$this->get_spinner_template(array('tag' => 'li', 'size' => "fa-3x"))
					."</script>

					<script type='text/template' id='template_filter_products_message'>
						<li class='info_text'>
							<p>".sprintf(__("I could not find any %s", 'lang_webshop'), __("Products", 'lang_webshop'))."</p>
						</li>
					</script>

					<script type='text/template' id='template_filter_products_item'>
						<li class='list_item list_item_<%= product_id %><% if(category_id > 0){ %> category_<%= category_id %><% } %>'>
							<div>
								<% if(custom_category_id > 0)
								{ %>
									<div class='custom_category custom_category_<%= custom_category_id %>'></div>
								<% } %>
								<h2>";

									/*$out .= "<% if(category_icon != '')
									{ %>
										<i class='<%= category_icon %>' title='<%= category_title %>'></i>
									<% } %>";*/

									$out .= "<% if(product_url != '#')
									{ %>
										<a href='<%= product_url %>'><%= product_title %></a>
									<% }

									else
									{ %>
										<%= product_title %>
									<% } %>";

									/*$out .= "<% if(product_location != '')
									{ %>
										<span>(<%= product_location %>)</span>
									<% } %>";*/

								$out .= "</h2>";

								/*$out .= "<% if(product_address != '')
								{ %>
									<span class='location'><i class='fas fa-map-marker-alt'></i> <%= product_address %></span>
								<% } %>";*/

								$out .= "<% if(product_info != '')
								{ %>
									<p><%= product_info %></p>
								<% } %>
							</div>
							<% if(product_url != '#')
							{ %>
								<div class='list_url'>
									<a href='<%= product_url %>'>".__("Read More", 'lang_webshop')."</a>
								</div>
							<% }

							if(product_coordinates != '')
							{ %>"
								.input_hidden(array(
									'value' => "<%= product_coordinates %>",
									'xtra' => "class='map_coordinates' data-id='<%= product_id %>' data-name='<%= product_title %>'"
										."<% if(product_marker_info != '')
										{ %>"
											." data-text='<%= product_marker_info %>'"
										."<% } %>"
										."<% if(product_url != '#')
										{ %>"
											." data-url='<%= product_url %>' data-link_text='".__("Read More", 'lang_webshop')."'"
										."<% } %>"
										.(IS_ADMINISTRATOR ? " data-type='products_coordinates'" : ""),
								))
							."<% } %>
						</li>
					</script>

					<script type='text/template' id='template_filter_products_load_more'>
						<li".get_form_button_classes("widget_load_more").">"
							.show_button(array('text' => $filter_products_load_more_button_text, 'class' => "button"))
						."</li>
					</script>";
				break;

				case 'products':
					$out .= "<script type='text/template' id='template_product_message'>
						<li class='info_text'>
							<p>".__("I could not find anything that corresponded to your choices", 'lang_webshop')."</p>
						</li>
					</script>

					<script type='text/template' id='template_product_item'>
						<li id='product_<%= product_id %>'<%= (product_url != '#' ? '' : ' class=ghost') %>>
							<div class='image'".(IS_ADMINISTRATOR ? " rel='".__FUNCTION__."'" : "").">
								<a href='<%= product_url %>'>
									<%= product_image %>
								</a>
							</div>
							<div class='content'>
								<% if(product_category != '' || product_data != '')
								{ %>
									<div class='meta'>
										<% if(product_category != '')
										{ %>
											<span class='category'><%= product_category %></span>
										<% } %>

										<% if(product_data != '')
										{ %>
											<%= product_data %>
										<% } %>
									</div>
								<% } %>
								<a href='<%= product_url %>'><%= product_title %></a>
								<% if(product_location != '')
								{ %>
									<p class='product_location'><%= product_location %></p>
								<% }

								if(product_clock != '')
								{ %>
									<span class='product_clock'><%= product_clock %></span>
								<% } %>
								<% if(product_meta.length > 0)
								{ %>
									<ul class='product_meta'>
										<% _.each(product_meta, function(meta)
										{ %>
											<li class='<%= meta.class %>'>
												<%= meta.content %>
											</li>
										<% }); %>
									</ul>
								<% }

								if(product_description != '')
								{ %>
									<div class='text'>
										<%= product_description %>
									</div>
								<% } %>
								<% if(product_price != '' || product_has_read_more == true)
								{ %>
									<div class='is-layout-flex wp-block-buttons-is-layout-flex'>
										<% if(product_price != '')
										{ %>";

											$cart_post_id = apply_filters('get_block_search', 0, 'mf/webshopcart');

											if($cart_post_id > 0)
											{
												$out .= "<div class='wp-block-button cart_buttons'>
													<a href='".get_the_permalink($cart_post_id)."' class='wp-block-button__link in_cart<% if(!(product_in_cart > 0)){ %> hide<% } %>'><span>".sprintf(__("%s in Cart", 'lang_webshop'), "<span><%= product_in_cart %></span>")."</span><i class='fa fa-check'></i></a>
													<a href='#' class='wp-block-button__link add_to_cart'><span>".__("Add", 'lang_webshop')."</span><i class='fa fa-plus'></i></a>
												</div>";
											}

										$out .= "<% }

										if(product_has_read_more == true)
										{ %>
											<div class='is-style-outline wp-block-button'>
												<a href='<%= product_url %>' class='wp-block-button__link'>".__("Read More", 'lang_webshop')."</a>
											</div>
										<% } %>
									</div>
								<% } %>"
							."</div>
						</li>
					</script>";
				break;

				case 'webshop_cart':
					$search_post_id = apply_filters('get_block_search', 0, 'mf/webshopsearch');

					$setting_webshop_tax_display = get_option('setting_webshop_tax_display');

					$out .= "<script type='text/template' id='template_webshop_cart_empty'>";

						if($search_post_id > 0)
						{
							$out .= sprintf(__("You don't have any products in your cart yet. %sStart shopping%s", 'lang_webshop'), "<a href='".get_the_permalink($search_post_id)."'>", "</a>");
						}

						else
						{
							$out .= sprintf(__("You don't have any products in your cart yet", 'lang_webshop'));
						}

					$out .= "</script>

					<script type='text/template' id='template_webshop_cart_item'>
						<tr id='product_<%= id %>'>
							<td>
								<a href='<%= product_url %>'><%= product_title %></a>
							</td>
							<td><%= price %></td>";

							if($setting_webshop_tax_display == 'yes')
							{
								$out .= "<td><%= product_tax %></td>";
							}

							$out .= "<td>".show_textfield(array('type' => 'number', 'name' => 'product_amount_<%= id %>', 'value' => "<%= amount %>"))."</td>
							<td><%= product_total %></td>
							<td><i class='fa fa-trash red'></i></td>
						</tr>
					</script>";
				break;
			}

			$this->template_used[$data['type']] = true;
		}

		return $out;
	}

	function get_event_calendar()
	{
		$out = "<div class='calendar_container'>
			<div class='calendar_header'>
				<button data-month='<%= last_month %>'>&laquo;</button>
				<span><%= nice_month %></span>
				<button data-month='<%= next_month %>'>&raquo;</button>
			</div>
			<div class='calendar_days'>";

				for($i = 0; $i < 7; $i++)
				{
					$out .= "<span class='day_name'>".day_name(array('number' => ($i < 6 ? $i + 1 : 0), 'short' => true))."</span>";
				}

				$out .= "<% _.each(days, function(day)
				{ %>
					<div class='day<%= day.class %>'>
						<% if(day.event_amount > 0)
						{ %>
							<a href='#' data-date='<%= day.date %>'>
								<%= day.number %>
								<ul>
									<% _.each(day.events, function(event)
									{ %>
										<li class='<%= event.class %>'></li>
									<% }); %>
								</ul>
							</a>
						<% }

						else
						{ %>
							<span><%= day.number %></span>
						<% } %>
					</div>
				<% }); %>
			</div>
		</div>";

		return $out;
	}

	function get_transient_coordinates_from_ip()
	{
		$out = "";

		$type = 'geoplugin';
		//$type = 'ipapi';

		switch($type)
		{
			case 'ipgeolocationapi':
				$url = "https://api.ipgeolocationapi.com/geolocate/".$this->ip_temp;
			break;

			case 'geoplugin':
				$url = "http://www.geoplugin.net/json.gp?ip=".$this->ip_temp;
			break;

			case 'ipapi':
				$url = "http://ip-api.com/json/".$this->ip_temp;
			break;
		}

		list($content, $headers) = get_url_content(array(
			'url' => $url,
			'catch_head' => true,
		));

		$log_message = "I could not connect to IP Geo API";

		switch($headers['http_code'])
		{
			case 200:
				$json = json_decode($content);

				switch($type)
				{
					case 'ipgeolocationapi':
						if(isset($json->geo->latitude) && isset($json->geo->longitude))
						{
							$out = $json->geo->latitude.",".$json->geo->longitude;
						}
					break;

					case 'geoplugin':
						if(isset($json->geoplugin_latitude) && isset($json->geoplugin_longitude))
						{
							$out = $json->geoplugin_latitude.",".$json->geoplugin_longitude;
						}
					break;

					case 'ipapi':
						if(isset($json->lat) && isset($json->lon))
						{
							$out = $json->lat.",".$json->lon;
						}
					break;
				}

				do_log($log_message, 'trash');
			break;

			default:
				do_log($log_message." (Type: ".$type."): ".$headers['http_code']." (".var_export($headers, true).", ".$content.")");
			break;
		}

		return $out;
	}

	function get_transient_town_from_coordinates()
	{
		$out = "";

		$setting_gmaps_api = get_option('setting_gmaps_api');

		if($setting_gmaps_api != '')
		{
			$url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=".$this->data_temp['latitude'].",".$this->data_temp['longitude']."&sensor=false&key=".$setting_gmaps_api;

			list($content, $headers) = get_url_content(array(
				'url' => $url,
				'catch_head' => true,
			));

			switch($headers['http_code'])
			{
				case 200:
					$json = json_decode($content);

					$postal_town = $country = "";

					foreach($json->results as $json_row)
					{
						foreach($json_row->address_components as $address_component)
						{
							if(isset($address_component->types[0]) && $address_component->types[0] == 'postal_town')
							{
								$postal_town = $address_component->long_name;
							}

							if(isset($address_component->types[0]) && $address_component->types[0] == 'country')
							{
								$country = $address_component->long_name;
							}

							if($postal_town != '' && $country != '')
							{
								$out = $postal_town.", ".$country;

								break 2;
							}
						}
					}
				break;

				default:
					do_log("I could not connect to gMaps: ".$headers['http_code']." (".var_export($headers, true).", ".$content.")");
				break;
			}
		}

		return $out;
	}

	function get_town_from_coordinates($data, $out)
	{
		if($data['initial'] != false)
		{
			if($data['latitude'] == '' || $data['longitude'] == '')
			{
				if(apply_filters('get_allow_cookies', true) == true)
				{
					$this->ip_temp = get_current_visitor_ip();

					$coordinates_from_ip = get_or_set_transient(array('key' => 'coordinates_from_ip_'.$this->ip_temp, 'callback' => array($this, 'get_transient_coordinates_from_ip')));

					@list($data['latitude'], $data['longitude']) = explode(",", $coordinates_from_ip);
				}
			}

			if($data['latitude'] != '' && $data['longitude'] != '')
			{
				$data['latitude'] = round($data['latitude'], 2);
				$data['longitude'] = round($data['longitude'], 2);

				$this->data_temp = $data;
				$this->out_temp = $out;

				$out['my_location'] = get_or_set_transient(array('key' => 'town_from_coordinates_'.$data['latitude'].'_'.$data['longitude'], 'callback' => array($this, 'get_transient_town_from_coordinates')));
			}
		}

		return $out;
	}

	function get_filter_products($data)
	{
		global $wpdb, $obj_font_icons;

		if(!isset($data['id'])){			$data['id'] = "";}
		if(!isset($data['category'])){		$data['category'] = "";}
		if(!isset($data['order_by'])){		$data['order_by'] = "";}
		if(!isset($data['link_product'])){	$data['link_product'] = 'yes';}
		if(!isset($data['latitude'])){		$data['latitude'] = "";}
		if(!isset($data['longitude'])){		$data['longitude'] = "";}
		if(!isset($data['initial'])){		$data['initial'] = false;}
		if(!isset($data['limit'])){			$data['limit'] = 0;}

		if($data['category'] != 'undefined' && $data['category'] != '')
		{
			$arr_categories = explode(",", $data['category']);
		}

		else
		{
			$arr_categories = [];
		}

		$out = [];

		if(isset($data['option_type']))
		{
			$this->option_type = ($data['option_type'] != '' ? "_".$data['option_type'] : '');
		}

		if($data['id'] != '')
		{
			$out['widget_id'] = $data['id'];
			$out['filter_products_hash'] = md5(var_export($data, true).date("YmdHis"));
		}

		$out['filter_products_response'] = [];

		$query_select = $query_join = $query_where = $query_order = $query_limit = "";

		if($data['latitude'] != '' && $data['longitude'] != '' && ($data['order_by'] == 'distance' || $data['order_by'] == 'map_center'))
		{
			$query_select .= ", (6371 * acos(
				cos( radians(".$data['latitude'].") )
				* cos( radians( latitude.meta_value ) )
				* cos( radians( longitude.meta_value ) - radians(".$data['longitude'].") )
				+ sin( radians(".$data['latitude'].") )
				* sin( radians( latitude.meta_value ) )
			)) AS distance";

			$query_join .= " INNER JOIN ".$wpdb->postmeta." AS latitude ON ".$wpdb->posts.".ID = latitude.post_id AND latitude.meta_key = '".$this->meta_prefix."latitude'";
			$query_join .= " INNER JOIN ".$wpdb->postmeta." AS longitude ON ".$wpdb->posts.".ID = longitude.post_id AND longitude.meta_key = '".$this->meta_prefix."longitude'";

			$query_order .= ($query_order != '' ? ", " : " ORDER BY ")."distance ASC";
		}

		else if($data['order_by'] == 'alphabetical')
		{
			$query_order .= ($query_order != '' ? ", " : " ORDER BY ")."post_title ASC";
		}

		else //latest
		{
			$query_order .= ($query_order != '' ? ", " : " ORDER BY ")."post_modified DESC";
		}

		if(count($arr_categories) > 0)
		{
			$query_join .= " INNER JOIN ".$wpdb->postmeta." AS postmeta_category ON ".$wpdb->posts.".ID = postmeta_category.post_id";
			$query_where .= " AND postmeta_category.meta_key = '".$this->meta_prefix.'category'."' AND postmeta_category.meta_value IN('".implode("','", $arr_categories)."')";
		}

		if($data['limit'] > 0)
		{
			$query_limit = " LIMIT ".$data['limit'].", 1000";
		}

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title".$query_select." FROM ".$wpdb->posts.$query_join." WHERE post_type = %s AND post_status = %s".$query_where." GROUP BY ID".$query_order.$query_limit, $this->post_type_products, 'publish'));

		$out['filter_products_amount'] = $wpdb->num_rows;

		$i = 0;

		foreach($result as $r)
		{
			if(isset($data['amount']) && $i >= $data['amount'])
			{
				break;
			}

			$post_id = $r->ID;
			$post_title = stripslashes(stripslashes($r->post_title));
			$category_id = get_post_meta($post_id, $this->meta_prefix.'category', true);

			$custom_category_id = $product_marker_info = $post_url = $product_info = $post_address = "";

			$custom_categories = $this->get_post_name_for_type('custom_categories');

			if($custom_categories != '')
			{
				$custom_category_id = get_post_meta($post_id, $this->meta_prefix.$custom_categories, true);
			}

			if($data['link_product'] == 'yes')
			{
				$post_url = get_permalink($post_id);
			}

			$post_location = get_post_meta($post_id, $this->meta_prefix.'location', true);

			if($post_location > 0)
			{
				$post_location = get_the_title($post_location);
			}

			if(is_user_logged_in())
			{
				$result_doc_type = $wpdb->get_results($wpdb->prepare("SELECT ID, post_name FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND post_status = %s AND meta_key = %s AND meta_value = %s GROUP BY ID ORDER BY menu_order ASC", $this->post_type_document_type, 'publish', $this->meta_prefix.'document_public', 'yes'));

				foreach($result_doc_type as $r)
				{
					$post_meta_marker = $post_meta = get_post_meta($post_id, $this->meta_prefix.$r->post_name, true);

					if($post_meta != '')
					{
						$post_document_type = get_post_meta($r->ID, $this->meta_prefix.'document_type', true);
						$post_document_symbol = get_post_meta($r->ID, $this->meta_prefix.'document_symbol', true);

						$post_meta_symbol = "";

						if($post_document_symbol != '')
						{
							if(!isset($obj_font_icons))
							{
								$obj_font_icons = new mf_font_icons();
							}

							$post_meta_symbol = $obj_font_icons->get_symbol_tag(array('symbol' => $post_document_symbol))." ";
						}

						switch($post_document_type)
						{
							case 'address':
								$post_meta = "<span class='location'>".$post_meta_symbol.$post_meta."</span>";
							break;

							case 'city':
								$post_meta = "<span>".$post_meta_symbol.$post_meta."</span>";
							break;

							/*case 'email':
								$post_meta = apply_filters('the_content', "<a href='mailto:".$post_meta."'>".$post_meta_symbol.$post_meta."</a>");
							break;*/

							case 'phone':
								$post_meta_marker = "[url=".format_phone_no($post_meta)."]".$post_meta_symbol.$post_meta."[/url]";
								$post_meta = "<a href='".format_phone_no($post_meta)."'>".$post_meta_symbol.$post_meta."</a>";
							break;

							case 'url':
								$parsed_url = parse_url($post_meta);

								$post_meta_marker = "[url=".$post_meta."]".(isset($parsed_url['host']) ? str_replace("www.", "", $parsed_url['host']) : $post_meta)."[/url]";
								$post_meta = "<a href='".$post_meta."'>".($post_meta_symbol != '' ? $post_meta_symbol : (isset($parsed_url['host']) ? str_replace("www.", "", $parsed_url['host']) : $post_meta))."</a>";
							break;

							default:
								$post_meta = "";
							break;
						}

						if($post_meta != '')
						{
							$product_marker_info .= ($product_marker_info != '' ? " | " : "").$post_meta_marker;
							$product_info .= ($product_info != '' ? " | " : "").$post_meta;
						}
					}
				}
			}

			else
			{
				$address_post_name = $this->get_post_name_for_type('address');

				if($address_post_name != '')
				{
					$post_address = get_post_meta($post_id, $this->meta_prefix.$address_post_name, true);
				}

				if($post_address != '')
				{
					$product_marker_info .= ($product_info != '' ? " | " : "").$post_address;
					$product_info .= ($product_info != '' ? " | " : "")."<span class='location'><i class='fas fa-map-marker-alt'></i> ".$post_address."</span>";
				}
			}

			$out['filter_products_response'][] = array(
				'category_id' => $category_id,
				'custom_category_id' => $custom_category_id,
				'product_id' => $post_id,
				'product_title' => $post_title,
				'product_marker_info' => $product_marker_info,
				'product_url' => $post_url,
				'product_location' => $post_location,
				//'product_address' => $post_address,
				'product_info' => $product_info,
				'product_coordinates' => get_post_meta($post_id, $this->meta_prefix.'coordinates', true),
			);

			$i++;
		}

		$out = $this->get_town_from_coordinates($data, $out);

		$out['success'] = true;

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

		$query_where = "post_type = '".$this->post_type_document_type."' AND post_status = 'publish'";
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

	/*function get_search_page_url($data = [])
	{
		global $wpdb;

		if(!isset($data['location_id'])){	$data['location_id'] = 0;}

		$out = "";

		$post_id = apply_filters('get_widget_search', 'webshop-search-widget');
		//$post_id = apply_filters('get_block_search', 0, 'mf/webshop...');

		if($post_id > 0)
		{
			$out = get_permalink($post_id);

			if($data['location_id'] > 0)
			{
				$location_post_name = $this->get_post_name_for_type('location');

				if(is_array($data['location_id']))
				{
					if(isset($data['location_id'][0]))
					{
						$data['location_id'] = $data['location_id'][0];
					}

					else
					{
						do_log("Location is Array: ".var_export($data['location_id'], true));
					}
				}

				$out .= "?".$location_post_name."=".$data['location_id']."#".$location_post_name."=".$data['location_id'];
			}
		}

		return $out;
	}*/

	function get_products_from_location($id)
	{
		global $wpdb;

		$location_post_name = $this->get_post_name_for_type('location');

		if($location_post_name != '')
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_status = %s AND meta_key = %s AND meta_value = '%d' GROUP BY ID", 'publish', $this->meta_prefix.$location_post_name, $id));
		}

		else
		{
			$result = [];
		}

		return $result;
	}

	function get_post_type_info($data)
	{
		global $wpdb;

		if(!isset($data['select'])){	$data['select'] = "ID, post_name, post_title";}
		if(!isset($data['single'])){	$data['single'] = true;}

		$limit = $data['single'] == true ? " LIMIT 0, 1" : "";

		$query = $wpdb->prepare("SELECT ".$data['select']." FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND meta_key = %s AND meta_value = %s GROUP BY ID".$limit, $this->post_type_document_type, $this->meta_prefix.'document_type', $data['type']);

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
			$this->post_name_for_type[$this->option_type][$type] = $wpdb->get_var($wpdb->prepare("SELECT post_name FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND post_status = %s AND meta_key = %s AND meta_value = %s GROUP BY ID LIMIT 0, 1", $this->post_type_document_type, 'publish', $this->meta_prefix.'document_type', $type));
		}

		return $this->post_name_for_type[$this->option_type][$type];
	}

	function get_post_name_from_id($id)
	{
		global $wpdb;

		return $wpdb->get_var($wpdb->prepare("SELECT post_name FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_id = '%d' AND meta_key = %s GROUP BY ID LIMIT 0, 1", $id, $this->meta_prefix.'document_type'));
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

			$out .= $wpdb->get_var($wpdb->prepare("SELECT post_title FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE meta_key = '".$this->meta_prefix.$email_post_type."' AND meta_value = %s GROUP BY ID LIMIT 0, 1", $data['email']));
		}

		return $out;
	}

	function gather_product_meta($data)
	{
		global $obj_font_icons;

		if($data['public'] == 'yes' && (is_array($data['meta']) && count($data['meta']) > 0 || $data['meta'] != '' || in_array($data['type'], array('divider', 'heading'))))
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
			}

			$symbol_code = (isset($data['symbol']) ? $this->obj_font_icons->get_symbol_tag(array('symbol' => $data['symbol'])) : "");

			switch($data['type'])
			{
				case 'email':
					$data['meta'] = apply_filters('the_content', "<a href='mailto:".$data['meta']."'>".$data['meta']."</a>");

					$data['meta'] = str_replace(array("<p>", "</p>"), "", $data['meta']);
				break;

				case 'phone':
					$data['meta'] = "<a href='".format_phone_no($data['meta'])."'>".$data['meta']."</a>";
				break;

				case 'url':
					$meta_original = $data['meta'];
					$url_parts = parse_url($data['meta']);

					$data['meta'] = "<a href='".$data['meta']."'>".str_replace("www.", "", $url_parts['host'])."</a>";

					if(!isset($url_parts['host']))
					{
						do_log("host does not exist (".$meta_original." -> ".var_export($url_parts, true).")");
					}
				break;
			}

			switch($data['type'])
			{
				case 'categories':
					if(is_array($data['meta']))
					{
						if(!isset($obj_font_icons))
						{
							$obj_font_icons = new mf_font_icons();
						}

						$content = "<span title='".$data['title']."'>";

							$i = 0;

							foreach($data['meta'] as $category_id)
							{
								if(is_array($category_id) && isset($category_id[0]))
								{
									$category_id = $category_id[0];
								}

								$category_title = get_the_title($category_id);

								$content .= ($i > 0 ? ", " : "").$category_title;

								$category_icon = get_post_meta($category_id, $this->meta_prefix.'category_icon', true);

								$this->product_categories .= "<span>"
									.$obj_font_icons->get_symbol_tag(array(
										'symbol' => $category_icon,
										'class' => "category_".$category_id,
									))
									.$category_title
								."</span>";

								$i++;
							}

						$content .= "</span>";
					}
				break;

				case 'read_more_button':
					$content = $data['meta'];
				break;

				/*case 'container_start':
					$content = "<ul>";
				break;

				case 'container_start':
					$content = "</ul>";
				break;*/

				case 'divider':
					$content = "<hr>";
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

				case 'location':
					$content = $data['meta'];
				break;

				case 'heading':
					$content = "<h3>".$symbol_code.$data['title']."</h3>";
				break;

				default:
					$content = "<span title='".$data['title']."'>".$symbol_code.$data['title']."</span><span>".$data['meta']."</span>";
				break;
			}

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

		$this->product_meta = [];

		$this->product_id = $post->ID;
		$this->product_title = $post->post_title;

		if($post->post_excerpt != '')
		{
			$this->product_description = $post->post_excerpt;
		}

		else
		{
			$this->product_description = shorten_text(array('string' => strip_tags($post->post_content), 'limit' => 120));
		}

		$this->product_has_content = $this->product_has_read_more = false;
		$this->product_price = $this->product_image = $this->arr_category_id = '';
		$this->product_url = "#";

		if($data['single'] == true)
		{
			$this->product_has_content = true;
			$this->product_content = apply_filters('the_content', $post->post_content);
		}

		else if($post->post_content != '')
		{
			$this->product_has_content = true;
			$this->product_url = get_permalink($this->product_id);
		}

		$this->product_image = get_the_post_thumbnail_url($this->product_id, 'large'); // medium / large / full

		if($this->product_image == '')
		{
			$this->product_image = get_post_meta_file_src(array('post_id' => $this->product_id, 'meta_key' => $this->meta_prefix.'product_image', 'image_size' => 'large', 'single' => $data['single_image']));
		}

		$this->show_in_result = true;
		$this->product_has_email = false;
		$this->number_amount = $this->price_amount = $this->size_amount = 0;

		$this->product_address = $this->product_categories = $this->product_map = $this->product_coordinates = $this->product_social = $this->search_url = "";

		if($data['single'] == true)
		{
			$this->product_form_buy = "";
			$this->arr_product_property = $this->arr_product_quick = [];
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

		$this->meta_title = get_post_meta_or_default($this->meta_id, $this->meta_prefix.'document_alt_text', true, $this->meta_title);
		$this->meta_symbol = get_post_meta($this->meta_id, $this->meta_prefix.'document_symbol', true);
	}

	function get_product_in_cart()
	{
		global $wpdb;

		$out = 0;

		if($this->product_id > 0 && $this->get_cookie() != '')
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = %s AND meta_value = %s WHERE post_type = %s AND post_status = %s ORDER BY post_modified DESC LIMIT 0, 1", $this->meta_prefix.'cart_hash', $this->get_cookie(), $this->post_type_orders, 'draft'));

			foreach($result as $r)
			{
				$arr_products = get_post_meta($r->ID, $this->meta_prefix.'products', true);

				if(is_array($arr_products))
				{
					foreach($arr_products as $key => $arr_value)
					{
						if($arr_products[$key]['id'] == $this->product_id)
						{
							$out = $arr_products[$key]['amount'];
							break;
						}
					}
				}
			}
		}

		return $out;
	}

	function get_product_data($data, &$json_output)
	{
		global $obj_form;

		$is_single = false;

		$this->product_init(array('post' => $data['product'], 'single' => $is_single, 'single_image' => $data['single_image']));

		foreach($this->result as $r)
		{
			$this->meta_init(array('meta' => $r, 'single' => $is_single));

			$post_search = check_var($this->meta_name, 'char');

			$post_meta = '';

			switch($this->meta_type)
			{
				case 'categories':
				case 'categories_v2':
					$this->arr_category_id = $post_meta = get_post_meta($this->product_id, $this->meta_prefix.'category', false);

					if($post_search != '' && !in_array($post_search, $post_meta))
					{
						$this->show_in_result = false;

						break;
					}
				break;

				case 'file_advanced':
					$post_meta = get_post_meta_file_src(array('post_id' => $this->meta_id, 'meta_key' => $this->meta_prefix.$this->meta_name, 'is_image' => false));
				break;

				case 'location':
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

						$str_locations = "";

						foreach($post_meta as $location_id) //$this->sort_location(array('array' => $post_meta, 'reverse' => true))
						{
							$str_locations .= ($str_locations != '' ? ", " : "").get_the_title($location_id);
						}

						if($data['show_location_in_data'] == true)
						{
							$this->product_data .= "<span class='".$this->meta_type."'>".$str_locations."</span>";
						}

						else
						{
							if($this->meta_public == 'no')
							{
								$this->product_location .= ($this->product_location != '' ? ", " : "")."<span class='".$this->meta_type."'>".$str_locations."</span>";
							}

							else
							{
								$post_meta = $str_locations;
							}
						}

						if($this->meta_public == 'no')
						{
							$post_meta = "";
						}
					}
				break;

				case 'read_more_button':
					if($this->product_has_content && $this->product_url != "#")
					{
						$this->product_has_read_more = true;

						/*$post_meta = "<div".get_form_button_classes().">
							<a href='".$this->product_url."' class='button'>".$this->meta_title."</a>
						</div>";*/
					}
				break;

				default:
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
										$this->meta_symbol = $this->obj_font_icons->get_symbol_tag(array('symbol' => $this->meta_symbol));
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

							case 'coordinates':
								$this->product_coordinates .= $post_meta;
								$this->product_map = $post_meta;

								$post_meta = "";
							break;

							case 'email':
								$this->product_has_email = true;
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
								if($this->meta_type == 'price' && $post_meta != '')
								{
									$this->product_price = $post_meta;
								}

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
											$this->product_data .= $this->obj_font_icons->get_symbol_tag(array('symbol' => $this->meta_symbol, 'title' => $this->meta_title));
										}

										else
										{
											$this->product_data .= $this->meta_title;
										}

										$this->product_data .= "&nbsp;";

										if($this->meta_type == 'price')
										{
											$this->product_data .= $this->display_price(array('price' => $post_meta));
										}

										else
										{
											$this->product_data .= $post_meta;
										}

									$this->product_data .= "</span>";

									$post_meta = "";
								}
							break;

							case 'page':
								$this->meta_title = get_the_title($post_meta);

								$post_meta = get_permalink($post_meta);
							break;

							case 'content':
							case 'description':
							case 'textarea':
								$this->product_has_content = true;

								if($this->product_url == '#')
								{
									$this->product_url = get_permalink($this->product_id);
								}
							break;

							case 'ghost':
							case 'overlay':
							case 'phone':
							case 'social':
							case 'text':
							case 'url':
								//Do nothing
							break;

							default:
								$arr_filtered_meta_type = apply_filters('filter_webshop_meta_type', array('page' => 'list', 'meta_type' => $this->meta_type, 'post_meta' => $post_meta, 'meta_type_found' => false));

								if($arr_filtered_meta_type['meta_type_found'])
								{
									$post_meta = $arr_filtered_meta_type['post_meta'];
								}

								else
								{
									do_log(sprintf("The type %s does not have a case", $this->meta_type)." (Product: ".$this->product_id." -> Meta: ".$this->meta_id." -> list)");
								}
							break;
						}
					}
				break;
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
				$this->product_url = '#';
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

			/*else if(is_array($this->arr_category_id) && count($this->arr_category_id) > 0)
			{
				$product_image = "<div class='category_icon'>";

					foreach($this->arr_category_id as $category_id)
					{
						$category_icon = get_post_meta($category_id, $this->meta_prefix.'category_icon', true);

						$product_image .= $this->obj_font_icons->get_symbol_tag(array('symbol' => $category_icon, 'title' => get_the_title($category_id), 'class' => "category_".$category_id));
					}

				$product_image .= "</div>";
			}*/

			else
			{
				$product_image = apply_filters('get_image_fallback', "");
			}

			$product_category = "";

			$arr_categories = get_post_meta($this->product_id, $this->meta_prefix.'category', false);

			foreach($arr_categories as $category_id)
			{
				$product_category .= ($product_category != '' ? ", " : "").get_the_title($category_id);
			}

			$json_output['product_response'][] = array(
				'product_id' => $this->product_id,
				'product_title' => $this->product_title,
				'product_clock' => ($this->product_clock),
				'product_address' => $this->product_address,
				'product_data' => $this->product_data,
				'product_category' => $product_category,
				'product_location' => $this->product_location,
				'product_url' => $this->product_url,
				'product_price' => $this->product_price,
				'product_in_cart' => $this->get_product_in_cart(),
				'product_has_read_more' => $this->product_has_read_more,
				'product_image' => $product_image,
				'product_meta' => $this->product_meta,
				'product_description' => apply_filters('the_content', $this->product_description),
				'product_has_email' => $this->product_has_email,
				'product_map' => $this->product_map,
				'product_timestamp' => date("Y-m-d H:i:s"),
			);
		}
	}

	function get_distance($coordinates_1, $coordinates_2)
	{
		list($lat_1, $lon_1) = $this->get_lat_long_from_coordinates($coordinates_1);
		list($lat_2, $lon_2) = $this->get_lat_long_from_coordinates($coordinates_2);

		/* v1 */
		/*$earth_radius = 6371000; //meters and your distance will be in meters

		$p1 = ($lon_1 - $lon_2) * cos(.5 * ($lat_1 + $lat_2)); //convert lat/lon to radians
		$p2 = ($lat_1 - $lat_2);
		$distance_1 = $earth_radius * sqrt($p1 * $p1 + $p2 * $p2);*/

		/* v2 */
		//$distance_2 = acos(sin($lat_1) * sin($lat_2) + cos($lat_1) * cos($lat_2) * cos($lon_1 - $lon_2));

		/* v3 */
		$theta = $lon_1 - $lon_2;
		$dist = sin(deg2rad($lat_1)) * sin(deg2rad($lat_2)) + cos(deg2rad($lat_1)) * cos(deg2rad($lat_2)) * cos(deg2rad($theta));
		$dist = acos($dist);
		$dist = rad2deg($dist);
		$distance = $dist * 60 * 1.1515 * 1.609344; //kilometers

		return $distance;
	}

	function get_lat_long_from_coordinates($coordinates)
	{
		return explode(", ", str_replace(array("(", ")"), "", $coordinates));
	}

	/*function insert_sent($data)
	{
		global $wpdb;

		$wpdb->get_results($wpdb->prepare("SELECT productID FROM ".$wpdb->prefix."webshop_sent WHERE productID = '%d' AND answerID = '%d' LIMIT 0, 1", $data['product_id'], $data['answer_id']));

		if($wpdb->num_rows == 0)
		{
			$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."webshop_sent SET productID = '%d', answerID = '%d'", $data['product_id'], $data['answer_id']));
		}
	}*/

	/*function get_widget_list($instance, $result, $rows)
	{
		$out = "<div>"
			."<ul class='webshop_item_list".($instance['webshop_show_info'] == 'yes' ? "" : " expand_image_container")." text_columns ".($rows % 3 == 0 || $rows > 4 ? "columns_3" : "columns_2")."'>";

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

						$arr_product = [];

						$this->get_product_data(array('product' => $r, 'single_image' => true, 'show_location_in_data' => true), $arr_product);

						if(isset($arr_product['product_response']))
						{
							$arr_product = $arr_product['product_response'][0];

							$out .= "<li>
								<div class='image'".(IS_ADMINISTRATOR ? " rel='".__FUNCTION__."'" : "").">";

									if(is_user_logged_in() && is_plugin_active("mf_slideshow/index.php"))
									{
										global $obj_slideshow;

										if(!isset($obj_slideshow))
										{
											$obj_slideshow = new mf_slideshow();
										}

										$arr_product_image = get_post_meta_file_src(array('post_id' => $post_id, 'meta_key' => $this->meta_prefix.'product_image', 'image_size' => 'large', 'single' => false));

										$out .= "<div class='product_slideshow'>"
											.$obj_slideshow->render_slides(array(
												'images' => $arr_product_image,
											))
										."</div>";
									}

									else
									{
										$out .= "<a href='".$arr_product['product_url']."'>"
											.$arr_product['product_image']
										."</a>";
									}

									if($arr_product['product_data'] != '')
									{
										$out .= "<div class='product_data'>".$arr_product['product_data']."</div>";
									}

								$out .= "</div>";

								if($instance['webshop_show_info'] == 'yes')
								{
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
														$out .= "<span>".get_the_title($value)."</span>";
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
	}*/
}

if(class_exists('mf_import'))
{
	class mf_webshop_import extends mf_import
	{
		var $obj_webshop;
		var $prefix;
		var $table = "posts";
		var $post_type;
		var $arr_actions = array('import');
		var $columns = [];
		var $arr_type = array(
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
		var $unique_columns = array(
			'post_title',
		);

		function get_defaults()
		{
			global $wpdb;

			$this->obj_webshop = new mf_webshop();

			$this->prefix = $wpdb->base_prefix;
			$this->post_type = $this->obj_webshop->post_type_products;
			$this->columns = array(
				'post_title' => __("Title", 'lang_webshop'),
				'post_content' => __("Content", 'lang_webshop'),
			);

			foreach($this->arr_type as $type)
			{
				$result = $this->obj_webshop->get_post_type_info(array('type' => $type, 'single' => false));

				foreach($result as $r)
				{
					$this->columns[$r->post_name] = $r->post_title;
				}
			}
		}

		function get_external_value(&$strRowField, &$value)
		{
			$saved_option = false;

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
}

if(class_exists('RWMB_Field') && class_exists('RWMB_Text_Field'))
{
	class RWMB__Field extends RWMB_Field{}

	class RWMB_Address_Field extends RWMB_Text_Field
	{
		static public function html($meta, $field)
		{
			return "<input type='text' name='".$field['field_name']."' id='".$field['id']."' value='".$meta."' class='rwmb-text rwmb-address maps_location'>";
		}
	}

	/*class RWMB_Categories_Field extends RWMB_Field
	{
		static public function html($meta, $field)
		{
			//Do nothing here since this is shown in the UI for mf_products if there are any mf_categories
		}
	}*/

	class RWMB_Coordinates_Field extends RWMB_Text_Field
	{
		static public function html($meta, $field)
		{
			return input_hidden(array('name' => $field['field_name'], 'value' => $meta, 'xtra' => "class='maps_coordinates'"));
		}
	}

	class RWMB_Custom_Categories_Field extends RWMB_Text_Field
	{
		static public function html($meta, $field)
		{
			global $wpdb, $obj_webshop;

			if(!isset($obj_webshop))
			{
				$obj_webshop = new mf_webshop();
			}

			$post_name = str_replace($obj_webshop->meta_prefix, "", $field['id']);
			$post_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_name = %s", $obj_webshop->post_type_document_type, $post_name));

			$arr_data = [];
			get_post_children(array(
				'add_choose_here' => true,
				'post_type' => $obj_webshop->post_type_custom_categories,
				'join' => " INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = '".$obj_webshop->meta_prefix."document_type'",
				'where' => "meta_value = '".esc_sql($post_id)."'",
				//'debug' => true,
			), $arr_data);

			return show_select(array('data' => $arr_data, 'name' => $field['field_name'], 'value' => $meta, 'class' => "rwmb-select-wrapper", 'xtra' => self::render_attributes($field['attributes']))); //, 'required' => true
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
			if(is_plugin_active("mf_calendar/index.php"))
			{
				global $obj_calendar;

				if(!isset($obj_calendar))
				{
					$obj_calendar = new mf_calendar();
				}

				$arr_data = [];
				get_post_children(array('add_choose_here' => true, 'post_type' => $obj_calendar->post_type), $arr_data);

				return show_select(array('data' => $arr_data, 'name' => $field['field_name'], 'value' => $meta, 'class' => "rwmb-select-wrapper", 'suffix' => get_option_page_suffix(array('post_type' => $obj_calendar->post_type, 'value' => $meta)), 'xtra' => self::render_attributes($field['attributes'])));
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
			return "<input type='text' name='".$field['field_name']."' id='".$field['id']."' value='".$meta."' class='rwmb-text rwmb-local_address maps_location'>";
		}
	}

	class RWMB_Location_Field extends RWMB_Select_Field
	{
		public static function html($meta, $field)
		{
			$options = self::transform_options($field['options']);
			$attributes = self::call('get_attributes', $field, $meta);
			$attributes['data-selected'] = $meta;
			$walker = new RWMB_Walker_Select( $field, $meta );

			$attributes['class'] .= " mf_form_field multiselect";

			do_action('init_multiselect');

			$output = sprintf(
				"<select %s>",
				self::render_attributes($attributes)
			);

				if(!$field['multiple'] && $field['placeholder'])
				{
					$output .= "<option value=''>".esc_html($field['placeholder'])."</option>";
				}

				$output .= $walker->walk($options, $field['flatten'] ? -1 : 0)
			."</select>"
			.self::get_select_all_html($field);

			return $output;
		}
	}

	class RWMB_Overlay_Field extends RWMB_Page_Field{}

	/*class RWMB_Overlay_Field extends RWMB_Textarea_Field
	{
		static public function html($meta, $field)
		{
			$attributes = self::get_attributes($field, $meta);

			return sprintf("<textarea %s>%s</textarea>", self::render_attributes($attributes), $meta);
			//return show_textarea(array('name' => $field['field_name'], 'value' => $meta, 'class' => "rwmb-content large-text", 'xtra' => self::render_attributes($field['attributes'])));
		}
	}*/

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
				if(is_plugin_active("mf_social_feed/index.php"))
				{
					global $obj_social_feed;

					if(!isset($obj_social_feed))
					{
						$obj_social_feed = new mf_social_feed();
					}

					$arr_data = [];
					get_post_children(array('add_choose_here' => true, 'post_type' => $obj_social_feed->post_type), $arr_data);

					return show_select(array('data' => $arr_data, 'name' => $field['field_name'], 'value' => $meta, 'class' => "rwmb-select-wrapper", 'suffix' => get_option_page_suffix(array('post_type' => $obj_social_feed->post_type, 'value' => $meta)), 'xtra' => self::render_attributes($field['attributes'])));
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