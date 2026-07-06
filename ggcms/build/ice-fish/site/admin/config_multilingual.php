<?php

/**
 * Multilingual settings
 * v1.2.21 - added $languages
 */

// add selection of all languages
$languages = mysql_select("SELECT id,name FROM languages ORDER BY `rank` DESC", 'array');

// Extra language fields in forms per module
$config['lang_fields'] = array(
	'pages'=>array(
		array('input td12', 'name', array('name' => a18n('name'))),
		array('tinymce td12', 'text', array('name' => a18n('text'),'attr'=>'style="height:500px"')),
		array('input td4', 'url', array('name' => a18n('url'))),
		array('input td4', 'title', array('name' => a18n('title'))),
		array('input td4', 'description', array('name' => a18n('description')))
	),
	'blog_category'=>array(
		array('input td12', 'name', array('name' => a18n('name'))),
		array('textarea td12','text', array('name' => a18n('text'))),
		array('input td4', 'url', array('name' => a18n('url'))),
		array('input td4', 'title', array('name' => a18n('title'))),
		array('input td4', 'description', array('name' => a18n('description')))
	),
	'blog_tags'=>array(
		array('input td12', 'name', array('name' => a18n('name'))),
		array('textarea td12', 'text', array('name' => a18n('text'))),
		array('input td4', 'url', array('name' => a18n('url'))),
		array('input td4', 'title', array('name' => a18n('title'))),
		array('input td4', 'description', array('name' => a18n('description')))
	),
	'blog'=>array(
		array('input td12', 'name', array('name' => a18n('name'))),
		array('input td12', 'name_2', array('name' => 'name2')),
		array('tinymce td12', 'text', array('name' => a18n('text'),'attr'=>'style="height:500px"')),
		array('input td4', 'url', array('name' => a18n('url'))),
		array('input td4', 'title', array('name' => a18n('title'))),
		array('input td4', 'description', array('name' => a18n('description')))
	),
	'videos'=>array(
		array('input td12', 'name', array('name' => a18n('name'))),
		array('input td12', 'name_2', array('name' => 'name2')),
		array('input td4', 'url', array('name' => a18n('url'))),
		array('input td4', 'title', array('name' => a18n('title'))),
		array('input td4', 'description', array('name' => a18n('description')))
	),
	'casinos_tags'=>array(
		array('input td12', 'name', array('name' => a18n('name'))),
//		array('tinymce td12', 'text', array('name' => a18n('text'),'attr'=>'style="height:500px"')),
		array('input td4', 'url', array('name' => a18n('url'))),
		array('input td4', 'title', array('name' => a18n('title'))),
		array('input td4', 'description', array('name' => a18n('description')))
	),
	'sportsbooks_tags'=>array(
		array('input td12', 'name', array('name' => a18n('name'))),
//		array('tinymce td12', 'text', array('name' => a18n('text'),'attr'=>'style="height:500px"')),
		array('input td4', 'url', array('name' => a18n('url'))),
		array('input td4', 'title', array('name' => a18n('title'))),
		array('input td4', 'description', array('name' => a18n('description')))
	),
	'casinos'=>array(
		array('input td6', 'name', array('name' => a18n('name'))),
		array('input td6', 'name_2', array('name' => 'Invitation bonus')),
		array('tinymce td12', 'text', array('name' => a18n('text'),'attr'=>'style="height:250px"')),
		array('input td6', 'advantages', array('name' => 'advantages')),
		array('input td6', 'disadvantages', array('name' => 'disadvantages')),
		array('input td6', 'bonus_name_1', array('name' => 'bonus_name_1')),
		array('input td6', 'bonus_description_1', array('name' => 'bonus_description_1')),
		array('input td6', 'bonus_name_2', array('name' => 'bonus_name_2')),
		array('input td6', 'bonus_description_2', array('name' => 'bonus_description_2')),
		array('input td6', 'bonus_name_3', array('name' => 'bonus_name_3')),
		array('input td6', 'bonus_description_3', array('name' => 'bonus_description_3')),
		array('input td6', 'bonus_name_4', array('name' => 'bonus_name_4')),
		array('input td6', 'bonus_description_4', array('name' => 'bonus_description_4')),
		array('input td6', 'question_1', array('name' => 'question_1')),
		array('input td6', 'answer_1', array('name' => 'answer_1')),
		array('input td6', 'question_2', array('name' => 'question_2')),
		array('input td6', 'answer_2', array('name' => 'answer_2')),
		array('input td6', 'question_3', array('name' => 'question_3')),
		array('input td6', 'answer_3', array('name' => 'answer_3')),
		array('input td6', 'question_4', array('name' => 'question_4')),
		array('input td6', 'answer_4', array('name' => 'answer_4')),
		array('input td4', 'url', array('name' => a18n('url'))),
		array('input td4', 'title', array('name' => a18n('title'))),
		array('input td4', 'description', array('name' => a18n('description'))),

		array('input td12','banner_url', array('name'=>'banner_url')),
		array('file td6','banner', array('name' =>'banner (1320x320)', 'sizes'=>array(''=>''))),
		array('file td6','banner_m', array('name' =>'mobile banner 480+ (448x538)', 'sizes'=>array(''=>''))),

	),
	'sportsbooks'=>array(
		array('input td6', 'name', array('name' => a18n('name'))),
		array('input td6', 'name_2', array('name' => 'Invitation bonus')),
		array('tinymce td12', 'text', array('name' => a18n('text'),'attr'=>'style="height:250px"')),
		array('input td6', 'advantages', array('name' => 'advantages')),
		array('input td6', 'disadvantages', array('name' => 'disadvantages')),
		array('input td6', 'bonus_name_1', array('name' => 'bonus_name_1')),
		array('input td6', 'bonus_description_1', array('name' => 'bonus_description_1')),
		array('input td6', 'bonus_name_2', array('name' => 'bonus_name_2')),
		array('input td6', 'bonus_description_2', array('name' => 'bonus_description_2')),
		array('input td6', 'bonus_name_3', array('name' => 'bonus_name_3')),
		array('input td6', 'bonus_description_3', array('name' => 'bonus_description_3')),
		array('input td6', 'bonus_name_4', array('name' => 'bonus_name_4')),
		array('input td6', 'bonus_description_4', array('name' => 'bonus_description_4')),
		array('input td6', 'question_1', array('name' => 'question_1')),
		array('input td6', 'answer_1', array('name' => 'answer_1')),
		array('input td6', 'question_2', array('name' => 'question_2')),
		array('input td6', 'answer_2', array('name' => 'answer_2')),
		array('input td6', 'question_3', array('name' => 'question_3')),
		array('input td6', 'answer_3', array('name' => 'answer_3')),
		array('input td6', 'question_4', array('name' => 'question_4')),
		array('input td6', 'answer_4', array('name' => 'answer_4')),
		array('input td4', 'url', array('name' => a18n('url'))),
		array('input td4', 'title', array('name' => a18n('title'))),
		array('input td4', 'description', array('name' => a18n('description'))),

		array('input td12','banner_url', array('name'=>'banner_url')),
		array('file td6','banner', array('name' =>'banner (1320x320)', 'sizes'=>array(''=>''))),
		array('file td6','banner_m', array('name' =>'mobile banner 480+ (448x538)', 'sizes'=>array(''=>''))),

	),
	'ticketoftheday'=>array(
		array('input td12', 'name', array('name' => a18n('name'))),
		array('input td12', 'url', array('name' => a18n('url'))),
		array('tinymce td12', 'text', array('name' => a18n('text'),'attr'=>'style="height:250px"')),
		array('input td12', 'b1_1_analysis', array('name' => a18n('analysis 1'))),
		array('input td12', 'b1_2_analysis', array('name' => a18n('analysis 2'))),
		array('input td12', 'b1_3_analysis', array('name' => a18n('analysis 3'))),
		array('input td12', 'b1_4_analysis', array('name' => a18n('analysis 4'))),
		array('input td12', 'b1_5_analysis', array('name' => a18n('analysis 5'))),
		array('input td12', 'b1_6_analysis', array('name' => a18n('analysis 6'))),
		array('input td12', 'b1_7_analysis', array('name' => a18n('analysis 7'))),
		array('input td12', 'b1_8_analysis', array('name' => a18n('analysis 8'))),
		array('input td12', 'b1_9_analysis', array('name' => a18n('analysis 9'))),
		array('input td12', 'b1_10_analysis', array('name' => a18n('analysis 10'))),
		array('input td12', 'b1_11_analysis', array('name' => a18n('analysis 11'))),
		array('input td12', 'b1_12_analysis', array('name' => a18n('analysis 12'))),
		array('input td12', 'b1_13_analysis', array('name' => a18n('analysis 13'))),
		array('input td12', 'b1_14_analysis', array('name' => a18n('analysis 14'))),
		array('input td12', 'b1_15_analysis', array('name' => a18n('analysis 15'))),
		array('input td12', 'b1_16_analysis', array('name' => a18n('analysis 16'))),
		array('input td12', 'b1_17_analysis', array('name' => a18n('analysis 17'))),
		array('input td12', 'b1_18_analysis', array('name' => a18n('analysis 18'))),
		array('input td12', 'b1_19_analysis', array('name' => a18n('analysis 19'))),
		array('input td12', 'b1_20_analysis', array('name' => a18n('analysis 20'))),
	),
	'betoftheday'=>array(
		array('input td12', 'name', array('name' => a18n('name'))),
		array('input td12', 'url', array('name' => a18n('url'))),
		array('tinymce td12', 'text', array('name' => a18n('text'),'attr'=>'style="height:250px"')),
		array('input td12', 'b1_1_analysis', array('name' => a18n('analysis 1'))),
		array('input td12', 'b1_2_analysis', array('name' => a18n('analysis 2'))),
		array('input td12', 'b1_3_analysis', array('name' => a18n('analysis 3'))),
		array('input td12', 'b1_4_analysis', array('name' => a18n('analysis 4'))),
		array('input td12', 'b1_5_analysis', array('name' => a18n('analysis 5'))),
		array('input td12', 'b1_6_analysis', array('name' => a18n('analysis 6'))),
		array('input td12', 'b1_7_analysis', array('name' => a18n('analysis 7'))),
		array('input td12', 'b1_8_analysis', array('name' => a18n('analysis 8'))),
		array('input td12', 'b1_9_analysis', array('name' => a18n('analysis 9'))),
		array('input td12', 'b1_10_analysis', array('name' => a18n('analysis 10'))),
		array('input td12', 'b1_11_analysis', array('name' => a18n('analysis 11'))),
		array('input td12', 'b1_12_analysis', array('name' => a18n('analysis 12'))),
		array('input td12', 'b1_13_analysis', array('name' => a18n('analysis 13'))),
		array('input td12', 'b1_14_analysis', array('name' => a18n('analysis 14'))),
		array('input td12', 'b1_15_analysis', array('name' => a18n('analysis 15'))),
		array('input td12', 'b1_16_analysis', array('name' => a18n('analysis 16'))),
		array('input td12', 'b1_17_analysis', array('name' => a18n('analysis 17'))),
		array('input td12', 'b1_18_analysis', array('name' => a18n('analysis 18'))),
		array('input td12', 'b1_19_analysis', array('name' => a18n('analysis 19'))),
		array('input td12', 'b1_20_analysis', array('name' => a18n('analysis 20'))),
	),
	'advices'=>array(
		array('input td12', 'name', array('name' => a18n('name'))),
		array('input td12', 'name_2', array('name' => 'name2')),
		array('input td4', 'url', array('name' => a18n('url'))),
		array('input td4', 'title', array('name' => a18n('title'))),
		array('input td4', 'description', array('name' => a18n('description')))
	),
	'popups'=>array(
		array('textarea td12', 'text', array('name' => a18n('text'),'attr'=>'style="height:250px"')),
	),
	'ads'=>array(
		array('textarea td12', 'html', array('name' => 'html (will be used if filled, otherwise images will be used)')),
		array('file td6','img',  array('name' => 'desktop', 'sizes' => array(''=>''))),
		array('file td6','img_2', array('name' => 'mobile',  'sizes' => array(''=>''))),
		array('input td6','url', array('name'=>'url'))
	),
	'gallery'=>array(
		array('input td6','alt', array('name'=>'alt')),
		array('input td6','title', array('name'=>'title'))
	),


/*
	// products
	'shop_products'=>array(
		array('input td12', 'name', true, array('name' => a18n('name'))),
		array('tinymce td12', 'text', true, array('name' => a18n('text'))),
		array('input td12','url',true, array('name' => a18n('url'))),
		array('input td12','title',true, array('name' => a18n('title'))),
		array('input td12','description',true, array('name' => a18n('description')))
	),
	// categories
	'shop_categories'=>array(
		array('input td12', 'name', true, array('name' => a18n('name'))),
		array('tinymce td12', 'text', true, array('name' => a18n('text'))),
		array('input td12','url',true, array('name' => a18n('url'))),
		array('input td12','title',true, array('name' => a18n('title'))),
		array('input td12','description',true, array('name' => a18n('description'))),
	),
	// user fields
	'user_fields'=>array(
		array('input td6', 'name',true, array('name' => a18n('name'))),
		array('input td6', 'hint',true, array('name' => a18n('hint'))),
	),
	// order statuses
	'order_types'=>array(
		array('input td12', 'name',true, array('name' => a18n('name'))),
		array('textarea td12', 'text', true, array('name' => a18n('text')))
	),
	// delivery
	'order_deliveries'=>array(
		array('input td12', 'name', true, array('name' => a18n('name'))),
		array('textarea td12', 'text', true, array('name' => a18n('text'))),
	),
*/
);

