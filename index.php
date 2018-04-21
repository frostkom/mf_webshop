<?php
/*
Plugin Name: MF Webshop
Plugin URI: https://github.com/frostkom/mf_webshop
Description: 
Version: 1.3.0.12
Licence: GPLv2 or later
Author: Martin Fors
Author URI: http://frostkom.se
Text Domain: lang_webshop
Domain Path: /lang

Depends: Meta Box, MF Base
GitHub Plugin URI: frostkom/mf_webshop
*/

include_once("include/classes.php");
include_once("include/functions.php");

$obj_webshop = new mf_webshop();

add_action('cron_base', 'activate_webshop', mt_rand(1, 10));

add_action('init', 'init_webshop');
add_action('widgets_init', 'widgets_webshop');

if(is_admin())
{
	register_activation_hook(__FILE__, 'activate_webshop');
	register_deactivation_hook(__FILE__, 'deactivate_webshop');
	register_uninstall_hook(__FILE__, 'uninstall_webshop');

	add_action('admin_init', 'settings_webshop');
	add_action('admin_init', array($obj_webshop, 'admin_init'), 0);

	add_action('admin_menu', array($obj_webshop, 'admin_menu'));
	add_action('rwmb_meta_boxes', 'meta_boxes_webshop');
	add_action('rwmb_enqueue_scripts', 'meta_boxes_script_webshop');

	add_action('restrict_manage_posts', 'post_filter_select_webshop');
	add_action('pre_get_posts', 'post_filter_query_webshop');

	add_filter('manage_mf_products_posts_columns', 'column_header_products', 5);
	add_action('manage_mf_products_posts_custom_column', 'column_cell_products', 5, 2);

	add_filter('manage_mf_categories_posts_columns', 'column_header_categories', 5);
	add_action('manage_mf_categories_posts_custom_column', 'column_cell_categories', 5, 2);

	add_filter('manage_mf_document_type_posts_columns', 'column_header_document_type', 5);
	add_action('manage_mf_document_type_posts_custom_column', 'column_cell_document_type', 5, 2);

	add_filter('manage_mf_location_posts_columns', 'column_header_location', 5);
	add_action('manage_mf_location_posts_custom_column', 'column_cell_location', 5, 2);

	add_action('save_post', 'save_post_webshop', 10, 3);
	add_action('rwmb_before_save_post', 'before_save_meta_webshop');
}

else
{
	add_action('wp_head', array($obj_webshop, 'wp_head'), 0);
}

add_filter('single_template', 'custom_templates_webshop');
add_action('plugins_loaded', array('PageTemplater', 'get_instance'));

add_action('wp_login', 'uninit_webshop');
add_action('wp_logout', 'uninit_webshop');

add_filter('filter_form_after_fields', 'filter_form_after_fields_webshop');
add_filter('filter_form_on_submit', 'filter_form_on_submit_webshop');

add_shortcode('mf_back_to_search', 'shortcode_back_to_search');

load_plugin_textdomain('lang_webshop', false, dirname(plugin_basename(__FILE__))."/lang/");

function activate_webshop()
{
	global $wpdb;

	require_plugin("meta-box/meta-box.php", "Meta Box");

	$default_charset = DB_CHARSET != '' ? DB_CHARSET : "utf8";

	$arr_update_column = array();

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."webshop_order (
		orderID INT UNSIGNED NOT NULL AUTO_INCREMENT,
		orderInvoice DATETIME NOT NULL,
		orderDelivery DATETIME NOT NULL,
		customerID INT UNSIGNED NOT NULL,
		orderName VARCHAR(60) NOT NULL,
		orderEmail VARCHAR(200) NOT NULL,
		orderText TEXT NOT NULL,
		deliveryTypeID INT UNSIGNED NOT NULL,
		userID INT UNSIGNED NOT NULL,
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

	//replace_option(array('old' => 'settings_text_color_info', 'new' => 'setting_webshop_text_color_info'));

	/*if(function_exists('add_css_selectors'))
	{
		add_css_selectors(array(
			"",
		));
	}*/

	replace_user_meta(array('old' => 'mf_orders_viewed', 'new' => 'meta_orders_viewed'));
}

function deactivate_webshop()
{
	mf_uninstall_plugin(array(
		'options' => array('settings_color_button_hover', 'settings_text_color_button_hover', 'settings_color_button_2_hover', 'setting_webshop_mobile_breakpoint', 'setting_local_storage', 'setting_webshop_allow_individual_contant'),
		'meta' => array('meta_orders_viewed'),
	));
}

