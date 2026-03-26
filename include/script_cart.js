jQuery(function($)
{
	var dom_obj_widget = $(".widget.webshop_cart"),
		countdown_interval;

	function render_cart(data)
	{
		var response = data.response_webshop_cart,
			count_temp = response.products.length,
			html = "";

		if(count_temp > 0)
		{
			var dom_template = $("#template_webshop_cart_item").html(),
				product_time_limit = 0;

			for(var i = 0; i < count_temp; i++)
			{
				html += _.template(dom_template)(response.products[i]);

				if(response.products[i].product_time_limit > product_time_limit)
				{
					product_time_limit = response.products[i].product_time_limit;
				}
			}

			dom_obj_widget.find(".cart_products tbody").html(html);

			if(product_time_limit > 0)
			{
				$(".cart_countdown").removeClass('hide');

				var totalSeconds = (product_time_limit * 60),
					dom_countdown = $(".cart_countdown").find("span");

				function formatTime(sec)
				{
					var m = Math.floor(sec / 60),
						s = (sec % 60);

					return m + "m " + (s < 10 ? "0" + s : s) + "s";
				}

				function tick()
				{
					if(totalSeconds <= 0)
					{
						dom_countdown.text("0m 00s");

						clearInterval(countdown_interval);

						return;
					}

					dom_countdown.text(formatTime(totalSeconds));

					totalSeconds--;
				}

				dom_countdown.text(formatTime(totalSeconds));

				tick();

				clearInterval(countdown_interval);
				countdown_interval = setInterval(tick, 1000);
			}

			dom_obj_widget.find(".cart_totals .shipping_cost").html(response.shipping_cost);
			dom_obj_widget.find(".cart_totals .total_sum_invoice").html(response.total_sum_invoice);
			dom_obj_widget.find(".cart_totals .total_sum").html(response.total_sum);
			dom_obj_widget.find(".cart_totals .total_tax").html(response.total_tax);

			dom_obj_widget.find(".proceed_to_checkout .total_sum_invoice").html(response.total_sum_invoice);
			dom_obj_widget.find(".proceed_to_checkout .total_sum").html(response.total_sum);

			if(dom_obj_widget.find(".swish_manual").length > 0)
			{
				var swish_link = dom_obj_widget.find(".proceed_to_checkout .swish_manual_form a").attr('rel');

				dom_obj_widget.find(".proceed_to_checkout .swish_manual_form a").attr('href', swish_link.replace('[total_sum]', response.total_sum_raw));
			}

			dom_obj_widget.find(".cart_summary").removeClass('hide');
		}

		else
		{
			dom_obj_widget.find(".cart_products tbody tr").html(_.template($("#template_webshop_cart_empty").html())());

			dom_obj_widget.find(".cart_summary").addClass('hide');

			update_cart_icon();
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

	function display_payment_alternatives_or_not()
	{
		var is_all_entered = true;

		$(".order_details input[required]").each(function()
		{
			if($(this).val().trim() === '')
			{
				is_all_entered = false;
				return false;
			}
		});

		if(is_all_entered == true)
		{
			$(".payment_require_information").addClass('hide');
			$(".payment_alternatives").removeClass('hide');
		}

		else
		{
			$(".payment_alternatives").addClass('hide');
			$(".payment_require_information").removeClass('hide');
		}
	}

	function get_webshop_cart()
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
					render_cart(data);
				}

				/*else if(data.data.redirect_url)
				{
					window.location.href = data.data.redirect_url;
				}*/
			}
		});
	}

	get_webshop_cart();

	setInterval(function()
	{
		get_webshop_cart();
	}, 60000);

	/* Update Cart */
	/* ##################### */
	var update_timeout;

	dom_obj_widget.on('change', ".cart_products .mf_form_field[inputmode='numeric']", function()
	{
		var dom_obj = $(this);

		clearTimeout(update_timeout);

		update_timeout = setTimeout(function()
		{
			update_product_amount(dom_obj.parents("tr").attr('id'), dom_obj.val());
		}, 1000);
	});

	dom_obj_widget.on('click', ".cart_products .fa-trash.red", function()
	{
		var dom_obj = $(this);

		update_product_amount(dom_obj.parents("tr").attr('id'), 0);
	});
	/* ##################### */

	/* Get user details if logged in */
	/* ##################### */
	function update_swish_manual(value)
	{
		if(value != '')
		{
			dom_obj_widget.find(".swish_manual_message").addClass('hide');
			dom_obj_widget.find(".proceed_to_checkout .contact_phone").text(value);
			dom_obj_widget.find(".swish_manual_form").removeClass('hide');
		}

		else
		{
			dom_obj_widget.find(".swish_manual_form").addClass('hide');
			dom_obj_widget.find(".proceed_to_checkout .contact_phone").text(script_webshop_cart.unknown_label);
			dom_obj_widget.find(".swish_manual_message").removeClass('hide');
		}
	}

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
						$.each(data.response_fields, function(key, arr_value)
						{
							dom_obj_widget.find("#" + arr_value.id).val(arr_value.value);

							if(arr_value.id == 'contact_phone')
							{
								update_swish_manual(arr_value.value);
							}
						});

						display_payment_alternatives_or_not();
					}
				}
			});
		}
	}
	/* ##################### */

	/* Save Order Details */
	/* ##################### */
	dom_obj_widget.on('blur', ".proceed_to_checkout .order_details input", function()
	{
		var form_data = $(this).closest("form").serialize();

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
					display_payment_alternatives_or_not();
				}
			}
		});
	}).on('input', ".proceed_to_checkout .order_details input[name='contact_phone']", function()
	{
		update_swish_manual(this.value);
	});
	/* ##################### */

	/* Invoice */
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

	/* Swish (Manual) */
	/* ##################### */
	$(document).on('change', ".toggle_swish_manual input[type='checkbox']", function()
	{
		var dom_checked = this.checked,
			has_phone_number_val = $(".proceed_to_checkout input[name='contact_phone']").val(),
			is_disabled = (!dom_checked || has_phone_number_val == '');

		$(".toggle_swish_manual button[name='btnWebshopPaySwishManual']").attr('disabled', is_disabled);
	}).trigger('change');
	/* ##################### */

	/* Validate card details & activate buy button */
	/* ##################### */
	/*$(document).on('input', ".toggle_card #payment_card_no", function()
	{
		let value = $(this).val();

		value = value.replace(/\D/g, '');
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
	});*/
	/* ##################### */
});