function update_cart_icon()
{
	var dom_obj = jQuery(".webshop_cart_icon"),
		cart_amount = parseInt(dom_obj.children("div").text());

	dom_obj.addClass('updating').removeClass('hide');

	setTimeout(function()
	{
		dom_obj.removeClass('updating').children("div").text(cart_amount + 1);
	}, 250);
}

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