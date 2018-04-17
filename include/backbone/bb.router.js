var WebshopApp = Backbone.Router.extend(
{
	routes: {
		"*actions": "the_rest"
	},
	the_rest: function(action_type)
	{
		myWebshopView.loadPage(action_type);
	}
});

new WebshopApp();