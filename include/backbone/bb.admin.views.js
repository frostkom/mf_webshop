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
		"change .event_children .form_textfield input": "add_event_field"
	},

	do_redirect: function()
	{
		var response = this.model.get('redirect');

		if(response != '')
		{
			location.href = response + "?redirect_to=" + location.href;

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

	loadPage: function(tab_active)
	{
		this.hide_message();

		jQuery(".admin_container .loading").removeClass('hide').siblings("div").addClass('hide');

		this.model.getPage(tab_active);
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

				if(amount > 0)
				{
					html = _.template(jQuery("#template_" + type).html())(response);
				}

				else
				{
					html = _.template(jQuery("#template_" + type + "_message").html())('');
				}

				jQuery("#" + type).html(html).removeClass('hide').siblings("div").addClass('hide');
			break;

			case 'admin_webshop_edit':
				html = _.template(jQuery("#template_" + type).html())(response);

				jQuery("#" + type).html(html).removeClass('hide').siblings("div").addClass('hide');

				if(typeof do_multiselect === 'function')
				{
					do_multiselect();
				}

				jQuery("#" + type).find(".maps_search_container:not(.maps_initiated)").gmaps();

				this.add_event_field();
			break;
		}
	},

	add_event_field: function()
	{
		var dom_parent = jQuery(".event_children"),
			dom_last_child = dom_parent.children("div:last-child");

		if(dom_last_child.find(".form_textfield.event_name input").val() != '')
		{
			var clone = dom_last_child.clone();

			clone.find("input").val('').attr('value', '');

			dom_parent.append(clone);
		}
	}
});

var myWebshopAdminView = new WebshopAdminView({model: new WebshopAdminModel()});