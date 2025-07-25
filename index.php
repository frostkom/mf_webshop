<?php
/*
Plugin Name: MF Webshop
Plugin URI: https://github.com/frostkom/mf_webshop
Description:
Version: 2.2.5.4
Licence: GPLv2 or later
Author: Martin Fors
Author URI: https://martinfors.se
Text Domain: lang_webshop
Domain Path: /lang

Requires Plugins: meta-box
*/

if(!function_exists('is_plugin_active') || function_exists('is_plugin_active') && is_plugin_active("mf_base/index.php"))
{
	include_once("include/classes.php");
	include_once("include/functions.php");

	$obj_webshop = new mf_webshop();

	add_action('cron_base', 'activate_webshop', mt_rand(1, 10));
	add_action('cron_base', array($obj_webshop, 'cron_base'), mt_rand(1, 10));

	add_action('enqueue_block_editor_assets', array($obj_webshop, 'enqueue_block_editor_assets'));
	add_action('init', array($obj_webshop, 'init'));

	if(is_admin())
	{
		register_activation_hook(__FILE__, 'activate_webshop');
		register_uninstall_hook(__FILE__, 'uninstall_webshop');

		add_action('admin_init', array($obj_webshop, 'settings_webshop'));
		add_action('admin_init', array($obj_webshop, 'admin_init'), 0);

		add_action('admin_menu', array($obj_webshop, 'admin_menu'));

		add_filter('filter_sites_table_pages', array($obj_webshop, 'filter_sites_table_pages'));

		add_action('restrict_manage_posts', array($obj_webshop, 'restrict_manage_posts'));
		add_action('pre_get_posts', array($obj_webshop, 'pre_get_posts'));

		add_filter('manage_posts_columns', array($obj_webshop, 'column_header'), 5, 2);
		add_action('manage_pages_custom_column', array($obj_webshop, 'column_cell'), 5, 2);

		add_filter('display_post_states', array($obj_webshop, 'display_post_states'), 10, 2);

		add_filter('enter_title_here', array($obj_webshop, 'enter_title_here'));

		add_action('save_post', array($obj_webshop, 'save_post'), 10, 3);
		add_action('wp_trash_post', array($obj_webshop, 'wp_trash_post'));

		add_action('rwmb_meta_boxes', array($obj_webshop, 'rwmb_meta_boxes'));
		add_action('rwmb_enqueue_scripts', array($obj_webshop, 'rwmb_enqueue_scripts'));
		add_action('rwmb_before_save_post', array($obj_webshop, 'rwmb_before_save_post'));

		add_action('manage_users_columns', array($obj_webshop, 'manage_users_columns'));
		add_action('manage_users_custom_column', array($obj_webshop, 'manage_users_custom_column'), 10, 3);

		add_action('show_user_profile', array($obj_webshop, 'edit_user_profile'));
		add_action('edit_user_profile', array($obj_webshop, 'edit_user_profile'));
		add_action('profile_update', array($obj_webshop, 'profile_update'));

		add_filter('filter_cookie_types', array($obj_webshop, 'filter_cookie_types'));

		add_filter('get_group_sync_type', array($obj_webshop, 'get_group_sync_type'), 10);
	}

	else
	{
		//add_action('wp_head', array($obj_webshop, 'wp_head'), 0);
		//add_action('wp_footer', array($obj_webshop, 'wp_footer'));

		/*add_filter('get_theme_core_info_title', array($obj_webshop, 'get_theme_core_info_title'));
		add_filter('get_theme_core_info_text', array($obj_webshop, 'get_theme_core_info_text'));
		add_filter('get_theme_core_info_button_link', array($obj_webshop, 'get_theme_core_info_button_link'));*/
	}

	//add_filter('init_base_admin', array($obj_webshop, 'init_base_admin'), 10, 2);

	add_filter('filter_is_file_used', array($obj_webshop, 'filter_is_file_used'));

	if(wp_is_block_theme() == false)
	{
		add_action('widgets_init', array($obj_webshop, 'widgets_init'));
	}

	add_action('wp_login', array($obj_webshop, 'uninit'));
	add_action('wp_logout', array($obj_webshop, 'uninit'));

	add_filter('default_content', array($obj_webshop, 'default_content')); //, 10, 2

	add_filter('filter_form_after_fields', array($obj_webshop, 'filter_form_after_fields'));
	add_filter('filter_form_on_submit', array($obj_webshop, 'filter_form_on_submit'));

	add_filter('before_meta_box_fields', array($obj_webshop, 'before_meta_box_fields'));
	add_action('rwmb_after_save_post', array($obj_webshop, 'rwmb_after_save_post'));

	add_shortcode('mf_back_to_search', array($obj_webshop, 'shortcode_back_to_search'));

	add_filter('single_template', array($obj_webshop, 'single_template'));
	add_filter('theme_templates', array($obj_webshop, 'get_page_templates'));

	add_filter('get_group_sync_addresses', array($obj_webshop, 'get_group_sync_addresses'), 10, 2);

	add_action('wp_ajax_api_webshop_call', array($obj_webshop, 'api_webshop_call'));
	add_action('wp_ajax_nopriv_api_webshop_call', array($obj_webshop, 'api_webshop_call'));

	function activate_webshop()
	{
		global $wpdb;

		$obj_webshop = new mf_webshop();

		$default_charset = (DB_CHARSET != '' ? DB_CHARSET : 'utf8');

		$arr_update_column = [];

		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."webshop_order (
			orderID INT UNSIGNED NOT NULL AUTO_INCREMENT,
			orderInvoice DATETIME NOT NULL,
			orderDelivery DATETIME NOT NULL,
			customerID INT UNSIGNED NOT NULL,
			orderName VARCHAR(60) NOT NULL,
			orderEmail VARCHAR(100) NOT NULL,
			orderText TEXT NOT NULL,
			deliveryTypeID INT UNSIGNED NOT NULL,
			userID INT UNSIGNED DEFAULT NULL,
			orderCreated DATETIME NOT NULL,
			PRIMARY KEY (orderID)
		) DEFAULT CHARSET=".$default_charset);

		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."webshop_product2user (
			productID INT UNSIGNED DEFAULT NULL,
			userID INT UNSIGNED DEFAULT NULL,
			orderID INT UNSIGNED NOT NULL,
			webshopCookie VARCHAR(50) DEFAULT NULL,
			productAmount INT UNSIGNED,
			webshopDone ENUM('0','1') DEFAULT '0',
			webshopCreated DATETIME DEFAULT NULL,
			KEY productID (productID),
			KEY userID (userID)
		) DEFAULT CHARSET=".$default_charset);

		$arr_update_column[$wpdb->prefix."webshop_product2user"] = array(
			'webshopAmount' => "ALTER TABLE [table] CHANGE [column] productAmount INT UNSIGNED",
		);

		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."webshop_sent (
			productID INT UNSIGNED DEFAULT NULL,
			answerID INT UNSIGNED DEFAULT NULL,
			KEY productID (productID),
			KEY answerID (answerID)
		) DEFAULT CHARSET=".$default_charset);

		update_columns($arr_update_column);
	}

	function uninstall_webshop()
	{
		include_once("include/classes.php");

		$obj_webshop = new mf_webshop();

		$arr_options = array('setting_webshop_display_sort', 'setting_webshop_sort_default', 'setting_webshop_display_filter', 'setting_map_visibility', 'setting_map_visibility_mobile', 'setting_webshop_map_placement', 'setting_webshop_map_button_placement', 'setting_webshop_color_info', 'setting_webshop_text_color_info', 'setting_gmaps_api', 'setting_webshop_replace_show_map', 'setting_webshop_replace_hide_map', 'setting_range_min_default', 'setting_range_choices', 'settings_filter_diff', 'setting_search_max', 'setting_show_all_min', 'setting_require_search', 'setting_quote_form_popup', 'setting_quote_form', 'setting_quote_form_single', 'setting_webshop_force_individual_contact', 'setting_webshop_payment_form', 'setting_webshop_replace_webshop', 'setting_webshop_icon', 'setting_webshop_replace_product', 'setting_webshop_replace_products', 'setting_webshop_replace_enter_title_here', 'setting_webshop_replace_categories', 'setting_webshop_replace_doc_types', 'setting_replace_send_request_for_quote', 'setting_webshop_display_breadcrumbs', 'setting_webshop_allow_multiple_categories', 'setting_replace_add_to_search', 'setting_replace_remove_from_search', 'setting_replace_return_to_search', 'setting_replace_search_for_another', 'setting_replace_quote_request', 'setting_webshop_replace_none_checked', 'setting_webshop_replace_too_many', 'setting_map_info', 'setting_webshop_replace_products_slug', 'setting_webshop_replace_categories_slug', 'setting_webshop_symbol_inactive_image', 'setting_webshop_symbol_active_image', 'setting_ghost_inactive_image', 'setting_ghost_active_image', 'setting_webshop_symbol_inactive', 'setting_webshop_symbol_active', 'setting_webshop_replace_filter_products', 'setting_replace_search_result_info', 'setting_webshop_replace_favorites_info');

		foreach($obj_webshop->arr_option_types as $option_type)
		{
			$obj_webshop->option_type = ($option_type != '' ? "_".$option_type : '');

			/*foreach([] as $key => $value)
			{
				$arr_options[] = $value;
			}*/

			$arr_option_types[] = $obj_webshop->post_type_categories;
			$arr_option_types[] = $obj_webshop->post_type_products;
			$arr_option_types[] = $obj_webshop->post_type_custom_categories;
			$arr_option_types[] = $obj_webshop->post_type_document_type;
			$arr_option_types[] = $obj_webshop->post_type_location;
			$arr_option_types[] = $obj_webshop->post_type_customers;
			$arr_option_types[] = $obj_webshop->post_type_delivery_type;
		}

		$obj_webshop->option_type = '';

		mf_uninstall_plugin(array(
			'options' => $arr_options,
			'meta' => array('meta_orders_viewed', 'meta_webshop_session', 'meta_webshop_reminder_sent'),
			'post_types' => $arr_option_types,
			'tables' => array('webshop_order', 'webshop_product2user', 'webshop_sent'),
		));
	}
}