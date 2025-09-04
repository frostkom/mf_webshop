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

		jQuery.ajax(
		{
			url: script_webshop_models.ajax_url,
			type: 'post',
			dataType: 'json',
			data: {
				action: 'api_webshop_call',
				type: url
			},
			success: function(data)
			{
				self.set(data);
			}
		});
	}
});