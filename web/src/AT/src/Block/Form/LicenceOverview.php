<?php

namespace AT\Block\Form;

use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\DB\DB;

class LicenceOverview extends \AsyncWeb\DefaultBlocks\Form{
    protected $requiresAuthenticatedUser = true;
	public function getUsersCount($row){
        $managers = \AT\Classes\Licence::licenceUsersManagers($row["row"]["id2"]);
        if(isset($managers[\AsyncWeb\Objects\User::getEmailOrId()])){
            return '<a class="btn btn-xs btn-light btn-outline-primary" href="/Form_LicenceUsers/licence='.$row["row"]["id2"].'">'.Language::get("Manage licence users [%users%]",["%users%"=>count(\AT\Classes\Licence::licenceUsersCount($row["row"]["id2"]))]).'</a>';
        }else{
            return Language::get("Active users [%users%]",["%users%"=>count(\AT\Classes\Licence::licenceUsersCount($row["row"]["id2"]))]);
        }
        
	}
	public function initTemplate(){
        $licences = [];
		$this->formSettings = array(
            "uid"=>"licences",
            "table" => "fin_licences",
            "col" => array( 
                array("name"=>Language::get("Licence Name"),"data"=>array("col"=>"name"),"usage"=>array("MFi","MFu","DBVs","DBVe")),
                "btn"=>
                array("name"=>Language::get("Users count"),"virtual"=>true,"filter"=>array("type"=>"php","function"=>"PHP::\\AT\\Block\\Form\\LicenceOverview::getUsersCount()"),"usage"=>array("DBVs","DBVe")),
                array("name"=>Language::get("Licence Type"),"data"=>array("col"=>"type"),"filter"=>array("type"=>"option","option"=>array(
                  "personal"=>Language::get("Fin PERSONAL"),
                  "premium"=>Language::get("Fin PREMIUM"),
                  "enterprise"=>Language::get("Fin ENTERPRISE"),
                )),"usage"=>array("MFi","MFu","DBVs","DBVe")),
                array("name"=>Language::get("Licence Start"),"data"=>array("col"=>"start","datatype"=>"date"),"usage"=>array("MFi","MFu","DBVs","DBVe")),
                array("name"=>Language::get("Licence End"),"data"=>array("col"=>"end","datatype"=>"date"),"usage"=>array("MFi","MFu","DBVs","DBVe")),
                
                

            ),
            "texts"=>array("no_data"=>Language::get("You do not have any active licence yet")),
            "bootstrap"=>"1","MakeDVView"=>5,
            "iter"=>array("per_page"=>"30"),
        );
        $ids = \AT\Classes\Licence::availableUserLicencesIds();
        if($ids){
            $this->formSettings["where"] = [["col"=>"id2","op"=>"in","value"=>$ids]];
        }else{
            $this->formSettings["where"]["email"] = \AsyncWeb\Objects\User::getEmailOrId();
        }

        //var_dump(\AsyncWeb\Objects\User::getEmailOrId());
        $this->preProcess();
		$this->initTemplateForm();
        $this->postProcess();
	}
    public function preProcess(){
    }
    public function postProcess(){
        $this->template = '<h2>Historie vašich licencí</h2>'.$this->template;
        
    }
}