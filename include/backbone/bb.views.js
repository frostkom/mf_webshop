if(typeof script_webshop_views != 'undefined')
{
	function preload(url)
	{
		var img = new Image();
		img.src = url;
	}

	if(script_webshop_views.symbol_active){		preload(script_webshop_views.symbol_active);}
	if(script_webshop_views.symbol_inactive){	preload(script_webshop_views.symbol_inactive);}
	if(script_webshop_views.ghost_active){		preload(script_webshop_views.ghost_active);}
	if(script_webshop_views.ghost_inactive){	preload(script_webshop_views.ghost_inactive);}
}

var my_lat, my_lon;

var WebshopView = Backbone.View.extend(
{
	el: jQuery("body"),

	initialize: function()
	{
		if(jQuery(".webshop_map.display_on_mobile").length > 0)
		{
			var body_obj = jQuery("body");

			if(jQuery(document).width() < script_webshop_views.mobile_breakpoint)
			{
				jQuery(".webshop_map.hide_on_mobile .webshop_map_container").attr("id", "webshop_map_hide");
			}

			else
			{
				jQuery(".webshop_map.display_on_mobile .webshop_map_container").attr("id", "webshop_map_hide");
			}
		}

		/* Product */
		this.model.on("change:product_response", this.show_products, this);
		this.model.on("change:product_amount", this.show_product_amount, this);

		this.is_favorites_view = (jQuery(".product_favorites").length > 0);
		this.has_product_result = (jQuery(".product_list.webshop_item_list").length > 0);

		this.get_products_storage();

		/* Events */
		this.model.on("change:calendar_response", this.show_calendars, this);
		this.model.on("change:event_hash", this.show_events, this);

		this.dom_obj_events = jQuery(".webshop_widget.webshop_events");
		this.dom_obj_products = jQuery(".webshop_widget.webshop_filter_products");

		this.is_events_view = (this.dom_obj_events.length > 0);

		/* Filter Products */
		this.model.on("change:filter_products_hash", this.show_filter_products, this);

		this.is_filter_products_view = (this.dom_obj_products.length > 0);

		if(this.is_favorites_view)
		{
			if(typeof this.form_products != 'undefined' && this.form_products != '')
			{
				location.hash = "webshop/" + this.form_products;
			}

			else
			{
				this.form_products = location.hash.replace('#', '');
				this.form_products = this.form_products.replace('webshop/', '');

				this.set_products_storage();
			}

			if(typeof this.form_products != 'undefined' && this.form_products != '')
			{
				this.show_results_view();
			}

			else
			{
				this.show_no_results_view();
			}
		}

		else if(this.is_events_view || this.is_filter_products_view)
		{
			if(this.is_events_view)
			{
				this.process_events_view();
			}

			if(this.is_filter_products_view)
			{
				this.process_filter_products_view();
			}
		}

		else
		{
			this.if_search_view();
			this.if_product_view();
			this.if_thanks_view();
		}
	},

	events:
	{
		/* Widget Search */
		"click .webshop_form .webshop_form_link a": "search_all_products",

		/* Template Search */
		"click .product_list.webshop_item_list .products": "products_change",
		"change .product_search input[type=range]": "filter_distance",
		"submit .product_search": "submit_form",
		"click .widget .is_map_toggler": "toggle_aside",

		/* Result List */
		"change #webshop_search input": "search_products_change",
		"change #webshop_search select": "search_products_change",
		"blur #webshop_search input[type!='checkbox']": "search_products_change",
		"mouseenter .product_list.webshop_item_list > li": "section_hover",
		"mouseleave .product_list.webshop_item_list > li": "section_unhover",
		"change .webshop_form form select": "search_product_amount",
		"click #product_form.form_button_container .form_button button, #product_form.form_button_container .wp-block-button button": "product_add_to_search_or_not",
		"click .product_list.webshop_item_list > li": "set_last_product",

		/* Favorites */
		"click .quote_button .button_print": "print_favorites",

		/* Events */
		"click .webshop_widget .calendar_header button": "change_month",
		"click .event_calendar .day a": "change_date",
		"change .webshop_events .event_filters .event_filter_category": "change_category",
		"change .webshop_events .event_filters .event_filter_order_by": "change_order_by",
		"click .webshop_widget .widget_load_more button": "load_more_button",

		/* Products */
		"change .webshop_filter_products .product_filters .product_filter_order_by": "change_order_by"
	},

	process_events_view: function()
	{
		this.dom_calendar = this.dom_obj_events.find(".event_calendar");
		this.has_calendar = this.dom_calendar.length > 0;

		if(this.has_calendar)
		{
			this.load_calendar();
		}

		if(this.dom_obj_events.find(".event_filter_category:checked").length > 0)
		{
			var dom_obj = this.dom_obj_events.find(".event_filter_category:checked"),
				dom_list = dom_obj.parents(".webshop_events").find(".widget_list");

			dom_list.attr(
			{
				'data-category': dom_obj.attr('value')
			});
		}

		var dom_obj = this.dom_obj_events.find(".event_filter_order_by");

		if(dom_obj.length > 0)
		{
			var dom_list = dom_obj.parents(".webshop_events").find(".widget_list");

			dom_list.attr(
			{
				'data-order_by': dom_obj.val(),
				'data-limit': 0
			}).empty();

			if(navigator.geolocation)
			{
				var self = this;

				navigator.geolocation.getCurrentPosition(function(position)
				{
					my_lat = position.coords.latitude;
					my_lon = position.coords.longitude;

					dom_list.attr(
					{
						'data-latitude': my_lat,
						'data-longitude': my_lon
					});

					self.load_all_events();

					if(typeof add_my_position_marker === 'function')
					{
						add_my_position_marker(position);
					}
				},
				function(msg)
				{
					self.load_all_events();
				});
			}

			else
			{
				this.load_all_events();
			}
		}

		else
		{
			this.load_all_events();
		}
	},

	process_filter_products_view: function()
	{
		var dom_obj = this.dom_obj_products.find(".product_filter_order_by");

		if(dom_obj.length > 0)
		{
			var dom_list = dom_obj.parents(".webshop_filter_products").find(".widget_list");

			dom_list.attr(
			{
				'data-order_by': dom_obj.val(),
				'data-limit': 0
			}).empty();

			if(navigator.geolocation)
			{
				var self = this;

				navigator.geolocation.getCurrentPosition(function(position)
				{
					my_lat = position.coords.latitude;
					my_lon = position.coords.longitude;

					dom_list.attr(
					{
						'data-latitude': my_lat,
						'data-longitude': my_lon
					});

					self.load_all_filter_products();
				},
				function(msg)
				{
					self.load_all_filter_products();
				});
			}

			else
			{
				this.load_all_filter_products();
			}
		}

		else
		{
			this.load_all_filter_products();
		}
	},

	search_all_products: function(e)
	{
		jQuery(e.currentTarget).parents("form").submit();

		return false;
	},

	loadPage: function(tab_active)
	{
		if(this.has_product_result)
		{
			if(this.is_favorites_view)
			{
				if(this.form_products != '')
				{
					this.model.getPage('favorites=' + this.form_products);
				}
			}

			else
			{
				this.model.getPage(tab_active);
			}
		}
	},

	show_results_view: function()
	{
		var favorites_url = jQuery(".quote_button a.button").attr('href').replace("[url]", location.href);

		jQuery(".quote_button a.button").attr({'href': favorites_url});

		jQuery(".search_result_info").show();
	},

	show_no_results_view: function()
	{
		jQuery(".favorite_result").addClass('hide');
		jQuery(".favorite_fallback").removeClass('hide');
	},

	if_search_view: function()
	{
		if(jQuery("#webshop_search").length > 0)
		{
			if(jQuery(".widget.webshop_search, .widget.webshop_map").length > 0)
			{
				jQuery("body").addClass('is_webshop_search_page');
			}

			this.get_hash();
			this.search_products(false);

			var dom_obj_range = jQuery(".product_search input[type=range]");

			if(dom_obj_range.length > 0)
			{
				if(navigator.geolocation)
				{
					navigator.geolocation.getCurrentPosition(function(position)
					{
						my_lat = position.coords.latitude;
						my_lon = position.coords.longitude;
					},
					function(msg)
					{
						dom_obj_range.parent(".form_textfield").addClass('hide');
					});
				}
			}
		}

		if(jQuery(".webshop_form .product_filtered_amount").length > 0)
		{
			this.search_product_amount();
		}
	},

	if_product_view: function()
	{
		var dom_product_buttons = jQuery("#product_form.form_button_container");

		if(dom_product_buttons.length > 0)
		{
			var form_serialized = jQuery.Storage.get('form_serialized');

			this.get_products_storage();

			if(script_webshop_views.force_individual_contact == 'no' && (typeof form_serialized != 'undefined' || typeof this.form_products != 'undefined'))
			{
				dom_product_buttons.find(".has_not_searched").addClass('hide');
				dom_product_buttons.find(".has_searched").removeClass('hide');

				if(typeof this.form_products !== 'undefined')
				{
					var product_id = dom_product_buttons.find(".button-primary").attr('product_id');

					if(typeof product_id !== 'undefined' && product_id > 0)
					{
						jQuery.each(this.form_products.split(','), function(index, value)
						{
							if(value == product_id)
							{
								dom_product_buttons.find(".remove_from_search").removeClass('hide');
								dom_product_buttons.find(".add_to_search").addClass('hide');
							}
						});
					}
				}
			}

			else
			{
				dom_product_buttons.find(".has_searched").addClass('hide');
				dom_product_buttons.find(".has_not_searched").removeClass('hide');
			}

			var response = JSON.parse(jQuery.Storage.get('result_products')),
				products_amount = response.length;

			if(products_amount > 0)
			{
				var current_product_id = jQuery(".has_searched button").attr('product_id'),
					html = "";

				for(var i = 0; i < products_amount; i++)
				{
					if(response[i]['product_id'] == current_product_id)
					{
						for(var j = 1; (i - j) >= 0; j++)
						{
							if(response[(i - j)]['product_url'] != '')
							{
								html += "<a href='" + response[(i - j)]['product_url'] + "' class='product_previous'><i class='fa fa-chevron-left'></i><span>" + response[(i - j)]['product_title'] + "</span></a>";

								break;
							}
						}

						for(var j = 1; (i + j) < products_amount; j++)
						{
							if(response[(i + j)]['product_url'] != '')
							{
								html += "<a href='" + response[(i + j)]['product_url'] + "' class='product_next'><span>" + response[(i + j)]['product_title'] + "</span><i class='fa fa-chevron-right'></i></a>";

								break;
							}
						}
					}
				}

				if(html != "")
				{
					jQuery(".product_previous_next").html(html).removeClass('hide');
				}

				else
				{
					jQuery(".product_previous_next").addClass('hide');
				}
			}
		}
	},

	if_thanks_view: function()
	{
		var dom_obj = jQuery("#mf_back_to_search");

		if(dom_obj.length > 0)
		{
			var form_serialized = jQuery.Storage.get('form_serialized');

			if(form_serialized != '')
			{
				dom_obj.attr({'href': form_serialized}).removeClass('hide');
			}

			jQuery.Storage.remove('form_products');
			jQuery.Storage.remove('last_product');
		}
	},

	get_hash: function()
	{
		var hash = location.hash.replace('#', '');
		hash = hash.replace('webshop/', '');

		if(hash != '')
		{
			jQuery.each(hash.split('&'), function(index, value)
			{
				var arr_values = value.split('=');

				/* Filter product[] etc. */
				if(arr_values[0].indexOf('%5B') === -1)
				{
					var dom_obj = jQuery("#" + arr_values[0]);

					if(dom_obj.is("[type='checkbox']"))
					{
						dom_obj.prop('checked', arr_values[1]);
					}

					else
					{
						dom_obj.val(arr_values[1]);
					}
				}
			});
		}
	},

	set_hash: function()
	{
		var form_serialized = jQuery("#product_form").serialize().replace(/[^&]+=&/g, '').replace(/&[^&]+=$/g, '');

		location.hash = "webshop/" + form_serialized;

		jQuery.Storage.set({'form_serialized': location.href});
	},

	set_products: function()
	{
		var form_products = '';

		jQuery(".products:checked").each(function()
		{
			form_products += (form_products != '' ? "," : "") + jQuery(this).val();
		});

		this.form_products = form_products;
		this.set_products_storage();
	},

	get_products_storage: function()
	{
		this.form_products = jQuery.Storage.get('form_products');
	},

	set_products_storage: function()
	{
		jQuery.Storage.set({'form_products': this.form_products});
	},

	get_products: function()
	{
		this.get_products_storage();

		if(typeof this.form_products !== 'undefined')
		{
			jQuery.each(this.form_products.split(','), function(index, value)
			{
				jQuery("#products_" + value).prop('checked', true);
			});
		}
	},

	products_change: function()
	{
		this.set_products();

		this.show_quote_request_button();
	},

	get_coordinates_from_string: function(string)
	{
		return string.replace("(", "").replace(")", "").split(", ");
	},

	get_distance: function(lat1, lon1, lat2, lon2, unit)
	{
		var radlat1 = (Math.PI * lat1 / 180),
			radlat2 = (Math.PI * lat2 / 180),
			theta = (lon1 - lon2),
			radtheta = (Math.PI * theta / 180),
			dist = (Math.sin(radlat1) * Math.sin(radlat2) + Math.cos(radlat1) * Math.cos(radlat2) * Math.cos(radtheta));

		dist = Math.acos(dist);
		dist = (dist * 180 / Math.PI);
		dist = (dist * 60 * 1.1515 * 1.609344);

		return dist;
	},

	filter_distance: function(e)
	{
		if(typeof my_lat != 'undefined' && typeof my_lon != 'undefined')
		{
			var self = this,
				dom_val = jQuery(e.currentTarget).val();

			jQuery(".product_list.webshop_item_list .map_coordinates").each(function()
			{
				var dom_obj = jQuery(this),
					dom_parent = dom_obj.parents("li"),
					dom_coordinates = dom_obj.val(),
					arr_coordinates = self.get_coordinates_from_string(dom_coordinates),
					distance = self.get_distance(my_lat, my_lon, arr_coordinates[0], arr_coordinates[1]);

				if(distance < dom_val)
				{
					dom_parent.removeClass('hide');
				}

				else
				{
					dom_parent.addClass('hide');
				}
			});
		}
	},

	submit_form: function(e)
	{
		if(this.model.get('products_checked') == 0)
		{
			jQuery(e.currentTarget).find(".show_if_none_checked").removeClass('hide');

			return false;
		}

		if(this.model.get('products_checked') > script_webshop_views.search_max || this.product_form_has_changed() == false)
		{
			jQuery(e.currentTarget).find(".show_if_too_many").removeClass('hide');

			scroll_to_top();

			return false;
		}
	},

	product_form_has_changed: function()
	{
		return (script_webshop_views.require_search == 'no' || jQuery("#webshop_search .form_select:first-of-type select").val() != '' || jQuery("#webshop_search .form_checkbox input").is(":checked") || jQuery("#webshop_map_bounds").val() != '');
	},

	show_quote_request_button: function()
	{
		var self = this,
			products_total = 0,
			products_checked = 0;

		jQuery(".product_list.webshop_item_list .products").each(function()
		{
			var dom_obj = jQuery(this),
				dom_parent = dom_obj.parents("li"),
				is_hidden = dom_parent.hasClass('hide'),
				is_checked = dom_obj.is(":checked");

			if(!is_hidden && is_checked)
			{
				products_checked++;

				dom_parent.addClass('active');
			}

			else
			{
				dom_parent.removeClass('active');
			}

			if(!is_hidden)
			{
				products_total++;
			}
		});

		self.model.set('products_total', products_total);
		self.model.set('products_checked', products_checked);

		jQuery(".product_search .quote_button, .product_search .quote_button .form_button > *:not(.is_map_toggler), .product_search .quote_button, .product_search .quote_button .wp-block-button > *:not(.is_map_toggler)").addClass('hide');

		jQuery(".quote_button, .show_if_results").removeClass('hide');

		this.update_total_amount();
		this.update_quote_amount();
	},

	update_total_amount: function()
	{
		jQuery(".search_result_info > span").html(this.model.get('products_total'));
	},

	update_quote_amount: function()
	{
		jQuery(".form_button .show_if_results span, .wp-block-button .show_if_results span").html(this.model.get('products_checked'));
	},

	set_last_product: function(e)
	{
		var last_product = jQuery(e.currentTarget).attr('id').replace('product_', '');

		jQuery.Storage.set({'last_product': last_product});
	},

	print_favorites: function()
	{
		window.print();

		return false;
	},

	/*scroll_to_last_product: function()
	{
		var last_product = jQuery.Storage.get('last_product');

		if(typeof last_product !== 'undefined' && last_product > 0)
		{
			var dom_obj = jQuery("#product_" + last_product);

			if(dom_obj.length > 0)
			{
				jQuery("html, body").animate(
				{
					scrollTop: dom_obj.offset().top
				}, 800);
			}
		}
	},*/

	show_products: function()
	{
		if(this.has_product_result)
		{
			var response = this.model.get('product_response'),
				products_amount = response.length,
				html = '';

			if(products_amount > 0)
			{
				var dom_template = jQuery("#template_product_item").html();

				jQuery.Storage.set({'result_products': JSON.stringify(response)});

				for(var i = 0; i < products_amount; i++)
				{
					html += _.template(dom_template)(response[i]);
				}

				jQuery(".product_list.webshop_item_list").html(html);

				this.get_products();

				this.show_map_coordinates({'fit_icons': true, 'remove_markers': false});

				/*if(!this.is_favorites_view)
				{
					this.scroll_to_last_product();
				}*/
			}

			else
			{
				jQuery.Storage.remove('result_products');

				if(this.is_favorites_view)
				{
					this.show_no_results_view();
				}

				else
				{
					html = _.template(jQuery("#template_product_message").html())('');

					jQuery(".product_list.webshop_item_list").html(html);
				}
			}

			this.show_quote_request_button();
		}
	},

	show_product_amount: function()
	{
		var response = this.model.get('product_amount'),
			html = "";

		if(response > script_webshop_views.show_all_min)
		{
			html = " " + response;
		}

		jQuery(".webshop_form .product_filtered_amount").html(html);
	},

	search_products_change: function()
	{
		jQuery.Storage.remove('last_product');

		this.search_products(true);
	},

	search_products: function(empty_bounds)
	{
		if(empty_bounds == true)
		{
			map_bounds_obj.val('');
		}

		this.set_hash();
	},

	search_product_amount: function()
	{
		this.model.getPage("type=amount&" + jQuery('.webshop_form form').serialize());
	},

	product_add_to_search_or_not: function(e)
	{
		var product_id = jQuery(e.target).attr('product_id');

		if(typeof product_id !== 'undefined' && product_id > 0)
		{
			if(jQuery(e.target).hasClass('send_request_for_quote'))
			{
				var form_url = jQuery(e.target).attr('form_url');

				if(typeof form_url !== 'undefined' && form_url != '' && form_url != '#')
				{
					location.href = form_url + "?products=" + product_id;
					return false;
				}

				else
				{
					location.href = script_webshop_views.site_url;
					return false;
				}
			}

			else
			{
				this.get_products_storage();

				var form_products = this.form_products;

				if(jQuery(e.target).hasClass('remove_from_search'))
				{
					var form_products_new = "";

					jQuery.each(form_products.split(','), function(index, value)
					{
						if(value != product_id && value != 'undefined')
						{
							form_products_new += (form_products_new != '' ? "," : "") + value;
						}
					});

					form_products = form_products_new;
				}

				else
				{
					form_products += (form_products != '' ? "," : "") + product_id;
				}

				this.form_products = form_products;
				this.set_products_storage();
			}
		}

		var form_serialized = jQuery.Storage.get('form_serialized');

		if(typeof form_serialized !== 'undefined' && form_serialized != '')
		{
			location.href = form_serialized;
			return false;
		}

		else
		{
			var search_url = jQuery(e.target).attr('search_url');

			if(typeof search_url !== 'undefined' && search_url != '')
			{
				location.href = search_url;
				return false;
			}

			else
			{
				location.href = script_webshop_views.site_url;
				return false;
			}
		}
	},

	show_map_coordinates: function(data)
	{
		if(search_map_obj.length > 0 && search_map_obj.is(":visible"))
		{
			if(data.remove_markers == true)
			{
				remove_markers();
			}

			jQuery(".map_coordinates").each(function()
			{
				var dom_obj = jQuery(this),
					dom_product = dom_obj.parents("li"),
					is_hovering = dom_product.hasClass('hover'),
					icon = (is_hovering ? script_webshop_views.symbol_active : script_webshop_views.symbol_inactive);

				if(dom_obj.parents("li").hasClass('ghost'))
				{
					icon = (is_hovering ? script_webshop_views.ghost_active : script_webshop_views.ghost_inactive);
				}

				add_map_location({'dom_obj': dom_obj, 'icon': icon});
			});

			if(data.fit_icons == true)
			{
				fitIcons();
			}
		}
	},

	section_hover: function(e)
	{
		jQuery(e.currentTarget).addClass('hover');

		this.show_map_coordinates({'fit_icons': false, 'remove_markers': true});
	},

	section_unhover: function(e)
	{
		jQuery(e.currentTarget).removeClass('hover');

		this.show_map_coordinates({'fit_icons': false, 'remove_markers': true});
	},

	toggle_aside: function(e)
	{
		var dom_obj = jQuery(e.currentTarget);

		jQuery(".map_wrapper").toggle();
		jQuery(".is_map_toggler").children("span").toggle();

		if(dom_obj.hasClass('is_map_toggler'))
		{
			if(map_initialized == false)
			{
				init_maps();
			}

			this.show_map_coordinates({'fit_icons': true, 'remove_markers': false});
		}
	},

	load_calendar: function()
	{
		var date = this.dom_calendar.attr('data-date'),
			product_id = this.dom_calendar.attr('data-product_id') || 0;

		if(this.dom_calendar.find(".calendar_header").length > 0)
		{
			this.dom_calendar.find(".calendar_header").html(_.template(jQuery("#template_calendar_spinner").html())(''));
		}

		var get_vars = "type=calendar&date=" + date;

		if(product_id > 0)
		{
			get_vars += "&product_id=" + product_id;
		}

		this.model.getPage(get_vars);
	},

	show_calendars: function()
	{
		var response = this.model.get('calendar_response'),
			html = '',
			dom_template = jQuery("#template_calendar").html();

		this.dom_calendar.children(".widget_spinner").remove();

		this.dom_calendar.html(_.template(dom_template)(response));
	},

	change_month: function(e)
	{
		var dom_obj = jQuery(e.currentTarget),
			new_month = dom_obj.attr('data-month');

		this.dom_calendar.attr({'data-date': new_month});
		this.load_calendar();

		return false;
	},

	change_date: function(e)
	{
		var dom_obj = jQuery(e.currentTarget),
			dom_list = dom_obj.parents(".webshop_events").find(".widget_list");

		dom_obj.parent(".day").addClass('today').siblings(".day").removeClass('today');

		dom_list.attr(
		{
			'data-date': dom_obj.attr('data-date'),
			'data-limit': 0
		}).empty();

		this.load_events(dom_list);

		return false;
	},

	change_category: function(e)
	{
		var dom_obj = jQuery(e.currentTarget),
			dom_list = dom_obj.parents(".webshop_events").find(".widget_list");

		if(dom_obj.is(":checked"))
		{
			dom_obj.parent(".form_checkbox").siblings(".form_checkbox").children(".event_filter_category").prop('checked', false);

			dom_list.attr(
			{
				'data-category': dom_obj.attr('value')
			});
		}

		else
		{
			dom_list.removeAttr('data-category');
		}

		dom_list.attr(
		{
			'data-limit': 0
		}).empty();

		this.load_events(dom_list);
	},

	change_order_by: function(e)
	{
		var dom_obj = jQuery(e.currentTarget),
			dom_list = dom_obj.parents(".webshop_events, .webshop_filter_products").find(".widget_list");

		dom_list.attr(
		{
			'data-order_by': dom_obj.val(),
			'data-limit': 0
		}).empty();

		if(dom_obj.hasClass("event_filter_order_by"))
		{
			this.load_events(dom_list);
		}

		else
		{
			this.load_filter_products(dom_list);
		}
	},

	load_events: function(dom_obj)
	{
		var widget_id = dom_obj.attr('id'),
			option_type = (dom_obj.attr('data-option_type') || ''),
			product_id = (dom_obj.attr('data-product_id') || 0),
			event_id = (dom_obj.attr('data-event_id') || 0),
			event_type = (dom_obj.attr('data-event_type') || ''),
			date = dom_obj.attr('data-date'),
			category = (dom_obj.attr('data-category') || ''),
			order_by = (dom_obj.attr('data-order_by') || ''),
			latitude = (dom_obj.attr('data-latitude') || ''),
			longitude = (dom_obj.attr('data-longitude') || ''),
			limit = dom_obj.attr('data-limit'),
			months = (dom_obj.attr('data-months') || ''),
			amount = dom_obj.attr('data-amount'),
			get_vars = "type=events&id=" + widget_id + "&start_date=" + date + "&amount=" + amount;

		if(months != '')
		{
			get_vars += "&months=" + months;
		}

		if(option_type != '')
		{
			get_vars += "&option_type=" + option_type;
		}

		if(product_id > 0)
		{
			get_vars += "&product_id=" + product_id;
		}

		if(event_id > 0)
		{
			get_vars += "&event_id=" + event_id;
		}

		if(event_type != '')
		{
			get_vars += "&event_type=" + event_type;
		}

		if(typeof category !== 'undefined' && category != '')
		{
			get_vars += "&category=" + category;
		}

		if(order_by != '')
		{
			get_vars += "&order_by=" + order_by;
		}

		if(latitude != '')
		{
			get_vars += "&latitude=" + latitude;
		}

		if(longitude != '')
		{
			get_vars += "&longitude=" + longitude;
		}

		if(dom_obj.children("li").length == 0)
		{
			get_vars += "&initial=true";
		}

		if(limit > 0)
		{
			get_vars += "&limit=" + limit;
		}

		dom_obj.children(".widget_load_more").remove();

		if(dom_obj.children(".widget_spinner").length == 0)
		{
			dom_obj.append(_.template(jQuery("#template_event_spinner").html())(''));
		}

		this.model.getPage(get_vars);
	},

	load_all_events: function()
	{
		var self = this;

		this.dom_obj_events.each(function()
		{
			self.load_events(jQuery(this).find(".widget_list"));
		});
	},

	show_events: function()
	{
		var widget_id = this.model.get('widget_id'),
			dom_widget = jQuery("#" + widget_id),
			response = this.model.get('event_response'),
			amount = response.length,
			html = '';

		dom_widget.children(".widget_spinner").remove();

		if(amount > 0)
		{
			var dom_template = jQuery("#template_event_item").html();

			for(var i = 0; i < amount; i++)
			{
				html += _.template(dom_template)(response[i]);
			}

			dom_widget.append(html);

			this.show_map_coordinates({'fit_icons': true, 'remove_markers': false});
		}

		else
		{
			html = _.template(jQuery("#template_event_message").html())({'start_date': this.model.get('event_start_date'), 'end_date': this.model.get('event_end_date')});

			dom_widget.html(html);
		}

		this.show_or_hide_load_more(widget_id, amount);

		this.add_my_location_to_filter(this.dom_obj_events.find(".event_filter_order_by"));
	},

	add_my_location_to_filter: function(dom_obj)
	{
		var my_location = this.model.get('my_location');

		if(typeof my_location !== 'undefined' && my_location != '')
		{
			var dom_obj_distance = dom_obj.children("option[value='distance']:not(.has_my_location)");

			if(dom_obj_distance.length > 0)
			{
				dom_obj_distance.addClass('has_my_location').append(" (" + my_location + ")");

				this.model.set({'my_location': ''});
			}
		}
	},

	load_filter_products: function(dom_obj)
	{
		var widget_id = dom_obj.attr('id'),
			option_type = (dom_obj.attr('data-option_type') || ''),
			category = dom_obj.attr('data-category'),
			order_by = (dom_obj.attr('data-order_by') || 'alphabetical'),
			link_product = (dom_obj.attr('data-link_product') || ''),
			limit = dom_obj.attr('data-limit'),
			amount = dom_obj.attr('data-amount'),
			get_vars = "type=filter_products&id=" + widget_id + "&amount=" + amount;

		if(typeof category !== 'undefined' && category != '')
		{
			get_vars += "&category=" + category;
		}

		if(order_by != '')
		{
			if(option_type != '')
			{
				get_vars += "&option_type=" + option_type;
			}

			get_vars += "&order_by=" + order_by;

			if(link_product != '')
			{
				get_vars += "&link_product=" + link_product;
			}

			switch(order_by)
			{
				default:
				case 'distance':
					var latitude = (dom_obj.attr('data-latitude') || ''),
						longitude = (dom_obj.attr('data-longitude') || '');
				break;

				case 'map_center':
					var latitude = (dom_obj.attr('data-map-latitude') || ''),
						longitude = (dom_obj.attr('data-map-longitude') || '');
				break;
			}

			if(latitude != '')
			{
				get_vars += "&latitude=" + latitude;
			}

			if(longitude != '')
			{
				get_vars += "&longitude=" + longitude;
			}

			if(dom_obj.children("li").length == 0)
			{
				get_vars += "&initial=true";
			}

			if(limit > 0)
			{
				get_vars += "&limit=" + limit;
			}

			dom_obj.children(".widget_load_more").remove();

			if(dom_obj.children(".widget_spinner").length == 0)
			{
				dom_obj.append(_.template(jQuery("#template_filter_products_spinner").html())(''));
			}

			this.model.getPage(get_vars);
		}
	},

	load_all_filter_products: function()
	{
		var self = this;

		this.dom_obj_products.each(function()
		{
			self.load_filter_products(jQuery(this).find(".widget_list"));
		});
	},

	show_filter_products: function()
	{
		var self = this,
			widget_id = this.model.get('widget_id'),
			dom_widget = jQuery("#" + widget_id),
			response = this.model.get('filter_products_response'),
			amount = response.length,
			html = '';

		dom_widget.children(".widget_spinner").remove();

		if(amount > 0)
		{
			var dom_template = jQuery("#template_filter_products_item").html();

			for(var i = 0; i < amount; i++)
			{
				html += _.template(dom_template)(response[i]);
			}

			dom_widget.append(html);

			this.show_map_coordinates({'fit_icons': true, 'remove_markers': true});
		}

		else
		{
			html = _.template(jQuery("#template_filter_products_message").html())('');

			dom_widget.html(html);
		}

		this.show_or_hide_load_more(widget_id, amount);

		this.add_my_location_to_filter(this.dom_obj_products.find(".product_filter_order_by"));
	},

	show_or_hide_load_more: function(widget_id, amount)
	{
		var dom_widget = jQuery("#" + widget_id),
			dom_parent = dom_widget.parents(".webshop_widget"),
			dom_type = (dom_parent.hasClass('webshop_events') ? 'events' : 'filter_products');

		if(dom_type == "events")
		{
			var event_amount_left = this.model.get('event_amount_left'),
				event_amount = this.model.get('event_amount'),
				event_rest = (event_amount_left + event_amount - amount);

			if(!(event_amount_left > 0))
			{
				dom_widget.siblings(".widget_text").find("span").text(event_amount);
			}

			if(event_rest > 0)
			{
				var dom_template = jQuery("#template_event_load_more").html();

				dom_widget.append(_.template(dom_template)({'event_rest': event_rest}));
			}
		}

		else
		{
			var filter_products_amount = this.model.get('filter_products_amount'),
				filter_products_rest = (filter_products_amount - amount);

			dom_widget.siblings(".widget_text").find("span").text(filter_products_amount);

			if(filter_products_rest > 0)
			{
				var dom_template = jQuery("#template_filter_products_load_more").html();

				dom_widget.append(_.template(dom_template)({'filter_products_rest': filter_products_rest}));
			}
		}
	},

	load_more_button: function(e)
	{
		var dom_obj = jQuery(e.currentTarget),
			dom_parent = dom_obj.parents(".webshop_widget"),
			dom_type = (dom_parent.hasClass('webshop_events') ? 'events' : 'filter_products'),
			dom_list = dom_parent.find(".widget_list"),
			limit = dom_list.attr('data-limit'),
			amount = dom_list.attr('data-amount');

		dom_list.attr({'data-limit': (parseInt(amount) + parseInt(limit))});

		switch(dom_type)
		{
			case 'events':
				this.load_events(dom_list);
			break;

			default:
			case 'filter_products':
				this.load_filter_products(dom_list);
			break;
		}

		return false;
	}
});

var myWebshopView = new WebshopView({model: new WebshopModel()});