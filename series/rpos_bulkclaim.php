<?
//exit("Error-Please Claim After Some Time ");
/*
 */

$MINTransAmount  = 4824;
//$MINTransAmount  = 5;

if (!isset($_REQUEST['agentcode']) || $_REQUEST['agentcode'] == "")
    exit("Invalid Request 1");

if (!isset($_REQUEST['transno']) || strlen($_REQUEST['transno']) < 9)
    exit("Please type valid Transaction No. to claim");

require_once 'connection.php';
require_once 'LotteryUtilities.php';
require_once 'main.php3';

$conn = new Connection();

echo "<center><font color=\"red\" size=3>Note:-</font>This option is for current day transaction only.</center>";

/*
 * Validating agent for active
 */
$sqlstr = "SELECT block FROM agent_master WHERE agentcode='" . $_REQUEST['agentcode'] . "'";
$result = $conn->Execute($sqlstr, "ERROR- 0011");
if (mysql_num_rows($result) != 1)
    exit("<br><br><center>Please Contact your Agent 0012</center>");

$rowam = mysql_fetch_object($result);
mysql_free_result($result);
if ($rowam->block == 'Y' || is_null($rowam->block))
    exit("<br><br><center>Please Contact your Agent 0013</center>");

/*
 *  Validating amount in dailypld  
 */
$sqlstr = "SELECT sum(amount) as amt FROM ".$conn->MainDatabase.".dailypld 
           WHERE agentcode='".$_REQUEST['agentcode']."' 
               AND tno=".$_REQUEST['transno']." 
               AND vdate=curdate() AND tcode=11 AND gamecode=57
           HAVING amt>0";
$resultdpld = $conn->Execute($sqlstr, "<br><br><center>Ticket Not Found 0010</center>");
if(mysql_num_rows($resultdpld) <= 0)
     exit("<br><br><center><font size=6>Ticket not sold</font></center>");
$rowdpld = mysql_fetch_object($resultdpld);
if($rowdpld->amt < $MINTransAmount)
    exit("<br><br><center><font size=\"6\">Please claim this transaction through barcode claim option</font></center>");
$TransAmount = $rowdpld->amt ; 

/*
 *  VAlidating Sale data
 */

$lsqlstr = "SELECT confcode,l.lotcode,series,trstatus,drawtime, l.govtname   
            FROM posconf p,lotmas l,postrans t   
            WHERE  t.transno='" . $_REQUEST['transno'] . "' AND l.lotcode=p.lotcode 
                AND t.drawdate=p.transdate and p.transno=t.transno AND t.drawdate=curdate()
                AND trstatus in ('S','P','X','U') AND agentcode='".$_REQUEST['agentcode']."'  
            ORDER BY p.confcode";
            
$resultsale = $conn->Execute($lsqlstr, "Ticket Not Found 0014 ");
if (mysql_num_rows($resultsale) <= 0) { //Checking in the main Server
        exit("<br><br><center><font size=\"6\">Ticket Not Available for Claim</font></center>");
}

$isAlreadyClaim = false;
$confcodeList   = array();
$SeriesArray = array();
$DrawOverConfCodeList = array();

while($rowsale  = mysql_fetch_object($resultsale))
{   
    if ($rowsale->trstatus == "X")
        exit("<br><br><center><font size=\"6\">Ticket has been cancelled, can't be claimed.</font></center>");
    
    if ($rowsale->drawtime  > date("H:i:s"))
    {
        $DrawOverConfCodeList[] = $rowsale->confcode;
        continue;
    }
    
    if ($rowsale->trstatus == "P")
        $isAlreadyClaim = true;
    
    $confcodeList[$rowsale->confcode] = $rowsale->lotcode;   
    $SeriesArray[$rowsale->confcode] = $rowsale->series;
}

echo "<br><center><H3>Transaction Status for transaction number ".$_REQUEST['transno']."</H3><center>" ;

if ($isAlreadyClaim == true) {

    $repconn  = new Connection('REP');
    $confdata = implode("','",array_keys($confcodeList));    
    $Tlsqlstr = "SELECT confcode,claimdate,prizeamt FROM claimprize where confcode in ('" . $confdata  . "')";
    $Tresult  = $repconn->Execute($Tlsqlstr, "ERROR- 0016");
    if (mysql_num_rows($Tresult) <= 0) {
        exit("<br>Ticket Already Claimed");
    }
    ?>
    <table align="center" cellspacing="1" border="0" cellpadding="2" width="98%" bgcolor="#000000">        
        <tr bgcolor="#E2E2E2">
             <td width="5%" align="center">S.No.</td>    
            <td width="15%" align="center">Barcode </td>
            <td width="80%">Status</td>            
        </tr>    
    <? 
    $sno=0;
    $totalClaimPrizeamount = 0 ;
    while($Trow = mysql_fetch_object($Tresult))         
    {
        if(isset($confcodeList[$Trow->confcode]))
            unset($confcodeList[$Trow->confcode]);
        echo "<tr bgcolor=\"#FFFFFF\"><td>".++$sno."</td>";
        echo "<td>".$Trow->confcode."  </td>";
        echo "<td>Prize Amount <b>" . $Trow->prizeamt . "</b> ,Claimed at <b>" . formatdatetime($Trow->claimdate) . "</b></td></tr>";
        $totalClaimPrizeamount += $Trow->prizeamt;
    }
    echo "<tr bgcolor=\"#FFFFFF\" ><td colspan=\"2\" align=center>Total Claim Amount</td><td><b>".$totalClaimPrizeamount."</b></td></tr>";
    echo "</table><br>";
}

