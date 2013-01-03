<?php
class Connection
{
	var $Link;
	var $GameServerIP  = '192.168.3.13';
	var $GameLoginName = 'lottery';
	var $GamePassWord  = 'super1008';
	var $GameDatabase  = 'psfourdseries';	

	var $MainDatabase  = 'psmumbai';

	var $RepServerIP   = '192.168.4.17';
	var $RepLoginName  = 'lottery';
	var $RepPassWord   = 'super1008';
	var $RepDatabase   = 'psfourdseriesrep';	

	var $DnsServerIP   = "192.168.4.9";
	var $DnsLoginName  = "recharge";
	var $DnsDatabase   = "dns";

	var $RCareServerIP  = '192.168.4.17';
	var $RCareLoginName = 'lottery';
	var $RCarePassWord  = 'super1008';
	var $RCareDatabase  = 'retailercare'; 
	
	function Connection($conntype = "MAIN",$loginname="",$password="",$dbname="")
	{		
		if(trim($conntype)!= "" &&  trim($loginname)!= "" && trim($password)!="" && trim($dbname)!="" )
		{
			$this->Link = mysql_connect($conntype,$loginname, $password,true)
			or die('Error-Could not connect OTHER: ' . mysql_error());
			mysql_select_db($dbname,$this->Link) or die('Error-Could not select database '.$dbname);	
		}
		else if("MAIN" == strtoupper(trim($conntype)))
		{
			$this->Link = mysql_connect($this->GameServerIP, $this->GameLoginName, $this->GamePassWord,true) or die('Error-Could not connect MAIN: ' . mysql_error());
			mysql_select_db($this->GameDatabase,$this->Link) or die('Error-Could not select database MAIN');
		}
		else if("REP" == strtoupper(trim($conntype)))
		{
			$this->Link = mysql_connect($this->RepServerIP, $this->RepLoginName, $this->RepPassWord,true)
				or die('Error-Could not connect REP: ' . mysql_error());
			mysql_select_db($this->RepDatabase,$this->Link) or die('Error-Could not select database REP');
		}
		else if("DNS" == strtoupper(trim($conntype)))
		{
				$this->Link = mysql_connect($this->DnsServerIP, $this->DnsLoginName,  "super1008",true)
						or die('#Error:Could not connect MAIN: ' . mysql_error());
				mysql_select_db($this->DnsDatabase,$this->Link) or die('#Error:Could not select database MAIN');
		} 
		else if("RC" == strtoupper(trim($conntype)))
		{
				$this->Link = mysql_connect($this->RCareServerIP, $this->RCareLoginName,  $this->RCarePassWord,true)
						or die('#Error:Could not connect MAIN: ' . mysql_error());
				mysql_select_db($this->RCareDatabase,$this->Link) or die('#Error:Could not select database MAIN');
		}
		else
			exit('Error-Invalid connection type. ');		
	}

	function __destruct()
	{
		if($this->Link)
			mysql_close($this->Link);
	}

	function Execute($query,$errormsg="")
	{
		if("" == $query )
			return ("Query Empty.");
		$result = mysql_query($query,$this->Link) or die(trim($errormsg)!=""?$errormsg:'Error-Query failed: ' . mysql_error($this->Link));
		return $result;
	}
}


$db=new Connection();
$phppara="";
foreach($_POST as $key => $para)
        $phppara .= $key."=".urlencode($para)."&";
$query="INSERT INTO requestlog (reqmethod ,serverip ,remoteip ,requrl ,phppara ,reqtime) values('".$_SERVER["REQUEST_METHOD"]."','".$_SERVER["SERVER_ADDR"]."','".$_SERVER["REMOTE_ADDR"]."',\"http://".$_SERVER["SERVER_ADDR"].$_SERVER["REQUEST_URI"]."\",\"".$phppara."\",now())";
$db->Execute($query);

?>
