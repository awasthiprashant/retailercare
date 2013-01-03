<?
//exit("Error-Please Claim After Some Time ");
/*  select concat('http://192.168.3.11/commonpos/slfourdnumpos/testbulk.php?transno=',tno,'&agentcode=',agentcode) as query ,amount From slmain09.dailypld where tcode=11 and amount>10000 and gamecode=53  order by amount desc;
 * 
 */

if (!isset($_REQUEST['agentcode']) || $_REQUEST['agentcode'] == "")
    exit("Invalid Request 1");

if (!isset($_REQUEST['transno']) || strlen($_REQUEST['transno']) < 9)
    exit("Please type valid Transaction No. to claim");

if (!isset($_REQUEST['tamt'])  || $_REQUEST['tamt'] < 2 )
    exit("Please type valid Transaction Amount. to claim");


require_once 'connection.php';
require_once 'LotteryUtilities.php';
require_once 'main.php3';

$conn = new Connection();

/*
 *  Validating amount in dailypld  
 */

$GovtArray = Array("Govt. of Sikkim" => 127,
    "Govt. of Goa" => 128,
    "Govt. of Nagaland" => 129,
    "Govt. of Mizoram" => 133
);

/*
 *  VAlidating Sale data
 */
echo "<center><font color=\"red\" size=3>Note:-</font>This option is for current day transaction only.</center>";
echo "<br><center><H3>Transaction Status for transaction number " . $_REQUEST['transno'] . "</H3><center>";
$lsqlstr = "SELECT confcode,l.lotcode,series,trstatus,drawtime, l.govtname   
            FROM posconf p,lotmas l,postrans t   
            WHERE  t.transno='" . $_REQUEST['transno'] . "' AND l.lotcode=p.lotcode 
                AND t.drawdate=p.transdate and p.transno=t.transno AND t.drawdate=curdate()
                AND trstatus='S' AND agentcode='".$_REQUEST['agentcode']."'  
            ORDER BY p.confcode";
            
$resultsale = $conn->Execute($lsqlstr, "Ticket Not Found 0014 ");
if (mysql_num_rows($resultsale) <= 0) { //Checking in the main Server
        exit("<br><br><center>Ticket Not Available for Claim</center>");
}

$confcodeList   = array();
while($rowsale  = mysql_fetch_object($resultsale))
{       
    if ($rowsale->drawtime  > date("H:i:s"))
        exit("<br><br><center>Draw Not over, can't be claimed.</center><br>");    
    
    $confcodeList[$rowsale->confcode] = $rowsale->lotcode;   
    $Govtncode[$rowsale->lotcode] = array_key_exists($rowsale->govtname, $GovtArray) ? $GovtArray[$rowsale->govtname] : 0;
}

// echo "<br><center><H3>Transaction Status for transaction number ".$_REQUEST['transno']."</H3><center>" ;
$lotcodelist  = array_unique($confcodeList);
foreach($lotcodelist as $confcode  => $lotcode )
{
    $ResultArray[$lotcode] = LotteryUtilities::GetPrizeArray(date('Y-m-d'), $lotcode, $conn);
    if (!is_array($ResultArray[$lotcode]) || count($ResultArray[$lotcode]) < 2)
        exit("<br><center>Result Not declear</center>");
}
if(!is_array($ResultArray))
    exit("<br><center>Result Not declear</center>");

?>
 <table align="center" cellspacing="1" border="0" cellpadding="2" width="98%" bgcolor="#000000" >        
        <tr bgcolor="#E2E2E2">
             <td width="5%" align="center">S.No.</td>
            <td width="15%" align="center">Barcode </td>
            <td width="80%">Status</td>            
        </tr> 
<?
$repconn  = new Connection('REP');
$sno = 0;
$TotalPrizeAmount = 0;

foreach ($confcodeList as $confcode => $lotcode) {
    if (!array_key_exists($lotcode, $ResultArray))
        continue;
    $resultarry = $ResultArray[$lotcode];
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
        $printdatastr = $row->printdata . " Qty " . $row->quantity;
        foreach ($resultarry as $prizeno => $prizedata) {
            if (strlen($row->printdata) >= 8 && $prizedata['MATCHDIGITS'] >= 4 && strlen($prizedata['RESULT_NO']) > 8
                    && substr_count($prizedata['RESULT_NO'], ":" . substr($row->printdata, -1 * $prizedata['MATCHDIGITS']) . ":") == 1) {
                $prizeAmt += (int) $row->quantity * (double) $prizedata['PRIZEAMT'];
                break;
            }
        }
    }
    $prizestr = "";
    if ($prizeAmt > 0 && $prizeAmt <= 10000)
    {
         $sqlstr = "INSERT INTO claimprize VALUES 
                    ('" . $confcode . "',now(),'" . $_REQUEST['agentcode'] . "'," . $prizeAmt . ",'Y',0,'" . $Govtncode[$lotcode] . "')";
        $repconn->Execute($sqlstr, "Please claim this ticket after some time.");

        $sqlstr = "UPDATE posconf SET trstatus='P' WHERE confcode='" . $confcode . "'";
        $conn->Execute($sqlstr, "ERROR- 0018");                           

        $prizestr = "Prize Amount <b>Rs. " . $prizeAmt."</b> &nbsp;&nbsp; Winning No ".$printdatastr;
        $TotalPrizeAmount += $prizeAmt;
    }
    else if ($prizeAmt > 10000)
        $prizestr = "Ticket Should Be Claimed Through Head Office";
    else
        continue;
    echo("<tr bgcolor=\"#FFFFFF\"><td>" . ++$sno . "</td><td>" . $confcode . "  </td><td>" . $prizestr . "</td></tr>");
}

if( $TotalPrizeAmount <=0  )
         exit("<tr bgcolor=\"#FFFFFF\"><td align=\"center\" colspan=\"3\" >No Ticket Available for claiming</td></tr></table>");
echo "</table>";

if( $TotalPrizeAmount >2 )
{
    $sqlstr = "UPDATE " . $conn->MainDatabase . ".agent_master SET currentbal=currentbal-" . $TotalPrizeAmount . " 
        WHERE agentcode='" . $_REQUEST['agentcode'] . "'";
    $conn->Execute($sqlstr, "ERROR- 0020");

     $sqlstr = "INSERT INTO transactionwiseclaim_log 
        VALUES('".$_REQUEST['transno']."','".$_REQUEST['agentcode'] ."',".$_REQUEST['tamt'].",".$TotalPrizeAmount .",now())"; 
     $conn->Execute($sqlstr, "ERROR- 0022");	
     echo "<center><b><font color=green size=6>Transaction No ".$_REQUEST['transno']." Claim Successfully";
     echo "<br>Total Winning Amount Rs. ".$TotalPrizeAmount."</font></b></center>";
}
?>


