<?php

$sportbooks=mysql_select('select id,name from `sportsbooks`','array');
$countries =mysql_select('select id,name from `countries` order by name asc','array');
$sports    =mysql_select('select id,name from `sports` order by name asc','array');
$teams     =mysql_select('select id,name from `teams` order by name asc','array');

if ($get['u']=='get_teams') {

	$where=array('1=1');
        if(@$_GET['sport'])   $where[]='sport="'.(stripslashes_smart(@$_GET['sport'])+0).'"';
        if(@$_GET['country']) $where[]='country="'.(stripslashes_smart(@$_GET['country'])+0).'"';

	$query = "SELECT id,name FROM teams WHERE ".implode(' AND ',$where)." order by name asc";
        echo '<option value="">-team-</option>';
	if ($t = mysql_select($query,'array')) {
		foreach ($t as $k=>$v) {
			echo "<option value='".$k."'>$v</option>";
		}
	}
	die();
}

$a18n['b1_pariuri_adv']='adv partner';

for($i=1;$i<=20;$i++) {
  $a18n['b1_'.$i.'_pariuri']='partner';
  $a18n['b1_'.$i.'_kef']='rate';
  $a18n['b1_'.$i.'_time']='time';
  $a18n['b1_'.$i.'_analysis']='analysis';
  $a18n['b1_'.$i.'_abbr']='abbr';
  $a18n['b1_'.$i.'_s']='success';
  $a18n['b1_'.$i.'_u']='unsucc';

  $a18n['b1_'.$i.'_sport1']='team1';
  $a18n['b1_'.$i.'_country1']='';
  $a18n['b1_'.$i.'_team1']='';

  $a18n['b1_'.$i.'_sport2']='team2';
  $a18n['b1_'.$i.'_country2']='';
  $a18n['b1_'.$i.'_team2']='';
}

$query = "
	SELECT * FROM `betoftheday`
";

$table = array(
	'id'		=>	'date:desc',
	'img'		=>	'img',
	'date'		=>	'date',
	'display'	=>	'display'
);

$tabs = array(
	1=>a18n('common'),
        2=>'tab1',
);

if(!isset($post['date'])) $post['date']='';
                     else $post['date']=substr($post['date'],0,10).' 00:00:00';

$form[1][] = array('input td5','name');
$form[1][] = '<div class="form-group input col-xl-2"><label><span>date</span></label><input class="form-control" name="date" value="'.substr($post['date'],0,10).'"></div>';
$form[1][] = array('select td4','b1_pariuri_adv',array('value'=>array(true,$sportbooks,'')));
$form[1][] = array('checkbox','display');
$form[1][] = array('tinymce td12','text', array('attr'=>'style="height:250px"'));
$form[1][] = array('file td6','img',array(
	'sizes'=>array(
		''=>'',
//		'310-' =>'cut 310x252',
//		'416-' =>'cut 416x300',
//		'688-' =>'cut 688x435',
//		'1312-'=>'cut 1312x316',
	)
));
$form[1][] = array('seo','seo url');


