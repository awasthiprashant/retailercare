<?	

	$remoteip=$REMOTE_ADDR;
        $remoteadd="http://192.168.4.9";

        $str2="";
        $str2=$remoteadd."/paperlotterypos/rpos_claimshow.php?number=".$number."&drawdate=".$drawdate;
        if($str2=="")
                exit("Error: Invalid Ticket to Process<br>");
 
        $str2 = str_replace(" ","%20",$str2);
        $fp = fopen($str2,'r');
        if(!$fp)
                 exit("Error-SYSTEM REDIRECTION FAILURE!");

         $response = "";
         $claimdata = "";
         while(!feof($fp))
         {
                $response = fgets($fp,4096);
                $claimdata .= $response;
         }
         exit($claimdata);

?>
