<?php

namespace AT\Block\Form;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\DB\DB;

class Tickets extends \AsyncWeb\DefaultBlocks\Form{
    
    public function initTemplate(){
        \AsyncWeb\View\MakeDBView::$repair = true;
		$this->formSettings = array(
            "table" => "fin_ticket",
            "uid"=>"tickets", 
            "col" => array( 
               array("name"=>Language::get("Dátum vytvorenia"),"data"=>array("col"=>"created","datatype"=>"date"),"usage"=>array("DBVs","DBVe")),
               array("name"=>Language::get("Posledná zmena"),"data"=>array("col"=>"created","datatype"=>"date"),"usage"=>array("DBVs","DBVe")),
               array("name"=>Language::get("Stav"),"data"=>array("col"=>"type"),"filter"=>array("type"=>"option","option"=>array(
                  "new"=>Language::get("Nový"),
                  "assigned"=>Language::get("Priradený na riešenie"),
                  "closed"=>Language::get("Uzavretý"),
                )),"usage"=>array("DBVs","DBVe")),
               array("name"=>Language::get("Produkt"),"data"=>array("col"=>"related"),"filter"=>array("type"=>"option","option"=>array(
                  "no"=>Language::get("Bez určenia produktu"),
                  "firmy"=>Language::get("Katalóg firem"),
                  "datasety"=>Language::get("Fin DataSety"),
                  "personal"=>Language::get("Licencia Fin PERSONAL"),
                  "premium"=>Language::get("Licencia Fin PREMIUM"),
                  "enterprise"=>Language::get("Licencia Fin ENTERPRISE"),
                  "staznost"=>Language::get("Sťažnosť"),
                  "vylepsenie"=>Language::get("Návrh na vylepšenie"),
                )),"usage"=>array("MFi","MFu","DBVs","DBVe")),
                
               array("name"=>Language::get("Nadpis"),"data"=>array("col"=>"title"),"usage"=>array("MFi","MFu","DBVs","DBVe")),
               array("name"=>Language::get("Popis"),"form"=>["type"=>"textarea"],"data"=>array("col"=>"initmsg","datatype"=>"text"),"usage"=>array("MFi","MFu","DBVs","DBVe")),
            ),
            "texts"=>array("no_data"=>Language::get("Veľmi si cením spätnú väzbu. Napíšte nám..")),
             "bootstrap"=>"1",
             "where"=>array("email"=>\AsyncWeb\Objects\User::getEmailOrId()),
             "show_export"=>true,
             "iter"=>array("per_page"=>"30"),
             "MakeDVView"=>5,
             "show_filter"=>true,
            "rights"=>array("insert"=>"","update"=>"admin","delete"=>"admin"),
			"allowInsert"=>true,"allowUpdate"=>false,"allowDelete"=>false,"useForms"=>false,
        );
		$this->initTemplateForm();
	}
}