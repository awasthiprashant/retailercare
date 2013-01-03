<?

//exit("Error-Please Claim After Some Time ");
/*
 * $debug = 10;    For Backoffice View  
 * $debug = 11;    For Admin View
 */
// $_REQUEST['bcode']     = "FAFTABXG0075A";
// $_REQUEST['agentcode'] = "04177";


if (!isset($_REQUEST['agentcode']) || $_REQUEST['agentcode'] == "")
    exit("Invalid Request 1");

if (!isset($_REQUEST['bcode']) || strlen($_REQUEST['bcode']) < 13)
    exit("Please scan valid barcode");

$CLAIMDEBUG = 0;
if (isset($_REQUEST['debug']) && $_REQUEST['debug'] >= 10)
    $CLAIMDEBUG = $_REQUEST['debug'];


require_once 'connection.php';
require_once 'LotteryUtilities.php';
require_once 'main.php3';

$conn = new Connection();
$repconn = new Connection('REP');

$sqlstr = "SELECT block FROM agent_master WHERE agentcode='" . $_REQUEST['agentcode'] . "'";

/* @var $result callable */
$result = $conn->Execute($sqlstr, "ERROR- 0011");
if (mysql_num_rows($result) != 1)
    exit("Please Contact your Agent 0012");

$rowam = mysql_fetch_object($result);
mysql_free_result($result);
if ($rowam->block == 'Y' || is_null($rowam->block))
    exit("Please Contact your Agent 0013");

$dbconn = $conn;
$lsqlstr = "SELECT p.*,t.agentcode,t.dateofsale,t.drawdate,govtname, companey, lotname,drawtime,
        time_format(drawtime,'%h:%i %p') as dtime,dayofweek(p.transdate) as dw,sevendays,freq 
    FROM posconf p,lotmas l,postrans t 
    WHERE confcode='" . $_REQUEST['bcode'] . "' and l.lotcode=p.lotcode and t.drawdate=p.transdate and p.transno=t.transno and trstatus in ('S','P','X','U')";

$resultsale = $conn->Execute($lsqlstr, "Ticket Not Found 0014");
if (mysql_num_rows($resultsale) <= 0) { //Checking in the main Server
    //exit("Error-Please claim this ticket after some time");
    $resultsale = $repconn->Execute($lsqlstr, "Ticket Not Found 0015");
    if (mysql_num_rows($resultsale) > 0) { //Checking in the Report Server
        $dbconn = $repconn;
    }
    else
        exit("Ticket Not Sold");
}

$rowsale = mysql_fetch_object($resultsale);

if ($rowsale->drawdate <= DateAdd(date("Y-m-d"), -35))
    exit("Your Claimming period has been expire.");

if ($rowsale->agentcode <> $_REQUEST['agentcode'] && $CLAIMDEBUG == 0)
    exit("Ticket not Sold on this Counter.<br>");

if ($rowsale->trstatus == "X")
    exit("Ticket has been cancelled, can't be claimed.<br>");

if ($rowsale->trstatus == "P") {
    $Tlsqlstr = "SELECT prizeamt,date_format(claimdate,'%Y-%m-%d') AS cdate FROM claimprize where confcode='" . $_REQUEST['bcode'] . "'";
    $Tresult = $repconn->Execute($Tlsqlstr, "ERROR- 0016");
    if (mysql_num_rows($Tresult) > 0) {
        $Trow = mysql_fetch_object($Tresult);
        exit("Ticket Already Claimed<br>Prize Amount :" . $Trow->prizeamt . "<br>Claim Date :" . formatdate($Trow->cdate));
    }
    else
        exit("Ticket Already Claimed<br>");
}

if ($rowsale->series == "" || strlen($rowsale->series) < 2)
    exit("Invalid Series found");

$resultarry = LotteryUtilities::GetPrizeArray($rowsale->drawdate, $rowsale->lotcode, $conn);
if (!is_array($resultarry) || count($resultarry) < 2)
    exit("Result Not declear");

$GovtArray = Array("SNDGovt. of Sikkim" => 127,
    "PWGovt. of Sikkim" => 130,
    "SNDGovt. of Goa" => 128,
    "MYAGovt. of Nagaland" => 129,
    "SNDGovt. of Mizoram" => 133,
    "PWGovt. of Mizoram" => 137,
    "MYAGovt. of Mizoram" => 134);

