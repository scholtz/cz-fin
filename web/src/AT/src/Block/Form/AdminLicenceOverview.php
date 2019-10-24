<?php

namespace AT\Block\Form;

use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\DB\DB;

class AdminLicenceOverview extends LicenceOverview {
	
	public function getUsersCount($row){
		return '<a class="btn btn-xs btn-light btn-outline-primary" href="/Form_AdminLicenceUsers/licence='.$row["row"]["id2"].'">'.Language::get("Manage licence users").' ['.count(\AT\Classes\Licence::licenceUsersCount($row["row"]["id2"])).']</a>';
	}
    public function preProcess(){
        $this->formSettings["col"][] = ["name"=>Language::get("User"),"data"=>array("col"=>"email"),"usage"=>array("MFi","MFu","DBVs","DBVe")];
        unset($this->formSettings["where"]);

        $this->formSettings["allowInsert"]=true;
        $this->formSettings["allowUpdate"]=true;
        $this->formSettings["allowDelete"]=true;
        $this->formSettings["useForms"]=true;
        $this->formSettings["rights"]=["insert"=>"admin","update"=>"admin","delete"=>"admin"];
        
        $this->formSettings["col"]["btn"]["filter"]["function"]="PHP::\\AT\\Block\\Form\\AdminLicenceOverview::getUsersCount()";
        
    }
    public function postProcess(){
        $this->template = '<h2>Admin prehľad licencí</h2>'.$this->template;
    }
}