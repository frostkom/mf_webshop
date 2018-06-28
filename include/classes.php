<?php

class mf_webshop
{
	function __construct()
	{
		$this->meta_prefix = "mf_ws_";

		$this->range_min = $this->range_max = "";
		$this->interval_amount = $this->interval_count = 0;
		$this->arr_interval_type_data = $this->post_name_for_type = array();

		// Needs to be here because Poedit does not pick up this from below
		$arr_localize = array(
			__("Show all", 'lang_webshop'),
		);
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

		wp_enqueue_script('script_gmaps_api', "//maps.googleapis.com/maps/api/js?v=3.exp&libraries=places&key=".$setting_gmaps_api, $plugin_version);
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
			'symbol_inactive' => get_option_or_default('setting_webshop_symbol_inactive_image', get_map_marker_url('setting_webshop_symbol_inactive')),
			'symbol_active' => get_option_or_default('setting_webshop_symbol_active_image', get_map_marker_url('setting_webshop_symbol_active')),
			'ghost_inactive' => get_option('setting_ghost_inactive_image'),
			'ghost_active' => get_option('setting_ghost_active_image'),
			'search_max' => get_option_or_default('setting_search_max', 50),
			'show_all_min' => get_option_or_default('setting_show_all_min', 30),
			'require_search' => get_option('setting_require_search'),
		), $plugin_version);
		mf_enqueue_script('script_base_init', $plugin_base_include_url."backbone/bb.init.js", $plugin_version);
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

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = 'mf_location' AND post_parent = %s AND ID IN('".implode("','", $data['array'])."')", $data['parent']));

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
		$arr_types = array(
			'group_information' => "-- ".__("Information", 'lang_webshop')." --",
				'description' => __("Description", 'lang_webshop'),
				'heading' => __("Heading", 'lang_webshop'),
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

		//$arr_types = array_sort(array('array' => $arr_types, 'on' => 0, 'order' => 'asc', 'keep_index' => true));

		return $arr_types;
	}

	/* Admin */
	function admin_menu()
	{
		global $wpdb;

		$menu_root = 'mf_webshop/';
		$menu_start = "edit.php?post_type=mf_products";
		$menu_capability = override_capability(array('page' => $menu_start, 'default' => 'edit_posts'));

		$name_webshop = get_option_or_default('setting_webshop_replace_webshop', __("Webshop", 'lang_webshop'));

		if(IS_EDITOR)
		{
			$name_products = get_option_or_default('setting_webshop_replace_products', __("Products", 'lang_webshop'));
			$name_categories = get_option_or_default('setting_webshop_replace_categories', __("Categories", 'lang_webshop'));
			$name_doc_types = get_option_or_default('setting_webshop_replace_doc_types', __("Document Types", 'lang_webshop'));

			$name_location = __("Location", 'lang_webshop');
			$name_orders = __("Orders", 'lang_webshop');
			$name_customers = __("Customers", 'lang_webshop');
			$name_delivery_type = __("Delivery Type", 'lang_webshop');

			$location_post_name = $this->get_post_name_for_type('location');
			$price_post_name = $this->get_post_name_for_type('price');
			$email_post_name = $this->get_post_name_for_type('email');

			$count_message = "";

			$result = $wpdb->get_results("SELECT orderID FROM ".$wpdb->prefix."webshop_order LIMIT 0, 1");
			$rows_order = $wpdb->num_rows;

			$result = $wpdb->get_results("SELECT answerID FROM ".$wpdb->prefix."webshop_sent LIMIT 0, 1");
			$rows_stats = $wpdb->num_rows;

			if($rows_order > 0)
			{
				$count_message = count_orders_webshop();
			}

			add_menu_page($name_webshop, $name_webshop.$count_message, $menu_capability, $menu_start, '', 'dashicons-cart', 21);

			add_submenu_page($menu_start, $name_products, $name_products, $menu_capability, $menu_start);

			add_submenu_page($menu_start, $name_categories, $name_categories, $menu_capability, "edit.php?post_type=mf_categories");
			add_submenu_page($menu_start, $name_doc_types, $name_doc_types, $menu_capability, "edit.php?post_type=mf_document_type");

			if($location_post_name != '')
			{
				add_submenu_page($menu_start, $name_location, $name_location, $menu_capability, "edit.php?post_type=mf_location");
			}

			if($rows_order > 0)
			{
				add_submenu_page($menu_start, $name_orders, $name_orders.$count_message, $menu_capability, $menu_root.'orders/index.php');
			}

			if($price_post_name != '')
			{
				add_submenu_page($menu_start, $name_customers, $name_customers, $menu_capability, "edit.php?post_type=mf_customers");
				add_submenu_page($menu_start, $name_delivery_type, $name_delivery_type, $menu_capability, "edit.php?post_type=mf_delivery_type");
			}

			$menu_title = __("Import", 'lang_webshop');

			add_submenu_page($menu_start, $menu_title, $menu_title." ".strtolower($name_products), $menu_capability, $menu_root.'import/index.php');

			if($rows_stats > 0)
			{
				$menu_title = __("Statistics", 'lang_webshop');

				add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_root.'stats/index.php');
			}

			if($email_post_name != '' && is_plugin_active("mf_group/index.php"))
			{
				$menu_title = __("Send e-mail to all", 'lang_webshop')." ".strtolower($name_products);

				add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_root.'group/index.php');
			}
		}

		else
		{
			add_menu_page($name_webshop, $name_webshop, $menu_capability, $menu_start, '', 'dashicons-cart', 21);
		}
	}

	/* Public */
	function get_webshop_search()
	{
		global $wpdb;

		$out = "";

		$obj_font_icons = new mf_font_icons();

		$name_choose_here = "-- ".__("Choose Here", 'lang_webshop')." --";

		$out .= "<div id='webshop_search'>";

			$setting_webshop_display_sort = get_option('setting_webshop_display_sort');
			$setting_webshop_display_filter = get_option('setting_webshop_display_filter');

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

								get_post_children(array('post_type' => 'mf_categories', 'add_choose_here' => true, 'post_status' => 'publish'), $arr_data);

								$out .= show_select(array('data' => $arr_data, 'name' => $post_name, 'text' => $post_title, 'value' => check_var($post_name, 'char'), 'class' => $post_custom_class, 'required' => ($post_custom_required == 'yes')));
							break;

							case 'location':
								$arr_data = array(
									'' => $name_choose_here,
								);

								get_post_children(array('post_type' => 'mf_location', 'post_status' => 'publish'), $arr_data);

								$out .= show_select(array('data' => $arr_data, 'name' => $post_name, 'text' => $post_title, 'value' => check_var($post_name, 'char'), 'class' => $post_custom_class, 'required' => ($post_custom_required == 'yes')));
							break;

							case 'number':
							case 'price':
							case 'size':
							case 'address':
							case 'local_address':
								$is_numeric = in_array($post_custom_type, array('number', 'price', 'size'));

								//$obj_webshop = new mf_webshop();

								$arr_data = array(
									'' => $name_choose_here,
								);

								$result = $wpdb->get_results("SELECT ID FROM ".$wpdb->posts." WHERE post_type = 'mf_products' AND post_status = 'publish'");

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

								$out .= show_select(array('data' => $arr_data, 'name' => $post_name, 'text' => $post_title, 'value' => check_var($post_name, 'char'), 'class' => $post_custom_class, 'required' => ($post_custom_required == 'yes')));
							break;

							case 'interval':
								$obj_webshop_interval->increase_count();

								$post_document_alt_text = get_post_meta($post_id, $this->meta_prefix.'document_alt_text', true);

								if($post_document_alt_text != '')
								{
									$post_title = $post_document_alt_text;
								}

								$obj_webshop_interval->add_interval_type($post_name, $post_title);

								$result = $wpdb->get_results("SELECT ID FROM ".$wpdb->posts." WHERE post_type = 'mf_products' AND post_status = 'publish'");

								foreach($result as $r)
								{
									$page_id = $r->ID;

									$post_meta = get_post_meta($page_id, $this->meta_prefix.$post_name, true);

									list($post_meta_min, $post_meta_max) = get_interval_min($post_meta);

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

		$out .= "</div>";

		return $out;
	}

	function get_form_fields_passthru()
	{
		$out = "";

		$setting_quote_form = get_option('setting_quote_form');

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
		$name_choose = get_option_or_default('setting_webshop_replace_choose_product', __("Choose", 'lang_webshop'));

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
					<% }

						if(product_image != '')
						{ %>
							<img src='<%= product_image %>' alt='<%= product_title %>'>
						<% }

						else
						{ %>"
							.get_image_fallback()
						."<% }

					if(product_url != '')
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
				<% } %>

				<% if(product_has_email == 1 || 1 == 1)
				{ %>"
					.show_checkbox(array('name' => "products[]", 'value' => '<%= product_id %>', 'compare' => 'disabled', 'text' => $name_choose, 'switch' => true, 'switch_icon_on' => get_option('setting_webshop_switch_icon_on'), 'switch_icon_off' => get_option('setting_webshop_switch_icon_off'), 'xtra_class' => 'color_button_2')) //, 'compare' => '<%= product_id %>' //This makes it checked by default
				."<% } %>
				<a href='<%= product_url %>' class='product_link product_column'>".__("Read More", 'lang_webshop')."&hellip;</a>"
				.input_hidden(array('value' => "<%= product_map %>", 'xtra' => "class='map_coords' data-id='<%= product_id %>' data-name='<%= product_title %>' data-url='<%= product_url %>'"))
				//."<div class='clear'></div>"
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

		$query_where = "post_type = 'mf_document_type' AND post_status = 'publish'";
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

		if(!isset($this->post_name_for_type[$type]))
		{
			$this->post_name_for_type[$type] = $wpdb->get_var($wpdb->prepare("SELECT post_name FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE meta_key = '".$this->meta_prefix."document_type' AND meta_value = %s LIMIT 0, 1", $type));
		}

		return $this->post_name_for_type[$type];
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
		if($data['public'] == "yes" && ($data['meta'] != '' || $data['type'] == 'heading'))
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

				case 'description':
				case 'textarea':

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

		$obj_webshop = new mf_webshop();

		$has_interval = $has_number = false;

		$this->result = $obj_webshop->get_document_types(array('select' => "ID, post_status, post_title, post_name", 'order' => "menu_order ASC"));

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

			if($post_custom_public == "yes")
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
				list($this->interval_range_min, $this->interval_range_max) = get_interval_min($this->interval_range);
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
								list($post_meta_min, $post_meta_max) = get_interval_min($post_meta);

								if(!is_between(array('value' => array($post_meta_min, $post_meta_max), 'compare' => array($this->interval_range_min, $this->interval_range_max))))
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

									if(is_between(array('value' => array($post_meta), 'compare' => array($post_search_min, $post_search_max))))
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

						/*case 'page':
							if($post_meta != '')
							{
								$this->meta_title = get_the_title($post_meta);
								$post_meta = get_permalink($post_meta);
							}
						break;*/

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
				/*$this->product_title = get_option_or_default('setting_ghost_title', __("Hidden", 'lang_webshop'));
				$this->product_image = get_option('setting_ghost_image');*/
				$this->product_meta = array(
					array(
						'class' => 'description',
						//'content' => get_option_or_default('setting_ghost_text', __("This is hidden", 'lang_webshop')),
						'content' => $this->product_description,
					)
				);
			}

			/*if($this->product_image == '')
			{
				$this->product_image = get_option('setting_product_default_image');
			}*/

			$json_output['product_response'][] = array(
				'product_id' => $this->product_id,
				'product_title' => $this->product_title,
				'product_clock' => ($this->product_clock),
				'product_address' => $this->product_address,
				'product_data' => $this->product_data,
				'product_location' => $this->product_location,
				'product_url' => $this->product_url,
				'product_image' => ($this->product_image != '' ? $this->product_image : ''), /* Otherwise null passes this and JS does not like that */
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
									<a href='".$arr_product['product_url']."'>";

										if($arr_product['product_image'] != '')
										{
											$out .= "<img src='".$arr_product['product_image']."' alt='".$arr_product['product_title']."'>";
										}

										else
										{
											$out .= get_image_fallback();
										}

									$out .= "</a>";

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
}

class mf_webshop_import extends mf_import
{
	function get_defaults()
	{
		global $wpdb;

		$this->prefix = $wpdb->base_prefix;
		$this->table = "posts";
		$this->post_type = "mf_products";
		$this->actions = array('import');
		$this->columns = array(
			'post_title' => __("Title", 'lang_webshop'),
			'post_content' => __("Content", 'lang_webshop'),
		);

		$obj_webshop = new mf_webshop();

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
			$result = $obj_webshop->get_post_type_info(array('type' => $type, 'single' => false));

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

		$obj_webshop = new mf_webshop();

		foreach($this->arr_type as $type)
		{
			$result = $obj_webshop->get_post_type_info(array('type' => $type, 'single' => false));

			foreach($result as $r)
			{
				if($strRowField == $r->post_name)
				{
					$this->query_option[$obj_webshop->meta_prefix.$r->post_name] = $value;

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

	class RWMB_Description_Field extends RWMB_Textarea_Field{}

	class RWMB_Address_Field extends RWMB_Text_Field
	{
		static public function html($meta, $field)
		{
			return "<input type='text' name='".$field['field_name']."' id='".$field['id']."' value='".$meta."' class='rwmb-text rwmb-address'>";
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

				return show_select(array('data' => $arr_data, 'name' => $field['field_name'], 'value' => $meta, 'class' => "rwmb-select-wrapper", 'suffix' => "<a href='".admin_url("post-new.php?post_type=mf_calendar")."'><i class='fa fa-lg fa-plus'></i></a>", 'xtra' => self::render_attributes($field['attributes'])));
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

	class RWMB_Local_Address_Field extends RWMB_Text_Field
	{
		static public function html($meta, $field)
		{
			return "<input type='text' name='".$field['field_name']."' id='".$field['id']."' value='".$meta."' class='rwmb-text rwmb-local_address'>";
		}
	}

	class RWMB_Location_Field extends RWMB_Select_Field{}

	class RWMB_Categories_Field extends RWMB_Field
	{
		static public function html($meta, $field)
		{
			//Do nothing here since this is shown in the UI for mf_products if there are any mf_categories
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

					return show_select(array('data' => $arr_data, 'name' => $field['field_name'], 'value' => $meta, 'class' => "rwmb-select-wrapper", 'suffix' => "<a href='".admin_url("post-new.php?post_type=mf_social_feed")."'><i class='fa fa-lg fa-plus'></i></a>", 'xtra' => self::render_attributes($field['attributes'])));
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

		$name_webshop = get_option_or_default('setting_webshop_replace_webshop', __("Webshop", 'lang_webshop'));
		$this->name_products = get_option_or_default('setting_webshop_replace_products', __("Products", 'lang_webshop'));

		parent::__construct('webshop-widget', __("Form", 'lang_webshop').($name_webshop != '' ? " (".$name_webshop.")" : ""), $widget_ops);

		$this->meta_prefix = "mf_ws_";

		$this->name_doc_types = get_option_or_default('setting_webshop_replace_doc_types', __("Document Types", 'lang_webshop'));
	}

	function get_doc_type_input($data)
	{
		global $wpdb;

		if(!isset($data['value'])){		$data['value'] = '';}

		$arr_data = array();
		$out = "";

		$obj_webshop = new mf_webshop();
		$obj_webshop_interval = new mf_webshop();

		$result = $obj_webshop->get_document_types(array('select' => "ID, post_title, post_name", 'join' => "INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = '".$this->meta_prefix."document_searchable'", 'where_key' => "ID = '%d' AND meta_value = 'yes'", 'where_value' => $data['post_id']));

		$obj_webshop_interval->set_interval_amount($result);

		foreach($result as $r)
		{
			$post_title = $r->post_title;
			$post_name = $r->post_name;

			$post_custom_type = get_post_meta($data['post_id'], $this->meta_prefix.'document_type', true);

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

					get_post_children(array('post_type' => 'mf_location'), $arr_data);

					$out = show_select(array('data' => $arr_data, 'name' => $post_name, 'value' => $data['value']));
				break;

				case 'categories':
					$arr_data = array(
						'' => $post_title."?"
					);

					get_post_children(array('post_type' => 'mf_categories'), $arr_data);

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

					$result = $wpdb->get_results("SELECT ID FROM ".$wpdb->posts." WHERE post_type = 'mf_products' AND post_status = 'publish'");

					foreach($result as $r)
					{
						$page_id = $r->ID;

						$post_meta = get_post_meta($page_id, $this->meta_prefix.$post_name, true);

						if($is_numeric)
						{
							$arr_data[$post_meta] = $post_meta;

							$obj_webshop->set_range($post_meta);
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

						$obj_webshop->calculate_range($arr_data);
					}

					$out = show_select(array('data' => $arr_data, 'name' => $post_name, 'value' => $data['value']));
				break;

				case 'interval':
					$obj_webshop_interval->increase_count();

					$post_document_alt_text = get_post_meta($data['post_id'], $this->meta_prefix.'document_alt_text', true);

					if($post_document_alt_text != '')
					{
						$post_title = $post_document_alt_text;
					}

					$obj_webshop_interval->add_interval_type($post_name, $post_title);

					$result = $wpdb->get_results("SELECT ID FROM ".$wpdb->posts." WHERE post_type = 'mf_products' AND post_status = 'publish'");

					foreach($result as $r)
					{
						$page_id = $r->ID;

						$post_meta = get_post_meta($page_id, $this->meta_prefix.$post_name, true);

						list($post_meta_min, $post_meta_max) = get_interval_min($post_meta);

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

		$obj_webshop = new mf_webshop();
		$result = $obj_webshop->get_document_types(array('select' => "ID, post_title, post_name", 'join' => "INNER JOIN ".$wpdb->postmeta." AS meta1 ON ".$wpdb->posts.".ID = meta1.post_id AND meta1.meta_key = '".$obj_webshop->meta_prefix."document_searchable' LEFT JOIN ".$wpdb->postmeta." AS meta2 ON ".$wpdb->posts.".ID = meta2.post_id AND meta2.meta_key = '".$obj_webshop->meta_prefix."document_type_order'", 'where_key' => "meta1.meta_value = %s", 'where_value' => 'yes', 'order' => "meta2.meta_value + 0 ASC, menu_order ASC"));

		foreach($result as $r)
		{
			$post_id = $r->ID;
			$post_title = $r->post_title;
			$post_name = $r->post_name;

			$post_custom_type = get_post_meta($post_id, $this->meta_prefix.'document_type', true);

			if(in_array($post_custom_type, array('number', 'price', 'size', 'address', 'local_address', 'interval', 'location', 'categories')))
			{
				$arr_data_doc_type[$post_id] = $post_title;
			}
		}

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('webshop_heading'), 'text' => __("Heading", 'lang_webshop'), 'value' => $instance['webshop_heading']))
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
		$obj_webshop = new mf_webshop();
		$this->post_name = $obj_webshop->get_post_name_for_type('location');

		$widget_ops = array(
			'classname' => 'webshop_list webshop_widget',
			'description' => __("Display start page list", 'lang_webshop')
		);

		$this->arr_default = array(
			'webshop_heading' => "",
			'webshop_action' => 0,
			'webshop_locations' => "",
		);

		$name_webshop = get_option_or_default('setting_webshop_replace_webshop', __("Webshop", 'lang_webshop'));

		parent::__construct('webshop-list-widget', __("List", 'lang_webshop').($name_webshop != '' ? " (".$name_webshop.")" : ""), $widget_ops);

		$this->meta_prefix = "mf_ws_";
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
				get_post_children(array('post_type' => 'mf_location'), $arr_data);

				echo "<div class='section'>
					<ul class='text_columns columns_3'>"; //".(count($arr_data) % 3 == 0 || count($arr_data) > 4 ? "" : "columns_2")."

						foreach($arr_data as $key => $value)
						{
							if(in_array($key, $instance['webshop_locations']))
							{
								echo "<li><a href='".get_permalink($instance['webshop_action'])."?".$this->post_name."=".$key."#".$this->post_name."=".$key."'>".trim($value, "&nbsp;")."</a></li>";
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
		get_post_children(array('post_type' => 'mf_location'), $arr_data_locations);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('webshop_heading'), 'text' => __("Heading", 'lang_webshop'), 'value' => $instance['webshop_heading']))
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

		$name_webshop = get_option_or_default('setting_webshop_replace_webshop', __("Webshop", 'lang_webshop'));

		parent::__construct('webshop-favorites-widget', __("Favorites", 'lang_webshop').($name_webshop != '' ? " (".$name_webshop.")" : ""), $widget_ops);

		$this->name_products = get_option_or_default('setting_webshop_replace_products', __("Products", 'lang_webshop'));
	}

	function widget($args, $instance)
	{
		global $wpdb;

		extract($args);

		$instance = wp_parse_args((array)$instance, $this->arr_default);

		if(count($instance['webshop_products']) > 0)
		{
			$obj_webshop = new mf_webshop();

			echo $before_widget;

				if($instance['webshop_heading'] != '')
				{
					echo $before_title
						.$instance['webshop_heading']
					.$after_title;
				}

				$query_join = "";

				$address_post_name = $obj_webshop->get_post_name_for_type('address');

				if($address_post_name != '')
				{
					$query_join = " LEFT JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = '".esc_sql($obj_webshop->meta_prefix.$address_post_name)."'";
				}

				$result = $wpdb->get_results("SELECT ID, post_title, post_excerpt, post_content FROM ".$wpdb->posts.$query_join." WHERE post_type = 'mf_products' AND post_status = 'publish' AND ID IN ('".implode("','", $instance['webshop_products'])."') ORDER BY menu_order ASC");
				$rows = $wpdb->num_rows;

				echo $obj_webshop->get_widget_list($instance, $result, $rows)
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
		get_post_children(array('post_type' => 'mf_products', 'order_by' => 'post_title'), $arr_data);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('webshop_heading'), 'text' => __("Heading", 'lang_webshop'), 'value' => $instance['webshop_heading']))
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

		$name_webshop = get_option_or_default('setting_webshop_replace_webshop', __("Webshop", 'lang_webshop'));

		parent::__construct('webshop-recent-widget', __("Recent", 'lang_webshop').($name_webshop != '' ? " (".$name_webshop.")" : ""), $widget_ops);

		$this->name_products = get_option_or_default('setting_webshop_replace_products', __("Products", 'lang_webshop'));
	}

	function widget($args, $instance)
	{
		global $wpdb;

		extract($args);

		$instance = wp_parse_args((array)$instance, $this->arr_default);

		if($instance['webshop_amount'] > 0)
		{
			$obj_webshop = new mf_webshop();

			echo $before_widget;

				if($instance['webshop_heading'] != '')
				{
					echo $before_title
						.$instance['webshop_heading']
					.$after_title;
				}

				$query_join = $query_where = "";

				$address_post_name = $obj_webshop->get_post_name_for_type('address');
				$ghost_post_name = $obj_webshop->get_post_name_for_type('ghost');

				if($ghost_post_name != '')
				{
					$query_join .= " LEFT JOIN ".$wpdb->postmeta." AS ghost_meta ON ".$wpdb->posts.".ID = ghost_meta.post_id AND ghost_meta.meta_key = '".esc_sql($obj_webshop->meta_prefix.$ghost_post_name)."'";
					$query_where .= " AND (ghost_meta.meta_value = '0' OR ghost_meta.meta_value IS null)";
				}

				if($address_post_name != '')
				{
					$query_join .= " LEFT JOIN ".$wpdb->postmeta." AS address_meta ON ".$wpdb->posts.".ID = address_meta.post_id AND address_meta.meta_key = '".esc_sql($obj_webshop->meta_prefix.$address_post_name)."'";
				}

				$result = $wpdb->get_results("SELECT ID, post_title, post_excerpt, post_content FROM ".$wpdb->posts.$query_join." WHERE post_type = 'mf_products' AND post_status = 'publish'".$query_where." ORDER BY post_date DESC LIMIT 0, ".esc_sql($instance['webshop_amount']));
				$rows = $wpdb->num_rows;

				echo $obj_webshop->get_widget_list($instance, $result, $rows)
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
			.show_textfield(array('name' => $this->get_field_name('webshop_heading'), 'text' => __("Heading", 'lang_webshop'), 'value' => $instance['webshop_heading']))
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