$govtname = $rowsale->companey . $rowsale->govtname;
$govtname = array_key_exists($govtname, $GovtArray) ? $GovtArray[$govtname] : 0;

$drawSTR = "Series ".$rowsale->series."<br>";
$drawSTR .= LotteryUtilities::GetLotSufficxName($rowsale->dw, $rowsale->sevendays, $rowsale->freq) . ", ";
$drawSTR .= "Draw " . $rowsale->drawno . "  " . formatdate($rowsale->drawdate) . "  " . $rowsale->dtime . "<br>";
$drawSTR .= "Dateofsale " . formatdatetime($rowsale->dateofsale) . "<br>" . $_REQUEST['bcode'] . "<br>";

$sqlstr = "SELECT printdata ,quantity,digitno 
           FROM posdetails 
           WHERE confcode='" . $_REQUEST['bcode'] . "' AND transdate='" . $rowsale->drawdate . "'";
$resultdet = $dbconn->Execute($sqlstr, 'ERROR 0017');
if (mysql_num_rows($resultdet) <= 0)
    exit("Error-Unable to Claim  Ticket 2");

$mainstr = "";
$prizeAmt = 0;

while ($row = mysql_fetch_object($resultdet)) {
    $checkprize = false;
    foreach ($resultarry as $prizeno => $prizedata) {
        if (strlen($row->printdata) == 10 && strlen($prizedata['RESULT_NO']) > 10 
                && strlen($row->digitno) == 5 && strlen($rowsale->series) == 2
                && (($prizedata['RESULT_NO'] == ":" . $row->printdata . ":"  && $rowsale->series ==  $prizedata['LOTSERIES'])
                || substr_count($prizedata['RESULT_NO'], ":" . $rowsale->series . "-" . $row->digitno . ":") == 1
                )) {
            $mainstr .= "<b>" . str_replace("-", " ", $row->printdata) . "</b>  qty " . $row->quantity . "<br>";
            $prizeAmt += (int) $row->quantity * (double) $prizedata['PRIZEAMT'];
            $checkprize = true;
            break;
        }
    }
    if ($checkprize == false)
        $mainstr .= str_replace("-", " ", $row->printdata) . "  qty " . $row->quantity . "<br>";
}

if ($prizeAmt <= 0)
    exit("No Wining Ticket.<br>");


if ($prizeAmt > 0 && $prizeAmt <= 10000) {
    $sqlstr = "INSERT INTO claimprize VALUES('" . $_REQUEST['bcode'] . "',now(),'" . $_REQUEST['agentcode'] . "'," . $prizeAmt . ",'Y',0,'" . $govtname . "')";
    if ($CLAIMDEBUG == 0)
        $repconn->Execute($sqlstr, "Please claim this ticket after some time.");
    echo("Success<br>");
    echo("Prize amount Rs. " . $prizeAmt . "<br>");
    echo($drawSTR);
    echo($mainstr);

    if ($CLAIMDEBUG != 0)
        exit;

    $sqlstr = "UPDATE posconf SET trstatus='P' WHERE confcode='" . $_REQUEST['bcode'] . "'";
    $dbconn->Execute($sqlstr, "ERROR- 0018");

    $sqlstr = "UPDATE " . $conn->MainDatabase . ".Unclaim_details" . date('Ym', strtotime($rowsale->drawdate)) . " set trstatus='P' WHERE confcode='" . $_REQUEST['bcode'] . "' AND drawdate='" . $rowsale->drawdate . "' AND lotcode='" . $rowsale->lotcode . "'";
    $repconn->Execute($sqlstr, "ERROR- 0019");

    $sqlstr = "UPDATE " . $conn->MainDatabase . ".agent_master SET currentbal=currentbal-" . $prizeAmt . " WHERE agentcode='" . $_REQUEST['agentcode'] . "'";
    $conn->Execute($sqlstr, "ERROR- 0020");
} else if (($prizeAmt) > 10000) {
    echo($drawSTR);
    echo("<b>Ticket Should Be Claimed Through Head Office.</b><br>");
}
?>


