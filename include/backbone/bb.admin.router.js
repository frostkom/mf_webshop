var WebshopAdminApp = Backbone.Router.extend(
{
	routes:
	{
		"admin/webshop/:action": "handle",
		"admin/webshop/:action/:action": "handle"
	},

	handle: function(action1, action2)
	{
		var action = "admin/webshop/" + action1;

		if(action2 != null)
		{
			action += "/" + action2;
		}

		myWebshopAdminView.loadPage(action);
	}
});

new WebshopAdminApp();