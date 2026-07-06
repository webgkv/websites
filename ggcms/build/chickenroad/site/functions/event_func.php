<?php

/*
 * v1.4.14 - event_func добавлено
 */

/* SHOP_REVIEWS */
//изменение отзыва
function event_change_shop_reviews ($q) {
	product_reviews_calculate ($q);
}
//удаление отзыва
function event_delete_shop_reviews ($q) {
	product_reviews_calculate ($q);
}
//функция пересчета рейтинга товара
function product_reviews_calculate ($q) {
	if ($q) {
		if ($q['product'] > 0) {
			$q['product'] = intval($q['product']);
			$data = array(
				'id' => $q['product'],
				'rating' => mysql_select("SELECT SUM(rating)/COUNT(id) FROM shop_reviews WHERE display=1 AND product=" . $q['product'], 'string'),
			);
			//print_r($data);
			mysql_fn('update', 'shop_products', $data);
		}
	}
}
