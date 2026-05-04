var search_timeout;

var WebshopView = Backbone.View.extend(
{
	el: jQuery("body"),

	initialize: function()
	{
		/* Product */
		this.model.on("change:product_response", this.show_products, this);
		this.model.on("change:response_add_to_cart", this.show_add_to_cart, this);

		this.has_product_result = (jQuery(".webshop_search .grid_columns").length > 0);

		this.model.on("change:filter_products_hash", this.show_filter_products, this);

		this.if_search_view();
	},

	events:
	{
		/* Template Search */
		"click .webshop_search .grid_columns .products": "products_change",

		/* Result List */
		"keyup .widget.webshop_search form input": "search_products_delay",
		"change .widget.webshop_search form input": "search_products_delay",
		"change .widget.webshop_search form select": "search_products",
		"blur .widget.webshop_search form input[type!='checkbox']": "search_products",
		"click .webshop_search .grid_columns > li .add_to_cart": "add_to_cart",
		"click .webshop_search .grid_columns > li .disabled": "disabled",

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

	if_search_view: function()
	{
		if(jQuery(".widget.webshop_search form").length > 0)
		{
			this.get_hash();
			this.search_products();
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

	set_hash: function(hash)
	{
		var form_serialized = jQuery(".widget.webshop_search form").serialize().replace(/[^&]+=&/g, '').replace(/&[^&]+=$/g, '');

		location.hash = "webshop/" + form_serialized + (hash != '' && typeof hash != 'undefined' ? "&hash=" + hash : "");
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
	},

	product_form_has_changed: function()
	{
		return (jQuery(".widget.webshop_search form .form_select:first-of-type select").val() != '' || jQuery(".widget.webshop_search form .form_checkbox input").is(":checked"));
	},

	add_to_cart: function(e)
	{
		var product_id = jQuery(e.currentTarget).addClass('loading').parents("li").attr('id').replace('product_', '');

		this.model.getPage("type=add_to_cart&product_id=" + product_id);

		e.preventDefault();
		return false;
	},

	disabled: function(e)
	{
		e.preventDefault();
		return false;
	},

	show_add_to_cart: function()
	{
		var response = this.model.get('response_add_to_cart'),
			dom_obj_parent = jQuery(".webshop_search .grid_columns > li#product_" + response.product_id);

		dom_obj_parent.find(".add_to_cart").removeClass('loading');

		dom_obj_parent.find(".in_cart").removeClass('hide').find("span:first-of-type").addClass('updating');

		setTimeout(function()
		{
			dom_obj_parent.find(".in_cart span:first-of-type").text(response.product_amount).removeClass('updating');
		}, 250);

		if(response.is_allowed_to_buy == false)
		{
			dom_obj_parent.find(".add_to_cart").addClass('disabled').removeClass('add_to_cart').attr('title', response.is_allowed_to_buy_reason);
		}

		dom_obj_parent.find(".product_amount_left").text(response.product_amount_left);

		update_cart_icon();

		if(response.trigger_reload == 'yes')
		{
			this.set_hash(response.product_id);
		}
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

				jQuery(".webshop_search .grid_columns").html(html);

				this.get_products();
			}

			else
			{
				html = _.template(jQuery("#template_product_message").html())('');

				jQuery(".webshop_search .grid_columns").html(html);
			}
		}
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

	load_filter_products: function(dom_obj)
	{
		var widget_id = dom_obj.attr('id'),
			category = dom_obj.attr('data-category'),
			order_by = (dom_obj.attr('data-order_by') || 'menu_order'),
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