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
	case 'admin_webshop_list':
		if(is_user_logged_in())
		{
			$arr_list = array();

			$query_where = "";

			if(1 == 1 || !IS_ADMIN)
			{
				$query_where .= " AND post_author = '".get_current_user_id()."'";
			}

			$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = 'publish'".$query_where, $obj_webshop->post_type_products.$obj_webshop->option_type)); // INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id // AND ".$wpdb->postmeta.".meta_key = %s AND meta_value = %s //, $obj_webshop->meta_prefix.'category', $data['category']

			foreach($result as $r)
			{
				$arr_list[] = array(
					'post_id' => $r->ID,
					'post_title' => $r->post_title,
				);
			}

			$json_output['admin_webshop_response'] = array(
				'type' => $type,
				'list' => $arr_list,
			);
		}

		else
		{
			$json_output['redirect'] = wp_login_url();
		}
	break;

	case 'admin_webshop_edit':
		if(is_user_logged_in())
		{
			$post_id = check_var('post_id', 'int');

			if($post_id > 0)
			{
				
			}

			else
			{
				
			}

			$json_output['admin_webshop_response'] = array(
				'type' => $type,
				'post_id' => $post_id,
			);
		}

		else
		{
			$json_output['redirect'] = wp_login_url();
		}
	break;

	case 'calendar':
		$product_id = check_var('product_id', 'int');
		$date = check_var('date', 'date', true, date("Y-m-d"));

		$month = date("Y-m", strtotime($date));

		$nice_month = month_name(date("m", strtotime($date)))." ".date("Y", strtotime($date));

		$year_now = date("Y", strtotime($date));
		$month_now = date("m", strtotime($date));

		$first_date_of_month = date("Y-m-d", mktime(0, 0, 0, $month_now, 1, $year_now));
		$first_weekday_of_the_month = date("N", strtotime($first_date_of_month));

		$last_date_of_month = date("Y-m-t", strtotime($date));
		$last_weekday_of_the_month = date("N", strtotime($last_date_of_month));

		$date_start = date("Y-m-d", strtotime($first_date_of_month." -".($first_weekday_of_the_month - 1)." day"));
		$date_end = date("Y-m-d", strtotime($last_date_of_month." +".(7 - $last_weekday_of_the_month)." day"));

		$date_temp = $date_start;

		$arr_days = array();

		while($date_temp <= $date_end)
		{
			$day_number = date("j", strtotime($date_temp));

			$class = "";

			if($date_temp == date("Y-m-d"))
			{
				$class .= " today";
			}

			if(substr($date_temp, 0, 7) != substr($date, 0, 7))
			{
				$class .= " disabled";
			}

			$arr_events = array();

			$result = $obj_webshop->get_events(array('product_id' => $product_id, 'exact_date' => $date_temp, 'amount' => 5));

			foreach($result['event_response'] as $event)
			{
				$arr_events[] = array(
					'feed_id' => $event['feed_id'],
				);
			}

			$arr_days[] = array(
				'date' => $date_temp,
				'number' => $day_number,
				'class' => $class,
				'event_amount' => $result['event_amount'],
				'events' => $arr_events,
			);

			$date_temp = date("Y-m-d", strtotime($date_temp." +1 day"));
		}

		$json_output['calendar_response'] = array(
			//'month' => $month,
			'last_month' => date("Y-m-d", strtotime($date." -1 month")),
			'next_month' => date("Y-m-d", strtotime($date." +1 month")),
			'nice_month' => $nice_month,
			'days' => $arr_days,
		);

		$json_output['success'] = true;
	break;

	case 'events':
		$id = check_var('id', 'char');
		$option_type = check_var('option_type', 'char');
		$start_date = check_var('start_date', 'date', true, date("Y-m-d H:i:s"));
		$category = check_var('category', 'char');
		$option_type = check_var('option_type', 'char');
		$product_id = check_var('product_id', 'int');
		$limit = check_var('limit', 'int', true, '0');
		$amount = check_var('amount', 'int');

		$json_output = $obj_webshop->get_events(array('id' => $id, 'option_type' => $option_type, 'product_id' => $product_id, 'start_date' => $start_date, 'category' => $category, 'limit' => $limit, 'amount' => $amount));
	break;

	case 'filter_products':
		$id = check_var('id', 'char');
		$option_type = check_var('option_type', 'char');
		$category = check_var('category', 'char');
		$limit = check_var('limit', 'int', true, '0');
		$amount = check_var('amount', 'int');

		$json_output = $obj_webshop->get_filter_products(array('id' => $id, 'option_type' => $option_type, 'category' => $category, 'limit' => $limit, 'amount' => $amount));
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

		$query_join .= " LEFT JOIN ".$wpdb->postmeta." AS searchable ON ".$wpdb->posts.".ID = searchable.post_id AND searchable.meta_key = '".$obj_webshop->meta_prefix.'searchable'."'";
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

		foreach($result as $r)
		{
			$obj_webshop->get_product_data(array('product' => $r, 'single_image' => true, 'show_location_in_data' => false), $json_output);
		}

		$json_output['success'] = true;

		if($type == 'amount')
		{
			$json_output['product_amount'] = count($json_output['product_response']);

			unset($json_output['product_response']);
		}
	break;
}

echo json_encode($json_output);