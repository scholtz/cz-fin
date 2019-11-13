<?php

namespace AT\Block\Form;

use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\DB\DB;

class MonitorLiveCI extends \AsyncWeb\DefaultBlocks\Form{
    protected $requiresAuthenticatedUser = true;
    public function beforeInsert(){
        if(!\AsyncWeb\Objects\Group::is_in_group("admin")){
            $available = \AT\Classes\Licence::availableMonitorsLiveForUser();
            $current = \AT\Classes\Licence::currentUsageMonitorLive();
            if($current >= $available){
                throw new \Exception(Language::get("You have used all available (%available%) live monitors for your licences.",["%available%"=>$available]));
            }
        }
        return true;
    }
    public function onInsert($r){
        $row = $r["new"];
        DB::u("dev02.spravy_watch",$row["id2"],["t_clear"=>\AsyncWeb\Text\Texts::clear($row["t_ci"])]);
    }
    public function onUpdate($r){
        $row = $r["new"];
        DB::u("dev02.spravy_watch",$row["id2"],["t_clear"=>\AsyncWeb\Text\Texts::clear($row["t_ci"])]);
        
    }
	public function initTemplate(){
        \AsyncWeb\View\MakeDBView::$repair = true;
		$this->formSettings = array(
			"uid"=>"watchci",
			"table" => "dev02.spravy_watch",
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
                ["col"=>"-("],
                ["col"=>"t_clear","op"=>"isnot","value"=>null ],
                ["col"=>"-and"],
                ["col"=>"t_clear","op"=>"neq","value"=>"" ],
                ["col"=>"-)"],
                "email"=>\AsyncWeb\Objects\User::getEmailOrId()
            ],
            "execute"=>array(
                "beforeInsert"=>"PHP::\\AT\\Block\\Form\\MonitorLiveCI::beforeInsert()", // check licence
                "onInsert"=>"PHP::\\AT\\Block\\Form\\MonitorLiveCI::onInsert()", // clear text
                "onUpdate"=>"PHP::\\AT\\Block\\Form\\MonitorLiveCI::onUpdate()", // clear text
                ),
        );

        $this->preProcess();
        $this->initTemplateForm();
        $this->postProcess();
        
		
	}
    public function preProcess(){
        
    }
    public function postProcess(){
        $this->template = '<h1>LIVE monitoring médií</h1><p>Nastavení sledovaní výrazú bez diakritiky a bez sensibility na veľké a malé písmená (case-insensitive). Ihneď keď zaregistrujeme použitý výraz v článku, pošleme Vám email s odkazom na daný článok. Články sú spravidla sprocesované do 15 minút od ich publikácie.</p>'.$this->template;
    }
}