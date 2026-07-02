<?php
/**
 * Controls automatic random promo image insertion in blog articles.
 */

if (!function_exists('blog_promo_should_autoinsert')) {
	/**
	 * @param array<string,mixed> $post Blog row / page data (must include skip_random_images when set).
	 */
	function blog_promo_should_autoinsert(array $post): bool {
		if (!empty($post['skip_random_images'])) {
			return false;
		}
		return true;
	}
}
