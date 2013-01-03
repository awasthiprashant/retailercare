<?php
class LotteryUtilities
{
	  function GetDrawNo($lotcode,$drawdate,$connection)
	{
		if(!isset($connection))
			exit( "Error-Invalid Connection found");

		$sqlstr="SELECT drawno,freq,((to_days('".$drawdate."'))-(to_days(drawdate))) AS diffdr FROM lotmas WHERE lotcode='".$lotcode."'";
		$result = $connection->Execute($sqlstr);

		if(mysql_num_rows($result) < 1 )
			exit("Error-Invalid Drawdate found");

		$row=mysql_fetch_array($result);
        	if($row["diffdr"] <0)
			exit("Error-Invalid Draw No. found");

		if ($row["freq"] == "7 days a Week")
			 return ((int)($row["diffdr"] / 7) + $row["drawno"]);
		elseif ($row["freq"] == "Weekly" && $row["diffdr"] % 7 ==0 )
			return (($row["diffdr"] / 7) + $row["drawno"]);
		else
				exit("Error-Invalid Frequency found");
	}


	  function GetMrp($lotcode,$drawno,$connection)
	{
		if(!isset($connection))
			exit( "Error-Invalid Connection found");

		$sqlstr= "SELECT mrp FROM lotpara WHERE	drawno<=".$drawno." AND lotcode='".$lotcode."' ORDER BY drawno DESC LIMIT 1";
		$result = $connection->Execute($sqlstr);

		if(mysql_num_rows($result) < 1 )
			exit("Error-Invalid Drawdate found");

		$row=mysql_fetch_array($result);
		return $row['mrp'];
	}


	 function GetLotSufficxName($drawdate,$sevendays,$freq)
	{
		$dw=strftime("%u",strtotime($drawdate))+1;
		if ($freq=="7 days a Week")
		{
			$suff=explode(":",$sevendays);
			if($dw==1)
				return $suff[7];
			else
				return $suff[$dw-1];
		}
		else
			return "";

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

	function GetPrizeArray($drawdate, $lotcode, $connection) {
	        $resultarry = array();
        	$sqlstr = "SELECT prizeno,result_no,prizeamt,matchdigit ,r.lotseries
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
	            $resultarry[$rowres->prizeno]['LOTSERIES'] = $rowres->lotseries;
        	}	
	        return $resultarry;
    }

}
?>
