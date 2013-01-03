<?php

class LotteryUtilities {

    function GetDrawDate($lotcode, $curdate, $connection) {
        if (!isset($connection))
            exit("Error-Invalid Connection found");

        $sqlstr = "SELECT drawdate,freq ,drawtime,curtime() AS curtime ,((to_days('" . $curdate . "'))-(to_days(drawdate))) AS diffdr FROM lotmas WHERE lotcode='" . $lotcode . "'";
        $result = $connection->Execute($sqlstr);

        if (mysql_num_rows($result) < 1)
            exit("Error-Invalid Lottery found");

        $row = mysql_fetch_assoc($result);
        if ($row["diffdr"] < 0)
            $givedrawdate = $row["drawdate"];
        elseif ($row["freq"] == "Weekly") {
            if ($row["diffdr"] == 0 && $row["drawtime"] < $row["curtime"])
                $givedrawdate = DateAdd($curdate, 7);
            else if (($row["diffdr"] % 7) == 0)
                $givedrawdate = $curdate;
            else
                $givedrawdate=DateAdd($curdate, 7 - ($row["diffdr"] % 7));
        }
        elseif (strtolower($row["freq"]) == "monthly" || strtolower($row["freq"]) == "bumper") {
            if ($row['curtime'] <  $row["drawtime"])
                $sqlstr = "SELECT drawdate FROM lotpara WHERE  lotcode='" . $lotcode . "' AND drawdate>='" . $curdate . "' ORDER BY drawdate LIMIT 1";
            else
                $sqlstr = "SELECT drawdate FROM lotpara WHERE  lotcode='" . $lotcode . "' AND drawdate>'" . $curdate . "' ORDER BY drawdate LIMIT 1 ";
            $resultlp = $connection->Execute($sqlstr);
            if (mysql_num_rows($resultlp) <= 0)
                //exit("Error-Rate Not define for " . $row["freq"] . " Lottery");
		return "NOTAVAILABLE";
            $rowlp = mysql_fetch_assoc($resultlp);
            return $rowlp['drawdate'];
        }
        return $givedrawdate;
    }

    function GetDrawNo($lotcode, $drawdate, $connection) {
        if (!isset($connection))
            exit("Error-Invalid Connection found");

        $sqlstr = "SELECT drawno,drawdate,freq,((to_days('" . $drawdate . "'))-(to_days(drawdate))) AS diffdr FROM lotmas WHERE lotcode='" . $lotcode . "'";
        $result = $connection->Execute($sqlstr);

        if (mysql_num_rows($result) < 1)
            exit("Error-Invalid Drawdate found");

        $row = mysql_fetch_array($result);
        if ($row["diffdr"] < 0)
            exit("Error-Invalid Draw No. found");

        if ($row["freq"] == "7 days a Week")
            return ((int) ($row["diffdr"] / 7) + $row["drawno"]);
        elseif ($row["freq"] == "Weekly" && $row["diffdr"] % 7 == 0)
            return (($row["diffdr"] / 7) + $row["drawno"]);
        elseif (strtolower($row["freq"]) == "monthly" || strtolower($row["freq"]) == "bumper") {
            if (LotteryUtilities::GetWeekNoOfMonth($drawdate) == LotteryUtilities::GetWeekNoOfMonth($row["drawdate"]) && date('l', strtotime($drawdate)) == date('l', strtotime($row["drawdate"])))
                return ($row["drawno"] + (date('n', strtotime($drawdate)) - date('n', strtotime($row["drawdate"]))));
            else
                exit("Error-Invalid Draw No. found ");
        }
        else
            exit("Error-Invalid Frequency found ");
    }

    function GetWeekNoOfMonth($date) {
        $BeginningOfMonth = mktime(0, 0, 0, date('m', strtotime($date)), 1, date('Y', strtotime($date)));
        $mydate = strtotime($date);
        $checkdate = strtotime($date);

        $checkdate = mktime(0, 0, 0, date('m', $checkdate), date('d', $checkdate) + 1, date('Y', $checkdate));
        $i = 0;

        while (date('l', $checkdate) != date('l', $BeginningOfMonth) && $i++ < 100) {
            $mydate = mktime(0, 0, 0, date('m', $mydate), date('d', $mydate) + 1, date('Y', $mydate));
            $checkdate = mktime(0, 0, 0, date('m', $checkdate), date('d', $checkdate) + 1, date('Y', $checkdate));
        }
//		echo "<br>". date('l', $checkdate)."  *  ".date('l', $mydate); 
        $noofdays = ceil((($mydate - $BeginningOfMonth) / (60 * 60 * 24)));
        return (int) ($noofdays / 7) + 1;
    }

