<?php

$q = strtolower($_GET["q"]);
if (!$q) return;
$items = array("php", "python", "coldfusion", "corn", "test");

foreach ($items as $value) {
	if (strpos(strtolower($value), $q) === 0) {
		echo "$value\n";
	}
}

?>