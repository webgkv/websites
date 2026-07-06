<?php
/**
 * Controls automatic random promo image insertion in blog articles.
 * CTA buttons are handled separately (see cta_inject.php + blog/text.php); only images respect skip_random_images.
 */

if (!function_exists('blog_promo_should_autoinsert_images')) {
	/**
	 * @param array<string,mixed> $post Blog row / page data (must include skip_random_images when set).
	 */
	function blog_promo_should_autoinsert_images(array $post): bool {
		return empty($post['skip_random_images']);
	}
}

if (!function_exists('blog_promo_should_autoinsert')) {
	/**
	 * @deprecated Use blog_promo_should_autoinsert_images() — name kept for callers that predate CTA split.
	 */
	function blog_promo_should_autoinsert(array $post): bool {
		return blog_promo_should_autoinsert_images($post);
	}
}