function uninstall_webshop()
{
	mf_uninstall_plugin(array(
		'options' => array('setting_range_min_default', 'setting_range_choices', 'settings_filter_diff', 'setting_search_max', 'setting_show_all_min', 'setting_require_search', 'setting_product_default_image', 'setting_ghost_title', 'setting_ghost_image', 'setting_ghost_text', 'setting_quote_form_popup', 'setting_quote_form', 'setting_quote_form_single', 'setting_webshop_force_individual_contact', 'setting_webshop_replace_webshop', 'setting_webshop_replace_product', 'setting_webshop_replace_products', 'setting_webshop_replace_categories', 'setting_webshop_replace_doc_types', 'setting_replace_send_request_for_quote', 'setting_replace_add_to_search', 'setting_replace_remove_from_search', 'setting_replace_return_to_search', 'setting_replace_search_for_another', 'setting_replace_quote_request', 'setting_webshop_replace_none_checked', 'setting_webshop_replace_too_many', 'setting_webshop_replace_show_map', 'setting_replace_hide_map', 'setting_map_info', 'setting_webshop_replace_products_slug', 'setting_webshop_replace_categories_slug', 'setting_show_categories', 'setting_webshop_color_button', 'setting_webshop_text_color_button', 'setting_webshop_color_button_2', 'setting_color_button_negative', 'setting_webshop_color_info', 'setting_webshop_text_color_info', 'setting_gmaps_api', 'setting_map_visibility', 'setting_map_visibility_mobile', 'setting_webshop_symbol_inactive_image', 'setting_webshop_symbol_active_image', 'setting_ghost_inactive_image', 'setting_ghost_active_image', 'setting_webshop_symbol_inactive', 'setting_webshop_symbol_active', 'setting_webshop_replace_filter_products', 'setting_replace_search_result_info', 'setting_webshop_replace_favorites_info'),
		'post_types' => array('mf_categories', 'mf_products', 'mf_document_type', 'mf_location', 'mf_customers', 'mf_delivery_type'),
		'tables' => array('webshop_order', 'webshop_product2user'),
	));
}

function custom_templates_webshop($single_template)
{
	global $post;

	if(in_array($post->post_type, array("mf_categories", "mf_products")))
	{
		$single_template = plugin_dir_path(__FILE__)."templates/single-".$post->post_type.".php";
	}

	return $single_template;
}

class PageTemplater
{
	private static $instance;
	protected $templates;

	public static function get_instance()
	{
		if(null == self::$instance)
		{
			self::$instance = new PageTemplater();
		}

		return self::$instance;
	}

	// Initializes the plugin by setting filters and administration functions.
	private function __construct()
	{
		$this->templates = array();

		// Add a filter to the attributes metabox to inject template into the cache.
		if(version_compare(floatval(get_bloginfo('version')), '4.7', '<'))
		{
			// 4.6 and older
			add_filter('page_attributes_dropdown_pages_args', array($this, 'register_project_templates'));

		}

		else
		{
			// Add a filter to the wp 4.7 version attributes metabox
			add_filter('theme_page_templates', array($this, 'add_new_template'));
		}

		// Add a filter to the save post to inject out template into the page cache
		add_filter('wp_insert_post_data', array( $this, 'register_project_templates' ));

		// Add a filter to the template include to determine if the page has our template assigned and return it's path
		add_filter('template_include', array( $this, 'view_project_template'));

		$name_webshop = get_option_or_default('setting_webshop_replace_webshop', __("Webshop", 'lang_webshop'));

		// Add your templates to this array.
		$this->templates = array(
			'template_webshop.php' => $name_webshop,
			'template_webshop_search.php' => $name_webshop." (".__("Search", 'lang_webshop').")",
			'template_webshop_favorites.php' => $name_webshop." (".__("Favorites", 'lang_webshop').")",
		);
	}

	// Adds our template to the page dropdown for v4.7+
	public function add_new_template($posts_templates)
	{
		$posts_templates = array_merge($posts_templates, $this->templates);

		return $posts_templates;
	}

	// Adds our template to the pages cache in order to trick WordPress into thinking the template file exists where it doens't really exist.
	public function register_project_templates($atts)
	{
		// Create the key used for the themes cache
		$cache_key = "page_templates-".md5(get_theme_root()."/".get_stylesheet());

		// Retrieve the cache list. If it doesn't exist, or it's empty prepare an array
		$templates = wp_get_theme()->get_page_templates();

		if(empty($templates))
		{
			$templates = array();
		}

		// New cache, therefore remove the old one
		wp_cache_delete($cache_key , 'themes');

		// Now add our template to the list of templates by merging our templates with the existing templates array from the cache.
		$templates = array_merge($templates, $this->templates);

		// Add the modified cache to allow WordPress to pick it up for listing available templates
		wp_cache_add($cache_key, $templates, 'themes', 1800);

		return $atts;
	}

	// Checks if the template is assigned to the page
	public function view_project_template($template)
	{
		global $post;

		// Return template if post is empty
		if(!$post)
		{
			return $template;
		}

		$template_temp = get_post_meta($post->ID, '_wp_page_template', true);

		// Return default template if we don't have a custom one defined
		if(!isset($this->templates[$template_temp]))
		{
			return $template;
		}

		$file = plugin_dir_path(__FILE__)."templates/".$template_temp;

		// Just to be safe, we check if the file exist first
		if(file_exists($file))
		{
			return $file;
		}

		else
		{
			echo $file;
		}

		return $template;
	}
}