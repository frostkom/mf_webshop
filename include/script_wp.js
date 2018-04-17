jQuery(function($)
{
	var form_serialized = $.Storage.get('form_serialized'),
		form_products = $.Storage.get('form_products');

	if(typeof form_serialized == 'undefined' && typeof form_products == 'undefined')
	{
		$('button[name=btnLocalStorageClear]').attr('disabled', true);
	}

	$(document).on('click', "button[name=btnLocalStorageClear]", function()
	{
		$('#storage_response').html("<i class='fa fa-spinner fa-spin fa-2x'></i>");

		$.Storage.remove('form_products');
		$.Storage.remove('form_serialized');
		$.Storage.remove('last_product');

		$('button[name=btnLocalStorageClear]').attr('disabled', true);
		$('#storage_response').html(script_webshop_wp.cleared_message);

		return false;
	});
});