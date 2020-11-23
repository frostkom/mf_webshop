<?php
/*
Plugin Name: MF Webshop
Plugin URI: https://github.com/frostkom/mf_webshop
Description: 
Version: 2.1.7.0
Licence: GPLv2 or later
Author: Martin Fors
Author URI: https://frostkom.se
Text Domain: lang_webshop
Domain Path: /lang

Depends: Meta Box, MF Base, MF Maps
GitHub Plugin URI: frostkom/mf_webshop
*/

include_once("include/classes.php");
include_once("include/functions.php");

$obj_webshop = new mf_webshop();

add_action('cron_base', 'activate_webshop', mt_rand(1, 10));

add_action('init', array($obj_webshop, 'init'));

if(is_admin())
{
	register_activation_hook(__FILE__, 'activate_webshop');
	register_uninstall_hook(__FILE__, 'uninstall_webshop');

	add_action('admin_init', array($obj_webshop, 'settings_webshop'));
	add_action('admin_init', array($obj_webshop, 'admin_init'), 0);

	add_action('updated_option', array($obj_webshop, 'updated_option'), 10, 3);

	add_filter('wp_get_default_privacy_policy_content', array($obj_webshop, 'add_policy'));

	add_action('admin_menu', array($obj_webshop, 'admin_menu'));

	add_action('restrict_manage_posts', array($obj_webshop, 'restrict_manage_posts'));
	add_action('pre_get_posts', array($obj_webshop, 'pre_get_posts'));

	add_filter('manage_posts_columns', array($obj_webshop, 'column_header'), 5, 2);
	add_action('manage_pages_custom_column', array($obj_webshop, 'column_cell'), 5, 2);

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
}

else
{
	add_action('wp_head', array($obj_webshop, 'wp_head'), 0);
	add_action('wp_footer', array($obj_webshop, 'wp_footer'));

	add_filter('get_theme_core_info_title', array($obj_webshop, 'get_theme_core_info_title'));
	add_filter('get_theme_core_info_text', array($obj_webshop, 'get_theme_core_info_text'));
	add_filter('get_theme_core_info_button_link', array($obj_webshop, 'get_theme_core_info_button_link'));
}

add_filter('init_base_admin', array($obj_webshop, 'init_base_admin'), 10, 2);

add_filter('filter_is_file_used', array($obj_webshop, 'filter_is_file_used'));

add_action('widgets_init', array($obj_webshop, 'widgets_init'));

add_action('wp_login', array($obj_webshop, 'uninit'));
add_action('wp_logout', array($obj_webshop, 'uninit'));

add_filter('default_content', array($obj_webshop, 'default_content')); //, 10, 2

add_filter('filter_form_after_fields', array($obj_webshop, 'filter_form_after_fields'));
add_filter('filter_form_on_submit', array($obj_webshop, 'filter_form_on_submit'));

add_filter('before_meta_box_fields', array($obj_webshop, 'before_meta_box_fields'));
add_action('rwmb_after_save_post', array($obj_webshop, 'rwmb_after_save_post'));

add_shortcode('mf_back_to_search', array($obj_webshop, 'shortcode_back_to_search'));

add_filter('single_template', array($obj_webshop, 'single_template'));
add_filter('get_page_templates', array($obj_webshop, 'get_page_templates'));

load_plugin_textdomain('lang_webshop', false, dirname(plugin_basename(__FILE__))."/lang/");

function activate_webshop()
{
	global $wpdb;

	$obj_webshop = new mf_webshop();

	require_plugin("meta-box/meta-box.php", "Meta Box");
	require_plugin("mf_maps/index.php", "MF Maps");

	$default_charset = DB_CHARSET != '' ? DB_CHARSET : "utf8";

	$arr_update_column = array();

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

	/*if(function_exists('add_css_selectors'))
	{
		add_css_selectors(array(
			"",
		));
	}*/

	replace_post_type(array('old' => 'mf_categories', 'new' => 'mf_category'));
	replace_post_type(array('old' => 'mf_products', 'new' => 'mf_product'));
	replace_post_type(array('old' => 'mf_custom_categories', 'new' => 'mf_cust_cat'));
	replace_post_type(array('old' => 'mf_document_type', 'new' => 'mf_doc_type'));
	replace_post_type(array('old' => 'mf_customers', 'new' => 'mf_customer'));
	replace_post_type(array('old' => 'mf_delivery_type', 'new' => 'mf_delivery'));

	$obj_webshop->get_option_types();

	$arr_options = array();

	$arr_options[] = 'setting_local_storage';

	foreach($obj_webshop->arr_option_types as $option_type)
	{
		$obj_webshop->option_type = ($option_type != '' ? "_".$option_type : '');

		$arr_options[] = 'settings_color_button_hover'.$obj_webshop->option_type;
		$arr_options[] = 'settings_text_color_button_hover'.$obj_webshop->option_type;
		$arr_options[] = 'settings_color_button_2_hover'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_mobile_breakpoint'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_require_payment'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_allow_individual_contant'.$obj_webshop->option_type;
		$arr_options[] = 'setting_product_default_image'.$obj_webshop->option_type;
		$arr_options[] = 'setting_ghost_title'.$obj_webshop->option_type;
		$arr_options[] = 'setting_ghost_image'.$obj_webshop->option_type;
		$arr_options[] = 'setting_ghost_text'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_color_button'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_text_color_button'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_color_button_2'.$obj_webshop->option_type;
		$arr_options[] = 'setting_color_button_negative'.$obj_webshop->option_type;
		$arr_options[] = 'setting_show_categories'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_activate_frontend_admin'.$obj_webshop->option_type;
	}

	$obj_webshop->option_type = '';

	mf_uninstall_plugin(array(
		'options' => $arr_options,
	));

	// Update lat/long for all products with coordinates
	########################################
	/*$obj_webshop->get_option_types();

	foreach($obj_webshop->arr_option_types as $option_type)
	{
		$obj_webshop->option_type = ($option_type != '' ? "_".$option_type : '');

		$coordinates_post_name = $obj_webshop->get_post_name_for_type('coordinates');

		if($coordinates_post_name != '')
		{
			$result = $obj_webshop->get_list();

			foreach($result as $r)
			{
				$post_id = $r->ID;

				$post_coordinates = get_post_meta($post_id, $obj_webshop->meta_prefix.$coordinates_post_name, true);

				if($post_coordinates != '')
				{
					list($latitude, $longitude) = $obj_webshop->split_coordinates($post_coordinates);

					update_post_meta($post_id, $obj_webshop->meta_prefix.'latitude', $latitude);
					update_post_meta($post_id, $obj_webshop->meta_prefix.'longitude', $longitude);
				}
			}
		}
	}

	$obj_webshop->option_type = '';*/
	########################################
}

