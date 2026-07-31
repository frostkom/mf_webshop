jQuery(function($)
{
	var dom_obj_widget = $(".widget.webshop_order_confirmation");

	if(dom_obj_widget.find(".swish_manual_qr_code").length > 0)
	{
		$.ajax(
		{
			url: script_webshop_order_confirmation_swish_manual.ajax_url,
			type: 'post',
			dataType: 'json',
			data: {
				action: 'api_qr_code_image',
				post_url: script_webshop_order_confirmation_swish_manual.swish_link,
				output_type: 'image',
			},
			success: function(data)
			{
				dom_obj_widget.find(".swish_manual_qr_code").html(data.html);
			}
		});
	}
});