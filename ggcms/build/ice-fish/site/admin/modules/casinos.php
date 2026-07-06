<?php

$a18n['rate1']='Rating';
$a18n['rate2']='bonuses and offers';
$a18n['rate3']='odds';
$a18n['rate4']='authentication procedure';
$a18n['rate5']='deposits and withdrawals';
$a18n['rate6']='reliability rating';
$a18n['rate7']='transmission';
$a18n['rate8']='customer support';
$a18n['rate9']='easy to use';
$a18n['rate10']='signup bonuses';
$a18n['rate11']='iOS app';
$a18n['rate12']='Android app';
$a18n['website']='partner site';
$a18n['position']='position weight';
$a18n['banner']='banner (1320x320)';
$a18n['banner_m']='mobile banner 480+ (448x538)';
$a18n['banner_display']='show';

$tags    =mysql_select("SELECT id,name from casinos_tags",'rows_id');

//$filter[] = array('search');
$where ='';
//if (isset($get['search'])&&$get['search']!='') $where.= " AND LOWER(name) like '%".strtolower($get['search'])."%'";

$query = "
	SELECT * FROM casinos
	WHERE 1 $where
";

$table = array(
	'id'		=>	'position:desc name top',
	'img'		=>	'img',
	'name'		=>	'',
	'position'	=>	'',
	'top'		=>	'boolean',
	'display'	=>	'boolean'
);

//function after_save() {
//	global $get,$post;
//	if ($get['id'] != 'new') {
//	        if(!preg_match('#-id'.$get['id'].'$#iu',$post['url'])) $post['url'].='-id'.$get['id'];
//		$connect = mysql_connect_db();
//		mysqli_query($connect,'update news set url="'.$post['url'].'" where id='.$get['id']);
//	}
//}


$tabs = array(
	1=>a18n('common'),
        2=>'bonus',
	3=>'rating',
	4=>'questions',
	5=>'tags',
	6=>'banners'
);



//$form[] = array('input td3','date');
$form[1][] = array('input td4','name');
$form[1][] = array('input td4','name_2',array('name'=>'Invitation bonus'));
$form[1][] = array('input td2','position');
$form[1][] = array('checkbox td1','top');
$form[1][] = array('checkbox td1','display');

$form[1][] = array('input td4','website');
$form[1][] = array('input td2','bonus');
$form[1][] = array('input td2','min-deposit');
$form[1][] = array('input td2','min-bet');
$form[1][] = array('input td2','currency');

$form[1][] = array('input td4','app-store-url');
$form[1][] = array('checkbox td2','app-store');
$form[1][] = array('input td4','google-store-url');
$form[1][] = array('checkbox td2','google-store');

//$form[1][] = array('input td2','rank');

$form[1][] = array('input td6','advantages');
$form[1][] = array('input td6','disadvantages');


$form[1][] = array('tinymce td12','text',array('attr'=>'style="height:250px"'));
//$form[1][] = array('input td4','sports');
$form[1][] = array('input td4','limit');
//$form[1][] = array('input td4','platform');
$form[1][] = array('input td4','paymethod');
$form[1][] = array('input td4','language');
$form[1][] = array('input td4','license');
$form[1][] = array('input td4','country');
$form[1][] = array('input td4','products');
$form[1][] = array('input td4','address');
$form[1][] = array('checkbox td2','livechat');
$form[1][] = array('checkbox td2','livebet');


$form[1][] = array('file td6','img',array(
	'sizes'=>array(
		''=>'',
//                '128-'=>'cut 128x128',
//                '80-'=>'cut 80x80',
//		'64-'=>'cut 64x64',
//		'48-'=>'cut 48x48',
	)
));
$form[1][] = array('seo','seo url title description');

$form[2][] = array('input td6','bonus_name_1');
$form[2][] = array('input td6','bonus_description_1');
$form[2][] = array('input td6','bonus_name_2');
$form[2][] = array('input td6','bonus_description_2');
$form[2][] = array('input td6','bonus_name_3');
$form[2][] = array('input td6','bonus_description_3');
$form[2][] = array('input td6','bonus_name_4');
$form[2][] = array('input td6','bonus_description_4');
$form[2][] = array('input td12','terms-conditions-url');

