jQuery(function($)
{
	$.ajax(
	{
		url: script_webshop_cart.ajax_url,
		type: 'post',
		dataType: 'json',
		data: {
			action: 'api_webshop_call',
			type: 'webshop_cart'
		},
		success: function(data)
		{
			if(data.success)
			{
				var response = data.response_webshop_cart,
					count_temp = response.length,
					html = "";

				if(count_temp > 0)
				{
					var dom_template = $("#template_webshop_cart_item").html();

					for(var i = 0; i < count_temp; i++)
					{
						html += response[i];
						html += _.template(dom_template)(response[i]);
					}

					$(".widget.webshop_cart table tbody").html(html);
				}

				else
				{
					$(".widget.webshop_cart table tbody td").html(_.template($("#template_webshop_cart_empty").html())());
				}
			}

			else
			{
				console.log("Error...");
			}
		}
	});
});