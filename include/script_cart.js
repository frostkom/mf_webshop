jQuery(function($)
{
	var dom_obj_widget = $(".widget.webshop_cart");

	function render_cart(data)
	{
		var response = data.response_webshop_cart,
			count_temp = response.products.length,
			html = "";

		if(count_temp > 0)
		{
			var dom_template = $("#template_webshop_cart_item").html();

			for(var i = 0; i < count_temp; i++)
			{
				html += _.template(dom_template)(response.products[i]);
			}

			dom_obj_widget.find(".cart_products tbody").html(html);

			dom_obj_widget.find(".cart_totals .shipping_cost").html(response.shipping_cost);
			dom_obj_widget.find(".cart_totals .total_sum").html(response.total_sum);
			dom_obj_widget.find(".cart_totals .total_tax").html(response.total_tax);

			dom_obj_widget.find(".proceed_to_checkout .total_sum").html(response.total_sum);

			dom_obj_widget.find(".cart_summary").removeClass('hide');
		}

		else
		{
			dom_obj_widget.find(".cart_products tbody tr").html(_.template($("#template_webshop_cart_empty").html())());

			dom_obj_widget.find(".cart_summary").addClass('hide');

			var cart_amount = parseInt(jQuery(".webshop_cart_icon div").text());

			jQuery(".webshop_cart_icon").removeClass('hide').children("div").text(cart_amount + 1);
		}
	}

	function update_product_amount(product_name, product_amount)
	{
		$.ajax(
		{
			url: script_webshop_cart.ajax_url,
			type: 'post',
			dataType: 'json',
			data: {
				action: 'api_webshop_update_product_amount',
				product_name: product_name,
				product_amount: product_amount,
			},
			success: function(data)
			{
				if(data.success)
				{
					render_cart(data);
				}
			}
		});
	}

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
				render_cart(data);
			}
		}
	});

	/* Update cart */
	/* ##################### */
	var update_timeout;

	dom_obj_widget.on('change', ".cart_products .mf_form_field[type='number']", function()
	{
		var dom_obj = $(this);

		clearTimeout(update_timeout);

		update_timeout = setTimeout(function()
		{
			update_product_amount(dom_obj.parents("tr").attr('id'), dom_obj.val());
		}, 500);
	});

	dom_obj_widget.on('click', ".cart_products .fa-trash.red", function()
	{
		var dom_obj = $(this);

		update_product_amount(dom_obj.parents("tr").attr('id'), 0);
	});
	/* ##################### */

	/* Fetch user details if logged in */
	if($.isArray(script_webshop_cart.arr_webshop_input_type))
	{
		var arr_fields = [];

		$.each(script_webshop_cart.arr_webshop_input_type, function(key, value)
		{
			var dom_obj_type = dom_obj_widget.find(".mf_form_field[data-fetch_info='" + value + "']");

			dom_obj_type.each(function()
			{
				var dom_obj = $(this);

				if(dom_obj.val() == '')
				{
					arr_fields.push([dom_obj.attr('id'), value]);
				}
			});
		});

		if(arr_fields.length > 0)
		{
			$.ajax(
			{
				url: script_webshop_cart.ajax_url,
				type: 'post',
				dataType: 'json',
				data:
				{
					action: 'api_webshop_fetch_info',
					arr_fields: arr_fields
				},
				success: function(data)
				{
					if(data.success)
					{
						$.each(data.response_fields, function(key, value)
						{
							dom_obj_widget.find("#" + value.id).val(value.value);
						});
					}
				}
			});
		}
	}

	/* Update order details */
	dom_obj_widget.on('blur', ".proceed_to_checkout .order_details", function()
	{
		var form_data = $(this).parents("form").serialize();

		$.ajax(
		{
			url: script_webshop_cart.ajax_url,
			type: 'post',
			dataType: 'json',
			data: form_data,
			success: function(data)
			{
				if(data.success)
				{
					/*$.each(data.response_fields, function(key, value)
					{
						dom_obj_widget.find("#" + value.id).val(value.value);
					});*/
				}
			}
		});
	});

	/* Check if orgno is entered */
	/* ##################### */
	$(document).on('input', ".toggle_invoice #payment_ssn", function()
	{
		let value = $(this).val();

		/* Remove all non-digit characters */
		value = value.replace(/\D/g, '');

		$(this).val(value);
	});

	$(document).on('input', ".toggle_invoice input", function()
	{
		let anyEmpty = false;

		$(".toggle_invoice input").each(function()
		{
			if(!$(this).val())
			{
				anyEmpty = true;
				return false;
			}
		});

		if(anyEmpty)
		{
			$(".toggle_invoice button[name='btnWebshopPayInvoice']").attr('disabled', true);
		}

		else
		{
			$(".toggle_invoice button[name='btnWebshopPayInvoice']").removeAttr('disabled');
		}
	});
	/* ##################### */

	/* Validate card details & activate buy button */
	/* ##################### */
	$(document).on('input', ".toggle_card #payment_card_no", function()
	{
		let value = $(this).val();

		/* Remove all non-digit characters */
		value = value.replace(/\D/g, '');

		/* Insert space after every 4 digits */
		value = value.replace(/(.{4})/g, '$1 ').trim();

		$(this).val(value);
	});

	$(document).on('input', ".toggle_card #payment_card_expires", function()
	{
		let input = $(this).val().replace(/\D/g, '').slice(0, 4);

		if(input.length >= 3)
		{
			input = input.slice(0, 2) + '/' + input.slice(2);
		}

		$(this).val(input);
	});

	$(document).on('input', ".toggle_card input", function()
	{
		let anyEmpty = false;

		$(".toggle_card input").each(function()
		{
			if(!$(this).val())
			{
				anyEmpty = true;
				return false;
			}
		});

		if(anyEmpty)
		{
			$(".toggle_card button[name='btnWebshopPayCard']").attr('disabled', true);
		}

		else
		{
			$(".toggle_card button[name='btnWebshopPayCard']").removeAttr('disabled');
		}
	});
	/* ##################### */
});