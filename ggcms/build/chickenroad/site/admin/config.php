<?php

$config['admin_lang'] = 'en'; // admin panel language

$config['style'] = 'admin/templates';
$config['style'] = 'admin/templates2';

// many-to-many relations
$config['depend'] = array(
	//'shop_products'=>array('categories'=>'shop_products-categories'),
);

// mirror modules
$config['mirrors'] = array(
	//'articles'=>'news',
	'shop_products_special'=>'shop_products',
	'landing_items1'=>'landing_items',
	'landing_items2'=>'landing_items',
	'landing_items3'=>'landing_items',
	'authors' => 'site_authors',
);

// boolean $table keys that get icon classes
$config['boolean'] = array(
	'boolean','display','market','yandex_index','noindex'
);

//icons https://feathericons.com/
/*
 * layers list package settings shopping-cart twitch users map map-in globe
 */
$modules_admin = array(
	array(
		'module'=>'index',
		'icon'=>'home',
	),
	array(
		'module'=>'pages',
		'image'=>'sitemap',
		'icon'=>'git-branch', //layers list package
	),
	array(
		'module'=>'jobs',
		'name'=>'Jobs',
		'icon'=>'layers',
	),
	array(
		'name'=>'Content',
		'module'=>'content',
		'icon'=>'file-text',
	),
	array(
		'module'=>'media',
		'name'=>'Media',
		'icon'=>'image',
	),
	array(
		'name'=>'Authors',
		'module'=>'authors',
		'icon'=>'users',
	),
	array(
		'name'=>'Advertising',
		'module'=>'advertising',
		'icon'=>'dollar-sign',
	),
/*
	array(
		'module'=>'gallery',
		'image'=>'image',
		'icon'=>'image',
	),
	array(
		'name'=>'blog',
		'module'=>array(
			array('module'=>'blog','name'=>'blog'),
			array('module'=>'blog_category','name'=>'categories'),
			array('module'=>'blog_tags','name'=>'tags'),
		)
	),
	array(
		'name'=>'teams',
		'image'=>'circle',
		'icon'=>'circle',
		'module'=>array(
			array('module'=>'teams'),
			array('module'=>'countries'),
			array('module'=>'sports'),
		)
	),
	array(
		'name'=>'reviews',
		'image'=>'tablet',
		'icon'=>'tablet', //file
		'module'=>array(
			array('module'=>'recenzii','name'=>'articles'),
			array('module'=>'recenzii_category','name'=>'categories'),
			array('module'=>'recenzii_tags','name'=>'tags'),
		)
	),
	array(
		'module'=>'videos',
		'image'=>'youtube',
		'icon'=>'youtube',
	),
*/
/*
	array(
		'name'=>'casinos',
		'module'=>array(
			array('module'=>'casinos'),
			array('module'=>'casinos_tags','name'=>'tags'),
		)
	),
	array(
		'name'=>'betting info',
		'image'=>'info',
		'icon'=>'info',
		'module'=>array(
			array('module'=>'ticketoftheday','name'=>'ticket of the day'),
			array('module'=>'betoftheday','name'=>'bet of the day'),
			array('module'=>'advices'),
		)
	),
	array(
		'name'=>'advertise',
		'image'=>'credit-card',
		'icon'=>'credit-card',
		'module'=>array(
			array('module'=>'ads','name'=>'banners'),
			array('module'=>'partners',),
			array('module'=>'popups',),
			array('module'=>'popup_places',),
		),
	),
*/
/*
	array(
		'name'=>'users',
		'module'=>array(
			array('module'=>'users','name'=>'system users'),
			array('module'=>'user_types','name'=>'system roles'),
		),
	),
*/
	array(
		'name'=>'SEO',
		'image'=>'seo',
		'icon'=>'search',
		'module'=>array(
			array(
				'module'=>'seo_structured',
				'name'=>'Structured data'
			),
			array(
				'module'=>'seo_monitor',
				'name'=>'SEO Monitor'
			),
			array(
				'module'=>'seo_index_rules',
				'name'=>'Index rules'
			),
			array(
				'module'=>'seo_sitemap',
				'name'=>'Sitemap.xml'
			),
			array(
				'module'=>'seo_robots',
				'name'=>'robots.txt'
			),
			array(
				'module'=>'seo_custom_css',
				'name'=>'Site CSS'
			),
			array(
				'module'=>'seo_htaccess',
				'name'=>'.htaccess'
			),
		),
	),
	array(
		'name'=>'AI',
		'module'=>array(
			array('module'=>'ai_api','name'=>'API keys'),
		),
		'icon'=>'cpu',
	),
	array(
		'name'=>'Translations',
		'module'=>array(
			array('module'=>'translations','name'=>'Translations hub'),
			array('module'=>'translations_settings','name'=>'Settings & autopilot'),
		),
		'icon'=>'globe',
	),
	array(
		'name'=>'Logs',
		'module'=>array(
			array('module'=>'logs','name'=>'Viewer'),
		),
		'icon'=>'file-text',
	),
	array(
		'module'=>'telemetry',
		'name'=>'Telemetry',
		'icon'=>'radio',
	),
	array(
		'module'=>'languages',
		'name'=>'dictionary',
		'image'=>'dictionary',
		'icon'=>'book-open',
	),
	array(
		'module'=>'languages_json',
		'name'=>'Languages JSON',
		'icon'=>'download-cloud',
	),
	array(
		'name'=>'Settings',
		'module'=>'settings',
		'icon'=>'settings',
	),

);

