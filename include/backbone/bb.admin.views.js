var WebshopAdminView = Backbone.View.extend(
{
	el: jQuery("body"),

	initialize: function()
	{
		this.model.on("change:redirect", this.do_redirect, this);
		this.model.on('change:message', this.display_message, this);
		this.model.on("change:next_request", this.next_request, this);
		this.model.on("change:admin_webshop_response", this.view_response, this);
	},

	events:
	{
		"submit form": "submit_form",
		"blur .event_children .form_textfield input": "add_event_field",
		"change .event_children .form_textfield input": "add_event_field",
		"click .event_children .event_name .description .fa-trash": "clear_event_field"
	},

	do_redirect: function()
	{
		var response = this.model.get('redirect');

		if(response != '')
		{
			location.href = response; /* + (response.match(/\?/) ? "&" : "?") + "redirect_to=" + location.href*/

			this.model.set({'redirect': ''});
		}
	},

	hide_message: function()
	{
		jQuery(".error:not(.hide), .updated:not(.hide)").addClass('hide');
	},

	display_message: function()
	{
		var response = this.model.get('message');

		if(response != '')
		{
			this.hide_message();

			if(this.model.get('success') == true)
			{
				jQuery(".updated.hide").removeClass('hide').children("p").html(response);
			}

			else
			{
				jQuery(".error.hide").removeClass('hide').children("p").html(response);
			}

			scroll_to_top();

			jQuery(".mf_form button[type='submit']").removeClass('disabled').removeAttr('disabled');

			this.model.set({'message': ''});
		}
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

	display_container: function(dom_container)
	{
		dom_container.removeClass('hide').siblings("div").addClass('hide');
	},

	loadPage: function(action)
	{
		this.hide_message();

		var dom_container = jQuery("#" + action.replace(/\//g, '_'));

		if(dom_container.length > 0)
		{
			this.display_container(dom_container);
		}

		else
		{
			jQuery(".admin_container .loading").removeClass('hide').siblings("div").addClass('hide');
		}

		this.model.getPage(action);
	},

	submit_form: function(e)
	{
		var dom_obj = jQuery(e.currentTarget),
			dom_action = dom_obj.attr('data-action');

		this.model.submitForm(dom_action, dom_obj.serialize());

		dom_obj.find("button[type='submit']").addClass('disabled').attr('disabled', true);

		return false;
	},

	view_response: function()
	{
		var response = this.model.get('admin_webshop_response'),
			type = response.type,
			html = '';

		switch(type)
		{
			case 'admin_webshop_list':
				var amount = response.list.length;

				var dom_template = jQuery("#template_" + type),
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

				this.display_container(dom_container);
			break;

			case 'admin_webshop_edit':
				var dom_template = jQuery("#template_" + type),
					dom_container = jQuery("#" + type);

				html = _.template(dom_template.html())(response);

				dom_container.children("div").html(html);

				this.display_container(dom_container);

				if(typeof select_option === 'function')
				{
					select_option();
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

			clone.find("input").val('').attr('value', '');
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

			dom_parent.addClass('hide');
			dom_parent.find("input[type!='hidden']").val('').attr('value', '');
			dom_parent.find("select option:selected").prop('selected', false);
		}

		else
		{
			return false;
		}
	}
});

var myWebshopAdminView = new WebshopAdminView({model: new WebshopAdminModel()});