var WebshopAdminModel = Backbone.Model.extend(
{
	defaults: {
		/*'': 0*/
	},

	getPage: function(dom_href)
	{
		var self = this,
			url = (dom_href ? '?type=admin_webshop_' + dom_href.replace('#', '') : "");

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
	}
});