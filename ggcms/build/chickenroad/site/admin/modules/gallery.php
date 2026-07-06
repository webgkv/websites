<?php

if(isset($_GET['new_img'])) {

  $post=array();
  foreach($_POST as $k=>$v)
    $post[$k]=$v;

  if($post['img']) {

    $temp = ROOT_DIR.'files/temp/'.$post['img'].'/'; // temp dir on server
    // Numeric filename + temp dir = new upload
    if (is_numeric($post['img']) AND is_dir($temp) AND $handle = opendir($temp)) {
      $temp_file = ''; // temp filename on server
      while (false !== ($f = readdir($handle))) {
        if (strlen($f)>2 && is_file($temp.$f)) {
          $file = strtolower(trunslit($f));
          if($post['filename']) $file=preg_replace('#^.*(\.[^\.]+)$#',$post['filename'].'$1',$file);
          $temp_file = $temp.$f;
          break;
        }
      }
      $post['img']=$file;
      $id=mysql_fn('insert','gallery',$post);
      if($id) {
        if(copy2($temp_file,ROOT_DIR."files/gallery/$id/img/",$file,array(''=>''))) {
          echo $id;
        } else {
          mysql_fn('delete','gallery',$id);
        }
        //delete temp file
        delete_all($temp,true);
      }
    }
  }
  exit;

} elseif(isset($_GET['json'])) {

  $perpage=12;

  require(dirname(__FILE__).'/../../functions/data_func.php');

  $where=' where 1';

  if(isset($_GET['page'])&&$_GET['page']>0) $page=$_GET['page']+0;
                                       else $page=1;

  if(isset($_GET['search'])&&$_GET['search']) {
    $search=mysql_res($_GET['search']);
    $where.=" and (img like '%$search%' or filename like '%$search%' or alt like '%$search%' or title like '%$search%')";
  }
  $rows=mysql_select("select count(*) from gallery $where",'string');
  $q=mysql_select("select * from gallery $where order by id desc limit ".($page-1)*$perpage.",$perpage",'rows_id');

  $a=array(
    'pagination'=>'',
    'html'=>''
  );

  if(is_array($q)) foreach($q as $k=>$v) {
    $a['html'].="<div class='gimg-cover col-xl-2' style='padding:0'><a href='#' class='form-group gimg".($_GET['id']==$k?' gimg_active':'')."' data-id='$k'><img class='w-100' src='".imgstr('gallery',$k,$v['img'],'149x99')."'><div class='img d-none'>".$v['img']."</div><div class='name'>".$v['filename'].'&nbsp;'."</div><div class='alt'>".$v['alt'].'&nbsp;'."</div><div class='title'>".$v['title'].'&nbsp;'."</div></a></div>";
  }

  $a['pagination'] ='<nav><ul class="pagination">';
  for($i=1;$i<=ceil($rows/$perpage);$i++) {
    $a['pagination'].='<li class="page-item'.($i==$page?' active':'').'"><a class="page-link"'.($i==$page?'':' href="#"').">$i</a></li>";
  }
  $a['pagination'].='</ul></nav>';

  echo json_encode($a);
  exit;
}



$delete = array('blog'=>'gimg');

$tabs = array(
  1=>a18n('common'),
);

$table = array(
	'id'		=>	'id:desc',
	'img'		=>	'img',
	'filename'	=>	'',
	'alt'		=>	'',
	'title'		=>	'',
);

$form[1][] = array('input td4','filename');
$form[1][] = array('input td4','alt');
$form[1][] = array('input td4','title');
$form[1][] = array('file td6','img',array('sizes'=>array(''=>'')));

function event_change_gallery($q,$old=false) {
	global $get,$post;
	global $form;
	$ext=preg_replace('#^.*(\.[^\.]+)$#iu','$1',$post['img']);
	if($post['filename']!=''&&$post['img']!=$post['filename'].$ext) {
		mysql_fn('update','gallery',array('id'=>$post['id'],'img'=>$post['filename'].$ext));
		$path=ROOT_DIR.'files/gallery/'.$post['id'].'/img/';
		rename($path.$post['img'],$path.$post['filename'].$ext);
		rename($path.'a-'.$post['img'],$path.'a-'.$post['filename'].$ext);
		foreach($form[3][2]['sizes'] as $k2=>$v2)
			if($k2) rename($path.$k2.$post['img'],$path.$k2.$post['filename'].$ext);
		$post['img']=$post['filename'].$ext;
	}
}
