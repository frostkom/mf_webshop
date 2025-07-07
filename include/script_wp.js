jQuery(function($)
{
	var form_serialized = $.Storage.get('form_serialized'),
		form_products = $.Storage.get('form_products');

	if(typeof form_serialized == 'undefined' && typeof form_products == 'undefined')
	{
		$("button[name=btnLocalStorageClear]").addClass('is_disabled');
	}

	$(document).on('click', "button[name=btnLocalStorageClear]", function()
	{
		$("#storage_response").html(script_webshop_wp.loading_animation);

		$.Storage.remove('form_products');
		$.Storage.remove('form_serialized');
		$.Storage.remove('last_product');

		$("button[name=btnLocalStorageClear]").addClass('is_disabled');
		$("#storage_response").html(script_webshop_wp.cleared_message);

		return false;
	});
});