// extra language fields in other tables
$config['lang_tables'] = array(
/*
	// products
	'shop_products'=>array(
		'name'=>'VARCHAR( 255 ) NOT NULL',
		'text'=>'TEXT NOT NULL',
		'url'=>'VARCHAR( 255 ) NOT NULL',
		'title'=>'VARCHAR( 255 ) NOT NULL',
		'description'=>'VARCHAR( 255 ) NOT NULL'
	),
	// categories
	'shop_categories'=>array(
		'name'=>'VARCHAR( 255 ) NOT NULL',
		'text'=>'TEXT NOT NULL',
		'url'=>'VARCHAR( 255 ) NOT NULL',
		'title'=>'VARCHAR( 255 ) NOT NULL',
		'description'=>'VARCHAR( 255 ) NOT NULL'
	),
	// user fields
	'user_fields'=>array(
		'name'=>'VARCHAR( 255 ) NOT NULL',
		'hint'=>'VARCHAR( 255 ) NOT NULL'
	),
	// order statuses
	'order_types'=>array(
		'name'=>'VARCHAR( 255 ) NOT NULL',
		'text'=>'TEXT NOT NULL',
	),
	// delivery
	'order_deliveries'=>array(
		'name'=>'VARCHAR( 255 ) NOT NULL',
		'text'=>'TEXT NOT NULL',
	),
*/
);