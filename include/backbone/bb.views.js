var my_lat, my_lon, search_timeout;

var WebshopView = Backbone.View.extend(
{
	el: jQuery("body"),

	initialize: function()
	{
		/* Product */
		this.model.on("change:product_response", this.show_products, this);
		this.model.on("change:product_amount", this.show_product_amount, this);
		this.model.on("change:response_add_to_cart", this.show_add_to_cart, this);

		this.has_product_result = (jQuery(".product_list.webshop_item_list").length > 0);

		this.model.on("change:filter_products_hash", this.show_filter_products, this);

		this.if_search_view();
		this.if_product_view();
	},

	events:
	{
		/* Template Search */
		"click .product_list.webshop_item_list .products": "products_change",
		"change .widget.webshop_search form input[type=range]": "filter_distance",
		"submit .widget.webshop_search form": "submit_form",

		/* Result List */
		"keyup .widget.webshop_search form input": "search_products_delay",
		"change .widget.webshop_search form input": "search_products_delay",
		"change .widget.webshop_search form select": "search_products",
		"blur .widget.webshop_search form input[type!='checkbox']": "search_products",
		"mouseenter .product_list.webshop_item_list > li": "section_hover",
		"mouseleave .product_list.webshop_item_list > li": "section_unhover",
		"change .webshop_form form select": "search_product_amount",
		"click .widget.webshop_search form.form_button_container .form_button button, .widget.webshop_search form.form_button_container .wp-block-button button": "product_add_to_search_or_not",
		"click .product_list.webshop_item_list > li": "set_last_product",
		"click .product_list.webshop_item_list > li .add_to_cart": "add_to_cart",

		/* Filter Products */
		"click .webshop_widget .widget_load_more button": "load_more_button"
	},

	loadPage: function(tab_active)
	{
		if(this.has_product_result)
		{
			this.model.getPage(tab_active);
		}
	},

	show_results_view: function()
	{
		jQuery(".search_result_info").show();
	},

	show_no_results_view: function()
	{
		jQuery(".favorite_result").addClass('hide');
		jQuery(".favorite_fallback").removeClass('hide');
	},

	if_search_view: function()
	{
		if(jQuery(".widget.webshop_search form").length > 0)
		{
			this.get_hash();
			this.search_products();

			var dom_obj_range = jQuery(".widget.webshop_search form input[type=range]");

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
		var dom_product_buttons = jQuery(".widget.webshop_search form.form_button_container");

		if(dom_product_buttons.length > 0)
		{
			dom_product_buttons.find(".has_searched").addClass('hide');
			dom_product_buttons.find(".has_not_searched").removeClass('hide');

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
							if(response[(i - j)]['product_url'] != '#')
							{
								html += "<a href='" + response[(i - j)]['product_url'] + "' class='product_previous'><i class='fa fa-chevron-left'></i><span>" + response[(i - j)]['product_title'] + "</span></a>";

								break;
							}
						}

						for(var j = 1; (i + j) < products_amount; j++)
						{
							if(response[(i + j)]['product_url'] != '#')
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
		var form_serialized = jQuery(".widget.webshop_search form").serialize().replace(/[^&]+=&/g, '').replace(/&[^&]+=$/g, '');

		location.hash = "webshop/" + form_serialized;
	},

	set_products: function()
	{
		var form_products = '';

		jQuery(".products:checked").each(function()
		{
			form_products += (form_products != '' ? "," : "") + jQuery(this).val();
		});

		this.form_products = form_products;
	},

	get_products: function()
	{
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
		return (jQuery(".widget.webshop_search form .form_select:first-of-type select").val() != '' || jQuery(".widget.webshop_search form .form_checkbox input").is(":checked"));
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

		jQuery(".show_if_results").removeClass('hide');

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
	},

	add_to_cart: function(e)
	{
		var product_id = jQuery(e.currentTarget).addClass('loading').parents("li").attr('id').replace('product_', '');

		this.model.getPage("type=add_to_cart&product_id=" + product_id);

		return false;
	},

	show_add_to_cart: function()
	{
		var response = this.model.get('response_add_to_cart');

		var dom_obj_parent = jQuery(".product_list.webshop_item_list > li#product_" + response.product_id);

		dom_obj_parent.find(".add_to_cart").removeClass('loading');
		dom_obj_parent.find(".in_cart").removeClass('hide').find("span span").text(response.product_amount);

		jQuery(".icon-cart").removeClass('hide');
	},

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

				for(var i = 0; i < products_amount; i++)
				{
					html += _.template(dom_template)(response[i]);
				}

				jQuery(".product_list.webshop_item_list").html(html);

				this.get_products();
			}

			else
			{
				html = _.template(jQuery("#template_product_message").html())('');

				jQuery(".product_list.webshop_item_list").html(html);
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

	search_products_delay: function()
	{
		var self = this;

		clearTimeout(search_timeout);

		search_timeout = setTimeout(function()
		{
			self.search_products();
		}, 500);
	},

	search_products: function()
	{
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
			}
		}

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
	},

	section_hover: function(e)
	{
		jQuery(e.currentTarget).addClass('hover');
	},

	section_unhover: function(e)
	{
		jQuery(e.currentTarget).removeClass('hover');
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
		}

		else
		{
			html = _.template(jQuery("#template_filter_products_message").html())('');

			dom_widget.html(html);
		}

		this.show_or_hide_load_more(widget_id, amount);
	},

	show_or_hide_load_more: function(widget_id, amount)
	{
		var dom_widget = jQuery("#" + widget_id),
			dom_parent = dom_widget.parents(".webshop_widget");

		var filter_products_amount = this.model.get('filter_products_amount'),
			filter_products_rest = (filter_products_amount - amount);

		dom_widget.siblings(".widget_text").find("span").text(filter_products_amount);

		if(filter_products_rest > 0)
		{
			var dom_template = jQuery("#template_filter_products_load_more").html();

			dom_widget.append(_.template(dom_template)({'filter_products_rest': filter_products_rest}));
		}
	},

	load_more_button: function(e)
	{
		var dom_obj = jQuery(e.currentTarget),
			dom_parent = dom_obj.parents(".webshop_widget"),
			dom_list = dom_parent.find(".widget_list"),
			limit = dom_list.attr('data-limit'),
			amount = dom_list.attr('data-amount');

		dom_list.attr({'data-limit': (parseInt(amount) + parseInt(limit))});

		this.load_filter_products(dom_list);

		return false;
	}
});

var myWebshopView = new WebshopView({model: new WebshopModel()});