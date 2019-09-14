<?php
use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;

require_once("/cron2/settingsCZ.php");


Cron::start(24*3600);
echo "started ".date("c");

echo "processing ico sady..\n";

$res = DB::qb("data_opendata_meta_ico_core");
$i = 0;
$cc = DB::num_rows($res);
while($row=DB::f($res)){
    $i++;
    if($i % 100 == 0){echo ".";}
    if($i % 10000 == 0){echo $i."/$cc/".date("c")."\n";}
    
    $sada = DB::gr("data_opendata_sady_core",md5($row["datova-sada"]));
    
    if(!$sada){
        var_dump($row);
        continue;  
    }else{
//        echo "x";
    }
    
    $name = $sada["nazev"];
    $clear = Texts::clear($name);
    $context = [];
    if($row["count"] / $row["from"] < 0.2) continue;
    $poskytovatelClear = Texts::clear($sada["poskytovatel"]);
    
    if($poskytovatelClear == "cesky-telekomunikacni-urad" && strpos($clear,"podnikatele-v-") !== false){
        $context["context"] = "seznamy-podnikatelov";
    }else
    if($poskytovatelClear == "ceska-narodni-banka" && strpos($clear,"seznam") !== false){
        $context["context"] = "seznamy-podnikatelov";
    }else
    if(strpos($clear,"seznam-socialnich-sluzeb") !== false){
        $context["context"] = "seznamy-podnikatelov";
    }else
    if(strpos($clear,"registr-poskytovatelu") !== false){
        $context["context"] = "seznamy-podnikatelov";
    }else
    if(strpos($clear,"volebni-okrsky") !== false){
        $context["context"] = "volebni-okrsky";
    }else
    if(strpos($clear,"seznam-vladnich-instituci") !== false){
        $context["context"] = "seznam-vladnich-instituci";
    }elseif(strpos($clear,"ruian") !== false){
        $context["context"] = "adresy";
    }else
    if(strpos($clear,"skoly") !== false){
        $context["context"] = "skoly";
    }else
    if(strpos($clear,"materskych-skol") !== false){
        $context["context"] = "skoly";
    }else 
    if(strpos($clear,"skolach") !== false){
        $context["context"] = "skoly";
    }else
    if($row["column"] == "kod-adm"){
        $context["context"] = "adresy";
    }else
    if(strpos($row["odkazkestazeni"],"http://dataor.justice.cz") ===0){
        $context["context"] = "firmy";
    }else
    if(strpos($clear,"polozky") !== false){
        $context["context"] = "polozky";
    }else 
    if(strpos($clear,"kniha-dorucenych-faktur") !== false){
        $context["context"] = "faktury";
    }else
    if(strpos($clear,"faktury") !== false){
        $context["context"] = "faktury";
    }else
    if(strpos($clear,"prehled-faktur") !== false){
        $context["context"] = "faktury";
    }else
    if(strpos($clear,"seznam-uhrazenych-faktur") !== false){
        $context["context"] = "faktury";
    }else
    if(strpos($clear,"seznam-faktur") !== false){
        $context["context"] = "faktury";
    }else
    if(strpos($clear,"objednavek") !== false){
        $context["context"] = "objednavky";
    }else
    if(strpos($clear,"objednavky") !== false){
        $context["context"] = "objednavky";
    }else
    if(strpos($clear,"seznam-smluv") !== false){
        $context["context"] = "smlouvy";
    }else
    if(strpos($clear,"granty") !== false){
        $context["context"] = "granty";
    }else
    if(strpos($clear,"pokuty") !== false){
        $context["context"] = "pokuty";
    }else
    if(strpos($clear,"seznam-osob") !== false){
        $context["context"] = "seznam-osob";
    }else
    if(strpos($clear,"smlouvy") !== false){
        $context["context"] = "smlouvy";
    }else
    if(strpos($clear,"seznam-platnych-a-neplatnych-smluv") !== false){
        $context["context"] = "smlouvy";
    }else
    if(strpos($clear,"prehled-smluv") !== false){
        $context["context"] = "smlouvy";
    }else
    if(strpos($clear,"sportoviste") !== false){
        $context["context"] = "sport";
    }else
    if(strpos($clear,"kontroly") !== false){
        $context["context"] = "kontroly";
    }else
    if(strpos($clear,"kontrolovane-osoby") !== false){
        $context["context"] = "kontroly";
    }else
    if(strpos($clear,"rozhodnuti") !== false){
        $context["context"] = "rozhodnuti";
    }else
    if(strpos($clear,"prostory") !== false){
        $context["context"] = "prostory";
    }else
    if(strpos($clear,"setreni") !== false){
        $context["context"] = "setreni";
    }else
    if(strpos($clear,"odpad") !== false){
        $context["context"] = "odpad";
    }else
    {
        var_dump($row);
        var_dump($sada);
        var_dump($poskytovatelClear);
        exit;
    }
    
    DB::u("data_opendata_sady_core",$sada["id2"],$context);
}

Cron::end();
