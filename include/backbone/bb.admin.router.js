var WebshopAdminApp = Backbone.Router.extend(
{
	routes:
	{
		"admin/webshop/:actions": "handle"
    },

    handle: function(action)
	{
		myWebshopAdminView.loadPage(action);
    }
});

new WebshopAdminApp();