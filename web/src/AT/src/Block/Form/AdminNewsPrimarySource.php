<?php

namespace AT\Block\Form;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\DB\DB;

class AdminNewsPrimarySource extends \AsyncWeb\DefaultBlocks\Form{
    protected $requiresAuthenticatedUser = true;
    protected $requiresAllGroups = ["admin"];
    
    public function initTemplate(){
        \AsyncWeb\View\MakeDBView::$repair = true;
		$this->formSettings = array(
            "uid"=>"news_primary_source",
            "table" => "dev02.news_primary_source",
            "col" => array( 
               array("name"=>Language::get("Source"),"data"=>array("col"=>"source"),"usage"=>array("MFi","MFu","DBVs","DBVe")),
               array("name"=>Language::get("Enabled"),"data"=>array("col"=>"enabled"),"usage"=>array("MFi","MFu","DBVs","DBVe")),
               array("name"=>Language::get("Refresh rate [s]"),"data"=>array("col"=>"refreshrate"),"usage"=>array("MFi","MFu","DBVs","DBVe")),
               array("name"=>Language::get("Language"),"data"=>array("col"=>"language"),"usage"=>array("MFi","MFu","DBVs","DBVe")),
               array("name"=>Language::get("Force HTTPS"),"data"=>array("col"=>"https"),"usage"=>array("MFi","MFu","DBVs","DBVe")),
               array("name"=>Language::get("Check follow location"),"data"=>array("col"=>"followlocation"),"usage"=>array("MFi","MFu","DBVs","DBVe")),
               array("name"=>Language::get("Last download"),"data"=>array("col"=>"checked","datatype"=>"date"),"usage"=>array("DBVs","DBVe")),
               array("name"=>Language::get("Last update"),"data"=>array("col"=>"od","datatype"=>"date"),"usage"=>array("DBVs","DBVe")),
            ),
            "texts"=>array("no_data"=>Language::get("You did not make any deposit to your account yet")),
            "order"=>["od"=>"desc"],
            "bootstrap"=>"1",
             "show_export"=>true,
             "iter"=>array("per_page"=>"100"),
             "MakeDVView"=>5,
             "show_filter"=>true,
            "rights"=>array("insert"=>"admin","update"=>"admin","delete"=>"admin"),
			"allowInsert"=>true,"allowUpdate"=>true,"allowDelete"=>true,"useForms"=>true,
			        );

		$this->initTemplateForm();
	}
}