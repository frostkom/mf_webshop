var WebshopApp = Backbone.Router.extend(
{
	routes:
	{
		"webshop/:actions": "handle"
	},

	handle: function(action_type)
	{
		myWebshopView.loadPage(action_type);
	}
});

new WebshopApp();