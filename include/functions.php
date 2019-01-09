<?php

function confirm_payment_webshop($data)
{
	$obj_webshop = new mf_webshop();
	$obj_webshop->confirm_payment($data);
}