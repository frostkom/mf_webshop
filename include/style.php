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

if(!isset($obj_theme_core))
{
	$obj_theme_core = new mf_theme_core();
}

$obj_theme_core->get_params();
$setting_mobile_breakpoint = $obj_theme_core->options['mobile_breakpoint'];

$obj_webshop = new mf_webshop();

$setting_webshop_display_sort = get_option('setting_webshop_display_sort');
$setting_webshop_display_filter = get_option('setting_webshop_display_filter');

$setting_map_visibility = get_option('setting_map_visibility');
$setting_map_visibility_mobile = get_option('setting_map_visibility_mobile');
$setting_color_info = get_option('setting_webshop_color_info');
$setting_text_color_info = get_option('setting_webshop_text_color_info');

switch($setting_map_visibility)
{
	default:
	case 'everywhere':
		$desktop_aside = "block";
		$desktop_search_h2 = $desktop_product_h2 = "none";
		$desktop_search_div = $desktop_product_div = "block !important";
	break;

	case 'search':
		$desktop_aside = "block";
		$desktop_search_h2 = "none";
		$desktop_product_h2 = "block";
		$desktop_product_show = "none";
		$desktop_product_hide = "block";
		$desktop_search_div = "block !important";
		$desktop_product_div = "none";
	break;

	case 'single':
		$desktop_aside = "none";
		$desktop_search_h2 = "block";
		$desktop_search_show = "none";
		$desktop_search_hide = "block";
		$desktop_product_h2 = "none";
		$desktop_search_div = "none";
		$desktop_product_div = "block !important";
	break;

	case 'nowhere':
		$desktop_aside = "none";
		$desktop_search_h2 = $desktop_product_h2 = "block";
		$desktop_search_show = $desktop_product_show = "block";
		$desktop_search_hide = $desktop_product_hide = "none";
		$desktop_search_div = $desktop_product_div = "none";
	break;
}

$map_visibility_desktop = "#product_form > .aside, .widget.webshop_map{	display: ".$desktop_aside.";}
#product_form .is_map_toggler, .widget.webshop_map .is_map_toggler{		display: ".$desktop_search_h2.";}
.product_single .is_map_toggler{										display: ".$desktop_product_h2.";}
#product_form .map_wrapper, .widget.webshop_map .map_wrapper{			display: ".$desktop_search_div.";}
.product_single .map_wrapper{											display: ".$desktop_product_div.";}";

if(isset($desktop_search_show))
{
	$map_visibility_desktop .= "#product_form .is_map_toggler span:first-of-type, .widget.webshop_map .is_map_toggler span:first-of-type
	{
		display: ".$desktop_search_show.";
	}";
}

if(isset($desktop_search_hide))
{
	$map_visibility_desktop .= "#product_form .is_map_toggler span:last-of-type, .widget.webshop_map .is_map_toggler span:last-of-type
	{
		display: ".$desktop_search_hide.";
	}";
}

if(isset($desktop_product_show))
{
	$map_visibility_desktop .= ".product_single .is_map_toggler span:first-of-type
	{
		display: ".$desktop_product_show.";
	}";
}

