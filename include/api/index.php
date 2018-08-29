<?php

if(!defined('ABSPATH'))
{
	header('Content-Type: application/json');

	$folder = str_replace("/wp-content/plugins/mf_webshop/include/api", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

if(is_plugin_active('mf_cache/index.php'))
{
	$obj_cache = new mf_cache();
	$obj_cache->fetch_request();
	$obj_cache->get_or_set_file_content('json');
}

$obj_webshop = new mf_webshop();

$json_output = array();

if(1 == 1)
{
	$order = check_var('order', 'char', true, get_option('setting_webshop_sort_default', 'alphabetical'));
	//$sort = check_var('sort', 'char', true, 'asc');
	$favorites = check_var('favorites', 'char');

	$query_select = $query_join = $query_where = $query_group = $query_order = "";

	/*$ghost_post_name = $obj_webshop->get_post_name_for_type('ghost');

	if($ghost_post_name != '')
	{
		$query_join .= " LEFT JOIN ".$wpdb->postmeta." AS meta_ghost ON ".$wpdb->posts.".ID = meta_ghost.post_id AND meta_ghost.meta_key = '".esc_sql($obj_webshop->meta_prefix.$ghost_post_name)."'";
		$query_order .= ($query_order != '' ? ", " : "")."meta_ghost.meta_value + 0 ASC";
	}*/

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

		case 'popular':
			$query_select .= ", productID, COUNT(answerID) AS productAmount";
			$query_join .= " LEFT JOIN ".$wpdb->prefix."webshop_sent ON ".$wpdb->posts.".ID = ".$wpdb->prefix."webshop_sent.productID LEFT JOIN ".$wpdb->base_prefix."form2answer USING (answerID)"; // AND answerCreated > DATE_SUB(NOW(), INTERVAL 3 MONTH)
			$query_group = "productID";
			$query_order .= ($query_order != '' ? ", " : "")."productAmount DESC";
		break;

		case 'random':
			$query_order .= ($query_order != '' ? ", " : "")."RAND()";
		break;

		case 'size':
			$size_post_name = $obj_webshop->get_post_name_for_type('size');

			if($size_post_name != '')
			{
				$query_join .= " LEFT JOIN ".$wpdb->postmeta." AS meta_size ON ".$wpdb->posts.".ID = meta_size.post_id AND meta_size.meta_key = '".esc_sql($obj_webshop->meta_prefix.$size_post_name)."'";
				$query_order .= ($query_order != '' ? ", " : "")."meta_size.meta_value + 0"." ASC";
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

	$json_output['product_response'] = array();

	$result = $wpdb->get_results("SELECT ID, post_title, post_excerpt, post_content".$query_select." FROM ".$wpdb->posts.$query_join." WHERE post_type = 'mf_products' AND post_status = 'publish'".$query_where.($query_group != '' ? " GROUP BY ".$query_group : "").($query_order != '' ? " ORDER BY ".$query_order : ""));

	//$json_output['last_query'] = $wpdb->last_query;

	foreach($result as $r)
	{
		$obj_webshop->get_product_data(array('product' => $r, 'single_image' => true, 'show_location_in_data' => false), $json_output);
	}

	$json_output['success'] = true;
}

if(isset($_REQUEST['get_amount']))
{
	$json_output['product_amount'] = count($json_output['product_response']);

	unset($json_output['product_response']);
}

echo json_encode($json_output);