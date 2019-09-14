<?php

use AsyncWeb\Text\Texts;

$rename = [];


$rename["smlouvy"]["icdod"] = "ico";
$handlers["smlouvy"]["ico"]=function($data){
    $data["data-ico-clear"] = Texts::clear(str_pad($data["ico"],8,"0",STR_PAD_LEFT));
    return $data;
};



$rename["faktury"]["dodavatel-ico"] = "ico";
$rename["faktury"]["ico-esu"] = "ico";
$rename["faktury"]["icdod"] = "ico";

$rename["faktury"]["cislofa"] = "cislo-faktury";
$rename["faktury"]["cislo-faktury"] = "cislo-faktury";
$rename["faktury"]["ac-ag"] = "cislo-faktury";
$rename["faktury"]["cislofakturydodavatele"] = "cislo-faktury";

$rename["faktury"]["typ-transakce"] = "typ";
$rename["faktury"]["zkr-typ"] = "typ";
$rename["faktury"]["typ-dokladu"] = "typ";


$rename["faktury"]["vs"] = "variabilni-symbol";
$rename["faktury"]["variabilnisymbol"] = "variabilni-symbol";

$rename["faktury"]["c-dok"] = "castka-s-dph";
$rename["faktury"]["celkovacastka"] = "castka-s-dph";
$rename["faktury"]["c-mena"] = "celkova-castka-cizi-mena";
$rename["faktury"]["celkovacastkacizimena"] = "celkova-castka-cizi-mena";


$rename["faktury"]["typdokladu"] = "typ";
$rename["faktury"]["ucelplatby"] = "ucel-platby";

$rename["faktury"]["dat-uhr"] = "datum-uhrady";
$rename["faktury"]["dat-spl"] = "datum-splatnosti";
$rename["faktury"]["dat-vyst"] = "datum-vystaveni";
$rename["faktury"]["dat-uup"] = "datum-plneni";

$rename["faktury"]["datsplat"] = "datum-splatnosti";
$rename["faktury"]["datvyst"] = "datum-vystaveni";


$rename["faktury"]["datumuhrady"] = "datum-uhrady";
$rename["faktury"]["datumsplatnosti"] = "datum-splatnosti";
$rename["faktury"]["datumprijeti"] = "datum-prijeti";
$rename["faktury"]["datumvystaveni"] = "datum-vystaveni";
$rename["faktury"]["datumplneni"] = "datum-plneni";

$rename["faktury"]["ac-sml"] = "identifikator-smlouvy";
$rename["faktury"]["cislo-smlouvy"] = "identifikator-smlouvy";

$rename["faktury"]["kapitola"] = "kapitola-rozpoctu";
$rename["faktury"]["nazevkapitoly"] = "nazev-kapitoly";
$rename["faktury"]["cislo-rozpoctove-polozky"] = "kapitola-rozpoctu";
	

$rename["faktury"]["ucelplatby"] = "ucel-platby";
$rename["faktury"]["popis-faktury"] = "ucel-platby";
$rename["faktury"]["ucel-tx"] = "ucel-platby";
$rename["faktury"]["popis"] = "ucel-platby";
	
$rename["faktury"]["nazev-dodavatele"] = "dodavatel";

    
	    

$handlers["faktury"]["ico"]=function($data){
    $data["data-ico-clear"] = Texts::clear(str_pad($data["ico"],8,"0",STR_PAD_LEFT));
    
    
    
    if(($time = strtotime($data["datum-vystaveni"])) > 0){
        $data["data-date"] = $time;
    }
    if(!$data["data-date"]){
        if(($time = strtotime($data["datum-prijeti"])) > 0){
            $data["data-date"] = $time;
        }
    }
    
    return $data;
    
};

$handlers["faktury"]["castka"]=function($data){
    if(!$data["castka-s-dph"]){
        $data["castka-s-dph"] = $data["castka"];
    }
    return $data;
};

$handlers["faktury"]["castka-s-dph"]=function($data){
    if(strlen($data["castka-s-dph"]) > 3){
        $data["data-castka-s-dph"] = floatval(cena($data["castka-s-dph"]));
    }else{
        $data["data-castka-s-dph"] = floatval($data["castka-s-dph"]);
    }
    return $data;
};




