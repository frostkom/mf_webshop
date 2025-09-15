jQuery(function($)
{
	var dom_obj = $(".widget.webshop_cart");

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
					count_temp = response.products.length,
					html = "";

				if(count_temp > 0)
				{
					dom_obj.find("#proceed_to_checkout input[name='order_id']").val(response.order_id);

					var dom_template = $("#template_webshop_cart_item").html();

					for(var i = 0; i < count_temp; i++)
					{
						html += _.template(dom_template)(response.products[i]);
					}

					dom_obj.find(".cart_products tbody").html(html);

					dom_obj.find(".cart_totals .total_sum").html(response.total_sum);
					dom_obj.find(".cart_totals .total_tax").html(response.total_tax);
					dom_obj.find(".cart_totals").removeClass('hide');
				}

				else
				{
					dom_obj.find(".cart_products tbody td").html(_.template($("#template_webshop_cart_empty").html())());
				}
			}

			else
			{
				console.log("Error...");
			}
		}
	});

	/*$(document).on('click', ".proceed_to_checkout", function()
	{
		$("#proceed_to_checkout").removeClass('hide');
		$(this).addClass('hide');

		return false;
	});*/

	$(document).on('blur', "#proceed_to_checkout", function()
	{
		var form_data = $(this).serialize();

		$.ajax(
		{
			url: script_webshop_cart.ajax_url,
			type: 'post',
			dataType: 'json',
			data: form_data,
			success: function(data)
			{
				if(data.success){}

				else
				{
					console.log("Error...");
				}
			}
		});
	});
});