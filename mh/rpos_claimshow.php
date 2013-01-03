<?

if (!isset($_REQUEST['drawdate']) || !isset($_REQUEST['number']) || !isset($_REQUEST['lotcode']))
    exit("<br><br><center>Invalid Request</center>");

$numberarray = array();
$numbersdata = trim($_REQUEST['number']);
if (substr_count($numbersdata, " ") > 0)
    $numberarray = explode(" ", $numbersdata);
else
    $numberarray = explode("-", $numbersdata);
if (count($numberarray) != 2 || strlen($numbersdata) < 5)
     exit("<br><br><center>Invalid Ticker Number</center>");
$digitno = $numberarray[1];
$qty = 1;
$Series = $numberarray[0];
unset($numberarray);

require_once 'connection.php';
require_once 'LotteryUtilities.php';
require_once 'main.php3';

$conn = new Connection();
$resultarry = LotteryUtilities::GetPrizeArray($_REQUEST['drawdate'], $_REQUEST['lotcode'] , $conn);
list($prizeAmt, $bonusAmt, $prizeno, $mainstr) = LotteryUtilities::MatchResult( $digitno, $qty , $Series ,$resultarry );

if($prizeAmt <= 0)
     exit("<br><br><center><font size=5>No Winning Ticket</size></center>");
?>
<h3><center>View Ticket winning Information </center></h3>
<br>
<table border="0" cellspacing="1" cellpadding="4" bgcolor="#000000" align="center" width="80%">
    <tr bgcolor="#FFFFFF"><th><font size="5">Ticket Number</font></th>
        <td><font size="4"><?=$numbersdata?></font></td>
    </tr>
    <tr  bgcolor="#FFFFFF"><th><font size="5">Prize No</font></th>
        <td><font size="4"><?=$prizeno?></font></td>
    </tr>
    <tr  bgcolor="#FFFFFF"><th><font size="5">Prize Amount</font></th>
        <td><font size="4"><?=$prizeAmt?></font></td>
    </tr>        
</table>    
