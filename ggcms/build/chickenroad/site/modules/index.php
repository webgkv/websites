<?php

// Homepage (layout index): hero + page content from DB only.
// Legacy core/modules/index.php loaded blog/casinos/sportsbooks blocks that this layout no longer renders.

$abc['breadcrumb'] = array();
if (isset($showpariuri)) {
	$abc['showpariuri'] = $showpariuri;
}
