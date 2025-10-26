<?php
/*
Plugin Name: MF Webshop
Plugin URI: https://github.com/frostkom/mf_webshop
Description:
Version: 2.2.7.18
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

	add_action('cron_base', array($obj_webshop, 'cron_base'), mt_rand(1, 10));

	add_action('enqueue_block_editor_assets', array($obj_webshop, 'enqueue_block_editor_assets'));
	add_action('init', array($obj_webshop, 'init'));

	if(is_admin())
	{
		register_uninstall_hook(__FILE__, 'uninstall_webshop');

		add_action('admin_init', array($obj_webshop, 'settings_webshop'));
		add_filter('pre_update_option', array($obj_webshop, 'pre_update_option'), 10, 3);
		add_action('admin_init', array($obj_webshop, 'admin_init'), 0);

		add_action('admin_menu', array($obj_webshop, 'admin_menu'));

		add_filter('filter_sites_table_pages', array($obj_webshop, 'filter_sites_table_pages'));

		add_action('restrict_manage_posts', array($obj_webshop, 'restrict_manage_posts'));
		add_action('pre_get_posts', array($obj_webshop, 'pre_get_posts'));

		add_filter('manage_posts_columns', array($obj_webshop, 'column_header'), 5);
		add_action('manage_pages_custom_column', array($obj_webshop, 'column_cell'), 5, 2);

		//add_filter('manage_'.$obj_webshop->post_type_orders.'_posts_columns', array($obj_webshop, 'column_header'), 5);
		add_action('manage_'.$obj_webshop->post_type_orders.'_posts_custom_column', array($obj_webshop, 'column_cell'), 5, 2);

		add_filter('display_post_states', array($obj_webshop, 'display_post_states'), 10, 2);

		add_action('save_post', array($obj_webshop, 'save_post'), 10, 3);
		add_action('wp_trash_post', array($obj_webshop, 'wp_trash_post'));

		add_action('rwmb_meta_boxes', array($obj_webshop, 'rwmb_meta_boxes'));
		add_action('rwmb_enqueue_scripts', array($obj_webshop, 'rwmb_enqueue_scripts'));
		add_action('rwmb_before_save_post', array($obj_webshop, 'rwmb_before_save_post'));

		//add_filter('get_group_sync_type', array($obj_webshop, 'get_group_sync_type'), 10);
	}

	else
	{
		add_action('wp_head', array($obj_webshop, 'wp_head'), 0);
		add_action('wp_footer', array($obj_webshop, 'wp_footer'));
	}

	add_filter('filter_is_file_used', array($obj_webshop, 'filter_is_file_used'));

	add_action('rwmb_after_save_post', array($obj_webshop, 'rwmb_after_save_post'));

	add_filter('get_group_sync_addresses', array($obj_webshop, 'get_group_sync_addresses'), 10, 2);

	add_action('wp_ajax_api_webshop_cart_icon', array($obj_webshop, 'api_webshop_cart_icon'));
	add_action('wp_ajax_nopriv_api_webshop_cart_icon', array($obj_webshop, 'api_webshop_cart_icon'));

	add_action('wp_ajax_api_webshop_call', array($obj_webshop, 'api_webshop_call'));
	add_action('wp_ajax_nopriv_api_webshop_call', array($obj_webshop, 'api_webshop_call'));

	add_action('wp_ajax_api_webshop_update_product_amount', array($obj_webshop, 'api_webshop_update_product_amount'));
	add_action('wp_ajax_nopriv_api_webshop_update_product_amount', array($obj_webshop, 'api_webshop_update_product_amount'));

	add_action('wp_ajax_api_webshop_buy_button', array($obj_webshop, 'api_webshop_buy_button'));
	add_action('wp_ajax_nopriv_api_webshop_buy_button', array($obj_webshop, 'api_webshop_buy_button'));

	add_action('wp_ajax_api_webshop_add_to_cart', array($obj_webshop, 'api_webshop_add_to_cart'));
	add_action('wp_ajax_nopriv_api_webshop_add_to_cart', array($obj_webshop, 'api_webshop_add_to_cart'));

	add_action('wp_ajax_api_webshop_fetch_info', array($obj_webshop, 'api_webshop_fetch_info'));
	add_action('wp_ajax_nopriv_api_webshop_fetch_info', array($obj_webshop, 'api_webshop_fetch_info'));

	add_action('wp_ajax_api_webshop_order_update', array($obj_webshop, 'api_webshop_order_update'));
	add_action('wp_ajax_nopriv_api_webshop_order_update', array($obj_webshop, 'api_webshop_order_update'));

	function uninstall_webshop()
	{
		include_once("include/classes.php");

		$obj_webshop = new mf_webshop();

		$arr_options = array('setting_webshop_display_sort', 'setting_webshop_sort_default', 'setting_webshop_display_filter', 'setting_map_visibility', 'setting_map_visibility_mobile', 'setting_webshop_color_info', 'setting_webshop_text_color_info', 'setting_gmaps_api', 'setting_webshop_replace_show_map', 'setting_webshop_replace_hide_map', 'setting_range_min_default', 'setting_range_choices', 'settings_filter_diff', 'setting_search_max', 'setting_show_all_min', 'setting_webshop_icon', 'setting_webshop_allow_multiple_categories', 'setting_webshop_currency', 'setting_webshop_tax_rate', 'setting_webshop_tax_enter', 'setting_webshop_tax_display', 'setting_webshop_shipping_cost', 'setting_webshop_shipping_free_limit', 'setting_webshop_stripe_secret_key', 'setting_webshop_swish_merchant_number', 'setting_webshop_swish_certificate_file', 'setting_webshop_swish_certificate_password', 'setting_webshop_swish_key_file');

		/*foreach($obj_webshop->arr_option_types as $option_type)
		{
			$obj_webshop->option_type = ($option_type != '' ? "_".$option_type : '');

			$arr_option_types[] = $obj_webshop->post_type_categories;
			$arr_option_types[] = $obj_webshop->post_type_products;
			$arr_option_types[] = $obj_webshop->post_type_custom_categories;
			$arr_option_types[] = $obj_webshop->post_type_document_type;
			$arr_option_types[] = $obj_webshop->post_type_location;
			$arr_option_types[] = $obj_webshop->post_type_customers;
			$arr_option_types[] = $obj_webshop->post_type_delivery_type;
		}

		$obj_webshop->option_type = '';*/

		mf_uninstall_plugin(array(
			'options' => $arr_options,
			'user_meta' => array('meta_orders_viewed', 'meta_webshop_session', 'meta_webshop_reminder_sent'),
			//'post_types' => $arr_option_types,
		));
	}
}