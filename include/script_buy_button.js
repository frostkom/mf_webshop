jQuery(function($)
{
	var dom_obj_widget = $(".widget.webshop_buy_button");

	dom_obj_widget.each(function()
	{
		var dom_obj = $(this).find(".add_to_cart");

		$.ajax(
		{
			url: script_webshop_buy_button.ajax_url,
			type: 'post',
			dataType: 'json',
			data: {
				action: 'api_webshop_buy_button',
				product_id: dom_obj.attr('rel')
			},
			success: function(data)
			{
				if(data.success)
				{
					if(data.html && data.html != '')
					{
						dom_obj.parent("div").html(data.html);
					}
				}

				else
				{
					console.log("Error...");
				}
			}
		});
	});
});