if(isset($desktop_product_hide))
{
	$map_visibility_desktop .= ".product_single .is_map_toggler span:last-of-type
	{
		display: ".$desktop_product_hide.";
	}";
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

$map_visibility_mobile = "#product_form .is_map_toggler, .widget.webshop_map .is_map_toggler{	display: ".$mobile_search_h2.";}
.product_single .is_map_toggler{																display: ".$mobile_product_h2.";}
#product_form .map_wrapper, .widget.webshop_map .map_wrapper{									display: ".$mobile_search_div.";}
.product_single .map_wrapper{																	display: ".$mobile_product_div.";}";

if(isset($mobile_search_show))
{
	$map_visibility_mobile .= "#product_form .is_map_toggler span:first-of-type, .widget.webshop_map .is_map_toggler span:first-of-type
	{
		display: ".$mobile_search_show.";
	}";
}

if(isset($mobile_search_hide))
{
	$map_visibility_mobile .= "#product_form .is_map_toggler span:last-of-type, .widget.webshop_map .is_map_toggler span:last-of-type
	{
		display: ".$mobile_search_hide.";
	}";
}

if(isset($mobile_product_show))
{
	$map_visibility_mobile .= ".product_single .is_map_toggler span:first-of-type
	{
		display: ".$mobile_product_show.";
	}";
}

if(isset($mobile_product_hide))
{
	$map_visibility_mobile .= ".product_single .is_map_toggler span:last-of-type
	{
		display: ".$mobile_product_hide.";
	}";
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

	/*#wrapper
	{
		overflow: hidden;
	}*/

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

			/* This affects all aside h4 across all plugins */
			/*.aside ul i
			{
				position: absolute;
				right: 0;
			}*/

	/* This affects all aside h4 across all plugins */
	/*.aside h4
	{
		margin: 1.5em 0 .5em;
	}*/

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
			margin-right: .5em;
		}

	/* Widgets */
	.is_webshop_search_page #mf-content > div
	{
		padding: 0;
	}

		.is_webshop_search_page .widget.webshop_search #product_form
		{
			padding: 0;
		}

		body:not(.is_mobile).is_webshop_search_page .aside.left, body:not(.is_mobile).is_webshop_search_page .aside.right
		{
			-webkit-box-flex: 0 0 40%;
			-webkit-flex: 0 0 40%;
			-ms-flex: 0 0 40%;
			flex: 0 0 40%;
			margin-left: 0;
			max-width: 40%;
		}

			body:not(.is_mobile)/*.is_webshop_search_page*/ .widget.webshop_map > div
			{
				padding: 0 !important;
			}

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

			.widget .webshop_item_list .product_description p
			{
				margin-top: .5em;
			}";

	if(is_plugin_active("mf_calendar/index.php"))
	{
		echo ".widget.webshop_events .event_filters
		{
			margin-bottom: 0;
			padding-bottom: 0;
		}

		.event_calendar_container
		{
			background-color: #fff;
			border: .1em solid #eee;
			border-bottom: 0;
		}

			.event_calendar_header
			{
				border-bottom: .1em solid #eee;
				text-align: center;
				padding: 1em 0;
			}

				.event_calendar_header button
				{
					background: none !important;
					border: 0;
					color: inherit !important;
					cursor: pointer;
					font-size: 1em;
					padding: 0 .5em;
				}

			.event_calendar_days
			{
				display: grid;
				grid-template-columns: repeat(7, 1fr);
				grid-template-rows: 3.2em;
				grid-auto-rows: minmax(4.5em, auto);
			}

				.event_calendar_days .day_name
				{
					border-bottom: .1em solid #eee;
					font-weight: bold;
					font-size: .8em;
					line-height: 4;
					text-transform: uppercase;
					text-align: center;
				}

				.event_calendar_days .day
				{
					background-color: #fff;
					border-bottom: .1em solid #eee;
					border-right: .1em solid #eee;
					color: #999;
					font-size: 1em;
					padding-top: .8em;
					text-align: center;
					transition: all 1s ease;
				}

					.event_calendar_days .day:hover
					{
						background-color: #eee;
					}

					.event_calendar_days .day.today
					{
						background-color: #f5f5f5;
					}

					.event_calendar_days .day.disabled
					{
						background-image: url(\"data:image/svg+xml,%3Csvg width='20' height='20' viewBox='0 0 20 20' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23f6f6f6' fill-opacity='1' fill-rule='evenodd'%3E%3Cpath d='M0 20L20 0H10L0 10M20 20V10L10 20'/%3E%3C/g%3E%3C/svg%3E\");
						cursor: not-allowed;
					}

					.event_calendar_days .day a, .event_calendar_days .day span
					{
						display: block;
						transition: all 1s ease;
					}

						.event_calendar_days .day:hover a, .event_calendar_days .day:hover span
						{
							-webkit-transform: scale(1.5);
							transform: scale(1.5);
						}

					.event_calendar_days .day a
					{
						color: #333;
					}

					.event_calendar_days .day ul
					{
						list-style: none;
					}

						.event_calendar_days .day li
						{
							background: #999;
							border-radius: 50%;
							content: ' ';
							display: inline-block;
							height: .5em;
							width: .5em;
						}

					.event_calendar_days .day:nth-of-type(7n + 7)
					{
						border-right: 0;
					}

					.event_calendar_days .day:nth-of-type(n + 1):nth-of-type(-n + 7){		grid-row: 2;}
					.event_calendar_days .day:nth-of-type(n + 8):nth-of-type(-n + 14){		grid-row: 3;}
					.event_calendar_days .day:nth-of-type(n + 15):nth-of-type(-n + 21){		grid-row: 4;}
					.event_calendar_days .day:nth-of-type(n + 22):nth-of-type(-n + 28){		grid-row: 5;}
					.event_calendar_days .day:nth-of-type(n + 29):nth-of-type(-n + 35){		grid-row: 6;}

					.event_calendar_days .day:nth-of-type(7n + 1){							grid-column: 1/1;}
					.event_calendar_days .day:nth-of-type(7n + 2){							grid-column: 2/2;}
					.event_calendar_days .day:nth-of-type(7n + 3){							grid-column: 3/3;}
					.event_calendar_days .day:nth-of-type(7n + 4){							grid-column: 4/4;}
					.event_calendar_days .day:nth-of-type(7n + 5){							grid-column: 5/5;}
					.event_calendar_days .day:nth-of-type(7n + 6){							grid-column: 6/6;}
					.event_calendar_days .day:nth-of-type(7n + 7){							grid-column: 7/7;}";

				/*echo ".event_calendar_days .task
				{
					border-left: .3em solid;
					font-size: .8em;
					margin: .5em;
					padding: .5em .8em;
				}

					.event_calendar_days .task.warning
					{
						background: #fef0db;
						border-left-color: #fdb44d;
						color: #fc9b10;
					}

					.event_calendar_days .task.danger
					{
						background: #f9d1d9;
						border-left-color: #fa607e;
						color: #f8254e;
					}

					.event_calendar_days .task.info
					{
						background: #e2ecfd;
						border-left-color: #4786ff;
						color: #0a5eff;
					}";*/

		echo ".widget.webshop_events .product_categories
		{
			margin-top: 1em;
		}

		.widget.webshop_events .event_spinner
		{
			text-align: center;
		}

		.widget.webshop_events li.event_item
		{
			background: #f2f2f2;
			border-left: .3em solid #e2e2e2;
			display: -webkit-box;
			display: -ms-flexbox;
			display: -webkit-flex;
			display: flex;
			margin-bottom: .5em;
			overflow: hidden;
			padding: 1em 1em 1em .8em;
			transition: all .8s ease;
		}

			.widget.webshop_events li.event_item:hover
			{
				background: #e9e9e9;
				border-left-width: .6em;
			}

			#wrapper .widget.webshop_events li > div
			{
				-webkit-box-flex: 1 1 auto;
				-webkit-flex: 1 1 auto;
				-ms-flex: 1 1 auto;
				flex: 1 1 auto;
			}

				#wrapper .widget.webshop_events li .event_date
				{
					-webkit-box-flex: 0 0 4em;
					-webkit-flex: 0 0 4em;
					-ms-flex: 0 0 4em;
					flex: 0 0 4em;
				}

					#wrapper .widget.webshop_events li .event_date > div:first-of-type
					{
						font-size: 2em;
					}

						#wrapper .widget.webshop_events li .event_date sup
						{
							font-size: .5em;
							white-space: nowrap;
						}

						#wrapper .widget.webshop_events li .event_date > div:first-of-type span
						{
							float: left;
							width: 60%;
						}

							#wrapper .widget.webshop_events li .event_date > div:first-of-type span + span
							{
								font-size: .35em;
								padding-top: .5em;
								padding-left: 5%;
								width: 35%;
							}

					#wrapper .widget.webshop_events li .event_date > div:last-of-type
					{
						clear: both;
						text-transform: uppercase;
					}

				#wrapper .widget.webshop_events li h2
				{
					font-weight: normal;
					margin-bottom: 0;
				}

					#wrapper .widget.webshop_events li h2 a
					{
						display: inline;
						text-transform: uppercase;
					}

					#wrapper .widget.webshop_events li h2 span
					{
						margin-left: .5em;
						font-size: .7em;
					}

				#wrapper .widget.webshop_events li .event_url
				{
					-webkit-box-flex: 0 0 6em;
					-webkit-flex: 0 0 6em;
					-ms-flex: 0 0 6em;
					flex: 0 0 6em;
					text-align: center;
				}

					#wrapper .widget.webshop_events li .event_url a
					{
						background: #666;
						border-radius: 2em;
						color: #fff;
						opacity: 0;
						top: 50%;
						transition: all 1.2s ease;
						-webkit-transform: translateY(100%, -50%);
						transform: translate(100%, -50%);
					}

						#wrapper .widget.webshop_events li:hover .event_url a
						{
							opacity: 1;
							-webkit-transform: translateY(0%, -50%);
							transform: translate(0%, -50%);
						}

		.widget.webshop_events li.event_load_more
		{
			text-align: center;
		}";

		$obj_calendar = new mf_calendar();

		$result = $obj_calendar->get_calendar_colors();

		foreach($result as $r)
		{
			$post_id = $r->ID;
			$post_color = $r->meta_value;

			echo ".widget.webshop_events li.calendar_feed_".$post_id."
			{
				border-left-color: ".$post_color.";
			}

				#wrapper .widget.webshop_events li.calendar_feed_".$post_id." h2 a
				{
					color: ".$post_color.";
				}
				
			.event_calendar_days .day li.calendar_feed_".$post_id."
			{
				background: ".$post_color.";
			}";
		}
	}

	echo "/* Map */
	.map_wrapper
	{
		position: relative;
	}
		.widget.webshop_map #webshop_map
		{
			min-height: 300px;
			max-height: 100vh;
		}

		.is_webshop_search_page #webshop_map
		{
			min-height: 754px !important;
		}

		.single-mf_product #webshop_map
		{
			min-height: 300px;
			max-height: 100vh;
		}

			.webshop_map_info
			{
				background: #fff;
				border: 1px solid #ccc;
				font-size: 1.4em;
				left: 0;
				margin: .5em;
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

	.page-template-template_webshop_search #mf-content > div, .page-template-template_webshop_search #main, .page-template-template_webshop_search #product_form
	{
		overflow: unset;
	}

	.page-template-template_webshop_search aside > div, .page-template-template_webshop_search .aside > div
	{
		position: sticky;
		top: 0;
		z-index: 9;
	}

	#main > #product_form
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
			float: right;
			margin: 0;
			-webkit-box-ordinal-group: 2;
			-webkit-order: 2;
			-ms-flex-order: 2;
			order: 2;
			padding: 0;
			min-width: 40%;
		}

			#product_form .aside h2, .product_single h2.is_map_toggler, .widget.webshop_map h2
			{
				cursor: pointer;
				font-size: 1.3em;
				margin: .5em 0 .7em;
				padding: .5em;
				text-align: center;
			}

				#product_form .aside h2:hover, .product_single h2.is_map_toggler:hover, .widget.webshop_map h2:hover
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

				#product_form .search_result_info > span
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

				.product_categories.category_icon li
				{
					display: inline-block;
					margin-right: .5em;
					margin-bottom: .5em;
				}

					.product_categories.category_icon li label, .product_single .product_categories.category_icon span
					{
						background: ".$setting_color_info.";
						border-radius: .2em;
						color: ".$setting_text_color_info.";
						display: inline-block;
						padding: .25em .5em;
					}

						.product_single .product_categories.category_icon span
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

						.product_categories.category_icon li i, .product_single .product_categories.category_icon span i
						{
							margin-right: .4em;
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
			padding: 2em .8em;
			position: relative;
		}

			.product_list > li:nth-child(2n + 1)
			{
				background: #f1f1f1;
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
					margin-bottom: 0 !important;
					position: relative;
				}

					.product_list h2 a, .product_list h2 span
					{
						border: 0 !important;
						display: block;
						max-width: 75%;
						overflow: hidden;
						padding: 0;
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
					margin-top: 1em;
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

					.product_image_container
					{
						text-align: center;
					}

						.product_image_container a
						{
							color: inherit;
						}

							.is_mobile .product_image_container .category_icon
							{
								max-width: 35%;
							}

								.product_image_container .category_icon i:first-of-type
								{
									display: block;
									font-size: 5em;
									margin: 5% 0;
								}

									.product_image_container .category_icon i + i
									{
										display: inline-block;
										font-size: 1.1em;
										margin-right: .3em;
									}

									.product_image_container .category_icon i:last-of-type
									{
										margin-right: 0;
										margin-bottom: 5%;
									}";

								$obj_webshop->get_option_types();

								foreach($obj_webshop->arr_option_types as $option_type)
								{
									$obj_webshop->option_type = ($option_type != '' ? "_".$option_type : '');

									$result = $wpdb->get_results($wpdb->prepare("SELECT ID, meta_value FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND meta_key = %s AND meta_value != ''", $obj_webshop->post_type_categories.$obj_webshop->option_type, $obj_webshop->meta_prefix.'category_icon_color'));

									foreach($result as $r)
									{
										$post_id = $r->ID;
										$post_color = $r->meta_value;

										echo ".category_icon .category_".$post_id."
										{
											color: ".$post_color." !important;
										}";
									}
								}

							echo ".product_image_container img
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
						text-align: left;
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

			.product_single li + li, #product_result_form li + li
			{
				margin-top: 0;
			}

			.product_single li.type_text
			{
				float: none;
				width: 100%;
			}

			.single-mf_product .product_description
			{
				margin-bottom: .5em;
			}

				.single-mf_product .product_slideshow + .product_description
				{
					margin-top: 2em;
				}

			.product_meta .contact_button, .product_meta .read_more_button
			{
				margin-top: .5em;
			}

				#product_result_form .contact_button, #product_result_form .read_more_button
				{
					display: none;
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

						.product_quick > li > span
						{
							clear: left;
							float: left;
							margin-right: .4em;
							width: 40%;
						}

						.product_quick > li > div, .product_quick > li > ul
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
			min-width: 100%;
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

	#product_form .aside h2, .widget.webshop_map h2
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

	.".webshop_form li
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

		/*.webshop_widget ul li
		{
			float: left;
			padding-right: .5%;
			padding-left: .5%;
		}*/

			.webshop_widget ul li > div
			{
				margin: 0 .4em;
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