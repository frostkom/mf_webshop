<?php

if(!defined('ABSPATH'))
{
	header("Content-Type: text/css; charset=utf-8");

	$folder = str_replace("/wp-content/plugins/mf_webshop/include", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

if(!isset($obj_theme_core))
{
	$obj_theme_core = new mf_theme_core();
}

$obj_theme_core->get_params();

$setting_mobile_breakpoint = $obj_theme_core->options['mobile_breakpoint'];

$setting_webshop_display_sort = get_option('setting_webshop_display_sort');
$setting_webshop_display_filter = get_option('setting_webshop_display_filter');

$setting_color_info = get_option('setting_webshop_color_info');
$setting_text_color_info = get_option('setting_webshop_text_color_info');

$setting_map_visibility = get_option('setting_map_visibility');
$setting_map_visibility_mobile = get_option('setting_map_visibility_mobile');

switch($setting_map_visibility)
{
	default:
	case "everywhere":
		$desktop_aside = "block";
		$desktop_search_h2 = $desktop_product_h2 = "none";
		$desktop_search_div = $desktop_product_div = "block !important";
	break;

	case "search":
		$desktop_aside = "block";
		$desktop_search_h2 = "none";
		$desktop_product_h2 = "block";
		$desktop_product_show = "none";
		$desktop_product_hide = "block";
		$desktop_search_div = "block !important";
		$desktop_product_div = "none";
	break;

	case "single":
		$desktop_aside = "none";
		$desktop_search_h2 = "block";
		$desktop_search_show = "none";
		$desktop_search_hide = "block";
		$desktop_product_h2 = "none";
		$desktop_search_div = "none";
		$desktop_product_div = "block !important";
	break;

	case "nowhere":
		$desktop_aside = "none";
		$desktop_search_h2 = $desktop_product_h2 = "block";
		$desktop_search_show = $desktop_product_show = "block";
		$desktop_search_hide = $desktop_product_hide = "none";
		$desktop_search_div = $desktop_product_div = "none";
	break;
}

$map_visibility_desktop = "#product_form > .aside{	display: ".$desktop_aside.";}
#product_form .is_map_toggler{						display: ".$desktop_search_h2.";}
.product_single .is_map_toggler{					display: ".$desktop_product_h2.";}
#product_form .map_wrapper{							display: ".$desktop_search_div.";}
.product_single .map_wrapper{						display: ".$desktop_product_div.";}";

if(isset($desktop_search_show))
{
	$map_visibility_desktop .= "#product_form .is_map_toggler span:first-of-type{			display: ".$desktop_search_show.";}";
}

if(isset($desktop_search_hide))
{
	$map_visibility_desktop .= "#product_form .is_map_toggler span:last-of-type{			display: ".$desktop_search_hide.";}";
}

if(isset($desktop_product_show))
{
	$map_visibility_desktop .= ".product_single .is_map_toggler span:first-of-type{			display: ".$desktop_product_show.";}";
}

if(isset($desktop_product_hide))
{
	$map_visibility_desktop .= ".product_single .is_map_toggler span:last-of-type{			display: ".$desktop_product_hide.";}";
}

switch($setting_map_visibility_mobile)
{
	case "everywhere":
		$mobile_search_h2 = $mobile_product_h2 = "block";
		$mobile_search_show = $mobile_product_show = "block";
		$mobile_search_hide = $mobile_product_hide = "none";
		$mobile_search_div = $mobile_product_div = "none";
	break;

	case "search":
		$mobile_search_h2 = "block";
		$mobile_search_show = "block";
		$mobile_search_hide = "none";
		$mobile_search_div = "none";

		$mobile_product_h2 = "none";
		$mobile_product_div = "none";
	break;

	case "single":
		$mobile_search_h2 = "none";
		$mobile_search_div = "none";

		$mobile_product_h2 = "block";
		$mobile_product_show = "block";
		$mobile_product_hide = "none";
		$mobile_product_div = "none";
	break;

	default:
	case "nowhere":
		$mobile_search_h2 = $mobile_product_h2 = "none";
		$mobile_search_div = $mobile_product_div = "none";
	break;
}

$map_visibility_mobile = "#product_form .is_map_toggler{	display: ".$mobile_search_h2.";}
.product_single .is_map_toggler{							display: ".$mobile_product_h2.";}
#product_form .map_wrapper{									display: ".$mobile_search_div.";}
.product_single .map_wrapper{								display: ".$mobile_product_div.";}";

if(isset($mobile_search_show))
{
	$map_visibility_mobile .= "#product_form .is_map_toggler span:first-of-type{			display: ".$mobile_search_show.";}";
}

if(isset($mobile_search_hide))
{
	$map_visibility_mobile .= "#product_form .is_map_toggler span:last-of-type{			display: ".$mobile_search_hide.";}";
}

if(isset($mobile_product_show))
{
	$map_visibility_mobile .= ".product_single .is_map_toggler span:first-of-type{			display: ".$mobile_product_show.";}";
}

if(isset($mobile_product_hide))
{
	$map_visibility_mobile .= ".product_single .is_map_toggler span:last-of-type{			display: ".$mobile_product_hide.";}";
}

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

	li.disabled
	{
		cursor: no-drop;
		text-decoration: line-through;
	}

	#wrapper
	{
		overflow: hidden;
	}

	content > div > form, #mf-content > div > form
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

			.aside ul i
			{
				position: absolute;
				right: 0;
			}

	.aside h4
	{
		margin: 1.5em 0 .5em;
	}

	#wrapper h1 a
	{
		color: inherit;
		text-decoration: none;
	}

		section > img
		{
			width: 100%;
		}

		section p .fa
		{
			margin-right: 0.5em;
		}

	/* Widgets */
	#mf-after-header .webshop_form form, #mf-pre-content .webshop_form form
	{
		font-size: 1.6em;
		margin: 0 auto;
		max-width: 80%;
	    width: 500px;
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

	.widget .webshop_item_list .product_border
	{
		border: 1px solid #ccc;
		border-radius: .2em;
		bottom: 0;
		left: 0;
		position: absolute;
		right: 0;
		top: 0;
	}

	.widget .webshop_item_list .product_description
	{
		padding: 1em 0;
		position: relative;
		z-index 1;
	}

		.widget .webshop_item_list .product_border + .product_description
		{
			padding: 1em;
		}

			.widget .webshop_item_list .product_description h4
			{
				margin-bottom: .2em;
			}

			.widget .webshop_item_list .product_description .product_categories
			{

			}

			.widget .webshop_item_list .product_description p
			{
				margin-top: .5em;
			}

	/* Map */
	.map_wrapper
	{
		position: relative;
	}

		#webshop_map
		{
			min-height: 754px;
			max-height: 100vh;
		}

			.single-mf_products #webshop_map
			{
				min-height: 300px;
			}

			.webshop_map_info
			{
				background: #fff;
				border: 1px solid #ccc;
				font-size: 1.4em;
				left: 0;
				margin: 0.5em;
				opacity: 1;
				padding: 1em;
				position: absolute;
				right: 0;
				top: 0;
				transition: all .4s ease;
			}

				.map_wrapper:hover .webshop_map_info
				{
					opacity: .2;
				}

			#webshop_map_input
			{
				border: 1px solid #e1e1e1;
				box-sizing: border-box;
				margin: 2.5%;
				opacity: .5;
				padding: .5em;
				width: 89%;
			}

				#webshop_map_input:hover
				{
					opacity: 1;
				}

	/* Search */
	.page-template-template_webshop_search #mf-content > div
	{
		padding: 0;
	}

	#product_form
	{
		display: -webkit-box;
		display: -ms-flexbox;
		display: -webkit-flex;
		display: flex;
	}

		#product_form article
		{
			-webkit-box-flex: 1 1 60%;
			-webkit-flex: 1 1 60%;
			-ms-flex: 1 1 60%;
			flex: 1 1 60%;
			-webkit-box-ordinal-group: 1;
			-webkit-order: 1;
			-ms-flex-order: 1;
			order: 1;
			float: left;
			min-width: 60%;
		}

		#product_form .aside
		{
			-webkit-box-flex: 1 1 40%;
			-webkit-flex: 1 1 40%;
			-ms-flex: 1 1 40%;
			flex: 1 1 40%;
			margin: 0;
			-webkit-box-ordinal-group: 2;
			-webkit-order: 2;
			-ms-flex-order: 2;
			order: 2;
			float: right;
			min-width: 40%;
		}

			#product_form .aside h2, .product_single h2
			{
				cursor: pointer;
				font-size: 1.3em;
				margin: .5em 0 .7em;
				padding: .5em;
				text-align: center;
			}

				#product_form .aside h2:hover, .product_single h2:hover
				{
					box-shadow: inset 0 0 10em rgba(0, 0, 0, .2);
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

				#product_form .search_result_info span
				{
					font-weight: bold;
				}

			#webshop_search
			{
				padding: 1.5em 0 0;
			}";

				if($setting_webshop_display_sort == 'yes' || is_array($setting_webshop_display_sort) && count($setting_webshop_display_sort) > 1)
				{
					echo "#webshop_search .form_radio_multiple
					{
						float: left;
						width: 70%;
					}

						#webshop_search .form_radio_multiple label, #webshop_search .form_radio_multiple ul, #webshop_search .form_radio_multiple li, #webshop_search .form_radio_multiple input
						{
							display: inline-block;
						}

							#webshop_search .form_radio_multiple label
							{
								margin-right: 1em;
							}

							#webshop_search .form_radio_multiple ul
							{
								list-style: none;
							}

								#webshop_search .form_radio_multiple li + li
								{
									margin: 0 0 1em 1em;
								}

									#webshop_search .form_radio_multiple input
									{
										margin-right: .5em;
									}";
				}

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

				echo ".is_mobile #webshop_search .flex_flow
				{
					display: -webkit-box;
					display: -ms-flexbox;
					display: -webkit-flex;
					display: flex;
				}

					#webshop_search .flex_flow > *
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

				.form_button .info_text
				{
					position: relative;
				}

					.form_button .info_text:before
					{
						border: 1em solid transparent;
						border-bottom: 1em solid #808080;
						content: '';
						position: absolute;
						right: 50%;
						top: -2em;
					}

				.form_button button, .form_button .button
				{
					font-size: 1.3em;
					font-weight: bold;
				}

	/* Result List */
	.product_list
	{
		list-style: none;
	}

		.product_list > li
		{
			background: #f8f8f8;
			clear: both;
			overflow: hidden;
			padding: 0 .8em 0 0;
			position: relative;
		}

			.product_list > li.loading
			{
				padding: 2em;
				text-align: center;
			}

			.product_list > li.active
			{
				background: #efefef;
			}

			.product_heading
			{
				overflow: hidden;
			}

				.product_list h2
				{
					margin-bottom: 0;
					position: relative;
				}

					.product_list h2 a
					{
						display: inline-block;
						max-width: 75%;
						overflow: hidden;
						text-overflow: ellipsis;
						white-space: nowrap;
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
					padding-top: .3em;
					text-align: right;
					width: 30%;
				}

					.product_clock .icon-clock
					{
						margin-right: .3em;
					}

			.webshop_item_list .product_image_container
			{
				background: rgba(0, 0, 0, .1);
				color: #fff;
				overflow: hidden;
				position: relative;
				z-index: 1;
			}

				.webshop_item_list.expand_image_container .product_image_container
				{
					height: 100%;
				}

				.webshop_item_list > li > ul
				{
					margin-top: .5em;
				}

					.product_image_container a
					{
						color: inherit;
					}

						.product_image_container img
						{
							margin-bottom: -6px;
							width: 100%;
						}

					.product_image_container > p
					{
						display: none;
					}

					.product_data
					{
						bottom: 0;
						left: 0;
						position: absolute;
						white-space: nowrap;
					}

						.product_single .product_data
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

			.product_list > li > ul li a, .product_single ul li a
			{
				color: inherit;
			}

			.product_list li.type_text
			{
				float: left;
				white-space: nowrap;
				width: 50%;
			}

			.product_meta li.description
			{
				overflow: hidden;
				margin-bottom: .5em;
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

				.product_list > li li > span:first-child, .product_single li > span:first-child
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

	/* Product */
	.product_breadcrumbs
	{
		font-size: 1.4em;
	}

		.product_breadcrumbs span + span:before
		{
			content: '>';
			padding: 0 .5em;
		}

	.product_single
	{
		clear: both;
		overflow: hidden;
	}

	.product_single .product_location
	{
		display: inline-block;
		margin-bottom: 1em;
	}

		.product_single .product_location span, .product_single .product_location a
		{
			border-radius: .2em;
			display: inline-block;
			margin-right: .5em;
			padding: .25em .5em;
		}

			.product_single .product_location span
			{
				background: ".$setting_color_info.";
				color: ".$setting_text_color_info.";
			}

			.product_single .product_location a
			{
				border-bottom: 0;
				text-decoration: none;
			}

		.product_single .mf_share
		{
			clear: right;
			float: right;
			margin-bottom: .9em;
		}

		.product_single .product_container .product_slideshow
		{
			position: relative;
		}

		.product_single .product_meta
		{
			list-style: none;
			margin-top: 1em;
		}

			.product_single li + li
			{
				margin-top: 0;
			}

			.product_single li.type_text
			{
				float: none;
				width: 100%;
			}

			.single-mf_products .product_description
			{
				margin: 2em 1.2em 0 0;
			}

			.product_quick
			{
				list-style: none;
			}

					.product_quick > li
					{
						overflow: hidden;
						padding: 0 0 .2em;
					}

						.product_quick > li > span:first-child
						{
							clear: left;
							float: left;
							width: 40%;
						}

							.product_quick > li > span:first-child span
							{
								margin-right: .4em;
							}

						.product_quick > li > span:last-child, .product_quick > li > ul
						{
							float: left;
							width: 58%;
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

@media (max-width: ".($setting_mobile_breakpoint - 1)."px)
{
	#product_form
	{
		display: block;
	}

		#product_form article, #product_form .aside
		{
			float: none;
			width: auto;
		}

	.product_single > div
	{
		float: none;
		margin-right: 0;
		width: 100%;
	}"

	.$map_visibility_mobile

	.".webshop_form li + li
	{
		margin-top: .5em;
	}

		.webshop_form li select
		{
			margin: 0;
		}

	#product_form .aside h2
	{
		margin-top: .6em;
		margin-right: .5em;
		margin-left: .5em;
	}

	.product_single .product_container .product_slideshow
	{
		margin-bottom: 1em;
	}

	#webshop_search .form_checkbox
	{
		width: 100%;
	}
}

@media (min-width: ".$setting_mobile_breakpoint."px)
{"
	.$map_visibility_desktop

	.".webshop_form ul
	{

	}

		.webshop_form li
		{
			margin-right: 0;
		}

			.webshop_form li + li
			{
				margin-left: .5em;
			}

	.webshop_widget ul{}

		.webshop_list ul
		{
			text-align: center;
		}

			.webshop_widget ul li
			{
				float: left;
				margin-right: .5%;
				margin-left: .5%;
			}

	.product_list .product_image_container
	{
		float: left;
		width: 35%;
	}

	.product_list .product_column
	{
		clear: right;
		float: right;
		width: 62.5%;
	}

		.product_list .product_location, .product_list .product_meta, .product_list .product_description
		{
			margin-bottom: 0;
		}

	.product_list .form_switch
	{
		clear: right;
		float: right;
		margin-right: 0;
		max-width: 62.5%;
	}

	.product_single
	{
		clear: both;
		overflow: hidden;
	}

		.product_single .product_container
		{
			float: left;
			margin-right: 2%;
			width: 68%;
		}

		.product_single .product_aside
		{
			float: right;
			width: 30%;
		}

	.product_quick
	{
		display: -webkit-box;
		display: -ms-flexbox;
		display: -webkit-flex;
		display: flex;
		-webkit-box-flex-wrap: wrap;
		-webkit-flex-wrap: wrap;
		-ms-flex-wrap: wrap;
		flex-wrap: wrap;
	}

			.product_quick > li
			{
				-webkit-box-flex: 0 0 50%;
				-webkit-flex: 0 0 50%;
				-ms-flex: 0 0 50%;
				flex: 0 0 50%;
				float: left;

				/* Ugly fix to make it work in IE10-11 since they don't respect border-box when using flex */
				max-width: 49%;
			}

				.product_quick > li:nth-child(2n)
				{
					border-right: 1px solid #e8e8e8;
					padding-right: 5%;
				}

				.product_quick > li:nth-child(2n + 1)
				{
					padding-left: 5%;
				}

				.product_quick > li:first-child
				{
					-webkit-box-flex: 1 0 100%;
					-webkit-flex: 1 0 100%;
					-ms-flex: 1 0 100%;
					flex: 1 0 100%;
					border: 0;
					padding-left: 0;
					max-width: 100%;
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