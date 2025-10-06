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

		.webshop_favorites ul
		{
			list-style: none;
		}

			.webshop_favorites ul li
			{
				margin-bottom: 1em;
				position: relative;
			}

	.webshop_widget .widget_spinner
	{
		text-align: center;
	}";

		if($setting_webshop_display_filter == 'button')
		{
			echo ".widget.webshop_search form > .toggler
			{
				float: right;
				margin-bottom: 1em;
				text-align: right;
			}

				.widget.webshop_search form .toggle_container
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

					.widget.webshop_search form .toggle_container .form_checkbox
					{
						width: 100%;
					}";
		}

		echo ".widget.webshop_search form .flex_flow > *
		{
			margin-right: 1em;
			margin-bottom: 0;
		}

			.widget.webshop_search form .flex_flow > *:last-child
			{
				margin-right: 0;
			}

		.widget.webshop_search form .form_checkbox
		{
			float: left;
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
			width: 50%;
		}

		.widget.webshop_search form hr
		{
			border: none;
			border-top: .1em solid #d8d8d8;
			clear: both;
			margin: 18px 0;
		}

		.widget.webshop_search form .label
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
				}

				.product_data
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
	.webshop_form li + li
	{
		margin-top: .5em;
	}

		.webshop_form li select
		{
			margin: 0;
		}

	.widget.webshop_search form .form_checkbox
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