//v1.3.1
$config['sources']['admin']=array(
//	'/plugins/jquery/jquery-1.11.3.min.js',
	'/plugins/jquery/jquery.form.min.js',
	'/plugins/jquery/jquery.uploader.js',
	'/plugins/jquery/jquery-ui-1.11.4.custom/jquery-ui.min.js',
	'/plugins/jquery/jquery-ui-1.11.4.custom/jquery-ui.min.css',
	'/plugins/jquery/i18n/jquery.ui.datepicker-{localization}.js',
	'/plugins/tinymce_4.3.11/tinymce.min.js',
	//'/plugins/tinymce_5.0.4/tinymce.min.js',
	//'/plugins/tinymce_5.0.4/jquery.tinymce.min.js',
	'/plugins/highslide/highslide-with-gallery.js',
	'/templates/scripts/highslide.js',
	'/plugins/highslide/highslide.css',
	'/admin/templates/css/reset.css',
	'/admin/templates/css/style.css?',
	'/admin/templates/js/dnd.js',
	'/admin/templates/js/script.js?'
);

//v1.4.7
$config['sources']['admin_top' ] = array(
	// jQuery is provided by admin_bottom bundle.js (v3.4) — do not load 1.11 here (breaks Summernote)
	//Plugin styles
	'/'.$config['style'].'/vendors/bundle.css',
	//Slick
	'/'.$config['style'].'/vendors/slick/slick.css',
	'/'.$config['style'].'/vendors/slick/slick-theme.css',
	//vendors/vmap/jqvmap.min.css
	//App styles
	'/'.$config['style'].'/assets/css/app.min.css',
	'/'.$config['style'].'/assets/css/modify.css?',
	'/'.$config['style'].'/assets/css/custom.css?v=20260531_11',
	'/'.$config['style'].'/assets/css/admin-mobile.css?v=20260531_12'
);
$config['sources']['admin_bottom'] = array(
	//App scripts -->
	'/'.$config['style'].'/assets/js/app.min.js',

	'/'.$config['style'].'/vendors/lightbox/magnific-popup.css',
	'/'.$config['style'].'/vendors/lightbox/jquery.magnific-popup.min.js',

	'/'.$config['style'].'/vendors/select2/css/select2.min.css',
	'/'.$config['style'].'/vendors/select2/js/select2.min.js',

	'/'.$config['style'].'/vendors/clockpicker/bootstrap-clockpicker.min.css',
	'/'.$config['style'].'/vendors/clockpicker/bootstrap-clockpicker.min.js',

	'/'.$config['style'].'/vendors/datepicker/daterangepicker.css',
	'/'.$config['style'].'/vendors/datepicker/daterangepicker.js',

	'/'.$config['style'].'/vendors/select2/js/select2.min.js',
	'/'.$config['style'].'/vendors/select2/css/select2.min.css',

	'/plugins/jquery/jquery.form.min.js',
	'/plugins/jquery/jquery.uploader.js',
	'/plugins/tinymce_4.3.11/tinymce.min.js',
	'/'.$config['style'].'/js/media.js?v=20260527_17',
	'/'.$config['style'].'/js/script.js?v=20260702_15',
	'/'.$config['style'].'/js/file.js?v=20260527_15'
);