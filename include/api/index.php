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

$json_output = array(
	'success' => false,
);

$type = check_var('type');

//$arr_fields_excluded = array($obj_webshop->meta_prefix.'searchable');

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
					'post_url' => get_permalink($r->ID),
				);
			}

			$json_output['success'] = true;
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

			$json_output['admin_webshop_response'] = array(
				'type' => $type,
				'post_id' => $post_id,
				'post_title' => "",
				'post_name' => "",
				'meta_boxes' => array(),
			);

			if($post_id > 0)
			{
				$query_where = "";

				if(1 == 1 || !IS_ADMIN)
				{
					$query_where .= " AND post_author = '".get_current_user_id()."'";
				}

				$result = $wpdb->get_results($wpdb->prepare("SELECT post_title, post_name, post_type FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = 'publish'".$query_where, $obj_webshop->post_type_products.$obj_webshop->option_type));

				foreach($result as $r)
				{
					$json_output['admin_webshop_response']['post_title'] = $r->post_title;
					$json_output['admin_webshop_response']['post_name'] = $r->post_name;

					$arr_meta_boxes = $obj_webshop->rwmb_meta_boxes(array());

					foreach($arr_meta_boxes as $box_id => $arr_meta_box)
					{
						if(!isset($arr_meta_box['context']))
						{
							$arr_meta_boxes[$box_id]['context'] = 'normal';
						}

						if(in_array($r->post_type, $arr_meta_box['post_types']))
						{
							foreach($arr_meta_box['fields'] as $field_id => $arr_field)
							{
								$arr_meta_boxes[$box_id]['fields'][$field_id]['error'] = $arr_meta_boxes[$box_id]['fields'][$field_id]['class'] = $arr_meta_boxes[$box_id]['fields'][$field_id]['attributes'] = $arr_meta_boxes[$box_id]['fields'][$field_id]['suffix'] = $arr_meta_boxes[$box_id]['fields'][$field_id]['description'] = "";

								$id = $arr_meta_box['fields'][$field_id]['id'];
								$type = $arr_meta_boxes[$box_id]['fields'][$field_id]['type'];
								$multiple = isset($arr_meta_box['fields'][$field_id]['multiple']) ? $arr_meta_box['fields'][$field_id]['multiple'] : false;

								/*if(!in_array($id, $arr_fields_excluded))
								{*/
									//Add options
									switch($type)
									{
										case 'custom_categories':
											$post_name_temp = str_replace($obj_webshop->meta_prefix, "", $id);
											$post_id_temp = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_name = %s", $obj_webshop->post_type_document_type, $post_name_temp));

											$arr_data = array();
											get_post_children(array(
												'add_choose_here' => true,
												'post_type' => $obj_webshop->post_type_custom_categories,
												'join' => " INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = '".$obj_webshop->meta_prefix."document_type'",
												'where' => "meta_value = '".esc_sql($post_id_temp)."'",
												//'debug' => true,
											), $arr_data);

											$arr_meta_boxes[$box_id]['fields'][$field_id]['options'] = $arr_data;
										break;

										case 'education':
											if(is_plugin_active('mf_education/index.php'))
											{
												$obj_education = new mf_education();

												$arr_data = array();
												get_post_children(array('add_choose_here' => false, 'post_type' => $obj_education->post_type), $arr_data);

												$arr_meta_boxes[$box_id]['fields'][$field_id]['options'] = $arr_data;

												$multiple = true;
											}
										break;

										case 'event':
											if(is_plugin_active('mf_calendar/index.php'))
											{
												$obj_calendar = new mf_calendar();

												$arr_data = array();
												get_post_children(array('add_choose_here' => true, 'post_type' => $obj_calendar->post_type), $arr_data);

												$arr_meta_boxes[$box_id]['fields'][$field_id]['options'] = $arr_data;

												$arr_meta_boxes[$box_id]['fields'][$field_id]['class'] .= " has_suffix";
												$arr_meta_boxes[$box_id]['fields'][$field_id]['suffix'] = "<a href='".admin_url("post-new.php?post_type=".$obj_calendar->post_type)."'><i class='fa fa-plus-circle fa-lg'></i></a>";
											}

											else
											{
												$arr_meta_boxes[$box_id]['fields'][$field_id]['error'] = sprintf(__("You have to install the plugin %s first", 'lang_webshop'), "MF Calendar");
											}
										break;

										case 'location':
										case 'select3':
											$multiple = true;
										break;

										case 'page':
											$arr_data = array();
											get_post_children(array('add_choose_here' => true), $arr_data);

											$arr_meta_boxes[$box_id]['fields'][$field_id]['options'] = $arr_data;
										break;

										case 'social':
											if(is_plugin_active('mf_social_feed/index.php'))
											{
												$obj_social_feed = new mf_social_feed();

												$arr_data = array();
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

									switch($type)
									{
										case 'custom_categories':
										case 'education':
										case 'event':
										case 'location':
										case 'select':
										case 'select3':
											if($multiple)
											{
												$arr_meta_boxes[$box_id]['fields'][$field_id]['multiple'] = $multiple;
												$arr_meta_boxes[$box_id]['fields'][$field_id]['class'] .= " form_select_multiple";
												$arr_meta_boxes[$box_id]['fields'][$field_id]['attributes'] = " class='multiselect' multiple size='".get_select_size(array('count' => count($arr_meta_boxes[$box_id]['fields'][$field_id]['options'])))."'";
											}
										break;
									}

									//Add saved value
									$arr_meta_boxes[$box_id]['fields'][$field_id]['value'] = get_post_meta($post_id, $id, ($multiple == true ? false : true));
								/*}

								else
								{
									unset($arr_meta_boxes[$box_id]['fields'][$field_id]);
								}*/
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
		}

		else
		{
			$json_output['redirect'] = wp_login_url();
		}
	break;

	case 'admin_webshop_save':
		if(is_user_logged_in())
		{
			$post_id = check_var('post_id', 'int');

			$json_output['admin_webshop_response'] = array(
				'type' => $type,
				'post_id' => $post_id,
				//'debug' => var_export($_REQUEST, true),
			);

			if($post_id > 0)
			{
				$query_where = "";

				if(1 == 1 || !IS_ADMIN)
				{
					$query_where .= " AND post_author = '".get_current_user_id()."'";
				}

				$result = $wpdb->get_results($wpdb->prepare("SELECT post_title, post_name, post_type FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = 'publish'".$query_where, $obj_webshop->post_type_products.$obj_webshop->option_type));

				foreach($result as $r)
				{
					$post_data = array(
						'ID' => $post_id,
						'meta_input' => array(),
					);

					$post_title_old = $r->post_title;
					$post_title_new = check_var('post_title');

					if($post_title_new != $post_title_old)
					{
						$post_data['post_title'] = $post_title_new;
						//do_log(sprintf("Changed from %s to %s for %s", $post_title_old, $post_title_new, 'post_title'));
					}

					/*$post_name_old = $r->post_name;
					$post_name_new = check_var('post_name');

					if($post_name_new != $post_name_old)
					{
						$post_data['post_name'] = $post_name_new;
						//do_log(sprintf("Changed from %s to %s for %s in %s", $post_name_old, $post_name_new, 'post_name'));
					}*/

					$arr_meta_boxes = $obj_webshop->rwmb_meta_boxes(array());

					foreach($arr_meta_boxes as $box_id => $arr_meta_box)
					{
						if(in_array($r->post_type, $arr_meta_box['post_types']))
						{
							foreach($arr_meta_box['fields'] as $field_id => $arr_field)
							{
								$id = $arr_meta_box['fields'][$field_id]['id'];
								$type = $arr_meta_boxes[$box_id]['fields'][$field_id]['type'];
								$multiple = isset($arr_meta_box['fields'][$field_id]['multiple']) ? $arr_meta_box['fields'][$field_id]['multiple'] : false;

								/*if(!in_array($id, $arr_fields_excluded))
								{*/
									switch($type)
									{
										case 'education':
										case 'location':
										case 'select3':
											$multiple = true;
											$multiple = true;
										break;
									}

									$post_value_old = get_post_meta($post_id, $id, ($multiple == true ? false : true));
									$post_value_new = check_var($id, ($multiple == true ? 'array' : 'char'));

									if($post_value_new != $post_value_old)
									{
										$post_data['meta_input'][$id] = $post_value_new;
										//do_log(sprintf("Changed from %s to %s for %s in %s", var_export($post_value_old, true), var_export($post_value_new, true), $id, $r->post_title));
									}
								//}
							}
						}
					}

					if(count($post_data) > 2)
					{
						if(wp_update_post($post_data) > 0)
						{
							$json_output['success'] = true;
							$json_output['message'] = __("I have saved the information for you", 'lang_webshop');
						}

						else
						{
							$json_output['message'] = __("I could not update the information for you", 'lang_webshop');
						}
					}

					else
					{
						$json_output['message'] = __("It does not look like you changed anything, so nothing was saved", 'lang_webshop');
					}

					//$json_output['admin_webshop_response']['meta_boxes'] = $arr_meta_boxes;
				}
			}

			else
			{
				//Save initial information
			}
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