if(count($DrawOverConfCodeList) >0 )
{
    ?>
    <table align="center" cellspacing="1" border="0" cellpadding="2" width="98%" bgcolor="#000000">        
        <tr bgcolor="#E2E2E2">
            <td width="5%" align="center">S.No.</td>
            <td width="15%" align="center">Barcode </td>
            <td width="80%">Status</td>            
        </tr> 
    <?
    $sno = 0;
    foreach ($DrawOverConfCodeList as $confcode)
    {
        echo "<tr bgcolor=\"#FFFFFF\"><td>".++$sno."</td>";
        echo "<td>".$confcode."  </td>";
        echo "<td>Draw Not over</td></tr>";                
    }    
    echo "</table>"; 
}

if(count($confcodeList) <=0 )
    exit;

$lotcodelist  = array_unique($confcodeList);
foreach($lotcodelist as $confcode  => $lotcode )
{
    $ResultArray[$lotcode] = LotteryUtilities::GetPrizeArray(date('Y-m-d'), $lotcode, $conn);
    if (!is_array($ResultArray[$lotcode]) || count($ResultArray[$lotcode]) < 2)
        exit("<br><br><center><font size=\"6\">Result Not declear</font></center>");
}
if(!is_array($ResultArray))
    exit("<br><br><center><font size=\"6\">Result Not declear</font></center>");
?>
 <table align="center" cellspacing="1" border="0" cellpadding="2" width="98%" bgcolor="#000000">        
        <tr bgcolor="#E2E2E2">
             <td width="5%" align="center">S.No.</td>
            <td width="15%" align="center">Barcode </td>
            <td width="80%">Status</td>            
        </tr> 
<?
  $sno = 0;
    $TotalPrizeAmount = 0;

    foreach ($confcodeList as $confcode => $lotcode) {
        if (!array_key_exists($lotcode, $ResultArray))
            continue;
        $resultarry = $ResultArray[$lotcode];
        $series = $SeriesArray[$confcode];
        $prizeAmt = 0;
        $sqlstr = "SELECT printdata ,quantity,digitno,confcode  
                    FROM posdetails 
                    WHERE confcode='" . $confcode . "' AND transdate=curdate() 
                    ORDER BY confcode";
          $resultdet = $conn->Execute($sqlstr, 'ERROR 0017');
        if (mysql_num_rows($resultdet) <= 0)
            exit("Error-Unable to Claim  Ticket 2");
        $printdatastr = "";    
  while ($row = mysql_fetch_object($resultdet)) {
     foreach ($resultarry as $prizeno => $prizedata) {
         $printdatastr = $row->printdata . " Qty " . $row->quantity;
        if (strlen($row->printdata) == 10 && strlen($prizedata['RESULT_NO']) > 10
                && strlen($row->digitno) == 5 && strlen($series) == 2
                && (($prizedata['RESULT_NO'] == ":" . $row->printdata . ":"  && $series ==  $prizedata['LOTSERIES'])
                || substr_count($prizedata['RESULT_NO'], ":" . $series . "-" . $row->digitno . ":") == 1
                )) {
            $prizeAmt += (int) $row->quantity * (double) $prizedata['PRIZEAMT'];
            break;
        }
    }
  }
       
        $prizestr = "";
        if ($prizeAmt > 0 && $prizeAmt <= 10000) 
             $prizestr = "Prize Amount <b>Rs. " . $prizeAmt."</b> &nbsp;&nbsp; Winning No ".$printdatastr ;
        else if ($prizeAmt > 10000)  
            $prizestr = "Ticket Should Be Claimed Through Head Office";
        else 
            continue;
	echo("<tr bgcolor=\"#FFFFFF\"><td>".++$sno."</td><td>".$confcode."  </td><td>" . $prizestr  . "</td></tr>");
	$TotalPrizeAmount += $prizeAmt;
}

if( $TotalPrizeAmount <=0 && $isAlreadyClaim == false )
         echo("<tr bgcolor=\"#FFFFFF\"><td align=\"center\" colspan=\"3\" >No Winning ticket</td></tr>");

if( $TotalPrizeAmount >2 )
{
    echo "<tr  bgcolor=\"#FFFFFF\" ><td colspan=\"2\" align=center >Total Winning Amount</td><td><b>".$TotalPrizeAmount."</b></td></tr>";    
}
echo "</table>";

if( $TotalPrizeAmount >2 )
{?>
	<br>
	<a href="rpos_bulkclaimcommit.php?agentcode=<?=$_REQUEST['agentcode']?>&transno=<?=$_REQUEST['transno']?>&tamt=<?=$TransAmount?>" ><center><b>Claim All Winning barcode</b></center></a>
	<br><br>
<?
}
?>



