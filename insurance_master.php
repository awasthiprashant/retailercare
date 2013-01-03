<?
if(!isset($_POST['info']) ||!isset($_POST['agentcode']))
	exit("Error-Required Data Missing");
require_once "connection.php";
require_once "main.php3";
$con=new Connection('RC');
if($_POST['info']=="get")
{
	if(!isset($_POST['brand']))
		exit("Error-Required Data Missing[brand]");
	$return_str="";
	$tablename="agent_slave_".strtolower($_POST['brand']);
	$qury="select agentcounter,branchcode from ".$tablename." where agentcode='".$_POST['agentcode']."'";
	$exec=$con->Execute($qury,"Error-001");
	if(mysql_num_rows($exec)<=0)
		exit("Error-no record For this retailer");
	$row=mysql_fetch_array($exec);
	$branchcode=$row['branchcode'];
//	if($branchcode == "B01")
//		exit("Error-This option is not available for your branch");

	$agentcounter=$row['agentcounter'];
	$qury2="select *,a.area_id as area_id,a.name as areaname,a.zone_id, a.name,z.zone_id ,z.name as zone from retailer_zone as z, retailer_area as a, retailer_insurance_master as s WHERE  s.agentcounter='".$agentcounter."' and  s.agentcode='".$_POST['agentcode']."' and a.area_id=s.area_id and z.zone_id=s.zone_id and a.zone_id=z.zone_id"; 
	$result=$con->Execute($qury2);
	if(mysql_num_rows($result)>0)
	{
		$flag='update';
		$row=mysql_fetch_array($result);
		$birthdate=formatdate($row['dob']);
		$return_str=$flag."##".$row['agentname']."##".$birthdate."##".$row['loginname']."##".$row['mobile']."##".$row['phone']."##".$row['areaname']."##".$row['nominee_name']."##".urldecode ($row['address'])."##".$row['nominee_father_name']."##".urldecode ($row['prtrc_res_add'])."##".$row['nominee_surname']."##".urldecode ($row['prtin_res_add'])."##".$row['nominee_age']."##".$row['prtrc_name']."##".$row['relationship']."##".$row['prtrc_pan_no']."##".$row['banker_name']."##".$row['prtin_pan_no']."##".$row['banker_branch']."##".$row['prtin_first_name']."##".$row['banker_acc_type']."##".$row['prtin_father_name']."##".$row['prtin_surname']."##".urldecode ($row['reference'])."##".$row['application_no']."##".$row['zone_id']."##".$row['branchcode']."##".$row['lotname']."##".$row['firstsaledate']."##".$row['agentcounter']."##".$row['area_id']."##".$row['banker_acc_no']."##".$row['gender'];
	}
	else
	{
		$query="select s.add1, s.firstsaledate,d.agentname,d.director_pan_no,a.area_id ,a.zone_id, a.name,d.loginname, d.director_name,d.area_id,d.agentcode,z.zone_id ,d.date_of_birth, d.application_no, d.agentcounter, z.name as zone,d.res_add1, d.lotname, d.branchcode from ".$tablename." s, retailer_zone z, retailer_area a , retailer_details d WHERE  s.agentcounter='".$agentcounter."' and  s.agentcode='".$_POST['agentcode']."' and s.agentcounter=d.agentcounter and d.lotname='".$_POST['brand']."' AND d.branchcode='".$branchcode."' AND a.area_id=d.area_id AND a.zone_id=z.zone_id and s.agentcode=d.agentcode and s.branchcode=d.branchcode and d.zone_id=z.zone_id  order by d.agentname";
		$result=$con->Execute($query);
		if(mysql_num_rows($result)>0)
		{ 	$flag='save';
			$row=mysql_fetch_array($result);
			if(!is_null($row['date_of_birth']) && $row['date_of_birth']!="0000-00-00")
			{
				$birthdate=formatdate($row['date_of_birth']);
			}
			else
$birthdate=date('d-m-Y');
			$return_str=$flag."##".$row['agentname']."##".$birthdate."##".$row['loginname']."##".$row['name']."##".$row['add1']."##".$row['res_add1']."##".$row['director_name']."##".$row['director_pan_no']."##".$row['application_no']."##".$row['zone_id']."##".$row['branchcode']."##".$row['lotname']."##".$row['firstsaledate']."##".$row['agentcounter']."##".$row['area_id'];
		}
		else
		   $return_str="Error-No Record Found";
	}
	echo($return_str);
}
else if($_POST['info']=="set")
{
	if(!isset($_POST['appno']) || !isset($_POST['lotname']) || !isset($_POST['agtname']) || !isset($_POST['agtcountr']) || !isset($_POST['login']) || !isset($_POST['bcode']) || !isset($_POST['zoneid']) || !isset($_POST['areaid']) || !isset($_POST['shopadd']) || !isset($_POST['mob']) || !isset($_POST['ph']) || !isset($_POST['dob']) || !isset($_POST['prtrcname']) || !isset($_POST['panrc']) || !isset($_POST['prtrcresadd']) || !isset($_POST['prtinfname']) || !isset($_POST['prtinfathername']) || !isset($_POST['prtinsurname']) || !isset($_POST['prtinresadd']) || !isset($_POST['prtinpanno']) || !isset($_POST['nomname']) || !isset($_POST['nomage']) || !isset($_POST['relation'])  || !isset($_POST['bank']) || !isset($_POST['bankbranch']) || !isset($_POST['acctype']) || !isset($_POST['accno']) || !isset($_POST['ref']) || !isset($_POST['fsaledat']) || !isset($_POST['gender']) || !isset($_POST['nomfather']) || !isset($_POST['nomsurname']))
		exit("Error-Required Data Missing[set]");
		$insertQuery="insert into retailer_insurance_master(date_entered,application_no,agentcode,lotname,agentname,agentcounter,loginname,branchcode, zone_id,area_id,address,mobile,phone,dob,prtrc_name,prtrc_pan_no,prtrc_res_add,prtin_first_name,prtin_father_name,prtin_surname,prtin_res_add,prtin_pan_no,nominee_name,nominee_age ,relationship,banker_name ,banker_branch ,banker_acc_type 
,banker_acc_no,status,reference,firstsaledate,gender,nominee_father_name,nominee_surname)
values (now(),'".$_POST['appno']."','".$_POST['agentcode']."','".$_POST['lotname']."','".$_POST['agtname']."','".$_POST['agtcountr']."','".$_POST['login']."','".$_POST['bcode']."','".$_POST['zoneid']."','".$_POST['areaid']."','".urlencode($_POST['shopadd'])."','".$_POST['mob']."','".$_POST['ph']."','".$_POST['dob']."','".$_POST['prtrcname']."','".$_POST['panrc']."','".urlencode($_POST['prtrcresadd'])."','".$_POST['prtinfname']."','".$_POST['prtinfathername']."','".$_POST['prtinsurname']."','".urlencode($_POST['prtinresadd'])."','".$_POST['prtinpanno']."','".$_POST['nomname']."','".$_POST['nomage']."','".$_POST['relation']."','".$_POST['bank']."','".$_POST['bankbranch']."','".$_POST['acctype']."','".$_POST['accno']."','N','".urlencode($_POST['ref'])."','".$_POST['fsaledat']."','".$_POST['gender']."','".$_POST['nomfather']."','".$_POST['nomsurname']."')";
	$recordInsert=$con->Execute($insertQuery);
	$return_str="Display";
	echo($return_str);
}
else if($_POST['info']=="display")
{
	if(!isset($_POST['appno']) || !isset($_POST['agentcode']) || !isset($_POST['lotname']))
             exit("Invalid Request"); 
	$query="select *,a.area_id as area_id,a.name as areaname,a.zone_id, a.name,z.zone_id ,z.name as zone from retailer_zone as z, retailer_area as a, retailer_insurance_master as s where application_no='".$_POST['appno']."' and agentcode='".$_POST['agentcode']."' and lotname='".$_POST['lotname']."'  and a.area_id=s.area_id and z.zone_id=s.zone_id and a.zone_id=z.zone_id";
	$result=$con->Execute($query);
	if(mysql_num_rows($result)>0)
	{
		$row=mysql_fetch_array($result);
		($row['gender']=='M'?$gender="Male":$gender="Female");
		?>
				<table   border=0 width="100%"  cellpadding=1 cellspacing=1 >
				<col  width="24%"><col width="30%"><col  width="24%"><col width="30%">
				<input type="hidden" id="AuthVar" name="AuthVar" value="<?=$_AuthVarVal;?>" >
                <tr bgcolor='#DDDDD2'>
					<td align="center" colspan=6  ><u><b><font  size="5" face="Verdana">Insurance Details of Retailer &nbsp;<?=$row['agentname']?></b></u></td>
				</tr>
				<tr bgcolor='#DDDDD2'>
					<td valign="top"  ><b>Terminal Name</b></td>
    				<td align="left" valign="top" ><?=$row['agentname']?></td>
    				<td ><b>Date Of Birth</b></td>
    				<td ><?=date('d/m/Y',strtotime($row['dob']))?></td>
				</tr>
				<tr bgcolor='#DDDDD2'>
					<td valign="top"><b>Login Name.</b></td>
					<td align="left" valign="top"><?=$row['loginname']?></td>
					<td valign="top"><b>Retailer Contact</b></td>
					<td valign="top"><?=$row['mobile'].','.$row['phone']?></td>
				</tr>
				<tr bgcolor='#DDDDD2'>
					<td valign="top"><b>Location</b></td>
					<td valign="top"><?=$row['areaname']?></td>
					<td valign="top"><b>Nominee Name</b></td>
					<td valign="top"><?=$row['nominee_name']." ".$row['nominee_father_name']." ".$row['nominee_surname']?></td>
				</tr>
				<tr bgcolor='#DDDDD2'>
					<td valign="top"><b>Retailer's First Sale Date</b></td>
					<td valign="top"><?=$row['firstsaledate']?></td>
					<td valign="top"><b>Retailer Login Status</b></td>
					<td valign="top"><?='N'?></td>
				</tr>
				<tr bgcolor='#DDDDD2'>
					<td valign="top" ><b>Shop Address(RC)</b></td>
					<td valign="top"><?=urldecode($row['address'])?></td>
					<td valign="top"><b>Nominee Age</b></td>
					<td valign="top"><?=$row['nominee_age']?></td>
				</tr>
				<tr bgcolor='#DDDDD2'>
					<td valign="top"><b>Residence Address(RC)</b></td>
					<td valign="top"><?=urldecode ($row['prtrc_res_add'])?></td>
					<td valign="top"><b>Relationship with Nominee</b></td>
					<td valign="top"><?=$row['relationship']?></td>
				</tr>
				<tr bgcolor='#DDDDD2'>
					<td valign="top"><b>Residence Address</b></td>
					<td valign="top"><?=urldecode ($row['prtin_res_add'])?></td>
					<td valign="top"><b>Bank Name</b></td>
					<td valign="top"><?=$row['banker_name']?></td>
				</tr>
				<tr bgcolor='#DDDDD2'>
					<td valign="top"><b>Propriter Name(RC)</b></td>
					<td valign="top"><?=$row['prtrc_name']?>&nbsp;</td>
					<td valign="top"><b>Bank Branch Address</b></td>
					<td valign="top"><?=$row['banker_branch']?></td>
				</tr>
				<tr bgcolor='#DDDDD2'>
					<td valign="top" ><b>PAN NO.(RC)</b></td>
					<td valign="top"><?=$row['prtrc_pan_no']?></td>
					<td valign="top"><b>Bank Account Type</b></td>
					<td valign="top"><?=$row['banker_acc_type']?></td>
				</tr>
				<tr bgcolor='#DDDDD2'>
					<td valign="top"><b>PAN NO.</b></td></td>
					<td valign="top"><?=$row['prtin_pan_no']?></td>
					<td valign="top"><b>Bank Account Number</b></td></td>
					<td valign="top"><?=$row['banker_acc_no']?></td>
				</tr>
				<tr bgcolor='#DDDDD2'>
					<td valign="top"><b>Prop. First Name</b></td></td>
					<td valign="top"><?=$row['prtin_first_name']?></td>
					<td valign="top"><b>Prop. Father's Name</b></td></td>
					<td valign="top"><?=$row['prtin_father_name']?></td>
				</tr>
				<tr bgcolor='#DDDDD2'>
					<td valign="top"><b>Prop. Surname</b></td></td>
					<td valign="top"><?=$row['prtin_surname']?></td>
					<td valign="top"><b>Prop. Gender</b></td></td>
					<td valign="top"><?=$gender?></td>
			
				</tr>
				<tr bgcolor='#DDDDD2'>
					<td valign="top" rowspan="3"><b>Old Reference in Case of Shifitng.Shop Add.<br>Change If old and currunt working More than 1 Yr)</b></div></td></td>
					<td valign="top" colspan="3" rowspan="3"><?=urldecode ($row['reference'])?></td>
				</tr>
			</table>
			</center>
			</body>
	  <?
	}
}
?>
