var WebshopAdminView = Backbone.View.extend(
{
	el: jQuery("body"),

	initialize: function()
	{
		this.model.on("change:redirect", this.do_redirect, this);
		this.model.on("change:admin_webshop_response", this.view_response, this);
	},

	events:
	{
		"submit form": "submit_form"
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

	loadPage: function(tab_active)
	{
		jQuery(".admin_container .loading").removeClass('hide').siblings("div").addClass('hide');

		this.model.getPage(tab_active);
	},

	submit_form: function(e)
	{
		/* Save info to API */

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
					html += _.template(jQuery("#template_admin_webshop_list").html())(response);
				}

				else
				{
					html = _.template(jQuery("#template_admin_webshop_list_message").html())('');
				}
				
				jQuery("#" + type).html(html).removeClass('hide').siblings("div").addClass('hide');
			break;

			case 'admin_webshop_edit':
				html += _.template(jQuery("#template_admin_webshop_edit").html())(response);

				jQuery("#" + type).html(html).removeClass('hide').siblings("div").addClass('hide');
			break;
		}
	}
});

var myWebshopAdminView = new WebshopAdminView({model: new WebshopAdminModel()});