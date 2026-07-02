<?php

//todo удалить модуль

$a18n['limit']		= 'лимит';
$a18n['img']		= 'картинка';
$a18n['keyword']	= 'ключевой запрос';

$table = array(
	'A'=>'name',
	'B'=>'keyword',
	'C'=>'url',
	'D'=>'img',
	'E'=>'limit',
	'F'=>'count',
);

if (isset($_POST['export'])) {
	if (is_dir(ROOT_DIR.'files/temp') || mkdir(ROOT_DIR.'files/temp',0755,true)) {
		$file = 'files/temp/links_export_'.date('Y-m-d_H_i').'.'.$_POST['type'];
		$str = '';
		$content = '<h2>Содержание файла</h2>';
		$content.= '<a href="/'.$file.'">Скачать файл</a> &nbsp; <a href="">вернуться</a><br />';
		$content.= '<br /><table class="table"><tr>';
		$data = array();
		foreach ($table as $k=>$v) {
			$data[0][] = $a18n[$v];
			$content.= '<th>'.$a18n[$v].'</th>';
		}
		$content.= '</tr>';
		if ($links = mysql_select("
			SELECT sl.*,COUNT(d.id) count
			FROM seo_links sl
			LEFT JOIN `seo_links-pages` d ON d.parent=sl.id
			GROUP BY sl.id
		",'rows')) {
			$i = 0;
			foreach ($links as $q) {
				$i++;
				$content .= '<tr valign="top">';
				foreach ($table as $k => $v) {
					$data[$i][] = $q[$v];
					$content .= '<td>' . $q[$v] . '</td>';
				}
				$content .= '</tr>';
			}
		}
		$content.= '</table>';
		if ($_POST['type']=='csv') {
			foreach ($data as $key=>$val) {
				foreach ($val as $k=>$v)
					$str.= '"'.str_replace('"',"&quot;",$v).'";';
				$str.= "\n";
			}
			$str = iconv("UTF-8", "cp1251//TRANSLIT", $str);
			$fp = fopen(ROOT_DIR.$file, 'w');
			fwrite($fp,$str);
			fclose($fp);
			unset($table);
		}
		else {
			include (ROOT_DIR.'plugins/phpexcel/PHPExcel.php');
			include (ROOT_DIR.'plugins/phpexcel/PHPExcel/Writer/Excel2007.php');
			$objPHPExcel = new PHPExcel();
			$objPHPExcel->getProperties()->setCreator("Maarten Balliauw");
			$objPHPExcel->getProperties()->setLastModifiedBy("Maarten Balliauw");
			$objPHPExcel->getProperties()->setTitle("Office 2007 XLSX Test Document");
			$objPHPExcel->getProperties()->setSubject("Office 2007 XLSX Test Document");
			$objPHPExcel->getProperties()->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.");
			$objPHPExcel->getActiveSheet()
			    ->fromArray(
			        $data,  // The data to set
			        NULL,        // Array values with this value will not be set
			        'A1'         // Top left coordinate of the worksheet range where
			                     //    we want to set these values (default is A1)
			    );
			$objPHPExcel->getActiveSheet()->setTitle('Simple');
			$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
			$objWriter->save(ROOT_DIR.$file);

		}
	}
	else $content = 'не удалось создать каталог';
}
else {
	$content = '<br /><h2>Подтверждение создания файла</h2>';
	$content.= 'Будет сгенерирован файл excel c такой структурой:<br />';
	$content.= '<br /><table class="table"><tr>';
	foreach ($table as $k=>$v) {
		//$str.= '"'.str_replace('"',"&quot;",$fieldset[$v]).'";';
		$content.= '<th>'.$a18n[$v].'</th>';
	}
	$content.= '</tr></table>';
	$content.= '<form method="post" action="">';
	$content.= '<br /><select name="type"><option value="csv">csv</option><option value="xlsx">xlsx</option></select> &nbsp; ';
	$content.= '<input type="submit" name="export" value=" Сгенерировать файл ">';
	$content.= '</form>';
}
unset($table);