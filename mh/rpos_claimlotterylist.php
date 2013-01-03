<?php
	if(isset($_POST['drawdate']) && $_POST['drawdate']!="")
		$date=$_POST['drawdate'];
	$str_phpData="";
	include "connection.php";
	$con_obj=new Connection();
	$qury="select lotcode,lotname from lotmas where lotto=0";
	$result=$con_obj->Execute($qury);
	while($row=mysql_fetch_array($result))
	{
		$str_phpData=$str_phpData.$row['lotcode']."-".$row['lotname']."#";
	}
	echo $str_phpData;
?>

