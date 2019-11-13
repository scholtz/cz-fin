<?php

namespace AT\Block\Form;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\DB\DB;

class AdminCoupons extends \AsyncWeb\DefaultBlocks\Form{
    protected $requiresAuthenticatedUser = true;
    protected $requiresAllGroups = ["admin"];
    
    public function verifyRights(){
        
        if(!\AsyncWeb\Objects\Group::is_in_group("admin")){
            $this->template = '<div class="alert alert-danger">'.Language::get("You are not allowed to manage users for this licence").'</div>';
            $this->postProcess();
            return false;
        }
        
        return true;
    }
    public function initTemplate(){
        \AsyncWeb\View\MakeDBView::$repair = true;
		$this->formSettings = array(
            "table" => "fin_referal",
            "col" => array( 
               array("name"=>Language::get("Used"),"data"=>array("col"=>"used"),"usage"=>array("MFu","DBVs","DBVe")),
               array("name"=>Language::get("Code"),"data"=>array("col"=>"code"),"usage"=>array("MFi","MFu","DBVs","DBVe")),
               array("name"=>Language::get("User"),"data"=>array("col"=>"user"),"usage"=>array("MFi","MFu","DBVs","DBVe")),
               array("name"=>Language::get("Coupon Type"),"data"=>array("col"=>"type"),"filter"=>array("type"=>"option","option"=>array(
                  "full"=>Language::get("Full"),
                  "prenament"=>Language::get("Prenament"),
                  "week"=>Language::get("Week"),
                )),"usage"=>array("MFi","MFu","DBVs","DBVe")),
               array("name"=>Language::get("Licence Type"),"data"=>array("col"=>"licencetype"),"filter"=>array("type"=>"option","option"=>array(
                  "personal"=>Language::get("Fin PERSONAL"),
                  "premium"=>Language::get("Fin PREMIUM"),
                  "enterprise"=>Language::get("Fin ENTERPRISE"),
                )),"usage"=>array("MFi","MFu","DBVs","DBVe")),
                
                array("name"=>Language::get("Licence Start"),"data"=>array("col"=>"start","datatype"=>"date"),"usage"=>array("MFi","MFu","DBVs",
                "DBVe")),
                array("name"=>Language::get("Licence End"),"data"=>array("col"=>"end","datatype"=>"date"),"usage"=>array("MFi","MFu","DBVs","DBVe")),
                array("name"=>Language::get("Expires"),"data"=>array("col"=>"expire","datatype"=>"date"),"usage"=>array("MFi","MFu","DBVs","DBVe")),
               
                array("name"=>Language::get("Date"),"data"=>array("col"=>"created","datatype"=>"date"),"usage"=>array("DBVs","DBVe")),
            ),
            "texts"=>array("no_data"=>Language::get("You did not make any deposit to your account yet")),
             "bootstrap"=>"1",
             "where"=>array("users"=>Auth::userId()),
             "uid"=>"invoices",
             "show_export"=>true,
             "iter"=>array("per_page"=>"30"),
             "MakeDVView"=>5,
             "show_filter"=>true,
            "rights"=>array("insert"=>"admin","update"=>"admin","delete"=>"admin"),
			"allowInsert"=>true,"allowUpdate"=>true,"allowDelete"=>true,"useForms"=>true,
			        );
		if(\AsyncWeb\Objects\Group::is_in_group("admin")){
			$this->formSettings["where"] = array();
		}

		$this->initTemplateForm();
	}
}