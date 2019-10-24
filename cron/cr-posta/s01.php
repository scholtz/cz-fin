<?php

use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;

require_once("/cron2/settingsCZ.php");


Cron::start(24*3600);
echo "started ".date("c");

$name = "";
foreach(scandir(".") as $file){
    if(filesize($file) > 200000000){
        $name = $file;
    }
}
if(!$name){
    $cmpname = "";
    foreach(scandir(".") as $file){
        if(filesize($file) > 20000000 && filesize($file) < 50000000){
            //
            $cmpname = $file;
        }
    }
    if(!$cmpname){
        var_dump(shell_exec("wget https://www.mojedatovaschranka.cz/sds/datafile.do?format=xml\\&service=seznam_ds_po"));
        
        foreach(scandir(".") as $file){
            if(filesize($file) > 20000000 && filesize($file) < 50000000){
                //
                $cmpname = $file;
            }
        }
    }
    
    rename($cmpname,"data.xml.gz");
    $cmpname = "data.xml.gz";
    var_dump(shell_exec($cmd= "gzip -d -q \"$cmpname\""));
    var_dump($cmd);
    var_dump($cmpname);
    $name = "data.xml";
}

//
echo "idem spracovat $name\n";

$doc = new DOMDocument();
$doc->load($name);
echo "file loaded\n";
if(!$doc) exit;
$config = [];
$i = 0;
$cc = $doc->documentElement->childNodes->length;

foreach($doc->documentElement->childNodes as $node){
    
    $i++;
    if($i%100==1) echo ".";
	if($i%10000==1) echo "\n$i/$cc/".date("c")."";

    $update = [];
    if(!$node->childNodes) continue;
    foreach($node->childNodes as $child){
        if($child->childNodes && $child->childNodes->length == 1){
            $update[Texts::clear($child->nodeName)] = $child->nodeValue;
        }
    }
    $ico = $update["ico"];
    if(!$ico) continue;
    $update["idschranky"] = $update["id"];
    unset($update["id"]);
    DB::u("data_posta_datovaschranka",$update["idschranky"],$update,$config);
    $config = false;
}


Cron::end();
