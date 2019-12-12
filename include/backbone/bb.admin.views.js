var WebshopAdminView = Backbone.View.extend(
{
	el: jQuery("#admin_webshop_edit"),

	initialize: function()
	{
		this.model.on("change:redirect", myAdminView.do_redirect, this);
		this.model.on('change:message', myAdminView.display_message, this);
		this.model.on("change:next_request", this.next_request, this);
		this.model.on("change:admin_webshop_response", this.admin_webshop_response, this);
	},

	events:
	{
		"change .event_children .type_select #mf_calendar_category": "on_change_category",
		"blur .event_children .start_date": "set_limit_end_date",
		"change .event_children .start_date": "set_limit_end_date",
		"blur .event_children .start_time": "set_limit_end_time",
		"change .event_children .start_time": "set_limit_end_time",
		"submit form": "submit_form",
		"blur .event_children .form_textfield input": "add_event_field",
		/*"change .event_children .form_textfield input": "add_event_field",*/
		"click .event_children .event_name .description .fa-trash": "clear_event_field",
		"keyup .maps_location input": "clear_coordinates",
	},

	next_request: function()
	{
		var response = this.model.get("next_request");

		if(response != '')
		{
			this.model.getPage(response);

			this.model.set({"next_request" : ""});
		}
	},

	loadPage: function(action)
	{
		myAdminView.hide_message();

		var dom_container = jQuery("#" + action.replace(/\//g, '_'));

		if(dom_container.length > 0)
		{
			myAdminView.display_container(dom_container);
		}

		else
		{
			jQuery(".admin_container .loading").removeClass('hide').siblings("div").addClass('hide');
		}

		this.model.getPage(action);
	},

	on_change_category: function(e)
	{
		this.set_limit_end_date(jQuery(e.currentTarget).closest("li").find(".start_date"));
	},

	get_event_max_length: function(dom_obj)
	{
		var event_max_length = parseInt(script_webshop_admin_views.event_max_length),
			category_max_length = dom_obj.closest("li").find("#" + script_webshop_admin_views.calendar_meta_prefix + "category :selected");

		if(category_max_length.length > 0)
		{
			if(typeof category_max_length.data('event_max_length') !== 'undefined' && category_max_length.data('event_max_length') > 0)
			{
				event_max_length = category_max_length.data('event_max_length');
			}
		}

		return (event_max_length - 1);
	},

	set_limit_start_date: function(e)
	{
		var dom_obj = (e.currentTarget ? jQuery(e.currentTarget) : e),
			date = new Date();

		date.setDate(date.getDate() - this.get_event_max_length(dom_obj));

		var date_year = date.getFullYear(),
			date_month = (date.getMonth() + 1),
			date_day = date.getDate(),
			date_start_min = date_year + "-" + (date_month < 10 ? "0" : "") + date_month + "-" + (date_day < 10 ? "0" : "") + date_day;

		dom_obj.children("input[type='date']").attr(
		{
			'min': date_start_min
		});
	},

	set_limit_end_date: function(e)
	{
		var dom_obj = (e.currentTarget ? jQuery(e.currentTarget) : e),
			date_start_val = dom_obj.children("input[type='date']").val(),
			dom_sibling = dom_obj.siblings(".form_textfield").children("input[type='date']");

		if(date_start_val != '')
		{
			var date = new Date(date_start_val);

			date.setDate(date.getDate() + this.get_event_max_length(dom_obj));

			var date_year = date.getFullYear(),
				date_month = (date.getMonth() + 1),
				date_day = date.getDate(),
				date_start_max = date_year + "-" + (date_month < 10 ? "0" : "") + date_month + "-" + (date_day < 10 ? "0" : "") + date_day;

			dom_sibling.attr(
			{
				'min': date_start_val,
				'max': date_start_max
			});
		}

		else
		{
			dom_sibling.removeAttr('min').removeAttr('max');
		}
	},

	set_limit_end_time: function(e)
	{
		var dom_obj = (e.currentTarget ? jQuery(e.currentTarget) : e),
			date_start_val = dom_obj.children("input[type='time']").val(),
			dom_sibling = dom_obj.siblings(".form_textfield").children("input[type='time']");

		if(date_start_val != '')
		{
			dom_sibling.attr({'min': date_start_val});
		}

		else
		{
			dom_sibling.removeAttr('min');
		}
	},

	submit_form: function(e)
	{
		var dom_obj = jQuery(e.currentTarget),
			dom_action = dom_obj.attr('data-action'),
			api_url = dom_obj.attr('data-api-url') || '';

		if(api_url == '')
		{
			this.model.submitForm(dom_action, dom_obj.serialize());

			dom_obj.find("button[type='submit']").addClass('loading is_disabled').attr('disabled', true);

			return false;
		}
	},

	admin_webshop_response: function()
	{
		var response = this.model.get('admin_webshop_response'),
			type = response.type,
			html = '';

		switch(type)
		{
			case 'admin_webshop_list':
				var amount = response.list.length,
					dom_template = jQuery("#template_" + type),
					dom_container = jQuery("#" + type);

				if(amount > 0)
				{
					html = _.template(dom_template.html())(response);
				}

				else
				{
					html = _.template(jQuery("#template_" + type + "_message").html())('');
				}

				dom_container.children("div").html(html);

				myAdminView.display_container(dom_container);
			break;

			case 'admin_webshop_edit':
				var self = this,
					dom_template = jQuery("#template_" + type),
					dom_container = jQuery("#" + type);

				html = _.template(dom_template.html())(response);

				dom_container.children("div").html(html);

				/* Hack as long as show_textfield() etc. is used */
				dom_container.find(".description").each(function()
				{
					if(jQuery(this).is(":empty"))
					{
						jQuery(this).remove();
					}
				});

				myAdminView.display_container(dom_container);

				jQuery(".event_children .start_date").each(function()
				{
					self.set_limit_start_date(jQuery(this));
					self.set_limit_end_date(jQuery(this));
				});

				jQuery(".event_children .start_time").each(function()
				{
					self.set_limit_end_time(jQuery(this));
				});

				if(typeof select_option === 'function')
				{
					select_option();
				}

				if(typeof render_required === 'function')
				{
					render_required();
				}

				if(typeof do_multiselect === 'function')
				{
					do_multiselect();
				}

				dom_container.find(".maps_search_container:not(.maps_initiated)").gmaps();

				this.add_event_field();

				if(typeof init_media_button === 'function')
				{
					init_media_button();
				}
			break;
		}
	},

	add_event_field: function(e)
	{
		var dom_parent = jQuery(".event_children"),
			dom_last_child = dom_parent.children("li:not(.hide):last-child");

		if(dom_last_child.find(".form_textfield.event_name input").val() != '')
		{
			var clone = dom_last_child.clone();

			clone.find("input, textarea, select").val('').attr('value', '');
			clone.find(".event_name").children(".description").addClass('hide');

			dom_parent.append(clone);
		}

		if(typeof e !== 'undefined')
		{
			var dom_obj = jQuery(e.currentTarget);

			if(dom_obj.length > 0 && dom_obj.parent(".form_textfield").hasClass('event_name'))
			{
				if(dom_obj.val() != '')
				{
					dom_obj.siblings(".description").removeClass('hide');
				}

				else
				{
					dom_obj.siblings(".description").addClass('hide');
				}
			}
		}
	},

	clear_event_field: function(e)
	{
		var confirm_text = script_webshop_admin_views.confirm_question;

		if(confirm(confirm_text))
		{
			var dom_obj = jQuery(e.currentTarget),
				dom_parent = dom_obj.parents(".event_name").parents("li");

			dom_parent.find("input[type!='hidden']").val('').attr('value', '');
			dom_parent.find("select option:selected").prop('selected', false);

			if(dom_parent.is(":last-child") == false)
			{
				dom_parent.addClass('hide');
			}
		}

		else
		{
			return false;
		}
	},

	clear_coordinates: function(e)
	{
		jQuery(e.currentTarget).parent(".form_textfield").siblings(".maps_coordinates").val('');
	}
});

var myWebshopAdminView = new WebshopAdminView({model: new WebshopAdminModel()});