//$form[3][] = array('input td3','rate1');
$form[3][] = '<div class="form-group input col-xl-3"><label><span>Rating</span></label><div class="form-control" style="background:#EEE">'.(isset($post['rank'])?$post['rank']:'').'</div></div>';
$form[3][] = array('input td3','rate2');
$form[3][] = array('input td3','rate3');
$form[3][] = array('input td3','rate4');
$form[3][] = array('input td3','rate5');
$form[3][] = array('input td3','rate6');
$form[3][] = array('input td3','rate7');
$form[3][] = array('input td3','rate8');
$form[3][] = array('input td3','rate9');
$form[3][] = array('input td3','rate10');
$form[3][] = array('input td3','rate11');
$form[3][] = array('input td3','rate12');


$form[4][] = array('input td6','question_1');
$form[4][] = array('input td6','answer_1');
$form[4][] = array('input td6','question_2');
$form[4][] = array('input td6','answer_2');
$form[4][] = array('input td6','question_3');
$form[4][] = array('input td6','answer_3');
$form[4][] = array('input td6','question_4');
$form[4][] = array('input td6','answer_4');

//
foreach($tags as $tagk=>$tagv) {
  $form[5][] = '<div class="form-group input col-xl-3 tags">
                  <div class="form-check">
                    <input type="hidden" name="tags['.$tagk.']" value="'.((isset($post['tag1'])&&in_array($tagk,array($post['tag1'],$post['tag2'],$post['tag3'],$post['tag4'],
$post['tag5'],$post['tag6'],$post['tag7'],$post['tag8'],$post['tag9'],$post['tag10'],$post['tag11'],$post['tag12'],$post['tag13'],$post['tag14'],
$post['tag15'],$post['tag16'],$post['tag17'],$post['tag18'],$post['tag19'],$post['tag20'],$post['tag21'])))?'1':'0').'">
                    <label class="form-check-label">
                      <input class="form-check-input" type="checkbox" name="tags['.$tagk.']"'.((isset($post['tag1'])&&in_array($tagk,array($post['tag1'],$post['tag2'],$post['tag3'],$post['tag4'],
$post['tag5'],$post['tag6'],$post['tag7'],$post['tag8'],$post['tag9'],$post['tag10'],$post['tag11'],$post['tag12'],$post['tag13'],$post['tag14'],
$post['tag15'],$post['tag16'],$post['tag17'],$post['tag18'],$post['tag19'],$post['tag20'],$post['tag21'])))?' checked':'').'>
                      <span>'.$tagv['name'].'</span>
                    </label>
                  </div>
                </div>';
}

$form[6][] = array('input td11','banner_url');
$form[6][] = array('checkbox td1','banner_display');
$form[6][] = array('file td6','banner',array(
	'sizes'=>array(
		''=>'',
	)
));
$form[6][] = array('file td6','banner_m',array(
	'sizes'=>array(
		''=>'',
	)
));

// Exception when editing this module
if ($get['u']=='edit') {

	//rating
	$post['rank']=0;
	for($i=2;$i<=12;$i++) $post['rank']=$post['rank']+$post['rate'.$i];
	$post['rank']=round($post['rank']/11,2);

	$post['tag1']=$post['tag2']=$post['tag3']=$post['tag4']=0;
	$i=1;
	foreach($post['tags'] as $k=>$v) {
		if($v!='0') {
			$post['tag'.$i]=$k;
			$i++;
		}
	}
	unset($post['tags']);

}

$form[6][] = "
<script>

  $('body').on('change','.tags',function(){
    if($('.tags input[type=checkbox]:checked').length>21) {
      $(this).find('input[type=checkbox]').prop('checked',false);
      $(this).find('input[type=hidden]').val('0');
    } else {
      if($(this).find('input[type=checkbox]').is(':checked')) {
        $(this).find('input[type=hidden]').val('1');
      } else {
        $(this).find('input[type=hidden]').val('0');
      }
    }
  });

</script>";
