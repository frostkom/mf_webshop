var WebshopAdminModel = Backbone.Model.extend(
{
	getPage: function(dom_action)
	{
		var self = this,
			url = '';

		if(dom_action)
		{
			url += '?type=' + dom_action;
		}

		jQuery().callAPI(
		{
			base_url: script_webshop_admin_models.plugin_url + 'api/',
			url: url + "&timestamp=" + Date.now(),
			send_type: 'get',
			onSuccess: function(data)
			{
				self.set(data);
			}
		});
	},

	submitForm: function(dom_action, form_data)
	{
		var self = this,
			url = '';

		if(dom_action)
		{
			url += '?type=' + dom_action;
		}

		jQuery().callAPI(
		{
			base_url: script_webshop_admin_models.plugin_url + 'api/',
			url: url,
			data: form_data,
			onSuccess: function(data)
			{
				self.set(data);
			}
		});
	}
});