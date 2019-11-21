<?php

namespace AT\Block\Form;

use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\DB\DB;

class AdminLicenceOverview extends LicenceOverview {
    protected $requiresAuthenticatedUser = true;
    protected $requiresAllGroups = ["admin"];
	
	public function getUsersCount($row){
		return '<a class="btn btn-xs btn-light btn-outline-primary" href="/Form_AdminLicenceUsers/licence='.$row["row"]["id2"].'">'.Language::get("Manage licence users").' ['.count(\AT\Classes\Licence::licenceUsersCount($row["row"]["id2"])).']</a>';
	}
    public function preProcess(){
        \AsyncWeb\View\MakeDBView::$repair  = true;
        $this->formSettings["col"][] = ["name"=>Language::get("User"),"data"=>array("col"=>"email"),"usage"=>array("MFi","MFu","DBVs","DBVe")];
        unset($this->formSettings["where"]);

        $this->formSettings["allowInsert"]=true;
        $this->formSettings["allowUpdate"]=true;
        $this->formSettings["allowDelete"]=true;
        $this->formSettings["useForms"]=true;
        $this->formSettings["rights"]=["insert"=>"admin","update"=>"admin","delete"=>"admin"];
        
        $this->formSettings["col"]["btn"]["filter"]["function"]="PHP::\\AT\\Block\\Form\\AdminLicenceOverview::getUsersCount()";
        $this->formSettings["col"]["customprice"] = array("name"=>Language::get("Custom price"),"data"=>array("col"=>"customprice"),"usage"=>array("MFi","MFu","DBVs","DBVe"));
        $this->formSettings["col"]["customperiod"] = array("name"=>Language::get("Custom period"),"data"=>array("col"=>"customperiod"),"usage"=>array("MFi","MFu","DBVs","DBVe"));
        //$this->formSettings["col"]["slackwebhooks"] = array("name"=>Language::get("Slack webhook"),"data"=>array("col"=>"slackwebhooks"),"usage"=>array("MFi","MFu"));
        
    }
    public function postProcess(){
        $this->template = '<h2>Admin prehľad licencí</h2>'.$this->template;
    }
}