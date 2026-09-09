<?php

$obj_webshop = new mf_webshop();
$obj_export = new mf_webshop_export();

echo "<div class='wrap'>
	<h2>".__("Export", 'lang_webshop')."</h2>"
	.get_notification()
	.$obj_export->get_form()
."</div>";