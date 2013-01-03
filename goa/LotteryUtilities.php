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
        elseif (strtolower($row["freq"]) == "monthly" || strtolower($row["freq"]) == "bumper"  || strtolower($row["freq"]) == "fortnightly") {
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
			{
                $diff = LotteryUtilities::DateDiff($row["drawdate"],$drawdate);
                 return ($row["drawno"] + round($diff/30));
			}
            else
                exit("Error-Invalid Draw No. found ");

        }
	else if (strtolower($row["freq"]) == "fortnightly")
	{
		if(date('l', strtotime($drawdate)) != date('l', strtotime($row["drawdate"])))
			echo ("Error-Invalid Draw No. found");	

		 $weekNoofDrawdate = LotteryUtilities::GetWeekNoOfMonth($drawdate);
		 $weekNoofLotmas   = LotteryUtilities::GetWeekNoOfMonth($row["drawdate"]);
			
		if (  $weekNoofDrawdate == $weekNoofLotmas || $weekNoofDrawdate == $weekNoofLotmas + 2 )
	        {					
			$checkdate = $drawdate;        
			$tempdrawdate  = $row["drawdate"];
			$dncount = 0;
		    	$weekNo = 0;
			$month = date('m', strtotime($tempdrawdate ));  // 01
			while( $tempdrawdate  <=  $checkdate )
			{		
			   if($weekNo == $weekNoofLotmas  || $weekNo == $weekNoofLotmas + 2)
				$dncount++;

			   $tempdrawdate = LotteryUtilities::DateAdd($tempdrawdate,7);

			   if($month == date('m', strtotime($tempdrawdate)))
				   $weekNo++;						

			   if($month != date('m', strtotime($tempdrawdate)))
			   {
				$month = date('m', strtotime($tempdrawdate));
				$weekNo = 0;
			   }
			}
                	return  ( $row["drawno"] + $dncount) ;
	        }
		else
			exit ("Error-Invalid Draw No. found  2");
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

    function GetPrizeArray($drawdate, $lotcode, $connection) {
        $sqlstr = "SELECT prize,sch_no,result_no,prizeamt,matchdigits,chkseries,special,sp_prize_interv,noof_sp_prize FROM result r,schema_details d WHERE r.schdet_no=d.schdet_no AND  lotcode='" . $lotcode . "' AND drawdate='" . $drawdate . "' ORDER BY prize";
        $result = $connection->Execute($sqlstr);
        if (mysql_num_rows($result) <= 0)
            return false;

        $i = 0;
        while ($rowres = mysql_fetch_array($result)) {
            $specialres = LotteryUtilities::GenerateSpecialResult(substr($rowres['result_no'], 1, -1), $rowres['sp_prize_interv'], $rowres['noof_sp_prize']);
            if ($specialres != "")
                $resultarry[$i]['RESULT_NO'] = $specialres;
            else
                $resultarry[$i]['RESULT_NO'] = $rowres["result_no"];
            $resultarry[$i]['PRIZE'] = $rowres["prize"];
            $resultarry[$i]['PRIZEAMT'] = $rowres["prizeamt"];
            $resultarry[$i]['MATCHDIGITS'] = $rowres["matchdigits"];
            $resultarry[$i]['SERIES'] = $rowres["chkseries"];
            $resultarry[$i]['RETAILERBONUS'] = $rowres["special"];
            $i++;
        }
        return $resultarry;
    }

    function GenerateSpecialResult($resno, $prizeinvt, $noofprize) {
        if ($prizeinvt <= 0 || $noofprize <= 0)
            return "";
        $resultarray = array();
        for ($i = $resno; $i < $resno + $prizeinvt * $noofprize; $i+= $prizeinvt)
            $resultarray[] = $i;
        array_walk($resultarray, create_function('&$v', '$v = str_pad(substr($v,-4), 4, "0", STR_PAD_LEFT);'));
        array_unshift($resultarray, "");
        $resultarray[] = " ";
        return implode(":", $resultarray);
    }

    function GetPadLength($lotcode, $drawdate, $connection) {
        $sqlstr = "SELECT drawdate as ddate,tktnoupto FROM schema_master WHERE lotcode='" . $lotcode . "' AND drawdate<='" . $drawdate . "' ORDER BY drawdate DESC LIMIT 1";
        $tempresult = $connection->Execute($sqlstr);
        if (mysql_num_rows($tempresult) <= 0)
            return;
        $temprow = mysql_fetch_assoc($tempresult);
        return strlen($temprow["tktnoupto"]);
    }
    
    
     function CalculatePurchageRate($lotcode,$drawno,$agentcode,$connection,$type="Retailer")
	{	
        	$dno=0;
		$sqlstr="SELECT max(drawno) as maxdr from lotpara where drawno<=".$drawno." and lotcode='".$lotcode."'";
        	$result = $connection->Execute($sqlstr);
		$row=mysql_fetch_array($result);
		$dno=$row["maxdr"];          
 
		$maxdrawno =0;
    		$sqlstr="SELECT MAX(drawno) AS dno FROM partyrate WHERE drawno<=".$drawno." AND lotcode='".$lotcode."' AND agentcode='".$agentcode."'";
		$result = $connection->Execute($sqlstr);
		$row=mysql_fetch_array($result);
		if(mysql_num_rows($result)>0 && $row["dno"] !="" && strtoupper($row["dno"])!="NULL")
		{
			$maxdrawno = $row["dno"];
			$sqlstr= "SELECT purrate FROM partyrate WHERE drawno=".$maxdrawno." and lotcode='".$lotcode."' AND agentcode='".$agentcode."'";
			$result = $connection->Execute($sqlstr);
			$row=mysql_fetch_array($result);
			return $row["purrate"];
		}
		else
		{
			$maxdrawno =0;
			$sqlstr=" SELECT max(drawno) as maxdr from lotpara where drawno<=".$drawno." and lotcode='".$lotcode."'";
			$result = $connection->Execute($sqlstr);
			If (mysql_num_rows($result)<=0)
				return -1;
			$row=mysql_fetch_array($result);
			$maxdrawno = $row["maxdr"];
			$sqlstr= "SELECT salrate,agentsalerate FROM lotpara WHERE drawno=".$maxdrawno." and lotcode='".$lotcode."'";
			$result = $connection->Execute($sqlstr);
			$row=mysql_fetch_array($result);
			if($type == "Retailer")
				return $row["salrate"];
			else
				return $row["agentsalerate"];			
		}
	}


	function DateAdd($fdate,$nofodays)
	{
		if(ereg("([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})",$fdate,$regs) && LotteryUtilities::IsDate($fdate)&&is_numeric($nofodays))
			return date("Y-m-d",mktime(0,0,0,$regs[2],$regs[3]+$nofodays,$regs[1]));
		return false;		
	}

	function IsDate(&$fdate)
	{
		$fdate=trim($fdate);		

		if(ereg("([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})", $fdate, $regs))
			return checkdate($regs[2],$regs[3],$regs[1]);
		else
			return false;
	}

	Function DateDiff($date1,$date2)
        {
                //Used to calculate the difference between the two dates
                $days;
                if(ereg("([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})", $date1, $regs1 ))
                {
                        $d1=$regs1[3];
                        $m1=$regs1[2];
                        $y1=$regs1[1];
                }

                if(ereg("([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})", $date2, $regs2 ))
                {
                        $d2=$regs2[3];
                        $m2=$regs2[2];
                        $y2=$regs2[1];
                }

                $time1=mktime(0,0,0,$m1,$d1,$y1,1);
                $time2=mktime(0,0,0,$m2,$d2,$y2,1);
                $days=$time2-$time1;
                $days=$days/(60*60*24);
                if(($time1=="-1") || ($time2=="-1"))
                        $days=-1;
                return $days;
        }

}

?>
