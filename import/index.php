<?php

$obj_import = new mf_webshop_import();

echo "<div class='wrap'>
	<h2>".__("Import", 'lang_webshop')."</h2>"
	.$obj_import->do_display()
."</div>";