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

$type = check_var('type');

switch($type)
{
	case 'events':
		$strID = check_var('strID');
		$strOptionType = check_var('strOptionType');
		$dteDate = check_var('dteDate', 'date', true, date("Y-m-d H:i:s"));
		$intAmount = check_var('intAmount');

		$obj_calendar = new mf_calendar();

		$obj_webshop->option_type = ($strOptionType != '' ? "_".$strOptionType : '');

		$json_output['widget_id'] = $strID;
		$json_output['event_response'] = array();

		$arr_product_ids = $arr_product_translate_ids = array();

		$events_post_name = $obj_webshop->get_post_name_for_type('event');

		if($events_post_name != '')
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, meta_value FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND post_status = 'publish' AND ".$wpdb->postmeta.".meta_key = '".$obj_webshop->meta_prefix.$events_post_name."' AND meta_value > '0'", $obj_webshop->post_type_products.$obj_webshop->option_type));

			foreach($result as $r)
			{
				$arr_categories = get_post_meta($r->ID, $obj_webshop->meta_prefix.'category', false);

				$product_categories = "";

				foreach($arr_categories as $key => $value)
				{
					$product_categories .= ($product_categories != '' ? ", " : "").get_post_title($value);
				}

				$arr_product_ids[] = $r->meta_value;
				$arr_product_translate_ids[$r->meta_value] = array(
					'product_id' => $r->ID,
					'product_title' => $r->post_title,
					'product_categories' => $product_categories,
				);
			}

			if(count($arr_product_ids) > 0)
			{
				$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, calendar.meta_value AS calendar_id, start.meta_value AS post_start 
					FROM ".$wpdb->postmeta." AS calendar 
					INNER JOIN ".$wpdb->posts." ON ".$wpdb->posts.".ID = calendar.post_id AND calendar.meta_key = '".$obj_calendar->meta_prefix."calendar'
					INNER JOIN ".$wpdb->postmeta." AS start ON ".$wpdb->posts.".ID = start.post_id AND start.meta_key = '".$obj_calendar->meta_prefix."start'
				WHERE post_type = %s AND post_status = 'publish' AND calendar.meta_value IN ('".implode("', '", $arr_product_ids)."') AND start.meta_value >= %s ORDER BY start.meta_value ASC", 'mf_calendar_event', $dteDate));

				foreach($result as $r)
				{
					$feed_id = $r->calendar_id;
					$product_id = $arr_product_translate_ids[$feed_id]['product_id'];
					$product_title = $arr_product_translate_ids[$feed_id]['product_title'];
					$product_categories = $arr_product_translate_ids[$feed_id]['product_categories'];

					$post_id = $r->ID;
					$post_title = $r->post_title;
					//$post_url = get_permalink($post_id);

					$post_location = get_post_meta($post_id, $obj_calendar->meta_prefix.'location', true);
					$post_start = $r->post_start;
					//$post_start = get_post_meta($post_id, $obj_calendar->meta_prefix.'start', true);
					$post_end = get_post_meta($post_id, $obj_calendar->meta_prefix.'end', true);

					$json_output['event_response'][] = array(
						//'product_id' => $product_id,
						'product_title' => $product_title,
						'product_categories' => $product_categories,
						'product_url' => get_permalink($product_id),
						'feed_id' => $feed_id,
						//'post_id' => $post_id,
						'post_title' => $post_title,
						//'post_url' => get_permalink($post_id),
						//'post_start' => $post_start,
						'post_start_hour' => date("H", strtotime($post_start)),
						'post_start_minute' => date("i", strtotime($post_start)),
						'post_start_date' => (date("Y-m-d", strtotime($post_start)) == date("Y-m-d") ? __("Today", 'lang_webshop') : date("j", strtotime($post_start))." ".substr(month_name(date("m", strtotime($post_start))), 0, 3)),
						//'post_end' => $post_end,
						'post_duration' => date("H:i", strtotime($post_start))." - ".date("H:i", strtotime($post_end)),
						'post_location' => $post_location,
					);
				}
			}

			$json_output['success'] = true;
		}

		else
		{
			do_log("There was no post_name_for_type for event");
		}
	break;

	case 'amount':
	default:
		$obj_webshop->option_type = check_var('option_type', 'char');

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

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_excerpt, post_content".$query_select." FROM ".$wpdb->posts.$query_join." WHERE post_type = %s AND post_status = 'publish'".$query_where.($query_group != '' ? " GROUP BY ".$query_group : "").($query_order != '' ? " ORDER BY ".$query_order : ""), $obj_webshop->post_type_products.$obj_webshop->option_type));

		//$json_output['last_query'] = $wpdb->last_query;

		foreach($result as $r)
		{
			$obj_webshop->get_product_data(array('product' => $r, 'single_image' => true, 'show_location_in_data' => false), $json_output);
		}

		$json_output['success'] = true;

		if($type == 'amount') //isset($_REQUEST['get_amount'])
		{
			$json_output['product_amount'] = count($json_output['product_response']);

			unset($json_output['product_response']);
		}
	break;
}

echo json_encode($json_output);