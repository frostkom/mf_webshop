jQuery(function($)
{
	$('.rwmb-field input[connect_to]').each(function()
	{
		var dom_obj = $(this),
			connect_obj = $('#' + dom_obj.attr('connect_to'));

		connect_obj.attr('connect_from', dom_obj.attr('id'));
	});

	$('.rwmb-field input[connect_from]').on('blur change', function()
	{
		var dom_obj = $(this),
			connect_obj = $('#' + dom_obj.attr('connect_from')),
			value_max = parseInt(dom_obj.val());

		if((dom_obj.hasClass('rwmb-number') || dom_obj.hasClass('rwmb-size')) && connect_obj.hasClass('rwmb-interval'))
		{
			var value_min = Math.round(value_max * (script_webshop_meta.range_min_default / 100)),
				out = value_min + '-' + value_max;
		}

		if(typeof out !== 'undefined')
		{
			connect_obj.val(out);
		}

		/*else
		{
			console.log('Do something else (' + dom_obj.attr('id') + ' connected from ' + connect_obj.attr('id') + ')');
		}*/
	});
});