$config = [];

$config["faktury"]["cols"][$colname="data-castka-s-dph"]["before"] = 18;
$config["faktury"]["cols"][$colname]["after"] = 6;
$config["faktury"]["cols"][$colname]["type"] = "decimal";
$config["faktury"]["cols"][$colname="data-date"]["type"] = "int";
//$config["faktury"]["keys"][] = "data-ico-clear";






$rename["objednavky"]["datum-pripadu-dmr"] = "datum-objednani";
$rename["objednavky"]["datum-vystaveni"] = "datum-objednani";
$rename["objednavky"]["poznamka-1-255"] = "popis";
$rename["objednavky"]["predmet"] = "popis";
$rename["objednavky"]["nazev"] = "dodavatel";
$rename["objednavky"]["nazev-partnera"] = "dodavatel";

$rename["objednavky"]["hm-celkem"] = "celkova-castka";
$rename["objednavky"]["castka"] = "celkova-castka";




$handlers["objednavky"]["ico"]=function($data){
    $data["data-ico-clear"] = Texts::clear(str_pad($data["ico"],8,"0",STR_PAD_LEFT));
    
    if(($time = strtotime($data["datum-objednani"])) > 0){
        $data["data-date"] = $time;
    }
    
    if(strlen($data["celkova-castka"]) > 3){
        $data["data-celkova-castka"] = floatval(cena($data["celkova-castka"]));
    }else{
        $data["data-celkova-castka"] = floatval($data["celkova-castka"]);
    }
    
    return $data;
};


$handlers["pokuty"]["ico"]=function($data){
    $data["data-ico-clear"] = Texts::clear(str_pad($data["ico"],8,"0",STR_PAD_LEFT));
    
    if(($time = strtotime($data["datum-ukonceni-kontroly"])) > 0){
        $data["data-date"] = $time;
    }
    
    if(strlen($data["pokuta-v-kc"]) > 3){
        $data["data-pokuta-v-kc"] = floatval(cena($data["pokuta-v-kc"]));
    }else{
        $data["data-pokuta-v-kc"] = floatval($data["pokuta-v-kc"]);
    }
    
    return $data;
};


$handlers["prostory"]["ico-2-sml-strany"]=function($data){
    $data["data-ico-clear"] = Texts::clear(str_pad($data["ico-2-sml-strany"],8,"0",STR_PAD_LEFT));
    
    if(($time = strtotime($data["datum-podepsani-smlouvy"])) > 0){
        $data["data-date"] = $time;
    }
    
    
    return $data;
};


$handlers["rozhodnuti"]["ico"]=function($data){
    $data["data-ico-clear"] = Texts::clear(str_pad($data["ico"],8,"0",STR_PAD_LEFT));
    
    if(($time = strtotime($data["nabyti-pravni-moci-rozhodnuti"])) > 0){
        $data["data-date"] = $time;
    }
    
    
    return $data;
};

$handlers["setreni"]["ico-provozovatele"]=function($data){
    $data["data-ico-clear"] = Texts::clear(str_pad($data["ico-provozovatele"],8,"0",STR_PAD_LEFT));
    
    if(($time = strtotime($data["datum-uzavreni-setreni"])) > 0){
        $data["data-date"] = $time;
    }
    
    
    return $data;
};

$handlers["seznamy-podnikatelov"]["ico"]=function($data){
    $data["data-ico-clear"] = Texts::clear(str_pad($data["ico"],8,"0",STR_PAD_LEFT));
    
    if(($time = strtotime($data["platnost-od"])) > 0){
        $data["data-date"] = $time;
    }
    
    
    return $data;
};
$handlers["seznam-vladnich-instituci"]["ico"]=function($data){
    $data["data-ico-clear"] = Texts::clear(str_pad($data["ico"],8,"0",STR_PAD_LEFT));
    
    if(($time = strtotime($data["datum-vzniku"])) > 0){
        $data["data-date"] = $time;
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
