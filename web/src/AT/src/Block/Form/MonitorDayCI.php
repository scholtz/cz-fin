<?php

namespace AT\Block\Form;

use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\DB\DB;

class MonitorDayCI extends \AsyncWeb\DefaultBlocks\Form{
    protected $requiresAuthenticatedUser = true;
    public function beforeInsert(){
        if(!\AsyncWeb\Objects\Group::is_in_group("admin")){
            $available = \AT\Classes\Licence::availableMonitorsDayForUser();
            $current = \AT\Classes\Licence::currentUsageMonitorDay();
            if($current >= $available){
                throw new \Exception(Language::get("You have used all available (%available%) daily monitors for your licences.",["%available%"=>$available]));
            }
        }
        return true;
    }
    public function onInsert($r){
        $row = $r["new"];
        DB::u("dev02.spravy_watch_day",$row["id2"],["t_clear"=>\AsyncWeb\Text\Texts::clear($row["t_ci"])]);
    }
    public function onUpdate($r){
        $row = $r["new"];
        DB::u("dev02.spravy_watch_day",$row["id2"],["t_clear"=>\AsyncWeb\Text\Texts::clear($row["t_ci"])]);
        
    }
	public function initTemplate(){
        \AsyncWeb\View\MakeDBView::$repair = true;
		$this->formSettings = array(
			"uid"=>"watchci",
			"table" => "dev02.spravy_watch_day",
			"col" => array( 	
                array("name"=>"Filter Language","form"=>array("type"=>"select",),"texts"=>array("default"=>"all"),"data"=>array("col"=>"lang"),"usage"=>array("MFi","MFu","MFd","DBVs","DBVe"),"filter"=>array("type"=>"option","option"=>array(
				   "all"=>"Any language","sk"=>"Slovak","cs"=>"Czech","en"=>"English"
				),),),/**/
				//array("name"=>"Clear-Text","data"=>array("col"=>"t_clear"),"usage"=>array("DBVs","DBVe")),
				array("name"=>"Text","data"=>array("col"=>"t_ci"),"usage"=>array("MFi","MFu","DBVs","DBVe")),
				array("name"=>"Email","data"=>array("col"=>"email"),"usage"=>array("MFi","MFu","DBVs","DBVe")),
                "lic"=>array("name"=>Language::get("Licence"),"data"=>array("col"=>"licence"),"filter"=>array("type"=>"option","option"=>\AT\Classes\Licence::availableUserLicences()
                
                ),"usage"=>array("MFi","MFu","DBVs","DBVe")),
			),
			"bootstrap"=>"1",
            "rights"=>array("insert"=>"","update"=>"","delete"=>""),
			"allowInsert"=>true,"allowUpdate"=>true,"allowDelete"=>true,"useForms"=>true,
			"iter"=>array("per_page"=>"30"),
			"MakeDVView"=>5,
            "where"=>[
                "email"=>\AsyncWeb\Objects\User::getEmailOrId()
            ],
            "execute"=>array(
                "beforeInsert"=>"PHP::\\AT\\Block\\Form\\MonitorDayCI::beforeInsert()", // check licence
                "onInsert"=>"PHP::\\AT\\Block\\Form\\MonitorDayCI::onInsert()", // clear text
                "onUpdate"=>"PHP::\\AT\\Block\\Form\\MonitorDayCI::onUpdate()", // clear text
                ),
        );

        $this->preProcess();
        $this->initTemplateForm();
        $this->postProcess();
        
		
	}
    public function preProcess(){
        
    }
    public function postProcess(){
        $this->template = '<h1>Monitoring médií - denní report</h1><p>Nastavení sledování výrazů bez diakritiky a bez senzibility na velká a malá písmena (case-insensitive). O 8:00 ráno Vám vygenerujeme report o novinových článcích obsahujících Vámi vybrané výrazy.</p>'.$this->template;
    }
}