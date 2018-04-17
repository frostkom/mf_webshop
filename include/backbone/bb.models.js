var WebshopModel = Backbone.Model.extend(
{
	defaults: {
		'products_total': 0,
		'products_checked': 0
	},

	getPage: function(dom_href)
	{
		var self = this,
			url = (dom_href ? '?' + dom_href.replace('#', '') : "");

		jQuery().callAPI(
		{
			base_url: script_webshop_models.plugin_url + 'api/',
			url: url,
			send_type: 'get',
			onSuccess: function(data)
			{
				self.set(data);
			}
		});
	}
});