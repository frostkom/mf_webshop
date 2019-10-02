<?php

if(!defined('ABSPATH'))
{
	header('Content-Type: application/json');

	$folder = str_replace("/wp-content/plugins/mf_webshop/include/api", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

do_action('run_cache', array('suffix' => 'json'));

$obj_webshop = new mf_webshop();

$json_output = array(
	'success' => false,
);

$type = check_var('type');

$arr_type = explode("/", $type);

$type_switch = $arr_type[0];

$arr_fields_excluded = array($obj_webshop->meta_prefix.'searchable');

switch($type_switch)
{
	case 'admin':
		if(is_user_logged_in())
		{
			if(isset($arr_type[3]) && in_array($arr_type[3], array('list', 'edit', 'save')))
			{
				$obj_webshop->option_type = "_".$arr_type[2];
				$arr_type[2] = $arr_type[3];
			}

			$type_temp = $arr_type[1]."/".$arr_type[2];

			switch($type_temp)
			{
				case 'webshop/list':
					$arr_list = array();

					$query_where = "";

					if(1 == 1 || !IS_ADMIN)
					{
						$query_where .= " AND post_author = '".get_current_user_id()."'";
					}

					$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_status, post_modified FROM ".$wpdb->posts." WHERE post_type = %s AND (post_status = %s OR post_status = %s)".$query_where, $obj_webshop->post_type_products.$obj_webshop->option_type, 'publish', 'draft'));

					foreach($result as $r)
					{
						$arr_list[] = array(
							'post_id' => $r->ID,
							'post_title' => $r->post_title.($r->post_status == 'draft' ? " (".__("Draft").")" : ""),
							'post_url' => get_permalink($r->ID),
							'post_modified' => format_date($r->post_modified),
						);
					}

					$json_output['success'] = true;
					$json_output['admin_webshop_response'] = array(
						'type' => $arr_type[0]."_".str_replace("/", "_", $type_temp),
						'list' => $arr_list,
					);
				break;

				case 'webshop/edit':
					$post_id = isset($arr_type[3]) ? $arr_type[3] : 0;

					$json_output['admin_webshop_response'] = array(
						'type' => $arr_type[0]."_".str_replace("/", "_", $type_temp),
						'post_id' => $post_id,
						'post_title' => "",
						'post_name' => "",
						'meta_boxes' => array(),
						'timestamp' => date("Y-m-d H:i:s"),
					);

					if($post_id > 0)
					{
						$obj_webshop->get_option_type_from_post_id($post_id);

						$arr_meta_boxes = $obj_webshop->rwmb_meta_boxes(array());

						$query_where = "";

						if(1 == 1 || !IS_ADMIN)
						{
							$query_where .= " AND post_author = '".get_current_user_id()."'";
						}

						$result = $wpdb->get_results($wpdb->prepare("SELECT post_title, post_name, post_type, post_author FROM ".$wpdb->posts." WHERE post_type = %s AND (post_status = %s OR post_status = %s) AND ID = '%d'".$query_where, $obj_webshop->post_type_products.$obj_webshop->option_type, 'publish', 'draft', $post_id));

						foreach($result as $r)
						{
							$json_output['admin_webshop_response']['post_title'] = $post_title = $r->post_title;
							$json_output['admin_webshop_response']['post_name'] = $post_name = $r->post_name;
							$post_type = $r->post_type;
							$post_author = $r->post_author;

							foreach($arr_meta_boxes as $box_id => $arr_meta_box)
							{
								if(!isset($arr_meta_box['context']))
								{
									$arr_meta_boxes[$box_id]['context'] = 'normal';
								}

								if(in_array($post_type, $arr_meta_box['post_types']))
								{
									foreach($arr_meta_box['fields'] as $field_id => $arr_field)
									{
										$arr_meta_boxes[$box_id]['fields'][$field_id]['error'] = $arr_meta_boxes[$box_id]['fields'][$field_id]['class'] = "";
										$arr_children_temp = array();

										$id_temp = $arr_meta_box['fields'][$field_id]['id'];
										$value_temp = "";
										$type_temp = $arr_meta_boxes[$box_id]['fields'][$field_id]['type'];
										$multiple_temp = isset($arr_meta_box['fields'][$field_id]['multiple']) ? $arr_meta_box['fields'][$field_id]['multiple'] : false;

										$display_temp = $arr_meta_boxes[$box_id]['fields'][$field_id]['display'] = !in_array($id_temp, $arr_fields_excluded);

										if($display_temp)
										{
											// Add options
											switch($type_temp)
											{
												case 'custom_categories':
													$post_name_temp = str_replace($obj_webshop->meta_prefix, "", $id_temp);
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

														$multiple_temp = true;
													}
												break;

												case 'event':
													if(!is_plugin_active('mf_calendar/index.php'))
													{
														$arr_meta_boxes[$box_id]['fields'][$field_id]['error'] = sprintf(__("You have to install the plugin %s first", 'lang_webshop'), "MF Calendar");
													}
												break;

												case 'location':
												case 'select3':
													$multiple_temp = true;
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

											// Add multiple attributes
											switch($type_temp)
											{
												case 'custom_categories':
												case 'education':
												//case 'event':
												case 'location':
												case 'select':
												case 'select3':
													if($multiple_temp)
													{
														$arr_meta_boxes[$box_id]['fields'][$field_id]['class'] = " form_select_multiple";
														$arr_meta_boxes[$box_id]['fields'][$field_id]['attributes'] = " class='multiselect' multiple size='".get_select_size(array('count' => count($arr_meta_boxes[$box_id]['fields'][$field_id]['options'])))."'";
													}
												break;
											}

											// Get saved value
											switch($type_temp)
											{
												case 'file_advanced':
													$value_temp = array();

													$result_files = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM ".$wpdb->postmeta." WHERE post_id = '%d' AND meta_key = %s", $post_id, $id_temp));

													foreach($result_files as $r_file)
													{
														list($file_name, $file_url) = get_attachment_data_by_id($r_file->meta_value);

														$value_temp[] = $file_name."|".$file_url."|".$r_file->meta_value;
													}
												break;

												case 'custom_categories':
												case 'education':
												//case 'event':
												case 'location':
												case 'select':
												case 'select3':
													$value_temp = get_post_meta($post_id, $id_temp, ($multiple_temp != true));

													if(isset($value_temp[0]) && is_array($value_temp[0]))
													{
														$value_temp = $value_temp[0]; // MB saves as array(0 => array(0, 1)) but we want it to be array(0, 1) when we render it
													}
												break;

												default:
													$value_temp = get_post_meta($post_id, $id_temp, ($multiple_temp == true ? false : true));
												break;
											}

											// Get default value if empty
											if($value_temp == '' || $value_temp == 0)
											{
												switch($type_temp)
												{
													case 'email':
														$user_data = get_userdata($post_author);

														$value_temp = $user_data->user_email;
													break;

													case 'event':
														$obj_webshop->create_product_event_connection($post_id);
													break;
												}
											}

											// Get child values
											switch($type_temp)
											{
												case 'event':
													if(is_plugin_active("mf_calendar/index.php"))
													{
														$obj_calendar = new mf_calendar();

														$result_children = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_content FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND post_status = %s AND meta_key = %s AND meta_value = '%d'", $obj_calendar->post_type_event, 'publish', $obj_calendar->meta_prefix.'calendar', $value_temp));

														if($wpdb->num_rows > 0)
														{
															foreach($result_children as $r_children)
															{
																$event_start = get_post_meta($r_children->ID, $obj_calendar->meta_prefix.'start', true);
																$event_end = get_post_meta($r_children->ID, $obj_calendar->meta_prefix.'end', true);

																@list($event_start_date, $event_start_time) = explode(" ", $event_start, 2);
																@list($event_end_date, $event_end_time) = explode(" ", $event_end, 2);

																if($event_end_date >= date("Y-m-d") || ($event_end_date < DEFAULT_DATE && $event_start_date >= date("Y-m-d") || $event_start_date < DEFAULT_DATE))
																{
																	$event_location = get_post_meta($r_children->ID, $obj_calendar->meta_prefix.'location', true);
																	$event_coordinates = get_post_meta($r_children->ID, $obj_calendar->meta_prefix.'coordinates', true);

																	$arr_children_temp[$r_children->ID] = array(
																		'name' => $r_children->post_title,
																		'text' => $r_children->post_content,
																		'location' => $event_location,
																		'coordinates' => $event_coordinates,
																		'start_date' => $event_start_date,
																		'start_time' => $event_start_time,
																		'end_date' => $event_end_date,
																		'end_time' => $event_end_time,
																		'fields' => $obj_webshop->get_event_fields(array('post_id' => $r_children->ID)),
																	);
																}
															}
														}

														if(count($arr_children_temp) == 0)
														{
															$arr_children_temp[0] = array(
																'name' => '',
																'text' => '',
																'location' => '',
																'coordinates' => '',
																'start_date' => '',
																'start_time' => '',
																'end_date' => '',
																'end_time' => '',
																'fields' => $obj_webshop->get_event_fields(),
															);
														}
													}
												break;
											}
										}

										/*else
										{
											unset($arr_meta_boxes[$box_id]['fields'][$field_id]);
										}*/

										$arr_meta_boxes[$box_id]['fields'][$field_id]['value'] = $value_temp;
										$arr_meta_boxes[$box_id]['fields'][$field_id]['multiple'] = $multiple_temp;
										$arr_meta_boxes[$box_id]['fields'][$field_id]['children'] = $arr_children_temp;
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

					else
					{
						$user_data = get_userdata(get_current_user_id());

						$json_output['admin_webshop_response']['post_title'] = $user_data->display_name;
					}

					$json_output['admin_webshop_response']['option_type'] = $obj_webshop->option_type;
					$json_output['admin_webshop_response']['name_product'] = get_option_or_default('setting_webshop_replace_product'.$obj_webshop->option_type, __("Product", 'lang_webshop'));
				break;

				case 'webshop/save':
					$post_id = check_var('post_id', 'int');
					$post_title = check_var('post_title');

					$json_output['admin_webshop_response'] = array(
						'type' => $type,
						'post_id' => $post_id,
						//'debug' => var_export($_REQUEST, true),
					);

					if($post_id > 0)
					{
						$obj_webshop->get_option_type_from_post_id($post_id);

						$arr_meta_boxes = $obj_webshop->rwmb_meta_boxes(array());

						$query_where = "";

						if(1 == 1 || !IS_ADMIN)
						{
							$query_where .= " AND post_author = '".get_current_user_id()."'";
						}

						$result = $wpdb->get_results($wpdb->prepare("SELECT post_title, post_name, post_type FROM ".$wpdb->posts." WHERE post_type = %s AND (post_status = %s OR post_status = %s) AND ID = '%d'".$query_where, $obj_webshop->post_type_products.$obj_webshop->option_type, 'publish', 'draft', $post_id));

						foreach($result as $r)
						{
							$post_title_old = $r->post_title;
							//$post_name_old = $r->post_name;
							$post_type = $r->post_type;

							$error = $reload = $updated = false;

							$post_data = array(
								'ID' => $post_id,
								'post_modified' => date("Y-m-d H:i:s"),
								'meta_input' => array(),
							);

							if($post_title != $post_title_old)
							{
								$post_data['post_title'] = $post_title;
								//do_log(sprintf("Changed from %s to %s for %s", $post_title_old, $post_title, 'post_title'));

								$updated = true;
							}

							/*$post_name_new = check_var('post_name');

							if($post_name_new != $post_name_old)
							{
								$post_data['post_name'] = $post_name_new;
								//do_log(sprintf("Changed from %s to %s for %s in %s", $post_name_old, $post_name_new, 'post_name'));

								$updated = true;
							}*/

							foreach($arr_meta_boxes as $box_id => $arr_meta_box)
							{
								if(in_array($post_type, $arr_meta_box['post_types']))
								{
									foreach($arr_meta_box['fields'] as $field_id => $arr_field)
									{
										$id_temp = $arr_meta_box['fields'][$field_id]['id'];
										$type_temp = $arr_meta_boxes[$box_id]['fields'][$field_id]['type'];
										$multiple_temp = isset($arr_meta_box['fields'][$field_id]['multiple']) ? $arr_meta_box['fields'][$field_id]['multiple'] : false;

										if(!in_array($id_temp, $arr_fields_excluded))
										{
											// Prepare multiple
											switch($type_temp)
											{
												case 'education':
												case 'location':
												case 'select3':
													$multiple_temp = true;
												break;
											}

											// Prepare or save values
											switch($type_temp)
											{
												case 'event':
													if(is_plugin_active('mf_calendar/index.php'))
													{
														$obj_calendar = new mf_calendar();

														$calendar_id = get_post_meta($post_id, $id_temp, true);

														$arr_event_id = check_var($id_temp."_id", 'array');
														$arr_event_name = check_var($id_temp."_name", 'array');
														$arr_event_location = check_var($id_temp."_location", 'array');
														$arr_event_coordinates = check_var($id_temp."_coordinates", 'array');
														$arr_event_start_date = check_var($id_temp."_start_date", 'array');
														$arr_event_start_time = check_var($id_temp."_start_time", 'array');
														$arr_event_end_date = check_var($id_temp."_end_date", 'array');
														$arr_event_end_time = check_var($id_temp."_end_time", 'array');
														$arr_event_text = check_var($id_temp."_text");

														$count_temp = count($arr_event_name);

														for($i = 0; $i < $count_temp; $i++)
														{
															$arr_event_start_date[$i] = check_var($arr_event_start_date[$i], 'date', false);
															$arr_event_start_time[$i] = check_var($arr_event_start_time[$i], 'time', false);
															$arr_event_end_date[$i] = check_var($arr_event_end_date[$i], 'date', false, $arr_event_start_date[$i]);
															$arr_event_end_time[$i] = check_var($arr_event_end_time[$i], 'time', false, $arr_event_start_time[$i]);

															$event_start = $arr_event_start_date[$i].($arr_event_start_time[$i] != '' ? " ".$arr_event_start_time[$i] : '');
															$event_end = $arr_event_end_date[$i].($arr_event_end_time[$i] != '' ? " ".$arr_event_end_time[$i] : '');

															if($arr_event_id[$i] > 0)
															{
																$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND post_status = %s AND ID = '%d' AND meta_key = %s AND meta_value = '%d'", $obj_calendar->post_type_event, 'publish', $arr_event_id[$i], $obj_calendar->meta_prefix.'calendar', $calendar_id));
																$rows = $wpdb->num_rows;

																if($rows == 1)
																{
																	if($arr_event_name[$i] != '')
																	{
																		if($event_start > $event_end)
																		{
																			$error = true;
																			$json_output['message'] = __("The end date must be later than the start date", 'lang_webshop')." (".$event_start." -> ".$event_end.")";
																		}

																		else if($event_end > date("Y-m-d H:i", strtotime($event_start." +".($obj_webshop->event_max_length - 1)." day")))
																		{
																			$error = true;
																			$json_output['message'] = sprintf(__("The end date must be within %d days from the start date", 'lang_webshop'), $obj_webshop->event_max_length)." (".$event_start." -> ".$event_end.")";
																		}

																		else
																		{
																			$post_data_event = array(
																				'ID' => $arr_event_id[$i],
																				'post_title' => $arr_event_name[$i],
																				'post_content' => $arr_event_text[$i],
																				//'post_modified' => date("Y-m-d H:i:s"),
																				'meta_input' => array(
																					$obj_calendar->meta_prefix.'location' => $arr_event_location[$i],
																					$obj_calendar->meta_prefix.'coordinates' => $arr_event_coordinates[$i],
																					$obj_calendar->meta_prefix.'start' => $event_start,
																					$obj_calendar->meta_prefix.'end' => $event_end,
																				),
																			);

																			if(wp_update_post($post_data_event) > 0)
																			{
																				$updated = true;

																				$obj_webshop->set_events_meta_boxes($arr_event_id[$i], $i);

																				//do_action('rwmb_after_save_post', $arr_event_id[$i]); // Hook must be moved from within is_admin() in index.php
																				$obj_calendar->rwmb_after_save_post($arr_event_id[$i]);
																			}

																			else
																			{
																				do_log("I could not update (".var_export($post_data_event, true).")");
																			}
																		}
																	}

																	else
																	{
																		if(wp_trash_post($arr_event_id[$i]))
																		{
																			$reload = $updated = true;
																		}

																		else
																		{
																			do_log("I could not remove the post (".$arr_event_id[$i].")");
																		}
																	}
																}
															}

															else
															{
																if($arr_event_name[$i] != '')
																{
																	if($event_start > $event_end)
																	{
																		$error = true;
																		$json_output['message'] = __("The end date must be later than the start date", 'lang_webshop')." (".$event_start." -> ".$event_end.")";
																	}

																	else if($event_end > date("Y-m-d H:i", strtotime($event_start." +".($obj_webshop->event_max_length - 1)." day")))
																	{
																		$error = true;
																		$json_output['message'] = sprintf(__("The end date must be within %d days from the start date", 'lang_webshop'), $obj_webshop->event_max_length)." (".$event_start." -> ".$event_end.")";
																	}

																	else
																	{
																		$post_data_event = array(
																			'post_type' => $obj_calendar->post_type_event,
																			'post_status' => 'publish',
																			'post_title' => $arr_event_name[$i],
																			'post_content' => $arr_event_text[$i],
																			'meta_input' => array(
																				$obj_calendar->meta_prefix.'calendar' => $calendar_id,
																				$obj_calendar->meta_prefix.'location' => $arr_event_location[$i],
																				$obj_calendar->meta_prefix.'coordinates' => $arr_event_coordinates[$i],
																				//$obj_calendar->meta_prefix.'category' => $arr_event_category[$i],
																				$obj_calendar->meta_prefix.'start' => $event_start,
																				$obj_calendar->meta_prefix.'end' => $event_end,
																			),
																		);

																		$post_id_temp = wp_insert_post($post_data_event);

																		if($post_id_temp > 0)
																		{
																			$reload = $updated = true;

																			$obj_webshop->set_events_meta_boxes($post_id_temp, $i);

																			//do_action('rwmb_after_save_post', $post_id_temp); // Hook must be moved from within is_admin() in index.php
																			$obj_calendar->rwmb_after_save_post($post_id_temp);
																		}

																		else
																		{
																			do_log("I could not save (".var_export($post_data_event, true).")");
																		}
																	}
																}
															}
														}
													}
												break;

												case 'file_advanced':
													$post_value_new = check_var($id_temp, 'char');

													list($arr_files, $arr_ids) = get_attachment_to_send($post_value_new);

													// Use this instead?
													/*if($obj_webshop->update_rwmb_post_meta($post_id, $id_temp, $arr_ids))
													{
														$updated = true;
													}*/
													#################################
													/* Delete old connections */
													$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->postmeta." WHERE post_id = '%d' AND meta_key = %s AND meta_value NOT IN('".implode("','", $arr_ids)."')", $post_id, $id_temp));

													if($wpdb->num_rows > 0)
													{
														$updated = true;
													}

													/* Insert new connections */
													foreach($arr_ids as $file_id)
													{
														$wpdb->get_results($wpdb->prepare("SELECT meta_id FROM ".$wpdb->postmeta." WHERE post_id = '%d' AND meta_key = %s AND meta_value = '%d'", $post_id, $id_temp, $file_id));

														if($wpdb->num_rows == 0)
														{
															$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->postmeta." SET post_id = '%d', meta_key = %s, meta_value = '%d'", $post_id, $id_temp, $file_id));

															if($wpdb->num_rows > 0)
															{
																$updated = true;
															}
														}
													}
													#################################
												break;

												//case 'custom_categories':
												//case 'education':
												//case 'event':
												//case 'location':
												//case 'select':
												case 'select3':
													$post_value_new = check_var($id_temp, ($multiple_temp == true ? 'array' : 'char'));

													if($obj_webshop->update_rwmb_post_meta($post_id, $id_temp, $post_value_new))
													{
														$updated = true;
													}
												break;

												default:
													$post_value_old = get_post_meta($post_id, $id_temp, ($multiple_temp == true ? false : true));
													$post_value_new = check_var($id_temp, ($multiple_temp == true ? 'array' : 'char'));

													if($post_value_new != $post_value_old)
													{
														$post_data['meta_input'][$id_temp] = $post_value_new;
														//do_log(sprintf("Changed from %s to %s for %s in %s", var_export($post_value_old, true), var_export($post_value_new, true), $id_temp, $post_title));
													}
												break;
											}
										}
									}
								}
							}

							if($error == true)
							{
								// Do nothing. $json_output['message'] should be set so the user knows what's gone wrong
							}

							else if($updated == true)
							{
								if(wp_update_post($post_data) > 0 || $updated == true)
								{
									do_action('rwmb_after_save_post', $post_id);

									$json_output['success'] = true;
									$json_output['message'] = sprintf(__("I have saved the information for you. %sView the page here%s", 'lang_webshop'), "<a href='".get_permalink($post_id)."'>", "</a>");

									if($reload == true)
									{
										$json_output['next_request'] = "admin/webshop/edit/".$post_id;
									}
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
						}
					}

					else
					{
						$obj_webshop->option_type = check_var('option_type');

						$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = %s AND post_title = %s AND post_author = '%d'", $obj_webshop->post_type_products.$obj_webshop->option_type, 'publish', $post_title, get_current_user_id()));

						if($wpdb->num_rows == 0)
						{
							$post_data = array(
								'post_title' => $post_title,
								'post_type' => $obj_webshop->post_type_products.$obj_webshop->option_type,
								'post_status' => 'publish',
							);

							$post_id = wp_insert_post($post_data);

							if($post_id > 0)
							{
								do_action('rwmb_after_save_post', $post_id);

								$json_output['success'] = true;
								$json_output['message'] = sprintf(__("I have saved the information for you. %sView the page here%s", 'lang_webshop'), "<a href='".get_permalink($post_id)."'>", "</a>");
								$json_output['next_request'] = "admin/webshop/edit/".$post_id;
								//$json_output['debug'] = "Created: ".$wpdb->last_query;
							}

							else
							{
								$json_output['message'] = __("I could not save the information for you", 'lang_webshop');
							}
						}

						else
						{
							$json_output['message'] = __("One with that title already exists", 'lang_webshop');
						}
					}
				break;
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
					'class' => $event['list_class'],
				);
			}

			$arr_days[] = array(
				'date' => $date_temp,
				'number' => $day_number,
				'class' => $class,
				'event_amount_left' => $result['event_amount_left'],
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
		$option_type = check_var('option_type');
		$start_date = check_var('start_date', 'date', true, date("Y-m-d H:i:s"));
		$product_id = check_var('product_id', 'int');
		$event_id = check_var('event_id', 'int');
		$event_type = check_var('event_type');
		$category = check_var('category');
		$order_by = check_var('order_by');
		$latitude = check_var('latitude');
		$longitude = check_var('longitude');
		$limit = check_var('limit', 'int', true, '0');
		$amount = check_var('amount', 'int');

		$json_output = $obj_webshop->get_events(array(
			'id' => $id,
			'option_type' => $option_type,
			'product_id' => $product_id,
			'event_id' => $event_id,
			'event_type' => $event_type,
			'start_date' => $start_date,
			'category' => $category,
			'order_by' => $order_by,
			'latitude' => $latitude,
			'longitude' => $longitude,
			'limit' => $limit,
			'amount' => $amount,
		));
	break;

	case 'filter_products':
		$id = check_var('id', 'char');
		$option_type = check_var('option_type', 'char');
		$category = check_var('category', 'char');
		$order_by = check_var('order_by');
		$latitude = check_var('latitude');
		$longitude = check_var('longitude');
		$limit = check_var('limit', 'int', true, '0');
		$amount = check_var('amount', 'int');

		$json_output = $obj_webshop->get_filter_products(array(
			'id' => $id,
			'option_type' => $option_type,
			'category' => $category,
			'order_by' => $order_by,
			'latitude' => $latitude,
			'longitude' => $longitude,
			'limit' => $limit,
			'amount' => $amount,
		));
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