    function GetMrp($lotcode, $drawno, $connection) {
        if (!isset($connection))
            exit("Error-Invalid Connection found");

        $sqlstr = "SELECT mrp FROM lotpara WHERE	drawno<=" . $drawno . " AND lotcode='" . $lotcode . "' ORDER BY drawno DESC LIMIT 1";
        $result = $connection->Execute($sqlstr);

        if (mysql_num_rows($result) < 1)
            exit("Error-Invalid Drawdate found");

        $row = mysql_fetch_array($result);
        return $row['mrp'];
    }

    function GetLotSufficxName($drawdate, $sevendays, $freq) {
        $dw = strftime("%u", strtotime($drawdate)) + 1;
        if ($freq == "7 days a Week") {
            $suff = explode(":", $sevendays);
            if ($dw == 1)
                return $suff[7];
            else
                return $suff[$dw - 1];
        }
        else
            return "";
    }

    function getSeriesArray($drawno, $lotcode, $connection) {

        $sqlstr = "SELECT series FROM lotpara WHERE lotcode='" . $lotcode . "' AND drawno<='" . $drawno . "' AND series is not null ORDER BY drawno DESC LIMIT 1";
        $result = $connection->Execute($sqlstr);
        if (mysql_num_rows($result) <= 0)
            exit("Error-Rate not found" . "<br>");
        $rowres = mysql_fetch_array($result);
        $series = $rowres['series'];
        if (strlen($series) < 3)
            exit("Error-Invalid Series Found");
        return split(":", substr($series, 1, -1));
    }

    function MatchResult($digitno, $qty, $Series , $resultarry) {
        $prizeAmt = 0;
        $bonusAmt = 0;
        $prizeno = 0;
        $mainstr = "";

        if (!is_array($resultarry))
            exit("#Error:Invalid Result data");
        if (count($resultarry) <= 0)
            exit("#Error:Result Not declear");

        if (strlen($digitno) < 4 || $qty <= 0 || $Series == "")
            exit("#Error:Invalid Data request for MatchResult");

        for ($j = 0; $j < count($resultarry); $j++) {
            if (strlen($digitno) >= 4 && $resultarry[$j]['MATCHDIGITS'] >= 4 && strlen($resultarry[$j]['RESULT_NO']) > 4) {
                if ($resultarry[$j]['SERIES'] >= 1 && strlen($Series) > 1 && substr_count($resultarry[$j]['RESULT_NO'], ":" . $Series . "-" . substr($digitno, -($resultarry[$j]['MATCHDIGITS'])) . ":") == 1) {
                    $prizeAmt += $resultarry[$j]['PRIZEAMT'];
                    $bonusAmt += $resultarry[$j]['RETAILERBONUS'];
                    $mainstr .= "<br><b>" . $digitno . "</b>  Prize No " . $resultarry[$j]['PRIZE'];
                    $prizeno = $resultarry[$j]['PRIZE'];
                    break;
                } else if (substr_count($resultarry[$j]['RESULT_NO'], ":" . substr($digitno, -($resultarry[$j]['MATCHDIGITS'])) . ":") == 1) {
                    $prizeAmt += $qty * $resultarry[$j]['PRIZEAMT'];
                    $bonusAmt += $qty * $resultarry[$j]['RETAILERBONUS'];
                    $mainstr .= "<br><b>" . $digitno . "</b>  Prize No " . $resultarry[$j]['PRIZE'] . " Quantity " . $qty;
                    $prizeno = $resultarry[$j]['PRIZE'];
                    break;
                }
            }
        }
        return array($prizeAmt, $bonusAmt, $prizeno, $mainstr);
    }

    function GetPadLength($lotcode, $drawdate, $connection) {
        $sqlstr = "SELECT drawdate as ddate,tktnoupto FROM schema_master WHERE lotcode='" . $lotcode . "' AND drawdate<='" . $drawdate . "' ORDER BY drawdate DESC LIMIT 1";
        $tempresult = $connection->Execute($sqlstr);
        if (mysql_num_rows($tempresult) <= 0)
            return;
        $temprow = mysql_fetch_assoc($tempresult);
        return strlen($temprow["tktnoupto"]);
    }

    function GetPrizeArray($drawdate, $lotcode, $connection) {
        $resultarry = array();
        $sqlstr = "SELECT prizeno,result_no,prizeamt,matchdigit 
                   FROM result r,schema_details d 
                   WHERE r.schdet_no=d.schdet_no AND  lotcode='" . $lotcode . "' AND drawdate='" . $drawdate . "' ORDER BY prizeno";
        $result = $connection->Execute($sqlstr);
        if (mysql_num_rows($result) <= 0)
            return $resultarry;
        while ($rowres = mysql_fetch_object($result)) {
            $resultarry[$rowres->prizeno]['RESULT_NO'] = $rowres->result_no;
            $resultarry[$rowres->prizeno]['PRIZE'] = $rowres->prizeno;
            $resultarry[$rowres->prizeno]['PRIZEAMT'] = $rowres->prizeamt;
            $resultarry[$rowres->prizeno]['MATCHDIGITS'] = $rowres->matchdigit;
        }
        return $resultarry;
    }


}

?>
