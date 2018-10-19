<?php

$obj_webshop = new mf_webshop();
$obj_font_icons = new mf_font_icons();

add_action('wp_head', array($obj_webshop, 'wp_head_single_product'));

get_header();

	if(have_posts())
	{
		include_once("aside.php");

		echo "<article>";

			while(have_posts())
			{
				the_post();

				$post_id = $post->ID;
				$post_content = $post->post_content;

				$obj_webshop->get_option_type_from_post_id($post_id);

				if($post_content == '')
				{
					$post_content = $post->post_excerpt;
				}

				if($post_content == '')
				{
					$size_post_name = $obj_webshop->get_post_name_for_type('description');
					$post_content = get_post_meta($post_id, $obj_webshop->meta_prefix.$size_post_name, true);
				}

				$obj_webshop->product_init(array('post' => $post, 'single' => true, 'single_image' => false));

				foreach($obj_webshop->result as $r)
				{
					$obj_webshop->meta_init(array('meta' => $r, 'single' => true));

					$post_meta = '';
					//$has_data = false;

					switch($obj_webshop->meta_type)
					{
						case 'categories':
							$post_meta = get_post_meta($obj_webshop->product_id, $obj_webshop->meta_prefix.$obj_webshop->meta_name, false);
						break;

						case 'file_advanced':
							$post_meta = get_post_meta_file_src(array('post_id' => $obj_webshop->product_id, 'meta_key' => $obj_webshop->meta_prefix.$obj_webshop->meta_name, 'is_image' => false));
						break;

						case 'global_code':
							$post_meta = $obj_webshop->meta_alt_text;
						break;

						case 'location':
							$post_meta = get_post_meta($obj_webshop->product_id, $obj_webshop->meta_prefix.$obj_webshop->meta_name, false);

							if(is_array($post_meta) && count($post_meta) > 0)
							{
								//Does not work properly
								//$arr_locations = $obj_webshop->sort_location(array('array' => $post_meta, 'reverse' => true));
								$arr_locations = $post_meta;

								$post_meta = "";

								foreach($arr_locations as $location_id)
								{
									$location_title = get_the_title($location_id);
									$obj_webshop->search_url = $obj_webshop->get_template_url(array('location_id' => $location_id));

									if($obj_webshop->search_url != '')
									{
										$location_tag = "<a href='".$obj_webshop->search_url."'>".$location_title."</a>";
									}

									else
									{
										$location_tag = $location_title;
									}

									if($obj_webshop->meta_public == 'no')
									{
										$obj_webshop->product_address .= $location_tag;
									}

									else
									{
										$post_meta .= ($post_meta != '' ? ", " : "").$location_tag;
									}
								}
							}

							else
							{
								$post_meta = "";
							}
						break;
					}

					if($post_meta == '')
					{
						$post_meta = get_post_meta($obj_webshop->product_id, $obj_webshop->meta_prefix.$obj_webshop->meta_name, true);

						if($post_meta != '')
						{
							switch($obj_webshop->meta_type)
							{
								case 'address':
								case 'local_address':
									if($obj_webshop->meta_public == 'no')
									{
										$obj_webshop->product_address .= "<span>".$post_meta."</span>";
										$post_meta = "";
									}
								break;

								case 'checkbox':
									if($post_meta == 1)
									{
										$post_meta = "<i class='fa fa-check green'></i>";

										//$has_data = true;
									}

									else
									{
										$post_meta = "<i class='fa fa-times red'></i>";
									}
								break;

								case 'clock':
								case 'number':
								case 'size':
									//$has_data = true;
								break;

								case 'content':
									$arr_exclude = array("[", "]");
									$arr_include = array("<", ">");

									$post_content = str_replace($arr_exclude, $arr_include, $post_meta);
									$post_meta = "";

									//$has_data = true;
								break;

								case 'custom_categories':
									$post_meta = get_post_title($post_meta);
									//$has_data = true;
								break;

								case 'email':
									$obj_webshop->product_has_email = true;
								break;

								case 'event':
									/*if(is_plugin_active('mf_calendar/index.php'))
									{
										$obj_calendar = new mf_calendar();
										$obj_calendar->get_events(array('feeds' => array($post_meta), 'limit' => 1));

										$post_meta = $obj_calendar->arr_events;
									}*/
								break;

								case 'gps':
									$obj_webshop->product_map = $post_meta;

									$post_meta = "";
								break;

								case 'page':
									$obj_webshop->meta_title = get_the_title($post_meta);
									$post_meta = get_permalink($post_meta);

									//$has_data = true;
								break;

								case 'phone':
									if($obj_webshop->meta_public == 'no')
									{
										$post_meta = "";
									}
								break;

								case 'price':
									if(!isset($sesWebshopCookie)){		$sesWebshopCookie = '';}
									if(!isset($intProductAmount)){		$intProductAmount = '';}

									$intProductAmount_saved = $wpdb->get_var($wpdb->prepare("SELECT productAmount FROM ".$wpdb->prefix."webshop_product2user WHERE productID = '%d' AND webshopDone = '0' AND (userID = '%d' OR webshopCookie = %s)", $obj_webshop->product_id, get_current_user_id(), $sesWebshopCookie));

									$obj_webshop->product_form_buy = "<form method='post' action='' class='mf_form'>"
										.show_textfield(array('name' => 'intProductAmount', 'value' => ($intProductAmount_saved > 0 ? $intProductAmount_saved : $intProductAmount), 'type' => 'number', 'id' => "product_amount"))
										."<div class='form_button'>"
											.show_button(array('name' => 'btnProductBuy', 'text' => ($intProductAmount_saved > 0 ? __("Update Cart", 'lang_webshop') : __("Add to Cart", 'lang_webshop')), 'class' => "button-primary"));

											if($intProductAmount_saved > 0)
											{
												$obj_webshop->product_form_buy .= show_button(array('name' => 'btnProductBuy', 'text' => __("Delete", 'lang_webshop'), 'class' => "button-primary", 'xtra' => "id='product_delete'"));
											}

										$obj_webshop->product_form_buy .= "</div>"
										.input_hidden(array('name' => 'intProductID', 'value' => $obj_webshop->product_id))
										.wp_nonce_field('product_buy_'.$obj_webshop->product_id, '_wpnonce_product_buy', true, false)
									."</form>";

									//$has_data = true;
								break;

								case 'social':
									$obj_webshop->product_social = $post_meta;

									$post_meta = "";
								break;

								case 'container_start':

								break;

								case 'container_end':

								break;

								case 'description':
								case 'ghost':
								case 'interval':
								case 'location':
								case 'text':
								case 'textarea':
								case 'url':
									//Do nothing
								break;

								default:
									$arr_filtered_meta_type = apply_filters('filter_webshop_meta_type', array('page' => 'single', 'meta_type' => $obj_webshop->meta_type, 'post_meta' => $post_meta, 'meta_type_found' => false));

									if($arr_filtered_meta_type['meta_type_found'])
									{
										$post_meta = $arr_filtered_meta_type['post_meta'];
									}

									else
									{
										do_log(sprintf(__("The type '%s' does not have a case", 'lang_webshop'), $obj_webshop->meta_type)." (single)");
									}
								break;
							}
						}
					}

					$obj_webshop->gather_product_meta(array(
						'public' => $obj_webshop->meta_public,
						'title' => $obj_webshop->meta_title,
						'meta' => $post_meta,
						'type' => $obj_webshop->meta_type,
						'symbol' => $obj_webshop->meta_symbol,
					));

					if(($post_meta != '' || in_array($obj_webshop->meta_type, array('categories'))) && get_post_meta($obj_webshop->meta_id, $obj_webshop->meta_prefix.'document_property', true) == "yes")
					{
						$obj_webshop->arr_product_property[] = array(
							'symbol' => $obj_webshop->meta_symbol,
							'title' => $obj_webshop->meta_title,
							'content' => $post_meta,
							'type' => $obj_webshop->meta_type,
						);
					}

					if(($post_meta != '' || in_array($obj_webshop->meta_type, array('heading', 'categories'))) && get_post_meta($obj_webshop->meta_id, $obj_webshop->meta_prefix.'document_quick', true) == "yes")
					{
						$obj_webshop->arr_product_quick[] = array(
							'symbol' => $obj_webshop->meta_symbol,
							'title' => $obj_webshop->meta_title,
							'meta' => $post_meta,
							'type' => $obj_webshop->meta_type,
							//'has_data' => $has_data,
						);
					}
				}

				$ghost_post_name = $obj_webshop->get_post_name_for_type('ghost');

				if($ghost_post_name != '' && get_post_meta($obj_webshop->product_id, $obj_webshop->meta_prefix.$ghost_post_name, true) == true)
				{
					$obj_webshop->product_meta = $obj_webshop->arr_product_quick = $obj_webshop->arr_product_property = array();
				}

				if(is_plugin_active("mf_slideshow/index.php"))
				{
					$obj_slideshow = new mf_slideshow();
				}

				if(get_option('setting_webshop_display_breadcrumbs'.$obj_webshop->option_type) == 'yes')
				{
					$arr_categories = get_post_meta($post_id, $obj_webshop->meta_prefix.'category', false);

					if(count($arr_categories) > 0)
					{
						$obj_webshop->template_shortcodes['breadcrumbs']['html'] .= "<span>";

							$i = 0;

							foreach($arr_categories as $key => $value)
							{
								$obj_webshop->template_shortcodes['breadcrumbs']['html'] .= ($i > 0 ? ", " : "").get_post_title($value);

								$i++;
							}

						$obj_webshop->template_shortcodes['breadcrumbs']['html'] .= "</span>";
					}

					$obj_webshop->template_shortcodes['breadcrumbs']['html'] .= "<span>".$obj_webshop->product_title."</span>";
				}

				$obj_webshop->template_shortcodes['heading']['html'] = $obj_webshop->product_title;

				if($obj_webshop->product_address != '')
				{
					$obj_webshop->template_shortcodes['address']['html'] .= $obj_webshop->product_address;
				}

				if($obj_webshop->product_categories != '')
				{
					$obj_webshop->template_shortcodes['categories']['html'] .= $obj_webshop->product_categories;
				}

				if(is_plugin_active("mf_share/index.php") && shortcode_exists('mf_share'))
				{
					$obj_share = new mf_share();

					if($obj_share->is_correct_page())
					{
						$obj_webshop->template_shortcodes['share']['html'] .= apply_filters('the_content', "[mf_share type='options']");
					}
				}

				/*if($obj_webshop->template_shortcodes['address']['html'] != '' || $obj_webshop->template_shortcodes['share']['html'] != '')
				{
					echo "<div>".$obj_webshop->template_shortcodes['address']['html'].$obj_webshop->template_shortcodes['share']['html']."</div>";
				}*/

				if(isset($obj_slideshow) && count($obj_webshop->slideshow_images) > 0)
				{
					$obj_webshop->template_shortcodes['slideshow']['html'] = $obj_slideshow->show(array('images' => $obj_webshop->slideshow_images));
				}

				if($post_content != '')
				{
					$obj_webshop->template_shortcodes['description']['html'] = apply_filters('the_content', $post_content);
				}

				$count_temp = count($obj_webshop->arr_product_quick);

				if($count_temp > 0)
				{
					//$has_data = false;

					$product_quick_temp = "";

					for($i = 0; $i < $count_temp; $i++)
					{
						$product_quick_temp .= "<li class='".$obj_webshop->arr_product_quick[$i]['type']."'>";

							switch($obj_webshop->arr_product_quick[$i]['type'])
							{
								case 'heading':
									$product_quick_temp .= "<h3>".$obj_webshop->arr_product_quick[$i]['title']."</h3>";
								break;

								case 'event':
									if(is_plugin_active('mf_calendar/index.php'))
									{
										$data_temp = $obj_webshop->arr_product_quick[$i];

										$obj_calendar = new mf_calendar();
										$obj_calendar->get_events(array('feeds' => array($data_temp['meta']), 'limit' => 1));

										$data_temp['meta'] = $obj_calendar->arr_events;

										if(is_array($data_temp['meta']) && count($data_temp['meta']) > 0)
										{
											$product_quick_temp .= $obj_calendar->get_next_event(array('array' => $data_temp));

											//$has_data = true;
										}
									}
								break;

								case 'categories':
									$product_categories = get_post_meta($obj_webshop->product_id, $obj_webshop->meta_prefix.'category', false);

									$arr_categories = array();
									get_post_children(array('post_type' => $obj_webshop->post_type_categories.$obj_webshop->option_type), $arr_categories);

									$product_quick_temp .= "<span title='".$obj_webshop->arr_product_quick[$i]['title']."'>"
										.$obj_font_icons->get_symbol_tag(array('symbol' => $obj_webshop->arr_product_quick[$i]['symbol']))
										.$obj_webshop->arr_product_quick[$i]['title']
									.":</span>
									<ul>";

										foreach($arr_categories as $key => $value)
										{
											$is_chosen = in_array($key, $product_categories);

											$product_quick_temp .= "<li".($is_chosen ? "" : " class='disabled grey'")."><i class='fa".($is_chosen ? " fa-check green" : "")."'></i> ".$value."</li>";
										}

									$product_quick_temp .= "</ul>";
								break;

								case 'textarea':
									$product_quick_temp .= "<span title='".$obj_webshop->arr_product_quick[$i]['title']."'>"
										.$obj_font_icons->get_symbol_tag(array('symbol' => $obj_webshop->arr_product_quick[$i]['symbol']))
										.$obj_webshop->arr_product_quick[$i]['title']
									.":</span>
									<div>".apply_filters('the_content', $obj_webshop->arr_product_quick[$i]['meta'])."</div>";

									/*if($obj_webshop->arr_product_quick[$i]['has_data'] == true)
									{
										$has_data = true;
									}*/
								break;

								default:
									$product_quick_temp .= "<span title='".$obj_webshop->arr_product_quick[$i]['title']."'>"
										.$obj_font_icons->get_symbol_tag(array('symbol' => $obj_webshop->arr_product_quick[$i]['symbol']))
										.$obj_webshop->arr_product_quick[$i]['title']
									.":</span>
									<div>".$obj_webshop->arr_product_quick[$i]['meta']."</div>"; //This will mess up returned links, lke from 'education'  //apply_filters('the_content', )

									/*if($obj_webshop->arr_product_quick[$i]['has_data'] == true)
									{
										$has_data = true;
									}*/
								break;
							}

						$product_quick_temp .= "</li>";
					}

					/*if($has_data == true)
					{*/
						//echo $product_quick_temp;
						$obj_webshop->template_shortcodes['quick']['html'] = $product_quick_temp;
					//}
				}

				if($obj_webshop->product_map != '')
				{
					$setting_webshop_replace_show_map = get_option_or_default('setting_webshop_replace_show_map'.$obj_webshop->option_type, __("Show Map", 'lang_webshop'));
					$setting_webshop_replace_hide_map = get_option_or_default('setting_webshop_replace_hide_map'.$obj_webshop->option_type, __("Hide Map", 'lang_webshop'));

					$obj_webshop->template_shortcodes['map']['html'] = "<div class='form_button'>
						<h2 class='is_map_toggler button'>
							<span>".$setting_webshop_replace_show_map."</span>
							<span>".$setting_webshop_replace_hide_map."</span>
						</h2>
						<div class='map_wrapper'>
							<div id='webshop_map'></div>"
							.input_hidden(array('name' => "webshop_map_coords", 'value' => $obj_webshop->product_map, 'xtra' => "id='webshop_map_coords' class='map_coords' data-name='".$obj_webshop->product_title."' data-url=''"))
						."</div>
					</div>";
				}

				foreach($obj_webshop->product_meta as $product_meta)
				{
					if(is_array($product_meta['content']))
					{
						do_log("Content is array: ".var_export($product_meta, true));
					}

					$obj_webshop->template_shortcodes['meta']['html'] .= "<li class='".$product_meta['class']."'>".$product_meta['content']."</a>";
				}

				if($obj_webshop->product_form_buy != '')
				{
					$obj_webshop->template_shortcodes['meta']['html'] .= "<li>".$obj_webshop->product_form_buy."</li>";
				}

				if($obj_webshop->product_has_email == true)
				{
					$setting_quote_form = get_option('setting_quote_form'.$obj_webshop->option_type);
					$setting_quote_form_single = get_option('setting_quote_form_single'.$obj_webshop->option_type);

					if($setting_quote_form_single > 0 || $setting_quote_form > 0)
					{
						$obj_webshop->template_shortcodes['form']['html'] .= "<div id='product_form' class='mf_form form_button_container'>
							<div class='form_button'>";

								if($setting_quote_form > 0)
								{
									$setting_replace_add_to_search = get_option_or_default('setting_replace_add_to_search'.$obj_webshop->option_type, __("Add to Search", 'lang_webshop'));
									$setting_replace_remove_from_search = get_option_or_default('setting_replace_remove_from_search'.$obj_webshop->option_type, __("Remove from Search", 'lang_webshop'));
									$setting_replace_return_to_search = get_option_or_default('setting_replace_return_to_search'.$obj_webshop->option_type, __("Continue Search", 'lang_webshop'));

									$obj_webshop->template_shortcodes['form']['html'] .= "<div class='has_searched hide'>"
										.show_button(array('type' => 'button', 'text' => "<i class='fa fa-check'></i> ".$setting_replace_add_to_search, 'class' => "button-primary add_to_search", 'xtra' => "product_id='".$obj_webshop->product_id."'"))
										.show_button(array('type' => 'button', 'text' => "<i class='fa fa-times'></i> ".$setting_replace_remove_from_search, 'class' => "color_button_negative remove_from_search hide", 'xtra' => "product_id='".$obj_webshop->product_id."'"))
										.show_button(array('type' => 'button', 'text' => "<i class='fa fa-chevron-left'></i> ".$setting_replace_return_to_search, 'class' => "button-secondary return_to_search", 'xtra' => "search_url='".$obj_webshop->search_url."'"))
									."</div>";
								}

								$obj_webshop->template_shortcodes['form']['html'] .= "<div class='has_not_searched'>";

									if($setting_quote_form_single > 0)
									{
										$setting_replace_send_request_for_quote = get_option_or_default('setting_replace_send_request_for_quote'.$obj_webshop->option_type, __("Send request for quote", 'lang_webshop'));

										$obj_webshop->template_shortcodes['form']['html'] .= show_button(array('type' => 'button', 'text' => "<i class='fa fa-envelope'></i> ".$setting_replace_send_request_for_quote, 'class' => "button-primary send_request_for_quote", 'xtra' => "product_id='".$obj_webshop->product_id."' form_url='".get_form_url($setting_quote_form_single)."'"));
									}

									if($setting_quote_form > 0)
									{
										$setting_replace_search_for_another = get_option_or_default('setting_replace_search_for_another'.$obj_webshop->option_type, __("Search for Another", 'lang_webshop'));

										$obj_webshop->template_shortcodes['form']['html'] .= show_button(array('type' => 'button', 'text' => "<i class='fa fa-search'></i> ".$setting_replace_search_for_another, 'class' => "button-secondary search_for_another", 'xtra' => "search_url='".$obj_webshop->search_url."'"));
									}

								$obj_webshop->template_shortcodes['form']['html'] .= "</div>
							</div>
						</div>";
					}
				}

				if(count($obj_webshop->arr_product_property) > 0)
				{
					foreach($obj_webshop->arr_product_property as $product_property)
					{
						$out_property = "";

						if($product_property['type'] == 'categories')
						{
							$product_categories = get_post_meta($obj_webshop->product_id, $obj_webshop->meta_prefix.'category', false);

							$arr_categories = array();
							get_post_children(array('post_type' => $obj_webshop->post_type_categories.$obj_webshop->option_type), $arr_categories);

							$count_categories = count($arr_categories);
							$count_chosen = 0;

							$out_property .= "<ul>";

								foreach($arr_categories as $key => $value)
								{
									$is_chosen = in_array($key, $product_categories);

									$out_property .= "<li".($is_chosen ? "" : " class='disabled grey'")."><i class='fa".($is_chosen ? " fa-check green" : "")."'></i> ".$value."</li>";

									if($is_chosen)
									{
										$count_chosen++;
									}
								}

							$out_property .= "</ul>";

							if($count_chosen == $count_categories)
							{
								$out_property = "";
							}
						}

						else
						{
							$out_property .= "<div>".apply_filters('the_content', $product_property['content'])."</div>";
						}

						if($out_property != '')
						{
							$obj_webshop->template_shortcodes['property']['html'] .= "<li>
								<h3>"
									.$obj_font_icons->get_symbol_tag(array('symbol' => $product_property['symbol']))
									.$product_property['title']
								."</h3>"
								.$out_property
							."</li>";
						}
					}
				}

				if($obj_webshop->product_social > 0 && is_plugin_active('mf_social_feed/index.php'))
				{
					$obj_webshop->template_shortcodes['social']['html'] = "<div class='product_social'>
						<h3>".get_post_title($obj_webshop->product_social)."</h3>"
						.apply_filters('the_content', "[mf_social_feed id=".$obj_webshop->product_social." amount=4 filter=no border=no text=no likes=no]")
					."</div>";
				}

				$obj_webshop->template_shortcodes['previous_next']['html'] = "<div class='product_previous_next flex_flow'></div>";

				$setting_webshop_product_template = get_option('setting_webshop_product_template'.$obj_webshop->option_type);

				if($setting_webshop_product_template > 0)
				{
					$template = mf_get_post_content($setting_webshop_product_template);
				}

				else
				{
					$template = $obj_webshop->default_template;
				}

				//This adds a bunch of empty p tags
				//$template = apply_filters('the_content', $template);
				$template = do_shortcode($template);

				foreach($obj_webshop->template_shortcodes as $key => $value)
				{
					$html = ($value['html'] != '' ? str_replace("[html]", $value['html'], $value['formatting']) : '');

					$template = str_replace("[".$key."]", $html, $template);
				}

				echo $template;
			}

		echo "</article>";
	}

get_footer();