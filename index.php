<?php
/*
Plugin Name: MF Webshop
Plugin URI: https://github.com/frostkom/mf_webshop
Description: 
Version: 1.4.2.16
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
	add_action('rwmb_meta_boxes', array($obj_webshop, 'rwmb_meta_boxes'));
	add_action('rwmb_enqueue_scripts', array($obj_webshop, 'rwmb_enqueue_scripts'));

	add_action('restrict_manage_posts', array($obj_webshop, 'restrict_manage_posts'));
	add_action('pre_get_posts', array($obj_webshop, 'pre_get_posts'));

	add_filter('manage_posts_columns', array($obj_webshop, 'column_header'), 5, 2);
	add_action('manage_pages_custom_column', array($obj_webshop, 'column_cell'), 5, 2);

	add_action('save_post', array($obj_webshop, 'save_post'), 10, 3);
	add_action('rwmb_before_save_post', array($obj_webshop, 'rwmb_before_save_post'));

	add_action('manage_users_columns', array($obj_webshop, 'manage_users_columns'));
	add_action('manage_users_custom_column', array($obj_webshop, 'manage_users_custom_column'), 10, 3);

	add_action('show_user_profile', array($obj_webshop, 'show_profile'));
	add_action('edit_user_profile', array($obj_webshop, 'show_profile'));
	add_action('personal_options_update', array($obj_webshop, 'save_profile'));
	add_action('edit_user_profile_update', array($obj_webshop, 'save_profile'));
}

else
{
	add_action('wp_head', array($obj_webshop, 'wp_head'), 0);
}

add_action('widgets_init', array($obj_webshop, 'widgets_init'));

add_filter('single_template', 'custom_templates_webshop');
add_action('plugins_loaded', array('PageTemplater', 'get_instance'));

add_action('wp_login', array($obj_webshop, 'uninit'));
add_action('wp_logout', array($obj_webshop, 'uninit'));

add_filter('filter_form_after_fields', array($obj_webshop, 'filter_form_after_fields'));
add_filter('filter_form_on_submit', array($obj_webshop, 'filter_form_on_submit'));

add_shortcode('mf_back_to_search', array($obj_webshop, 'shortcode_back_to_search'));

load_plugin_textdomain('lang_webshop', false, dirname(plugin_basename(__FILE__))."/lang/");

function activate_webshop()
{
	global $wpdb, $obj_webshop;

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
		orderEmail VARCHAR(200) NOT NULL,
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

	replace_option(array('old' => 'setting_webshop_post_types', 'new' => 'setting_webshop_option_types'));

	replace_user_meta(array('old' => 'mf_orders_viewed', 'new' => 'meta_orders_viewed'));

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
	}

	$obj_webshop->option_type = '';

	mf_uninstall_plugin(array(
		'options' => $arr_options,
	));
}

function uninstall_webshop()
{
	global $obj_webshop;

	$obj_webshop->get_option_types();

	$arr_options = $arr_option_types = array();

	$arr_options[] = 'setting_webshop_option_types';

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
		$arr_options[] = 'setting_webshop_replace_product'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_replace_products'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_replace_categories'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_replace_doc_types'.$obj_webshop->option_type;
		$arr_options[] = 'setting_replace_send_request_for_quote'.$obj_webshop->option_type;
		$arr_options[] = 'setting_replace_add_to_search'.$obj_webshop->option_type;
		$arr_options[] = 'setting_replace_remove_from_search'.$obj_webshop->option_type;
		$arr_options[] = 'setting_replace_return_to_search'.$obj_webshop->option_type;
		$arr_options[] = 'setting_replace_search_for_another'.$obj_webshop->option_type;
		$arr_options[] = 'setting_replace_quote_request'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_replace_none_checked'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_replace_too_many'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_replace_show_map'.$obj_webshop->option_type;
		$arr_options[] = 'setting_replace_hide_map'.$obj_webshop->option_type;
		$arr_options[] = 'setting_map_info'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_replace_products_slug'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_replace_categories_slug'.$obj_webshop->option_type;
		$arr_options[] = 'setting_show_categories'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_color_info'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_text_color_info'.$obj_webshop->option_type;
		$arr_options[] = 'setting_gmaps_api'.$obj_webshop->option_type;
		$arr_options[] = 'setting_map_visibility'.$obj_webshop->option_type;
		$arr_options[] = 'setting_map_visibility_mobile'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_symbol_inactive_image'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_symbol_active_image'.$obj_webshop->option_type;
		$arr_options[] = 'setting_ghost_inactive_image'.$obj_webshop->option_type;
		$arr_options[] = 'setting_ghost_active_image'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_symbol_inactive'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_symbol_active'.$obj_webshop->option_type;
		$arr_options[] = 'setting_webshop_replace_filter_products'.$obj_webshop->option_type;
		$arr_options[] = 'setting_replace_search_result_info'.$obj_webshop->option_type;
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
		'tables' => array('webshop_order', 'webshop_product2user'),
	));
}

function custom_templates_webshop($single_template)
{
	global $post, $obj_webshop;

	if(substr($post->post_type, 0, strlen($obj_webshop->post_type_categories)) == $obj_webshop->post_type_categories)
	{
		$single_template = plugin_dir_path(__FILE__)."templates/single-".$obj_webshop->post_type_categories.".php";
	}

	else if(substr($post->post_type, 0, strlen($obj_webshop->post_type_products)) == $obj_webshop->post_type_products)
	{
		$single_template = plugin_dir_path(__FILE__)."templates/single-".$obj_webshop->post_type_products.".php";
	}

	return $single_template;
}

/* Have to be here so that template directories are correct */
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
		global $obj_webshop;

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
		add_filter('wp_insert_post_data', array($this, 'register_project_templates'));

		// Add a filter to the template include to determine if the page has our template assigned and return it's path
		add_filter('template_include', array($this, 'view_project_template'));

		$this->templates = array();

		/*$obj_webshop->get_option_types();

		foreach($obj_webshop->arr_option_types as $option_type)
		{
			$obj_webshop->option_type = ($option_type != '' ? "_".$option_type : '');

			$name_webshop = get_option_or_default('setting_webshop_replace_webshop'.$obj_webshop->option_type, __("Webshop", 'lang_webshop'));

			$this->templates['template_webshop.php'.($option_type != '' ? "?post_type=".$option_type : '')] = $name_webshop;
			$this->templates['template_webshop_search.php'.($option_type != '' ? "?post_type=".$option_type : '')] = $name_webshop." (".__("Search", 'lang_webshop').")";
			$this->templates['template_webshop_favorites.php'.($option_type != '' ? "?post_type=".$option_type : '')] = $name_webshop." (".__("Favorites", 'lang_webshop').")";
		}

		$obj_webshop->option_type = '';*/

		$name_webshop = get_option_or_default('setting_webshop_replace_webshop', __("Webshop", 'lang_webshop'));

		$this->templates['template_webshop.php'] = $name_webshop;
		$this->templates['template_webshop_search.php'] = $name_webshop." (".__("Search", 'lang_webshop').")";
		$this->templates['template_webshop_favorites.php'] = $name_webshop." (".__("Favorites", 'lang_webshop').")";
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