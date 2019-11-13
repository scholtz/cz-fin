<?php

namespace AT\Block\Form;

use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\DB\DB;

class HTML2RSS extends \AsyncWeb\DefaultBlocks\Form{
    protected $requiresAuthenticatedUser = true;
    protected $requiresAllGroups = ["admin"];
	public function initTemplate(){
        if(!\AsyncWeb\Objects\Group::is_in_group("admin")){
            return;
        }
        \AsyncWeb\View\MakeDBView::$repair = true;
		$this->formSettings = array(
			"uid"=>"html2rss",
			"table" => "dev02.config_html2rss",
			"col" => array( 	
                
				array("name"=>"Web","data"=>array("col"=>"web"),"usage"=>array("MFi","MFu","DBVs","DBVe")),
				array("name"=>"Base","data"=>array("col"=>"base"),"usage"=>array("MFi","MFu","DBVs","DBVe")),
				array("name"=>"Code","data"=>array("col"=>"code"),"usage"=>array("MFi","MFu","DBVs","DBVe")),
				array("name"=>"Iter rule","form"=>["type"=>"textarea"],"data"=>array("col"=>"rule_iter"),"usage"=>array("MFi","MFu")),
				array("name"=>"Link rule","form"=>["type"=>"textarea"],"data"=>array("col"=>"rule_link"),"usage"=>array("MFi","MFu")),
				array("name"=>"Perex rule","form"=>["type"=>"textarea"],"data"=>array("col"=>"rule_perex"),"usage"=>array("MFi","MFu")),
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