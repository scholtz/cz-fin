<?php
use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;

require_once("/cron2/settingsCZ.php");


Cron::start(24*3600);
echo "started ".date("c");

$basicwhere = ["spracovany"=>"0"];
$where = $basicwhere;
$work = true;
echo "\ncounting rows..";
$cc = 3279668;
$row = DB::qbr("data_firmy_ares02_webs",["cols"=>["c"=>"count(`id`)"],"where"=>$where]);
$cc = $row["c"];

$i=0;
while($work){  

//$where = ["id2"=>"974f47a432f53ea87a7d52fd5ac50a9b"];
  
$res = DB::qb("data_firmy_ares02_webs",[
    "limit"=>10000,
    "cols"=>["id","id2","data","web"],
    "where"=>$where,
    "order"=>["id"=>"asc"],
    ]);
$c = DB::num_rows($res);
//var_dump($c);
if(!$c) $work = false;
while($row=DB::f($res)){
    
    $i++;
    if($i%100==1) echo ".";
	if($i%10000==1) echo "\n$i/$cc/".date("c")."";
    
    $where = $basicwhere;
    $where[] = ["col"=>"id","op"=>"gt","value"=>$row["id"]];
    
	$data = gzuncompress($row["data"]);
    
    $doc = new DOMDocument();
    @$doc->loadXML($data);
    if(!$doc) return;
    $xpath = new DOMXpath($doc);
    if(!$xpath) return;
    $xpath->registerNamespace("D","/ares/xml_doc/schemas/ares/ares_datatypes/v_1.0.3");
    
    $fail = true;
    foreach($xpath->query("//D:E/D:ET") as $node){
        $fail = false;
        $ico = trim(strrchr($row["web"],"="),"=");
        //echo "$ico error:".$node->nodeValue;
        DB::u("data_firmy_ares02_webs_errors",$ico,["ico"=>$ico,"error"=>$node->nodeValue]);
    }
    foreach($xpath->query("//D:Vypis_RES") as $node){
        $icoNode = $xpath->query("D:ZAU/D:ICO",$node)->item(0);
        if(!$icoNode || !$icoNode->nodeValue){
            continue;
        }
        $fail = false;
        $ico = $icoNode->nodeValue;
        $update = [];
        if($tmpnode = $xpath->query("D:ZAU/D:PF/D:KPF",$node)->item(0)){
            $update["zau-pf-kpf"] = $tmpnode->nodeValue;
        }
        if($tmpnode = $xpath->query("D:SI",$node)->item(0)){
            foreach($tmpnode->childNodes as $siNode){
                if(substr($siNode->nodeName,0,2) != "D:") continue;
                $name = str_replace("D:","",$siNode->nodeName);
                $name = Texts::clear($name);
                $update["si-".$name] = trim($siNode->nodeValue);
            }
        }
        
        if($tmpnode = $xpath->query("D:ZUJ",$node)->item(0)){
            foreach($tmpnode->childNodes as $siNode){
                if(substr($siNode->nodeName,0,2) != "D:") continue;
                $name = str_replace("D:","",$siNode->nodeName);
                $name = Texts::clear($name);
                $update["zuj-".$name] = trim($siNode->nodeValue);
            }
        }
        
        if($tmpnode = $xpath->query("D:SU",$node)->item(0)){
            foreach($tmpnode->childNodes as $siNode){
                if(substr($siNode->nodeName,0,2) != "D:") continue;
                $name = str_replace("D:","",$siNode->nodeName);
                $name = Texts::clear($name);
                $update["su-".$name] = trim($siNode->nodeValue);
            }
        }
        
        DB::u("data_firmy_ares02_core",$ico,$update);
        
        if($tmpnode = $xpath->query("D:Nace",$node)->item(0)){
            $update =["ico"=>$ico];
            $id = md5($ico);
            foreach($tmpnode->childNodes as $siNode){
                if(substr($siNode->nodeName,0,2) != "D:") continue;
                $name = str_replace("D:","",$siNode->nodeName);
                $name = Texts::clear($name);
                $update[$name] = trim($siNode->nodeValue);
            }
            $id = md5($ico.$update["nace"]);

            DB::u("data_firmy_ares02_list_core",$id,$update);
        }
        
    }
    
    //var_dump(date("c"));exit;
    if($fail){
        DB::u("data_firmy_ares02_webs",$row["id2"],["err"=>"no-data","do"=>time()],false,false,false);
    }else{
        DB::u("data_firmy_ares02_webs",$row["id2"],["spracovany"=>"1"],false,false,false);
    }
}
}
Cron::end();
