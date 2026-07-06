<?php
/**
 * Admin header badge when search indexing is restricted on the public site.
 */
if (!function_exists('site_seo_admin_indexing_restrictions_active') || !site_seo_admin_indexing_restrictions_active()) {
	return;
}
?>
<li class="nav-item d-flex align-items-center mr-2" id="ifish-admin-index-guard-badge-wrap">
	<span class="badge badge-warning text-dark px-2 py-1" id="ifish-admin-index-guard-badge"
		title="Indexing restricted"
		data-toggle="tooltip" data-placement="bottom">
		<i data-feather="shield-off" class="align-middle" style="width:14px;height:14px;"></i>
		<span class="align-middle ml-1 d-none d-md-inline">Indexing limited</span>
	</span>
</li>
