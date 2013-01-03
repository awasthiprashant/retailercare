<?
header("Expires: Fri, 21 mar 1997 2:52:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache");
header("Content-Type: text/xml");
include "connection.php";
include "main.php3";
include "LotteryUtilities.php";

$conn =  new Connection();
$currdate= date("Y-m-d");

echo "<"."?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?".">";


$sqlstr = "SELECT agentcounter FROM agent_master WHERE loginname='".$username."'";
$result = $conn->Execute($sqlstr);

$recFound=mysql_num_Rows($result) ;
$row=mysql_fetch_assoc($result);
if($recFound<=0 || $row["agentcounter"]=='Agent')
	exit("<Error>Error-Invalid User for this option</Error>");

 $sqlstr="SELECT l.lotcode,l.lotname,l.drawdate,l.drawtime,l.shortname,l.posorder,l.posbcolor,l.posfcolor FROM lotmas l where l.lotto=0 and lotcode not in ('1005','1021')";
$result = $conn->Execute($sqlstr);
if(mysql_num_rows($result)<=0)
	exit("<Error>Error-Goa Lottery is unavailable</Error>");

$strout="";
$listFromToPerSeries=array();

while($row=mysql_fetch_assoc($result))
{
	$minlotfrom='';
	$maxlotto='';
 	$drawdate = LotteryUtilities::GetDrawDate($row["lotcode"],$currdate,$conn);
	if( $drawdate == "NOTAVAILABLE")
	{
		 $drawdate = "2012-11-19";
	}
	
	$drawno  =  LotteryUtilities::GetDrawNo($row["lotcode"],$drawdate,$conn);	
	unset($listFromToPerSeries);

	if(!setminmaxlotno($row["lotcode"],$drawdate,$drawno,$lotterydatabase,$conn))
		continue;

 	$mrp = LotteryUtilities::GetMrp($row["lotcode"],$drawno,$conn)/100;	
	if($mrp<=0)
		continue;

	$strout .= '<Lottery><Code>'.$row["lotcode"].'</Code>';
	$strout .= '<Name>'.$row["lotname"].'</Name>';
	$strout .= '<Drawdate>'.$drawdate.'</Drawdate>';
	$strout .= '<Drawtime>'.$row["drawtime"].'</Drawtime>';
	$strout .= '<Mrp>'.$mrp.'</Mrp>';	
	$strout .= '<shortname>'.$row["shortname"].'</shortname>';
	$strout .= '<posbcolor>'.$row["posbcolor"].'</posbcolor>';
	$strout .= '<posfcolor>'.$row["posfcolor"].'</posfcolor>';
	$strout .= '<SeriesData>';
	
	foreach($listFromToPerSeries as $keySeries => $valStockArray)
	{
		$strout .='<Series value="'.$keySeries.'">';
		foreach($valStockArray as $key=> $para )
		{
			$strout .='<Stock From="'.$para["lotfrom"].'" To="'.$para["lotto"].'"/>';
		}
		$strout .='</Series>';
	}
	$strout .= '</SeriesData>'.'</Lottery>';
}

if($strout=="")
	exit("<Error>Goa Lottery is unavailable2</Error>");
 exit('<LotteryData><PrizeMessage>Show</PrizeMessage>'.$strout.'</LotteryData>');

