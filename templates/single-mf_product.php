<?php

$obj_webshop = new mf_webshop();

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

				$name_show_map = get_option_or_default('setting_webshop_replace_show_map'.$obj_webshop->option_type, __("Show Map", 'lang_webshop'));
				$name_hide_map = get_option_or_default('setting_replace_hide_map'.$obj_webshop->option_type, __("Hide Map", 'lang_webshop'));

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
					$has_data = false;

					switch($obj_webshop->meta_type)
					{
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
								if($obj_webshop->meta_type == 'location')
								{
									$arr_locations = $obj_webshop->sort_location(array('array' => $post_meta, 'reverse' => true));

									foreach($arr_locations as $location_id)
									{
										$location_title = get_the_title($location_id);
										$obj_webshop->search_url = $obj_webshop->get_template_url(array('location_id' => $location_id));

										$obj_webshop->product_address .= "<a href='".$obj_webshop->search_url."'>".$location_title."</a>";
									}
								}
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
									$obj_webshop->product_address .= "<span>".$post_meta."</span>";

									if($obj_webshop->meta_public == 'no')
									{
										$post_meta = "";
									}
								break;

								case 'checkbox':
									if($post_meta == 1)
									{
										$post_meta = "<i class='fa fa-check green'></i>";

										$has_data = true;
									}

									else
									{
										$post_meta = "<i class='fa fa-times red'></i>";
									}
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
								break;

								case 'social':
									$obj_webshop->product_social = $post_meta;

									$post_meta = "";
								break;

								case 'clock':
								case 'number':
								case 'size':
									$has_data = true;
								break;

								case 'content':
									$arr_exclude = array("[", "]");
									$arr_include = array("<", ">");

									$post_content = str_replace($arr_exclude, $arr_include, $post_meta);
									$post_meta = "";
								break;

								case 'categories':
								case 'description':
								case 'ghost':
								case 'interval':
								case 'text':
								case 'textarea':
								case 'url':
									//Do nothing
								break;

								default:
									do_log(sprintf(__("The type '%s' does not have a case", 'lang_webshop'), $obj_webshop->meta_type)." (single)");
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
							'has_data' => $has_data,
						);
					}
				}

				$ghost_post_name = $obj_webshop->get_post_name_for_type('ghost');

				if($ghost_post_name != '' && get_post_meta($obj_webshop->product_id, $obj_webshop->meta_prefix.$ghost_post_name, true) == true)
				{
					$obj_webshop->product_meta = $obj_webshop->arr_product_quick = $obj_webshop->arr_product_property = array();
				}

				$obj_font_icons = new mf_font_icons();

				if(class_exists('mf_slideshow'))
				{
					$obj_slideshow = new mf_slideshow();
				}

				if(get_option('setting_webshop_display_breadcrumbs'.$obj_webshop->option_type) == 'yes')
				{
					echo "<div class='product_breadcrumbs'>";

						$arr_categories = get_post_meta($post_id, $obj_webshop->meta_prefix.'category', false);

						if(count($arr_categories) > 0)
						{
							echo "<span>";

								$i = 0;

								foreach($arr_categories as $key => $value)
								{
									echo ($i > 0 ? ", " : "").get_post_title($value);

									$i++;
								}

							echo "</span>";
						}

						echo "<span>".$obj_webshop->product_title."</span>
					</div>";
				}

				echo "<h1>".$obj_webshop->product_title."</h1>
				<section>
					<div class='product_single'>
						<div>";

							if($obj_webshop->product_address != '')
							{
								echo "<p class='product_location'>".$obj_webshop->product_address."</p>";
							}

							if(shortcode_exists('mf_share'))
							{
								echo apply_filters('the_content', "[mf_share type='options']");
							}

						echo "</div>
						<div class='product_container'>";

							if(isset($obj_slideshow))
							{
								echo "<div class='product_slideshow'>".$obj_slideshow->show(array('images' => $obj_webshop->slideshow_images))."</div>";
							}

							if($post_content != '')
							{
								echo "<div class='product_description'>".apply_filters('the_content', $post_content)."</div>";
							}

							$count_temp = count($obj_webshop->arr_product_quick);

							if($count_temp > 0)
							{
								$has_data = false;

								$product_quick_temp = "<ul class='product_quick'>";

									for($i = 0; $i < $count_temp; $i++)
									{
										$product_quick_temp .= "<li>";

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

															$has_data = true;
														}
													}
												break;

												case 'categories':
													$product_categories = get_post_meta($obj_webshop->product_id, $obj_webshop->meta_prefix.'category', false);

													$arr_categories = array();
													get_post_children(array('post_type' => $obj_webshop->post_type_categories.$obj_webshop->option_type), $arr_categories);

													$product_quick_temp .= "<span title='".$obj_webshop->arr_product_quick[$i]['title']."'>"
														.$obj_font_icons->get_symbol_tag($obj_webshop->arr_product_quick[$i]['symbol'])
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

												default:
													$product_quick_temp .= "<span title='".$obj_webshop->arr_product_quick[$i]['title']."'>"
														.$obj_font_icons->get_symbol_tag($obj_webshop->arr_product_quick[$i]['symbol'])
														.$obj_webshop->arr_product_quick[$i]['title']
													.":</span>
													<span>".apply_filters('the_content', $obj_webshop->arr_product_quick[$i]['meta'])."</span>";

													if($obj_webshop->arr_product_quick[$i]['has_data'] == true)
													{
														$has_data = true;
													}
												break;
											}

										$product_quick_temp .= "</li>";
									}

								$product_quick_temp .= "</ul>";

								if(true == $has_data)
								{
									echo $product_quick_temp;
								}
							}

						echo "</div>
						<div class='product_aside'>";

							if($obj_webshop->product_map != '')
							{
								echo "<h2 class='is_map_toggler color_button'>
									<span>".$name_show_map."</span>
									<span>".$name_hide_map."</span>
								</h2>
								<div class='map_wrapper'>
									<div id='webshop_map'></div>"
									.input_hidden(array('name' => "webshop_map_coords", 'value' => $obj_webshop->product_map, 'xtra' => "id='webshop_map_coords' class='map_coords' data-name='".$obj_webshop->product_title."' data-url=''"))
								."</div>";
							}

							echo "<ul class='product_meta'>";

								foreach($obj_webshop->product_meta as $product_meta)
								{
									echo "<li class='".$product_meta['class']."'>".$product_meta['content']."</a>";
								}

								if($obj_webshop->product_form_buy != '')
								{
									echo "<li>".$obj_webshop->product_form_buy."</li>";
								}

							echo "</ul>";

							if($obj_webshop->product_has_email == true)
							{
								$quote_form_url = get_form_url(get_option('setting_quote_form_single'.$obj_webshop->option_type));

								$setting_replace_send_request_for_quote = get_option_or_default('setting_replace_send_request_for_quote'.$obj_webshop->option_type, __("Send request for quote", 'lang_webshop'));
								$setting_replace_add_to_search = get_option_or_default('setting_replace_add_to_search'.$obj_webshop->option_type, __("Add to Search", 'lang_webshop'));
								$setting_replace_remove_from_search = get_option_or_default('setting_replace_remove_from_search'.$obj_webshop->option_type, __("Remove from Search", 'lang_webshop'));
								$setting_replace_return_to_search = get_option_or_default('setting_replace_return_to_search'.$obj_webshop->option_type, __("Continue Search", 'lang_webshop'));
								$setting_replace_search_for_another = get_option_or_default('setting_replace_search_for_another'.$obj_webshop->option_type, __("Search for Another", 'lang_webshop'));

								echo "<div id='product_form' class='mf_form form_button_container'>
									<div class='form_button'>
										<div class='has_searched hide'>"
											.show_button(array('type' => 'button', 'text' => "<i class='fa fa-check'></i> ".$setting_replace_add_to_search, 'class' => "button-primary add_to_search", 'xtra' => "product_id='".$obj_webshop->product_id."'"))
											.show_button(array('type' => 'button', 'text' => "<i class='fa fa-times'></i> ".$setting_replace_remove_from_search, 'class' => "color_button_negative remove_from_search hide", 'xtra' => "product_id='".$obj_webshop->product_id."'"))
											.show_button(array('type' => 'button', 'text' => "<i class='fa fa-chevron-left'></i> ".$setting_replace_return_to_search, 'class' => "button-secondary return_to_search", 'xtra' => "search_url='".$obj_webshop->search_url."'"))
										."</div>
										<div class='has_not_searched'>"
											.show_button(array('type' => 'button', 'text' => "<i class='fa fa-envelope'></i> ".$setting_replace_send_request_for_quote, 'class' => "button-primary send_request_for_quote", 'xtra' => "product_id='".$obj_webshop->product_id."' form_url='".$quote_form_url."'"))
											.show_button(array('type' => 'button', 'text' => "<i class='fa fa-search'></i> ".$setting_replace_search_for_another, 'class' => "button-secondary search_for_another", 'xtra' => "search_url='".$obj_webshop->search_url."'"))
										."</div>
									</div>
								</div>";
							}

						echo "</div>";

						if(count($obj_webshop->arr_product_property) > 0)
						{
							echo "<ul class='product_property'>";

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
										echo "<li>
											<h3>"
												.$obj_font_icons->get_symbol_tag($product_property['symbol'])
												.$product_property['title']
											."</h3>"
											.$out_property
										."</li>";
									}
								}

							echo "</ul>";
						}

						if($obj_webshop->product_social > 0 && is_plugin_active('mf_social_feed/index.php'))
						{
							echo "<div class='product_social'>
								<h3>".get_post_title($obj_webshop->product_social)."</h3>"
								.apply_filters('the_content', "[mf_social_feed id=".$obj_webshop->product_social." amount=4 filter=no border=no text=no likes=no]")
							."</div>";
						}

						echo "<div class='product_previous_next flex_flow'></div>";

					echo "</div>
				</section>";
			}

		echo "</article>";
	}

get_footer();