function uninstall_webshop()
{
	global $obj_webshop;

	$obj_webshop->get_option_types();

	$arr_options = $arr_option_types = array();

	$arr_options[] = 'setting_webshop_option_types';
	$arr_options[] = 'setting_webshop_display_sort';
	$arr_options[] = 'setting_webshop_sort_default';
	$arr_options[] = 'setting_webshop_display_filter';
	$arr_options[] = 'setting_map_visibility';
	$arr_options[] = 'setting_map_visibility_mobile';
	$arr_options[] = 'setting_webshop_color_info';
	$arr_options[] = 'setting_webshop_text_color_info';

	if(!is_plugin_active('mf_maps/index.php'))
	{
		$arr_options[] = 'setting_gmaps_api';
	}

	$arr_options[] = 'setting_webshop_replace_show_map';
	$arr_options[] = 'setting_webshop_replace_hide_map';

	foreach($obj_webshop->arr_option_types as $option_type)
	{
		$obj_webshop->option_type = ($option_type != '' ? "_".$option_type : '');

		$arr_options[] = 'setting_range_min_default'.$obj_webshop->option_type;
		$arr_options[] = 'setting_range_choices'.$obj_webshop->option_type;
		$arr_options[] = 'settings_filter_diff'.$obj_webshop->option_type;
		$arr_options[] = 'setting_search_max'.$obj_webshop->option_type;
		$arr_options[] = 'setting_show_all_min'.$obj_webshop->option_type;
		$arr_options[] = 'setting_require_search'.$obj_webshop->option_type;
		$arr_options[] = 'setting_quote_form_popup'.$obj_webshop->option_type;
		$arr_options[] = 'setting_quote_form'.$obj_webshop->option_type;
		$arr_options[] = 'setting_quote_form_single'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_force_individual_contact'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_payment_form'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_replace_webshop'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_icon'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_replace_product'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_replace_products'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_replace_enter_title_here'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_replace_categories'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_replace_doc_types'.$obj_webshop->option_type;
		$arr_options[] = 'setting_replace_send_request_for_quote'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_replace_title_information'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_replace_title_settings'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_replace_title_contact_info'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_replace_title_quick_info'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_replace_title_properties'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_display_breadcrumbs'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_allow_multiple_categories'.$obj_webshop->option_type;
		$arr_options[] = 'setting_replace_add_to_search'.$obj_webshop->option_type;
		$arr_options[] = 'setting_replace_remove_from_search'.$obj_webshop->option_type;
		$arr_options[] = 'setting_replace_return_to_search'.$obj_webshop->option_type;
		$arr_options[] = 'setting_replace_search_for_another'.$obj_webshop->option_type;
		$arr_options[] = 'setting_replace_quote_request'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_replace_none_checked'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_replace_too_many'.$obj_webshop->option_type;
		$arr_options[] = 'setting_map_info'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_replace_products_slug'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_replace_categories_slug'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_symbol_inactive_image'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_symbol_active_image'.$obj_webshop->option_type;
		$arr_options[] = 'setting_ghost_inactive_image'.$obj_webshop->option_type;
		$arr_options[] = 'setting_ghost_active_image'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_symbol_inactive'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_symbol_active'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_replace_filter_products'.$obj_webshop->option_type;
		$arr_options[] = 'setting_replace_search_result_info'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_display_images'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_max_file_uploads'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_replace_favorites_info'.$obj_webshop->option_type;

		$arr_option_types[] = $obj_webshop->post_type_categories.$obj_webshop->option_type;
		$arr_option_types[] = $obj_webshop->post_type_products.$obj_webshop->option_type;
		$arr_option_types[] = $obj_webshop->post_type_custom_categories.$obj_webshop->option_type;
		$arr_option_types[] = $obj_webshop->post_type_document_type.$obj_webshop->option_type;
		$arr_option_types[] = $obj_webshop->post_type_location.$obj_webshop->option_type;
		$arr_option_types[] = $obj_webshop->post_type_customers.$obj_webshop->option_type;
		$arr_option_types[] = $obj_webshop->post_type_delivery_type.$obj_webshop->option_type;
	}

	$obj_webshop->option_type = '';

	mf_uninstall_plugin(array(
		'options' => $arr_options,
		'meta' => array('meta_orders_viewed'),
		'post_types' => $arr_option_types,
		'tables' => array('webshop_order', 'webshop_product2user', 'webshop_sent'),
	));
}