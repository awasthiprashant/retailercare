<?
//07/01/2013
header("Expires: Fri, 21 mar 1997 2:52:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache");
if(""==$_REQUEST["lotcode"] || ""==$_REQUEST["drawdate"])
{
	?>
	<center><FONT SIZE="3" COLOR="#FF3300"><b>The scheme is unavailable.</b></FONT></center>
	<?
	exit();
}
require_once("connection.php");
require_once 'LotteryUtilities.php';
require_once "main.php3";

$lotteryconn = new Connection("MAIN");
$lotcode = $_REQUEST["lotcode"];

/*
if($_REQUEST['posversion'] < 2.066)
{
	$weekno = LotteryUtilities::GetWeekNoOfMonth(date("Y-m-d")); 
	if($weekno == 1)
		$lotcode='1017';
	else 
		$lotcode='1019';
}*/

$query = "SELECT sch_no FROM schema_master WHERE dayofweek(drawdate) =dayofweek('2012-12-01') AND lotcode='".$lotcode."' order by drawdate desc LIMIT 1";

$result = $lotteryconn->EXECUTE($query, "Error- There is some error on server Er-01");

if(mysql_num_rows($result)<=0)
{
	mysql_free_result($result);?>
	<BR><B><center><font face="Verdana, Arial, Helvetica, sans-serif" color="#FF0000"size=3><u>Scheme Not define for drawdate <br><?=formatdate($_REQUEST["drawdate"])?> <font></u></b></center>
	<?
	exit();
}

$row = mysql_fetch_array($result);

$scno= $row["sch_no"];

if($lotcode == "1017")
	$query = "SELECT  if(prize>1 and prize<90,concat('Z',prize),concat('A',prize)) as prize , prizeamt , winnerno FROM schema_details WHERE sch_no='".$scno."' ORDER BY prize";
else 
	$query = "SELECT  concat('1',prize) as prize , prizeamt , winnerno FROM schema_details WHERE sch_no='".$scno."' ORDER BY prize+0";


$result = $lotteryconn->EXECUTE($query, "Error- There is some error on server Er-02");

if(mysql_num_rows($result)<=0)
{
	mysql_free_result($result);?>
	<BR><B><center><font face="Verdana, Arial, Helvetica, sans-serif" color="#FF0000" size=3><u>Scheme not found?> <font></u></b></center>
	<?
	exit();
}
?>
<html>
<body>
	<table border="0" width ='100%' cellspacing="1" cellpadding="2" bgcolor="#000000">
		<tr>
		<td bgcolor="#1589FF" align="center"><font color="white"><font size="2">Rank</font></td>
		<td bgcolor="#1589FF" align="center"><font color="white"><font size="2">Prize</font></td>
		<td bgcolor="#1589FF" align="center"><font color="white"><font size="2">Winners</font></td>
		<td bgcolor="#1589FF" align="center"><font color="white"><font size="2">Total</font></td>
		</tr>
	<?
		while($row = mysql_fetch_assoc($result))
		{
		?>
			<tr bgcolor="#FFFFFF">
			<td align="center"><font size="2"><?=(substr($row['prize'],1)>90?"C":substr($row['prize'],1)) ;?></font></td>
			<td align="right"><font size="2"><?=$row["prizeamt"];?></font></td>
			<td align="left"><font size="2"><?=$row["winnerno"];?></font></td>
			<td align="right"><font size="2"><?=$row["winnerno"]*$row["prizeamt"];?></font></td>
			</tr>
	<?	}?>
	</table>
</body>
</html>	