function setminmaxlotno($lotcode,$drawdate,$drawno,$tempdb,$conn)
{
	global $minlotfrom,$maxlotto, $listFromToPerSeries;
	$listFromToPerSeries=array();

     $query  = "SELECT length(tktnoupto) as padlength FROM schema_master WHERE drawdate<='".$drawdate."' AND lotcode='".$lotcode."' ORDER BY drawdate DESC LIMIT 1";

	$result = $conn->Execute($query);
	if(mysql_num_rows($result) <=0)
		return false;

	$rowsm=mysql_fetch_assoc($result);

 	$query  = "SELECT series, lotfrom AS lotfrom ,lotto AS lotto FROM lotterystocktrans  WHERE drawdate='".$drawdate."' AND lotcode='".$lotcode."' ORDER BY series,lotfrom,lotto" ;
	$result = $conn->Execute($query);
	
	if( mysql_num_rows($result)<=0 )
	{
		$sqlstr = "SELECT series FROM lotpara WHERE lotcode='".$lotcode."' AND drawno<=".$drawno." ORDER BY drawno desc limit 1"; 
	
		$result = $conn->Execute($sqlstr);
		if( mysql_num_rows($result)<=0 )
		{
			exit('<Error>Error-Goa Lottery is unavailable3</Error>');
		}
			
		$row = mysql_fetch_assoc($result);

		$row["series"]=substr($row["series"],1,-1);
		$arraySeries=explode(":",$row["series"]);

		$j=0;
		for($i=0; $i<count($arraySeries); $i++)
		{			
			$listFromToPerSeries[$arraySeries[$j]][0]["lotfrom"]=0;
			$listFromToPerSeries[$arraySeries[$j]][0]["lotto"]=0;
			$j++;
		}
		return true;
	}

	$PadLength = strlen( $rowsm["padlength"]);
	$i=0;
	$tempSeries="";

	while($row = mysql_fetch_assoc($result) )
	{
		if($tempSeries==$row["series"])
			$i++;
		else 
		{
			$tempSeries=$row["series"];
			$i=0;
		}

		$listFromToPerSeries[$row["series"]][$i]["lotfrom"]=str_pad($row["lotfrom"],$PadLength,'0',STR_PAD_LEFT);;
		$listFromToPerSeries[$row["series"]][$i]["lotto"]=str_pad($row["lotto"],$PadLength,'0',STR_PAD_LEFT);
	}
	if(count($listFromToPerSeries)<=0)
		return false;
	else
		return true;
}

function give_prizeamt()
{
	global $lotterydatabase,$lotteryconn;
	$sqlstr = "select prizeamt from schema_master sm, schema_details sd where sm.sch_no=sd.sch_no and sd.prize=1 and sm.drawdate<=curdate() order by schdet_no desc limit 1";
	$result=mysql_db_query($lotterydatabase,$sqlstr,$lotteryconn) or die("Error-".mysql_error());
	$row=mysql_fetch_array($result);
	if(mysql_num_Rows($result)<=0)
		return 0;
	else
		return $row["prize_amt"];
}
/*
header("Content-type: text/xml");
echo "<?xml version='1.0' encoding='ISO-8859-1'?>";

echo '<LotteryData>
      <Lottery>
	 <Code>1000</Code>
         <Name>Goa Lottery</Name> 
         <Drawdate>2011-11-08</Drawdate>
	  <Drawtime>18:30:00</Drawtime>
	<Mrp>21</Mrp>	
	<shortname>shortname</shortname>
	<posbcolor>posbcolor</posbcolor>
	<posfcolor>posfcolor</posfcolor>
<SeriesData>
	 <Series value="SS839">	
            <Stock From="1" To="10" />
	    <Stock From="15" To="20" />
	 </Series>
	 <Series value="SS840">	
            <Stock From="1" To="10" />
	    <Stock From="15" To="20" />
	 </Series>
	 <Series value="SS841">	
            <Stock From="1" To="10" />
	    <Stock From="15" To="20" />
	 </Series>
</SeriesData>
	<prizeamt>23</prizeamt>
      </Lottery>

	<Lottery>
	 <Code>1001</Code>
         <Name>Goa Lottery1</Name> 
         <Drawdate>2011-11-08</Drawdate>
	  <Drawtime>18:35:00</Drawtime>	

	<Mrp>2</Mrp>	
	<shortname>shortname</shortname>
	<posbcolor>posbcolor</posbcolor>
	<posfcolor>posfcolor</posfcolor>
<SeriesData>
	 <Series value="SS842">	
            <Stock From="1" To="10" />
	    <Stock From="15" To="20" />
	 </Series>
	 <Series value="SS843">	
            <Stock From="1" To="10" />
	 </Series>
</SeriesData>
	<prizeamt>23</prizeamt>
      </Lottery>
    </LotteryData>';*/
?>

