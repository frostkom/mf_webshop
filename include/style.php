<?php

if(!defined('ABSPATH'))
{
	header("Content-Type: text/css; charset=utf-8");

	$folder = str_replace("/wp-content/plugins/mf_webshop/include", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

else
{
	global $wpdb;
}

$arr_breakpoints = apply_filters('get_layout_breakpoints', ['tablet' => 1200, 'mobile' => 930, 'suffix' => "px"]);

if(!isset($obj_webshop))
{
	$obj_webshop = new mf_webshop();
}

$setting_webshop_display_filter = get_option('setting_webshop_display_filter');

//$setting_map_visibility = get_option('setting_map_visibility');
//$setting_map_visibility_mobile = get_option('setting_map_visibility_mobile');
$setting_color_info = get_option('setting_webshop_color_info');
$setting_text_color_info = get_option('setting_webshop_text_color_info');

echo "@media all
{
	.info_text p, p.info_text, .info_text h3
	{
		background: ".$setting_color_info.";
		border-radius: .2em;
		color: ".$setting_text_color_info.";
		margin-bottom: 0;
		padding: .5em;
	}

		.info_text span
		{
			font-size: .8em;
			font-weight: normal;
		}

	li.is_disabled
	{
		cursor: no-drop;
		text-decoration: line-through;
	}

	content > div > form, > div > form
	{
		display: inherit;
		width: 100%;
	}

	.aside ul
	{
		list-style: none;
	}

		.aside ul a
		{
			color: inherit;
			display: block;
			padding: .3em 0;
			position: relative;
			text-decoration: none;
		}

			.aside ul p a
			{
				display: inline;
			}

	/* Widgets */
	.is_webshop_search_page > div
	{
		padding: 0;
	}

		.is_webshop_search_page article h1
		{
			padding-left: .4em;
			padding-right: .4em;
		}

		.is_webshop_search_page article section
		{
			padding-left: 1em;
			padding-right: 1em;
		}

		.is_webshop_search_page .widget.webshop_search #product_form
		{
			padding: 0;
		}

	.webshop_form ul
	{
		list-style: none;
		padding: 1.5em 0 0;
	}

		.webshop_form li select, .webshop_form li button
		{
			border: 0;
			font-size: inherit;
			font-weight: normal;
			height: 3em;
			padding: .8em 1em;
		}

			.webshop_form li button
			{
				width: 100%;
			}

		.webshop_list ul
		{
			list-style: none;
			text-align: center;
		}

		.webshop_favorites ul, .webshop_recent ul
		{
			list-style: none;
		}

			.webshop_favorites ul li, .webshop_recent ul li
			{
				margin-bottom: 1em;
				position: relative;
			}

	.webshop_widget .widget_spinner
	{
		text-align: center;
	}";

	//apply_filters('get_block_search', 0, 'mf/webshop...') > 0
	/*if(!is_plugin_active("mf_widget_logic_select/index.php") || apply_filters('get_widget_search', 'webshop-filter-products-widget') > 0)
	{
		echo ".widget.webshop_filter_products .product_filters
		{
			margin-bottom: 0;
		}

			.webshop_filter_products .product_filters > div:last-child select
			{
				margin-bottom: 0;
			}

		.webshop_filter_products .list_item
		{
			background: #e4eff3;
			display: flex;
			margin-bottom: .5em;
			overflow: hidden;
			padding: 1em 1em 1em .8em;
			transition: all .8s ease;
		}";

			$result = $obj_webshop->get_category_colors(array('type' => 'category_background_color'));

			foreach($result as $r)
			{
				$post_id = $r->ID;
				$post_color = $r->meta_value;

				echo ".webshop_filter_products .list_item.category_".$post_id."
				{
					background: ".$post_color.";
				}";
			}

			echo ".webshop_filter_products .list_item:hover
			{
				box-shadow: inset 0 0 20em rgba(0, 0, 0, .1);
			}

				.webshop_filter_products .list_item h2, .webshop_filter_products .list_item .location
				{
					display: block;
					overflow: hidden;
					text-overflow: ellipsis;
					white-space: nowrap;
				}

					.webshop_filter_products .list_item h2
					{
						cursor: pointer;
					}";
	}

	if(is_plugin_active("mf_calendar/index.php"))
	{
		$setting_calendar_date_bg = get_option_or_default('setting_calendar_date_bg', '#019cdb');

		$obj_base = new mf_base();
		$setting_calendar_date_text_color = $obj_base->get_text_color_from_background($setting_calendar_date_bg);

		//apply_filters('get_block_search', 0, 'mf/webshop...') > 0
		if(!is_plugin_active("mf_widget_logic_select/index.php") || apply_filters('get_widget_search', 'webshop-events-widget') > 0)
		{
			echo ".webshop_widget .widget_load_more
			{
				text-align: center;
			}";
		}

		//apply_filters('get_block_search', 0, 'mf/webshop...') > 0
		if(!is_plugin_active("mf_widget_logic_select/index.php") || apply_filters('get_widget_search', 'webshop-product_meta-widget') > 0)
		{
			echo ".aside .webshop_product_meta .webshop_category
			{
				background-color: ".$setting_calendar_date_bg.";
				color: ".$setting_calendar_date_text_color.";
				padding-right: .5em;
				padding-left: .5em;
			}

				.webshop_product_meta .webshop_category i
				{
					margin-right: .4em;
				}

				.webshop_product_meta .webshop_category span
				{
					margin-right: .3em;
				}

			.widget.webshop_product_meta .type_event_info li
			{
				border-top: .1em solid #ccc;
				padding: 1em 0;
			}

				.widget.webshop_product_meta .type_event_info li:last-of-type
				{
					border-bottom: .1em solid #ccc;
				}

				.widget.webshop_product_meta .type_event_info li i
				{
					margin-right: .5em;
				}

			.widget.webshop_product_meta .type_actions
			{
				display: flex;
				flex-wrap: wrap;
				list-style: none;
			}

				.widget.webshop_product_meta .type_actions li
				{
					background: #eee;
					border-radius: .5em;
					display: block;
					flex: 0 0 10em;
					margin: 0 1em 1em 0;
					position: relative;
					text-align: center;
				}

					.widget.webshop_product_meta .type_actions li:last-of-type
					{
						margin-right: 0;
					}

						.widget.webshop_product_meta .type_actions li a
						{
							border-bottom: 0;
							display: block;
							padding: 1em 0;
						}

							.widget.webshop_product_meta .type_actions li a:hover
							{
								text-shadow: 0 0 1em rgba(0, 0, 0, .3);
							}

								.widget.webshop_product_meta .type_actions li i
								{
									color: rgba(0, 0, 0, .6);
									display: block;
									font-size: 3em;
								}

									.widget.webshop_product_meta .type_actions li i + span
									{
										color: rgba(0, 0, 0, .6);
										display: inline-block;
										margin-top: .5em;
									}";
		}
	}

	$setting_webshop_search_page_map_height = 754;

	// Map
	echo ".widget.webshop_map #webshop_map
	{
		min-height: 300px;
		max-height: 100vh;
	}

		.is_webshop_search_page #webshop_map
		{
			min-height: ".$setting_webshop_search_page_map_height."px !important;
		}

		.single-mf_product #webshop_map
		{
			min-height: 300px;
			max-height: 100vh;
		}";*/

	echo "#main > #product_form
	{
		display: flex;
	}

		#product_form article
		{
			flex: 1 1 60%;
			order: 1;
			float: left;
			min-width: 60%;
		}

		#product_form .aside
		{
			flex: 1 1 40%;
			float: right;
			margin: 0;
			order: 2;
			padding: 0;
			min-width: 40%;
		}

			.widget.webshop_map h2
			{
				text-align: center;
			}

			#product_form .search_result_info
			{
				font-size: 1.2em;
				padding: 0 0 1.2em;
				text-align: center;
			}

				#product_form .search_result_info:first-child
				{
					padding: 1.2em 0 0;
				}

				#product_form .search_result_info > span
				{
					font-weight: bold;
				}

			#webshop_search
			{
				padding: 1.5em 0 0;
			}";

				if($setting_webshop_display_filter == 'button')
				{
					echo "#webshop_search > .toggler
					{
						float: right;
						margin-bottom: 1em;
						text-align: right;
					}

						#webshop_search .toggle_container
						{
							background: #fff;
							border: 1px solid #eee;
							margin-top: 2em;
							overflow: initial;
							padding: 1em;
							position: absolute;
							right: 0;
							z-index: 1;
						}

							#webshop_search .toggle_container .form_checkbox
							{
								width: 100%;
							}";
				}

				echo "#webshop_search .flex_flow > *
				{
					margin-right: 1em;
					margin-bottom: 0;
				}

					#webshop_search .flex_flow > *:last-child
					{
						margin-right: 0;
					}

				#webshop_search .form_checkbox
				{
					float: left;
					overflow: hidden;
					text-overflow: ellipsis;
					white-space: nowrap;
					width: 50%;
				}

				#webshop_search hr
				{
					border: none;
					border-top: .1em solid #d8d8d8;
					clear: both;
					margin: 18px 0;
				}

				#webshop_search .label
				{
					margin-bottom: 10px;
				}

				.product_categories.category_icon li
				{
					display: inline-block;
					margin-right: .5em;
					margin-bottom: .5em;
				}

					.product_categories.category_icon li label, .product_categories.category_icon > span
					{
						background: ".$setting_color_info.";
						border-radius: .2em;
						color: ".$setting_text_color_info.";
						display: inline-block;
						padding: .25em .5em;
					}

						.product_categories.category_icon > span
						{
							margin-right: .5em;
							margin-bottom: .5em;
						}

					.product_categories.category_icon li input
					{
						display: none;
					}

						.product_categories.category_icon li input:checked + label
						{
							box-shadow: inset 0 0 10em rgba(0, 0, 0, .1);
						}

						.product_categories.category_icon li i, .product_categories.category_icon > span i
						{
							margin-right: .4em;
						}

				.form_button .info_text, .wp-block-button .info_text
				{
					position: relative;
				}

					.form_button .info_text:before, .wp-block-button .info_text:before
					{
						border: 1em solid transparent;
						border-bottom: 1em solid #808080;
						content: '';
						position: absolute;
						right: 50%;
						top: -2em;
					}

	/* Result List */
	.product_list > li.loading
	{
		padding: 2em;
		text-align: center;
	}

	.product_list > li.active
	{
		background: #efefef;
	}

		.product_list .form_switch label
		{
			display: inline-block;
			font-weight: bold;
			min-height: 1.7em;
			padding: .3em .7em;
		}

		.product_clock
		{
			float: right;
			font-size: .8em;
			margin-top: 1.7em;
			text-align: right;
			width: 30%;
		}

			.product_clock .icon-clock
			{
				margin-right: .3em;
			}

		.webshop_item_list > li > ul
		{
			margin-top: .5em;
		}

			.webshop_item_list .image .category_icon i:first-of-type
			{
				display: block;
				font-size: 5em;
				margin: 5% 0;
			}

				.webshop_item_list .image .category_icon i + i
				{
					display: inline-block;
					font-size: 1.1em;
					margin-right: .3em;
				}

				.webshop_item_list .image .category_icon i:last-of-type
				{
					margin-right: 0;
					margin-bottom: 5%;
				}";

				// Categories
				############################
				$result = $obj_webshop->get_category_colors();

				foreach($result as $r)
				{
					$post_id = $r->ID;
					$post_color = $r->meta_value;

					echo ".category_icon .category_".$post_id.", .webshop_filter_products .list_item.category_".$post_id." h2 > i
					{
						color: ".$post_color." !important;
					}";
				}
				############################

				// Custom Categories
				############################
				$custom_categories = $obj_webshop->get_post_name_for_type('custom_categories');

				if($custom_categories != '')
				{
					$result = $wpdb->get_results($wpdb->prepare("SELECT ID, meta_value FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND meta_key = %s AND meta_value != '' GROUP BY meta_value", $obj_webshop->post_type_products.$option_type, $obj_webshop->meta_prefix.$custom_categories));

					if($wpdb->num_rows > 0)
					{
						echo ".webshop_filter_products .list_item .custom_category
						{
							background-size: contain;
							float: left;
							height: 2.5em;
							margin: .2em .5em 0 0;
							width: 2.5em;
						}";

						foreach($result as $r)
						{
							$post_id = $r->ID;
							$custom_category_id = $r->meta_value;

							if($custom_category_id > 0)
							{
								$custom_category_img = get_post_meta_file_src(array('post_id' => $custom_category_id, 'meta_key' => $obj_webshop->meta_prefix.'image', 'image_size' => 'thumbnail', 'single' => true));

								if($custom_category_img != '')
								{
									echo ".webshop_filter_products .list_item .custom_category.custom_category_".$custom_category_id."
									{
										background-image: url('".$custom_category_img."');
									}";
								}
							}
						}
					}
				}
				############################

				echo ".product_data
				{
					bottom: 0;
					left: 0;
					position: absolute;
					text-align: left;
					white-space: nowrap;
				}

					.single-mf_product .product_data
					{
						bottom: auto;
						top: 0;
					}

						.product_data > span
						{
							background: rgba(0, 0, 0, .5);
							display: inline-block;
							padding: .4em .5em;
						}

		.product_list > li > ul
		{
			color: #4a4a4a;
			list-style: none;
		}

			.product_list > li > ul li a, .single-mf_product section ul li a
			{
				border: 0;
				padding: 0;
			}

			.product_meta li.description
			{
				overflow: hidden;
				margin-bottom: .5em;
			}

			.product_meta li.divider hr
			{
				border-top: 0;
				color: #333;
			}

			.product_meta li a
			{
				display: inline;
			}

			li.type_image
			{
				float: left;
				min-height: 80px;
				margin: 0 2% 2%;
				max-width: 20%;
			}

				li.type_image img
				{
					border-radius: 50%;
				}

			.product_list > li li span
			{
				display: inline-block;
				overflow: hidden;
			}

				.product_list > li li > span:first-child, .single-mf_product li > span:first-child
				{
					margin-right: 2%;
					overflow: hidden;
					text-overflow: ellipsis;
					white-space: nowrap;
				}

					li.type_text span:first-child
					{
						font-weight: bold;
					}

					li.type_choice
					{
						float: left;
						width: 47%;
					}

						li + li.type_choice
						{
							margin-top: 0;
						}

						li.type_choice:nth-child(2n + 1)
						{
							margin-left: 6%;
						}

						li.type_choice > span:first-child
						{
							width: 84%;
						}

							li.type_choice > span:first-child > span
							{
								width: 14%;
							}

						li.type_choice > span:last-child
						{
							text-align: right;
							width: 10%;
						}

		.product_list .wp-block-buttons-is-layout-flex
		{
			justify-content: right;
		}

	/* Product */
	.product_breadcrumbs span + span:before, .webshop_breadcrumbs span + span:before
	{
		content: '/';
		padding: 0 .5em;
	}

	.single-mf_product section
	{
		clear: both;
		overflow: hidden;
	}

		.single-mf_product .product_location
		{
			display: inline-block;
			margin-bottom: .7em;
		}

			.single-mf_product .product_location span, .single-mf_product .product_location a
			{
				border-radius: .2em;
				display: inline-block;
				margin-right: .5em;
				padding: .25em .5em;
			}

				.single-mf_product .product_location span
				{
					background: ".$setting_color_info.";
					color: ".$setting_text_color_info.";
				}

				.single-mf_product .product_location a
				{
					border-bottom: 0;
					text-decoration: none;
				}

		.single-mf_product .mf_share
		{
			margin-bottom: .7em;
		}

		.single-mf_product .product_container .product_slideshow
		{
			position: relative;
		}

		.single-mf_product section .product_meta
		{
			list-style: none;
			margin-top: 1em;
		}

			.single-mf_product li + li, #product_result_form li + li
			{
				margin-top: 0;
			}

			.single-mf_product li.type_text
			{
				float: none;
				width: 100%;
			}

			.product_meta .read_more_button
			{
				margin-top: .5em;
			}

				#product_result_form .read_more_button
				{
					display: none;
				}

			.product_quick, .product_quick ul
			{
				list-style: none;
			}

					.product_quick > li
					{
						overflow: hidden;
						padding: 0 0 .2em;
					}

						.product_quick > li > span
						{
							clear: left;
							float: left;
							margin-right: .4em;
							width: 40%;
						}

							.product_quick > li > span > span[class^='icon-']
							{
								margin-right: .4em;
							}

						.product_quick > li > div, .product_quick > li > ul
						{
							float: left;
							width: 58%;
						}

							.product_quick > li > div .image
							{
								float: left;
								position: relative;
								width: 15%;
							}

							.product_quick > li > div .image + span
							{
								float: right;
								line-height: 2.1;
								overflow: hidden;
								text-overflow: ellipsis;
								width: 82%;
								white-space: nowrap;
							}

							.product_quick p
							{
								margin-bottom: 0;
							}

							.product_quick > li > ul
							{
								clear: none;
								list-style: none;
							}

								.product_quick > li ul li
								{
									clear: both;
								}

									.product_quick > li > ul i
									{
										min-width: 1.5em;
									}

			.product_property
			{
				background: #f8f8f8;
				list-style: none;
				margin: 0;
			}

				.product_property > li
				{
					border-top: 1px solid #e8e8e8;
					padding: 1em 0;
				}

					.product_property h3
					{
						font-size: 1.2em;
						font-weight: normal;
						padding: .2em .5em;
					}

						.product_property h3 i
						{
							color: #b3b3b3;
							margin-right: .3em;
						}

					.product_property div, .product_property > li > ul
					{
						margin: 0;
						padding: .2em .5em .3em;
					}

						.product_property > li > ul
						{
							clear: none;
							list-style: none;
							overflow: hidden;
						}

							.product_property > li > ul > li
							{
								float: left;
								width: 50%;
							}

								.product_property > li > ul i
								{
									min-width: 1.5em;
								}

			.product_social
			{
				clear: both;
			}

			.product_previous_next
			{
				padding-top: 1em;
				width: 100%;
			}

				.product_previous_next > *
				{
					white-space: nowrap;
				}

					.product_previous_next .product_next
					{
						text-align: right;
					}

						.product_previous_next span + .fa, .product_previous_next .fa + span
						{
							margin-left: 1em;
						}
}

@media (max-width: ".($arr_breakpoints['mobile'] - 1)."px)
{
	#product_form
	{
		display: block;
	}

		#product_form article, #product_form .aside
		{
			float: none;
			width: auto;
			min-width: 100%;
		}

	.single-mf_product section > div > div
	{
		float: none;
		margin-right: 0;
		width: 100%;
	}

	.webshop_form li + li
	{
		margin-top: .5em;
	}

		.webshop_form li select
		{
			margin: 0;
		}

	#product_form .aside h2, .widget.webshop_map h2
	{
		margin-top: .6em;
		margin-right: .5em;
		margin-left: .5em;
	}

	.single-mf_product .product_container .product_slideshow
	{
		margin-bottom: 1em;
	}

	#webshop_search .form_checkbox
	{
		width: 100%;
	}
}

@media (min-width: ".$arr_breakpoints['mobile']."px)
{
	.webshop_form li
	{
		margin-right: 0;
	}

		.webshop_form li + li
		{
			margin-left: .5em;
		}

	.webshop_list ul
	{
		text-align: center;
	}

	.product_list .form_switch
	{
		clear: right;
		float: right;
		margin-right: 0;
		max-width: 62.5%;
	}

	.single-mf_product section
	{
		clear: both;
		overflow: hidden;
	}

		.single-mf_product .product_container
		{
			float: left;
			margin-right: 2%;
			width: 68%;
		}

		.single-mf_product .product_aside
		{
			float: right;
			width: 30%;
		}

		.product_property li
		{
			overflow: hidden;
		}

			.product_property h3
			{
				float: left;
				overflow: hidden;
				text-overflow: ellipsis;
				width: 25%;
			}

				.product_property h3 span
				{
					margin-right: 2%;
				}

			.product_property div, .product_property ul
			{
				float: left;
				width: 75%;
			}
}";