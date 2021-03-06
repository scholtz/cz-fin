<?php

//https://wwwinfo.mfcr.cz/cgi-bin/ares/darv_res.cgi?ico=29025648&jazyk=cz&xml=1

use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;

require_once("/cron2/settingsCZ.php");


Cron::start(24*3600);
echo "started ".date("c");

/*
$res = DB::qb("data_arescz_company_core",["cols"=>["ico","clear"],"where"=>[["col"=>"datumvymazu","op"=>"is","value"=>null]]]);
while($row=DB::f($res)){
    
    
    
}
/**/

$c = 200000;
$init = 0;
$table = "data_firmy_ares02_webs";

//\AsyncWeb\DB\MysqliServer::$showlastquery = true;
echo "\ngetting res\n";
MyIter::$res = explode("\n",file_get_contents("ICO-list.csv"));
MyIter::$limit = count(MyIter::$res);

class MyIter{
	public static $iter = 0;
	public static $limit = 1000000;
	public static $res = null;
	private static $row = true;
	public static function get(){
		global $table;
        
        if(MyIter::$iter%1==0) echo ".";
		if(MyIter::$iter%100==0) echo MyIter::$iter." ".MyIter::$limit." ".date("c")."\n";
		MyIter::$iter++;

		MyIter::$row=trim(array_pop(MyIter::$res));
		if(!MyIter::$row) return null;
        if(strlen(MyIter::$row) != 8) return null;
        
        
		
		$ret = "http://wwwinfo.mfcr.cz/cgi-bin/ares/darv_res.cgi?jazyk=cz&xml=1&ico=".MyIter::$row;
		$adr=DB::qbr($table,array("where"=>array("id2"=>md5($ret)),"cols"=>array("checked")));
		Cron::requireFinish(true);
        if($adr) return null;
		if($adr && $adr["checked"] > time() - rand(15,120)*3600*24){ return null;}
		return $ret;
	}
	public static function work(){
		return count(MyIter::$res) > 0;
	}
	public static function spracuj(&$chinfo,&$text,&$err){
		global $table;
		$url = $chinfo["url"];
		Page::save($url,$text,$table,true,$chinfo,$err);
        file_put_contents("g01.html",$text);
		if(date("H")>= 8 && date("H") < 18){
            usleep(500000);
		}else{
            usleep(150000);
		}

	}
}


$ch = curl_init(); 
 $options = array
(
    CURLOPT_HEADER=>true,
    CURLOPT_RETURNTRANSFER=>true,
    CURLOPT_FOLLOWLOCATION=>false,
	CURLOPT_CONNECTTIMEOUT=>20,
	CURLOPT_TIMEOUT=>20,
	CURLOPT_USERAGENT=>"Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)",
	CURLOPT_ENCODING=>"gzip,deflate",
);
$interfaces = array(
array("adr"=>"92.240.235.194"),
array("adr"=>"92.240.235.195"),
array("adr"=>"92.240.235.196"),
array("adr"=>"92.240.235.197"),
array("adr"=>"92.240.235.198"),
array("adr"=>"92.240.235.199"),
array("adr"=>"92.240.235.200"),
array("adr"=>"92.240.235.201"),
array("adr"=>"92.240.235.202"),
array("adr"=>"92.240.235.203"),
/*array("adr"=>"92.240.235.204"),
array("adr"=>"92.240.235.205"),
array("adr"=>"92.240.235.206"),/**/
);

$mh = curl_multi_init();

$firma = 1;
$listen = array();
if(isset($interfaces)){
foreach($interfaces as $int){
 $ch = curl_init();
 $path = null;
 while($path == null && MyIter::work()){$path = MyIter::get();}
 $options[CURLOPT_URL] = $path;
 $options[CURLOPT_INTERFACE] = $int["adr"];
 curl_setopt_array($ch,$options);
 curl_multi_add_handle($mh,$ch);
 $listen[]=["handle"=>$ch,"options"=>$options];
}
}
do {
	curl_multi_select($mh,1);
	while (CURLM_CALL_MULTI_PERFORM == curl_multi_exec($mh, $running)) {}
	$info = curl_multi_info_read($mh);
	foreach($listen as $listener){
        $ch = $listener["handle"];
		if($ch===$info["handle"]){
			$html = curl_multi_getcontent($ch);
			$chinfo = curl_getinfo($ch);
            //$chinfo["interface"] = $listener["options"][CURLOPT_INTERFACE];
            //var_dump($chinfo);exit;
			$err = curl_error($ch);
			MyIter::spracuj($chinfo,$html,$err);
			curl_multi_remove_handle($mh, $ch);
			if(MyIter::work()){
				$path = null;
				while($path == null && MyIter::work()){$path = MyIter::get();}
				if(!$path) {$working=false;continue;}
				curl_setopt($ch,CURLOPT_URL,$path);
				curl_multi_add_handle($mh,$ch);
				$running = true;
			}
		}
	}
}while($running);

//echo "\narchiving $table ".date("c")."\n";
//$r = DB::clean("devcz","devczarchive",$table,"archiveObsolete",0);var_dump($r);
echo "done ".date("c")."\n";
 
Cron::end();

//include "/cron2/apps/cr-ares/s03-res.php";