jQuery(function($)
{
	$.ajax(
	{
		url: script_webshop_cart_icon.ajax_url,
		type: 'post',
		dataType: 'json',
		data:
		{
			action: 'api_webshop_cart_icon'
		},
		success: function(data)
		{
			if(data.success && data.product_amount > 0)
			{
				$(".webshop_cart_icon").removeClass('hide').children("div").text(data.product_amount);
			}
		}
	});
});