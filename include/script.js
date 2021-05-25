var map_initialized = false,
	has_maps = false,
	has_map_search = false,
	search_map = "webshop_map",
	search_map_obj = "",
	search_input = "webshop_map_input",
	search_input_obj = "",
	search_coords_obj = "",
	map_bounds_obj = "";

function remove_markers()
{
	if(markers)
	{
		for(i in markers)
		{
			markers[i].setMap(null);
		}
	}

	markers = [];
}

function add_map_location(data)
{
	if(!data.icon){	data.icon = "";}

	var coords_temp = data.dom_obj.val();

	if(coords_temp && coords_temp != '')
	{
		var pos = get_position_from_string(coords_temp),
			id = data.dom_obj.attr('data-id'),
			name = data.dom_obj.attr('data-name'),
			url = data.dom_obj.attr('data-url') || '',
			link_text = data.dom_obj.attr('data-link_text') || '';

		add_marker(
		{
			'pos': pos,
			'icon': data.icon,
			'id': id,
			'name': name,
			'text': (url != '' ? "<a href='" + url + "'>" + link_text + "</a>" : "")
		});
	}
}

function fitIcons()
{
	if(map_bounds_obj.length > 0 && map_bounds_obj.val() != '')
	{
		var coords_temp = map_bounds_obj.val().split("), (");

		if(coords_temp[0] && coords_temp[1])
		{
			var coords_temp_1 = coords_temp[0].replace("(", ""),
				coords_temp_2 = coords_temp[1].replace("(", "");

			if(coords_temp_1 != '' && coords_temp_2 != '')
			{
				var latlng_1 = get_position_from_string(coords_temp_1),
					latlng_2 = get_position_from_string(coords_temp_2),
					bounds = new google.maps.LatLngBounds(latlng_1, latlng_2);

				map_object.fitBounds(bounds);

				hide_products();
			}
		}
	}

	else
	{
		if(markers.length > 0)
		{
			var bound = new google.maps.LatLngBounds();

			for(var i in markers)
			{
				bound.extend(markers[i].getPosition());
			}

			if(markers.length >= 2)
			{
				map_object.fitBounds(bound);
			}

			else
			{
				map_object.panTo(bound.getCenter());
			}
		}
	}
}

function do_dragend()
{
	map_bounds_obj.val(map_object.getBounds());
	search_coords_obj.val('');
}

function hide_products()
{
	for(i in markers)
	{
		var dom_id = markers[i].id,
			dom_obj = jQuery("#products_" + dom_id),
			dom_parent = dom_obj.parents("li");

		if(map_object.getBounds().contains(markers[i].getPosition()))
		{
			if(dom_parent.hasClass('hide'))
			{
				/*dom_obj.prop("checked", true);*/
				dom_parent.removeClass('hide');
			}
		}

		else
		{
			if(!dom_parent.hasClass('hide'))
			{
				dom_obj.prop("checked", false);
				dom_parent.addClass('hide');
			}
		}
	}

	if(myWebshopView)
	{
		myWebshopView.show_quote_request_button();
	}
}

function init_maps()
{
	if(search_map_obj.length > 0 && search_map_obj.is(":visible"))
	{
		has_maps = true;
	}

	if(search_input_obj.length > 0 && search_input_obj.is(":visible"))
	{
		has_map_search = true;
	}

	if(has_maps == true)
	{
		init_map_object(document.getElementById(search_map));
	}

	if(has_map_search == true)
	{
		var input = document.getElementById(search_input);

		if(has_maps == true)
		{
			map_object.controls[google.maps.ControlPosition.TOP_LEFT].push(input);
		}

		var searchBox = new google.maps.places.SearchBox(input);

		google.maps.event.addListener(searchBox, 'places_changed', function()
		{
			var places = searchBox.getPlaces();

			if(places.length == 0)
			{
				return;
			}

			for(var i = 0, marker; marker = markers[i]; i++)
			{
				marker.setMap(null);
			}

			var bounds = new google.maps.LatLngBounds();

			for(var i = 0, place; place = places[i]; i++)
			{
				add_marker({'pos': place.geometry.location, 'name': place.name, 'letter': 'S'});

				bounds.extend(place.geometry.location);

				search_coords_obj.val(place.geometry.location);
				map_bounds_obj.val('');
			}

			if(has_maps == true)
			{
				fitIcons();
			}
		});
	}

	if(has_maps == true)
	{
		if(has_map_search == true)
		{
			google.maps.event.addListener(map_object, 'bounds_changed', function()
			{
				searchBox.setBounds(map_object.getBounds());
			});
		}

		else
		{
			/*google.maps.event.addListener(map_object, 'zoom_changed', hide_products);*/
			google.maps.event.addListener(map_object, 'dragend', do_dragend);
			google.maps.event.addListener(map_object, 'bounds_changed', function()
			{
				hide_products();
			});
		}

		var symbol_active_image = (script_webshop.symbol_active_image != '' ? script_webshop.symbol_active_image : "http://googlemapsmarkers.com/v1/" + script_webshop.symbol_active + "/");

		add_map_location({'dom_obj': search_coords_obj, 'icon': symbol_active_image});

		fitIcons();

		map_initialized = true;
	}
}

function show_list_active_or_not(self)
{
	var dom_product = self.parents("li");

	if(self.is(":checked"))
	{
		jQuery("#product_result_form").siblings(".form_button_container").find(".form_button .show_if_none_checked").remove();

		dom_product.addClass('active');
	}

	else
	{
		dom_product.removeClass('active');
	}
}

jQuery(function($)
{
	map_initialized = false;
	has_maps = false;
	has_map_search = false;

	search_map_obj = $("#" + search_map);
	search_input_obj = $("#" + search_input);
	search_coords_obj = $("#webshop_map_coords");
	map_bounds_obj = $("#webshop_map_bounds");

	init_maps();

	$(".mf_form > #product_result_form .form_switch input").each(function()
	{
		show_list_active_or_not($(this));
	});

	$(".mf_form > #product_result_form").parents(".mf_form").on('submit', function()
	{
		var dom_result = $(this).find("#product_result_form"),
			dom_buttons = $(this).find(".form_button");

		if(dom_result.find(".products:checked").length == 0)
		{
			if(dom_buttons.children(".show_if_none_checked").length == 0)
			{
				dom_buttons.append("<p class='show_if_none_checked info_text'>" + script_webshop.product_missing + "</p>");
			}

			return false;
		}
	});

	$(document).on('click', "#product_delete", function()
	{
		$("#product_amount").val('0');
	});

	$(document).on('click', "#order_proceed button", function()
	{
		$("#order_confirm").show();
		$("#order_proceed").hide();
	});

	$(document).on('change', ".mf_form > #product_result_form .form_switch input", function()
	{
		show_list_active_or_not($(this));
	});

	/* Inactive enter/submit when webshop_map_input is in focus */
	search_input_obj.on('keydown', function(event)
	{
		if(event.key == 'Enter')
		{
			return false;
		}
	});
});