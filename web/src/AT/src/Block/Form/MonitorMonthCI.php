<?php

namespace AT\Block\Form;

use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\DB\DB;

class MonitorMonthCI extends \AsyncWeb\DefaultBlocks\Form{
    protected $requiresAuthenticatedUser = true;
    public function beforeInsert(){
        if(!\AsyncWeb\Objects\Group::is_in_group("admin")){
            $available = \AT\Classes\Licence::availableMonitorsMonthForUser();
            $current = \AT\Classes\Licence::currentUsageMonitorMonth();
            if($current >= $available){ 
                throw new \Exception(Language::get("You have used all available (%available%) monthly monitors for your licences.",["%available%"=>$available]));
            }
        }
        return true;
    }
    public function onInsert($r){
        $row = $r["new"];
        DB::u("dev02.spravy_watch_month",$row["id2"],["t_clear"=>\AsyncWeb\Text\Texts::clear($row["t_ci"])]);
    }
    public function onUpdate($r){
        $row = $r["new"];
        DB::u("dev02.spravy_watch_month",$row["id2"],["t_clear"=>\AsyncWeb\Text\Texts::clear($row["t_ci"])]);
        
    }
	public function initTemplate(){
        \AsyncWeb\View\MakeDBView::$repair = true;
		$this->formSettings = array(
			"uid"=>"watchci",
			"table" => "dev02.spravy_watch_month",
			"col" => array( 	
                array("name"=>"Language","form"=>array("type"=>"select",),"texts"=>array("default"=>"sk"),"data"=>array("col"=>"lang"),"usage"=>array("MFi","MFu","MFd","DBVs","DBVe"),"filter"=>array("type"=>"option","option"=>array(
				   "sk"=>"Slovak","cs"=>"Czech","en"=>"English"
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
                "beforeInsert"=>"PHP::\\AT\\Block\\Form\\MonitorMonthCI::beforeInsert()", // check licence
                "onInsert"=>"PHP::\\AT\\Block\\Form\\MonitorMonthCI::onInsert()", // clear text
                "onUpdate"=>"PHP::\\AT\\Block\\Form\\MonitorMonthCI::onUpdate()", // clear text
                ),
        );

        $this->preProcess();
        $this->initTemplateForm();
        $this->postProcess();
        
		
	}
    public function preProcess(){
        
    }
    public function postProcess(){
        $this->template = '<h1>Monitoring médií - měseční report</h1><p>Nastavení sledovaní výrazú bez diakritiky a bez sensibility na veľké a malé písmená (case-insensitive). O 0:00 prvý den v měsící Vám vygenerujeme report o novinových článkoch ktoré obsahujú Vami vybrané výrazy za predchádzející měsíc.</p>'.$this->template;
    }
}