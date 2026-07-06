<?php
/**
 * Render author block for E-E-A-T (Expertise, Experience, Authoritativeness, Trustworthiness).
 */

require_once ROOT_DIR . 'functions/author_profiles.php';

/**
 * Footer/legal pages (module pages): no author block — informational, not editorial content.
 *
 * @return string[]
 */
function aviator_legal_page_slugs() {
	return array(
		'about-us',
		'terms-and-conditions',
		'privacy-policy',
		'responsible-gambling',
	);
}

function aviator_is_legal_page_slug($slug) {
	$slug = trim((string) $slug, "/ \t\n\r\0\x0B");
	return $slug !== '' && in_array($slug, aviator_legal_page_slugs(), true);
}

/**
 * Overlay name / job_title / bio from content_i18n for the current front language.
 *
 * @param array<string,mixed> $author site_authors row
 * @return array<string,mixed>
 */
function aviator_author_apply_locale(array $author) {
	return author_apply_locale($author);
}

function aviator_should_show_author_block($abc) {
	if (!is_array($abc)) {
		return true;
	}
	if (isset($abc['page']['url']) && aviator_is_legal_page_slug($abc['page']['url'])) {
		return false;
	}
	return true;
}

/**
 * Compact byline for top of editorial content: avatar + "By Name" (linked).
 *
 * @param array<string,mixed> $abc
 * @param array<string,mixed> $options optional: date (string)
 */
function aviator_render_author_byline($abc, $options = array()) {
	if (!aviator_should_show_author_block($abc)) {
		return '';
	}

	$author = author_for_abc($abc);
	$photo_url = author_photo_url($author);
	$profile_url = author_profile_url($author, $abc);
	$prefix = author_byline_prefix_label();
	$photo_alt = trim((string)($author['photo_alt'] ?? ''));
	if ($photo_alt === '') {
		$photo_alt = (string)($author['name'] ?? '');
	}
	$role = trim((string)($author['job_title'] ?? ''));
	$date_line = '';
	if (!empty($options['date'])) {
		$date_line = author_format_date_line($options['date']);
	}

	ob_start();
	?>
	<p class="author-byline" rel="author">
		<?php if ($photo_url): ?>
			<img class="author-byline-avatar" src="<?= htmlspecialchars($photo_url, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($photo_alt, ENT_QUOTES, 'UTF-8') ?>" width="36" height="36" loading="eager" decoding="async">
		<?php else: ?>
			<span class="author-byline-avatar author-byline-avatar--placeholder" aria-hidden="true"><i class="fa-solid fa-user-tie"></i></span>
		<?php endif; ?>
		<span class="author-byline-meta">
			<span class="author-byline-line">
				<span class="author-byline-prefix"><?= htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8') ?></span>
				<a href="<?= htmlspecialchars($profile_url, ENT_QUOTES, 'UTF-8') ?>" class="author-byline-name"><?= htmlspecialchars((string)$author['name'], ENT_QUOTES, 'UTF-8') ?></a>
			</span>
			<?php if ($role !== '' || $date_line !== ''): ?>
				<span class="author-byline-sub">
					<?php if ($role !== ''): ?><span class="author-byline-role"><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
					<?php if ($role !== '' && $date_line !== ''): ?><span class="author-byline-sep" aria-hidden="true"> · </span><?php endif; ?>
					<?php if ($date_line !== ''): ?><time class="author-byline-date" datetime="<?= htmlspecialchars(date('c', strtotime((string)$options['date'])), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($date_line, ENT_QUOTES, 'UTF-8') ?></time><?php endif; ?>
				</span>
			<?php endif; ?>
		</span>
	</p>
	<?php
	return ob_get_clean();
}

/** @deprecated Use aviator_render_author_byline() at the top of content. */
function aviator_render_author_block($abc) {
	return '';
}
