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
					dom_obj.siblings(".in_cart").removeClass('hide').find("span span").text(data.product_amount);

					$(".icon-cart").removeClass('hide');
				}
			}
		});
	});

	dom_obj_widget.on('click', ".add_to_cart", function()
	{
		var dom_obj = $(this).addClass('loading');

		$.ajax(
		{
			url: script_webshop_buy_button.ajax_url,
			type: 'post',
			dataType: 'json',
			data: {
				action: 'api_webshop_add_to_cart',
				product_id: dom_obj.attr('rel')
			},
			success: function(data)
			{
				if(data.success)
				{
					dom_obj.removeClass('loading').siblings(".in_cart").removeClass('hide').find("span span").text(data.response_add_to_cart.product_amount);

					$(".icon-cart").removeClass('hide');
				}
			}
		});
	});
});