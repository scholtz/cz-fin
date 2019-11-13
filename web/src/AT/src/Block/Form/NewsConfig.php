<?php

namespace AT\Block\Form;

use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\DB\DB;

class NewsConfig extends \AsyncWeb\DefaultBlocks\Form{
    protected $requiresAuthenticatedUser = true;
    protected $requiresAllGroups = ["admin"];

	public function initTemplate(){
        if(!\AsyncWeb\Objects\Group::is_in_group("admin")){
            return;
        }
		$this->formSettings = array(
			"uid"=>"html2text",
			"table" => "dev02.config_html2text",
			"col" => array( 	
                
				array("name"=>"Web","data"=>array("col"=>"web"),"usage"=>array("MFi","MFu","DBVs","DBVe")),
				array("name"=>"Headline Rules","form"=>["type"=>"textarea"],"data"=>array("col"=>"headline"),"usage"=>array("MFi","MFu")),
				array("name"=>"Perex Rules","form"=>["type"=>"textarea"],"data"=>array("col"=>"perex"),"usage"=>array("MFi","MFu")),
				array("name"=>"Text Rules","form"=>["type"=>"textarea"],"data"=>array("col"=>"rules"),"usage"=>array("MFi","MFu")),
				array("name"=>"RemoveBeforeProcessing", "form"=>["type"=>"textarea"],"data"=>array("col"=>"preprocessor"),"usage"=>array("MFi","MFu")),
				array("name"=>"LastChange","data"=>array("col"=>"od"),"filter"=>array("type"=>"date","format"=>"c"),"usage"=>array("DBVs","DBVe")),
			),
            "order"=>array("od"=>"desc"),
			"bootstrap"=>"1",
            "rights"=>array("insert"=>"admin","update"=>"admin","delete"=>"admin"),
			"allowInsert"=>true,"allowUpdate"=>true,"allowDelete"=>true,"useForms"=>true,
			"iter"=>array("per_page"=>"100"),
			"MakeDVView"=>5,
        );
        
		$this->initTemplateForm();
        $this->template = '<h1>Konfigurátor nastavenia médií</h1>'.$this->template;
	}
}