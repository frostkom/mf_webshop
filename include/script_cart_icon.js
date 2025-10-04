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
			if(data.success)
			{
				$(".icon-cart").removeClass('hide');
			}
		}
	});
});