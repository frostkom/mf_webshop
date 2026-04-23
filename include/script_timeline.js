jQuery(function($)
{
	var dom_obj = $(".webshop_timeline ul");

	$.ajax(
	{
		url: script_webshop_timeline.ajax_url,
		type: 'post',
		dataType: 'json',
		data:
		{
			action: 'api_webshop_timeline',
			search_post_id: dom_obj.data('search_post_id'),
			cart_post_id: dom_obj.data('cart_post_id'),
			shop_has_addon: dom_obj.data('shop_has_addon')
		},
		success: function(data)
		{
			if(data.success)
			{
				$(".webshop_timeline ul").html(data.html);
			}
		}
	});
});