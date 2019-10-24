<?php

namespace AT\Block\Form;

use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\DB\DB;

class MonitorLiveCS extends \AsyncWeb\DefaultBlocks\Form{
    protected $requiresAuthenticatedUser = true;
    public $type = "CS";
	public function initTemplate(){
		$this->formSettings = array(
			"uid"=>"watchcs",
			"table" => "dev02.spravy_watch",
			"col" => array( 	
                array("name"=>"Language","form"=>array("type"=>"select",),"texts"=>array("default"=>"sk"),"data"=>array("col"=>"lang"),"usage"=>array("MFi","MFu","MFd","DBVs","DBVe"),"filter"=>array("type"=>"option","option"=>array(
				   "sk"=>"Slovak","cs"=>"Czech","en"=>"English"
				),),),/**/
//				array("name"=>"Clear-Text","data"=>array("col"=>"t_clear"),"usage"=>array("MFi","MFu","DBVs","DBVe")),
				array("name"=>"Text","data"=>array("col"=>"t_ci"),"usage"=>array("MFi","MFu","DBVs","DBVe")),
				array("name"=>"Email","data"=>array("col"=>"email"),"usage"=>array("MFi","MFu","DBVs","DBVe")),
			),
			"bootstrap"=>"1",
            "rights"=>array("insert"=>"","update"=>"","delete"=>""),
			"allowInsert"=>true,"allowUpdate"=>true,"allowDelete"=>true,"useForms"=>true,
			"iter"=>array("per_page"=>"30"),
			"MakeDVView"=>5,
            "where"=>[
                ["col"=>"-("],
                ["col"=>"t_clear","op"=>"is","value"=>null ],
                ["col"=>"-or"],
                ["col"=>"t_clear","op"=>"eq","value"=>"" ],
                ["col"=>"-)"],
                "email"=>\AsyncWeb\Objects\User::getEmailOrId()
            ],
        );

        $this->preProcess();
        $this->initTemplateForm();
        $this->postProcess();
        
		
	}
    public function preProcess(){
        
    }
    public function postProcess(){
        $this->template = '<h1>LIVE monitoring médií - CS</h1><p>Nastavení sledovaní výrazú s diakritikou a sensibilitou na veľké a malé písmená (case-sensitive)</p>'.$this->template;
    }
}