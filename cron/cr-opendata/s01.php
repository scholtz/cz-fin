<?php
use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;

require_once("/cron2/settingsCZ.php");


Cron::start(24*3600);
echo "started ".date("c");

$webstable = "data_opendata_data_webs";

$basicwhere = ["spracovany"=>"0"];
$where = $basicwhere;
$work = true;
echo "\ncounting rows..";
$cc = 3279668;
$row = DB::qbr($webstable,["cols"=>["c"=>"count(`id`)"],"where"=>$where]);
$cc = $row["c"];

$i=0;

$renameInvoiceColumns["dodavatel-ico"] = "ico";
$renameInvoiceColumns["ico-esu"] = "ico";
$renameInvoiceColumns["icdod"] = "ico";

$renameInvoiceColumns["cislofa"] = "cislo-faktury";
$renameInvoiceColumns["cislo-faktury"] = "cislo-faktury";
$renameInvoiceColumns["ac-ag"] = "cislo-faktury";
$renameInvoiceColumns["cislofakturydodavatele"] = "cislo-faktury";

$renameInvoiceColumns["typ-transakce"] = "typ";
$renameInvoiceColumns["zkr-typ"] = "typ";

$renameInvoiceColumns["vs"] = "variabilni-symbol";
$renameInvoiceColumns["variabilnisymbol"] = "variabilni-symbol";

$renameInvoiceColumns["c-dok"] = "castka-s-dph";
$renameInvoiceColumns["celkovacastka"] = "castka-s-dph";
$renameInvoiceColumns["c-mena"] = "celkova-castka-cizi-mena";
$renameInvoiceColumns["celkovacastkacizimena"] = "celkova-castka-cizi-mena";


$renameInvoiceColumns["typdokladu"] = "typ";
$renameInvoiceColumns["ucelplatby"] = "ucel-platby";

$renameInvoiceColumns["dat-uhr"] = "datum-uhrady";
$renameInvoiceColumns["dat-spl"] = "datum-splatnosti";
$renameInvoiceColumns["dat-vyst"] = "datum-vystaveni";
$renameInvoiceColumns["dat-uup"] = "datum-plneni";

$renameInvoiceColumns["datsplat"] = "datum-splatnosti";
$renameInvoiceColumns["datvyst"] = "datum-vystaveni";


$renameInvoiceColumns["datumuhrady"] = "datum-uhrady";
$renameInvoiceColumns["datumsplatnosti"] = "datum-splatnosti";
$renameInvoiceColumns["datumprijeti"] = "datum-prijeti";
$renameInvoiceColumns["datumvystaveni"] = "datum-vystaveni";
$renameInvoiceColumns["datumplneni"] = "datum-plneni";

$renameInvoiceColumns["ac-sml"] = "identifikator-smlouvy";
$renameInvoiceColumns["cislo-smlouvy"] = "identifikator-smlouvy";

$renameInvoiceColumns["kapitola"] = "kapitola-rozpoctu";
$renameInvoiceColumns["nazevkapitoly"] = "nazev-kapitoly";

$renameInvoiceColumns["ucelplatby"] = "ucel-platby";
$renameInvoiceColumns["popis-faktury"] = "ucel-platby";
$renameInvoiceColumns["ucel-tx"] = "ucel-platby";
	

$handlersInvoices["ico"]=function($data){
    $data["ico"] = str_pad($data["ico"],8,"0",STR_PAD_LEFT);
    $data["data-ico-clear"] = Texts::clear($data["ico"]);
    return $data;
};
$handlersInvoices["castka"]=function($data){
    if(!$data["castka-s-dph"]){
        $data["castka-s-dph"] = $data["castka"];
    }
    return $data;
};

$handlersInvoices["castka-s-dph"]=function($data){
    if(strlen($data["castka-s-dph"]) > 3){
        $data["data-castka-s-dph"] = floatval(cena($data["castka-s-dph"]));
    }else{
        $data["data-castka-s-dph"] = floatval($data["castka-s-dph"]);
    }
    return $data;
};

function cena($cena){
    $cena = str_replace(["\xc2\xa0","\xc3\x82"], '', $cena);

	if(substr($cena,-3,1) == ","){
		$cena = str_replace(".","",$cena);
		$cena = str_replace(",",".",$cena);
		$cena = str_replace(" ","",$cena);
		return $cena;
	}else if(substr($cena,-3,1) == "."){
		$cena = str_replace(",","",$cena);
		$cena = str_replace(" ","",$cena);
		return $cena;
	}else if(substr($cena,-2,1) == ","){
		$cena = str_replace(" ","",$cena);
		return str_replace(".","",$cena);
		$cena = str_replace(",",".",$cena);
	}else if(substr($cena,-2,1) == "."){
		$cena = str_replace(" ","",$cena);
		return str_replace(",","",$cena);
	}else{
		$cena = str_replace(" ","",$cena);
		$cena = str_replace(".","",$cena);
		return str_replace(",","",$cena);
	}
}

$config = [];

$config["cols"][$colname="data-castka-s-dph"]["before"] = 18;
$config["cols"][$colname]["after"] = 6;
$config["cols"][$colname]["type"] = "decimal";
$config["keys"][] = "data-ico-clear";


while($work){  

  
$res = DB::qb($webstable,[
    "limit"=>10000,
    "cols"=>["id","id2","web","data"],
    "where"=>$where,
    "order"=>["id"=>"asc"],
    ]);
$c = DB::num_rows($res);
if(!$c) $work = false;

while($row=DB::f($res)){
    
    $i++;
    if($i%100==1) echo ".";
	if($i%10000==1) echo "\n$i/$cc/".date("c")."";
    
    $where = $basicwhere;
    $where[] = ["col"=>"id","op"=>"gt","value"=>$row["id"]];
    
	$data = gzuncompress($row["data"]);
    if(strpos(strtolower($row["web"]),"polozky") !== false){
        echo "inv items\n";
        //AsyncWeb\Text\CSV2DB::process($data,"data_all_invoiceitems",["source"=>$row["web"]]);
//        DB::u($webstable,$row["id2"],["spracovany"=>"1"],false,false,false);
    }else if(strpos(strtolower($row["web"]),"fakt") !== false){
        echo "$i inv ".$row["web"]."\n";
        AsyncWeb\Text\CSV2DB::process($data,"data_all_invoices3",$config,["source"=>$row["web"]],$renameInvoiceColumns,$handlersInvoices);
    }
    
}
}
Cron::end();
