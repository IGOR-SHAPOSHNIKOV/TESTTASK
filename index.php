<?require_once(dirname(__FILE__) . '/db_api/Db.class.php');
$db = new Db();
require_once(dirname(__FILE__) . '/functions.php');
session_start();
if ($_POST['a']){
    //debug ($_POST);
    if ($_POST['a']=='users'){
	if ((int)$_POST['users']['id'] > 0){
	    $id=(int)$_POST['users']['id'];
	    unset ($_POST['users']['id']);
	      foreach (array_keys($_POST['users']) as $t){
		$r.=$t.'=:'.$t.',';
	    }
	    $r=rtrim($r,',');
	   $sql="UPDATE users SET $r WHERE id=$id LIMIT 1";
	   //debug ($_POST['users'],'d');
	   //die ($sql );
	}else{
	//debug($_POST['users']);
	$sql="INSERT INTO users (".implode(',',array_keys($_POST['users'])).") VALUES (:".implode(',:',array_keys($_POST['users'])).")";
	}
	$db->query($sql,$_POST['users']);
	//debug ($_POST['users'],'gg');
	//debug ($sql);
    }
    if ($_POST['a']=='c_type' && trim($_POST['name']) !=''){
	$sql = "INSERT INTO contact_type (name) VALUES (:n)";
	$db->query($sql,['n'=>$_POST['name']]);
    }
    if ($_POST['a']=='cities' && trim($_POST['city'] !='' && $_POST['id_country']!='')){
	//
	if ((int)$_POST['id'] > 0){
	$sql = "UPDATE cities SET name=:n, id_country=:ctr WHERE id=:id";
	$db->query($sql,['n'=>$_POST['city'],'ctr'=>$_POST['id_country'],'id'=>$_POST['id']]);
	//debug ($_POST);
	}else{
	$sql = "INSERT INTO cities (name, id_country) VALUES (:n, :id)";
	$db->query($sql,['n'=>$_POST['city'],'id'=>$_POST['id_country']]);
	}
	//
    }
    //debug ($_POST);
    if ($_POST['a']=='countries' && trim($_POST['countries'] !='')){
	if ((int)$_POST['id'] > 0){
	   $sql = "UPDATE  countries SET name=:n WHERE id=:id"; 
	   $db->query($sql,['n'=>$_POST['countries'],'id'=>$_POST['id']]);
	    //debug ($_POST);
	}else{
	
	$sql = "INSERT INTO countries (name) VALUES (:c)";
	$db->query($sql,['c'=>$_POST['countries']]);
	}
    }
    $_SESSION['messade']="Выполнено";
    Header('Location:/?a='.$_POST['a']);
    debug ($_POST);
}
?>
<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
	<title>TODO supply a title</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script> 
	<style>
   ul.hr {
    margin: 0; /* Обнуляем значение отступов */
    padding: 4px; /* Значение полей */
   }
   ul.hr li {
    display: inline; /* Отображать как строчный элемент */
    margin-right: 5px; /* Отступ слева */
    border: 1px solid #000; /* Рамка вокруг текста */
    padding: 3px; /* Поля вокруг текста */
   }
  </style>
    </head>
    <body>
	<ul class="hr">
	    <li><a href="/">Пользователи</a></li>
	    <li><a href="?a=countries">Countries</a></li>
	    <li><a href="?a=cities">Cities</a></li>
	    <li><a href="?a=c_type">Contact type</a></li>
	</ul>
	<br>
	<?if ($_SESSION['message']){
	    echo $_SESSION['message'];
	    unset ($_SESSION['message']);
	}
	if (!isset($_GET['a']) || $_GET['a']=='users'){
	   $sql="SELECT * from users";
	   $res = $db->query($sql);
	   if (count($res) > 0){
	       $ctype=getContacttype();
	       $ctr=getCountries();
	       $stc=getCities();
	       foreach ($res as $k=>$v){?>
	<form method="post" id="f_<?=$v['id']?>">
	      <input type="text" name="users[notion]" placeholder="notion" value="<?=$v['notion']?>">
	<input type="text" name="users[registration_number]" placeholder="registration_number" value="<?=$v['registration_number']?>">
	<select name="users[contact_type]">
	    <option>--Выберите тип контакта--</option>>
	    <?=str_replace('value="'.$v['contact_type'].'"', 'value="'.$v['contact_type'].'" selected',$ctype);?>
	    <?//=$ctype;?>
	</select>
	<input type="text" name="users[contact]" placeholder="Добавьте контактную строку" value="<?=$v['contact']?>">
	<input type="text" name="users[address]" placeholder="address" value="<?=$v['address']?>">
	<input type="text" name="users[zipcode]" placeholder="zipcode" value="<?=$v['zipcode']?>">
	<input type="hidden" name="a" value="users">
	<input type="hidden" name="users[id]" value="<?=$v['id']?>">
	<select name="users[country]">
	    <option>--Выберите страну--</option>>
	    <?=str_replace('value="'.$v['country'].'"', 'value="'.$v['country'].'" selected',$ctr);?>
	    <?//=$ctr;?>
	</select>
	<select name="users[city]">
	    <option>--Выберите город--</option>>
	    <?=str_replace('value="'.$v['city'].'"', 'value="'.$v['city'].'" selected',$stc);?>
	    <?//=$stc;?>
	</select><input id="btn_<?=$v['id']?>" type="button" value="Сохранить" onclick="
	    var m=$(this).attr('id').split('_');
	    $('#f_'+m[1]).submit(); /*alert(m[1])*/">
		 <?}?>
	</form>
	   <?}?>
	<br><br>
	<form method="post">
	<input type="hidden" name="a" value="users">
	<input type="text" name="users[notion]" placeholder="notion">
	<input type="text" name="users[registration_number]" placeholder="registration_number">
	<select name="users[contact_type]">
	    <option>--Выберите тип контакта--</option>>
	    <?=getContacttype()?>
	</select>
	<input type="text" name="users[contact]" placeholder="Добавьте контактную строку">
	<input type="text" name="users[address]" placeholder="address">
	<input type="text" name="users[zipcode]" placeholder="zipcode">
	<select name="users[country]">
	    <option>--Выберите страну--</option>>
	    <?=getCountries()?>
	</select>
	<select name="users[city]">
	    <option>--Выберите город--</option>>
	    <?=getCities()?>
	</select>
	<br>
	<input type="submit" value="Добавить">
	</form>
	       
	       <?
	}
	if ($_GET['a']=='cities'){
	     $sql="SELECT * from cities";
	     $countries=  getCountries();
	     $res = $db->query($sql);
	     if (count($res) > 0){
		 foreach ($res as $k=>$v){?>
	<form method="post" id="f_<?=$v['id']?>">
	<input type="text" name="city" value="<?=$v['name']?>">
	<input type="hidden" name="a" value="<?=$_GET['a']?>">
	<input type="hidden" name="id" value="<?=$v['id']?>">
	<select name="id_country">
		<option value="">--Выбрать--</option>
		<?=str_replace('value="'.$v['id_country'].'"', 'value="'.$v['id_country'].'" selected',$countries);?>
	    </select>
	<input type="button" value="Созранить" id="btn_<?=$v['id']?>"  onclick="
	    var m=$(this).attr('id').split('_');
	    $('#f_'+m[1]).submit(); /*alert(m[1])*/"></form>
	<br>
		 <?}
		 
	     }?>
	<br>
	<form method="post">
	    <input type="text" name="city">
	    <select name="id_country">
		<option value="">--Выбрать--</option>
		<?=$countries?>
	    </select>
	    <input type="submit" value="Добавить">
	    <input type="hidden" name="a" value="<?=$_GET['a']?>">
	    
	</form>
	<?}
	if ($_GET['a']=='countries'){
	    $sql="SELECT * from countries";
	    $res = $db->query($sql);
	    //
	    if (count($res) > 0){
		foreach ($res as $data){?>
	<form method="post" id="f_<?=$data['id']?>">
	<input type="hidden" name="a" value="<?=$_GET['a']?>">
	<input type="hidden" name="id" value="<?=$data['id']?>">
	<input type="text" name="countries" id="countries_<?=$data['id']?>" value="<?=$data['name']?>"><input type="button" id="btn_<?=$data['id']?>" value="Сохранить" onclick="
	    var m=$(this).attr('id').split('_');
	    $('#f_'+m[1]).submit();"><br>
	</form>
		<?}
		//debug ($res);
	    }
	    ?>
	<br>
	<form method="post">
	    <input type="text" name="countries">
	    <input type="submit" value="Добавить">
	    <input type="hidden" name="a" value="<?=$_GET['a']?>">
	    
	</form>
	<?}
	if ($_GET['a']=='c_type'){
	    $sql="SELECT * from contact_type";
	    $res = $db->query($sql);
	    //
	    if (count($res) > 0){
		foreach ($res as $data){?>
	<input type="text" id="countries_<?=$data['id']?>" value="<?=$data['name']?>"><input type="button" value="Сохранить"><br>   
		<?}
		//debug ($res);
	    }
	    ?>
	<br>
	<form method="post">
	    <input type="text" name="name">
	    <input type="submit" value="Добавить">
	    <input type="hidden" name="a" value="<?=$_GET['a']?>">
	    
	</form>
	<?}?>
    </body>
</html>