for($i=1;$i<=20;$i++) {
//  $form[2][] = '</div><div class="form-row" style="background:#'.($i%2?'EEE':'FFF').';padding:10px 0">';
  $form[2][] = '</div><div class="form-row" style="background:#EEE;padding:10px 0;margin:10px 0">';
  $form[2][] = array('select td4','b1_'.$i.'_pariuri',array('value'=>array(true,$sportbooks,'')));
  $form[2][] = array('input td2','b1_'.$i.'_time');
  $form[2][] = array('input td2','b1_'.$i.'_kef');
  $form[2][] = array('checkbox td2','b1_'.$i.'_s');
  $form[2][] = array('checkbox td2','b1_'.$i.'_u');

//

  $form[2][] = '<div class="form-group select col-xl-2"><label><span>team1</span></label><select class="sport form-control" data-team="1" data-id="'.$i.'" id="b1_'.$i.'_sport1" name="b1_'.$i.'_sport1"><option value="">-sport-</option>';
  foreach($sports as $k=>$v) {
    $form[2][] = "<option value='$k'";
    if(isset($post['b1_'.$i.'_sport1'])&&$post['b1_'.$i.'_sport1']==$k) $form[2][] = " selected";
    $form[2][] = ">$v</option>";
  }
  $form[2][] = '</select></div>';

  $form[2][] = '<div class="form-group select col-xl-2"><label><span></span></label><select class="country form-control" data-team="1" data-id="'.$i.'" id="b1_'.$i.'_country1" name="b1_'.$i.'_country1"><option value="">-country-</option>';
  foreach($countries as $k=>$v) {
    $form[2][] = "<option value='$k'";
    if(isset($post['b1_'.$i.'_country1'])&&$post['b1_'.$i.'_country1']==$k) $form[2][] = " selected";
    $form[2][] = ">$v</option>";
  }
  $form[2][] = '</select></div>';

  $form[2][] = '<div class="form-group select col-xl-2"><label><span></span></label><select class="form-control" id="b1_'.$i.'_team1" name="b1_'.$i.'_team1"><option value="">-team-</option>';
  $where=array('1=1');
  if(isset($post['b1_'.$i.'_sport1'])&&$post['b1_'.$i.'_sport1'])   $where[]='sport="'.($post['b1_'.$i.'_sport1']).'"';
  if(isset($post['b1_'.$i.'_country1'])&&$post['b1_'.$i.'_country1']) $where[]='country="'.($post['b1_'.$i.'_country1']).'"';
  $teams1=mysql_select("SELECT id,name FROM teams WHERE ".implode(' AND ',$where)." order by name asc",'array');
  foreach($teams1 as $k=>$v) {
    $form[2][] = "<option value='$k'";
    if(isset($post['b1_'.$i.'_team1'])&&$post['b1_'.$i.'_team1']==$k) $form[2][] = " selected";
    $form[2][] = ">$v</option>";
  }
  $form[2][] = '</select></div>';

//

  $form[2][] = '<div class="form-group select col-xl-2"><label><span>team2</span></label><select class="sport form-control" data-team="2" data-id="'.$i.'" id="b1_'.$i.'_sport2" name="b1_'.$i.'_sport2"><option value="">-sport-</option>';
  foreach($sports as $k=>$v) {
    $form[2][] = "<option value='$k'";
    if(isset($post['b1_'.$i.'_sport2'])&&$post['b1_'.$i.'_sport2']==$k) $form[2][] = " selected";
    $form[2][] = ">$v</option>";
  }
  $form[2][] = '</select></div>';

  $form[2][] = '<div class="form-group select col-xl-2"><label><span></span></label><select class="country form-control" data-team="2" data-id="'.$i.'" id="b1_'.$i.'_country2" name="b1_'.$i.'_country2"><option value="">-country-</option>';
  foreach($countries as $k=>$v) {
    $form[2][] = "<option value='$k'";
    if(isset($post['b1_'.$i.'_country2'])&&$post['b1_'.$i.'_country2']==$k) $form[2][] = " selected";
    $form[2][] = ">$v</option>";
  }
  $form[2][] = '</select></div>';

  $form[2][] = '<div class="form-group select col-xl-2"><label><span></span></label><select class="form-control" id="b1_'.$i.'_team2" name="b1_'.$i.'_team2"><option value="">-team-</option>';
  $where=array('1=1');
  if(isset($post['b1_'.$i.'_sport2'])&&$post['b1_'.$i.'_sport2'])   $where[]='sport="'.($post['b1_'.$i.'_sport2']).'"';
  if(isset($post['b1_'.$i.'_country2'])&&$post['b1_'.$i.'_country2']) $where[]='country="'.($post['b1_'.$i.'_country2']).'"';
  $teams1=mysql_select("SELECT id,name FROM teams WHERE ".implode(' AND ',$where)." order by name asc",'array');
  foreach($teams1 as $k=>$v) {
    $form[2][] = "<option value='$k'";
    if(isset($post['b1_'.$i.'_team2'])&&$post['b1_'.$i.'_team2']==$k) $form[2][] = " selected";
    $form[2][] = ">$v</option>";
  }
  $form[2][] = '</select></div>';

//

  $form[2][] = array('input td2','b1_'.$i.'_abbr');
  $form[2][] = array('input td10','b1_'.$i.'_analysis');
}

$content.= '
<script type="text/javascript">
document.addEventListener("DOMContentLoaded", function () {

	$(document).on("change",".sport",function() {
		var aid=$(this).data("id");
		var ateam=$(this).data("team");
		$("#b1_"+aid+"_team"+ateam).prop("disabled",true);
		var sport  =$("#b1_"+aid+"_sport"+ateam).val();
		var country=$("#b1_"+aid+"_country"+ateam).val();
		$.get(
			"/admin.php?m=betoftheday&u=get_teams",
			{"sport":sport,"country":country},
			function(data){
				$("#b1_"+aid+"_team"+ateam).html(data);
				$("#b1_"+aid+"_team"+ateam).prop("disabled",false);
			}
		).fail(function() {
			alert("Нет соединения!");
		});
		return false;
	});

	$(document).on("change",".country",function() {
		var aid=$(this).data("id");
		var ateam=$(this).data("team");
		$("#b1_"+aid+"_team"+ateam).prop("disabled",true);
		var sport  =$("#b1_"+aid+"_sport"+ateam).val();
		var country=$("#b1_"+aid+"_country"+ateam).val();
		$.get(
			"/admin.php?m=betoftheday&u=get_teams",
			{"sport":sport,"country":country},
			function(data){
				$("#b1_"+aid+"_team"+ateam).html(data);
				$("#b1_"+aid+"_team"+ateam).prop("disabled",false);
			}
		).fail(function() {
			alert("Нет соединения!");
		});
		return false;
	});

});
</script>';