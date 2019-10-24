<?php

namespace AT\Block\Form;

use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\DB\DB;
use AsyncWeb\Frontend\URLParser;

class UserSettings extends \AsyncWeb\DefaultBlocks\Form{
	protected $showType = "UPDATE2";
	
	public function getInvoiceLink($row){
		return '<a href="/Content_Cat:Invoice/inv='.$row["row"]["id2"].'">'.$row["row"]["myinvoice"].'</a>';
	}
	public static function beforeInsert(){
		if(URLParser::v("settings_vat")){
			if(($err=\AsyncWeb\Connectors\VAT::verify(URLParser::v("settings_vat")))!==true){
				throw new Exception($err);
			}
		}
		return true;
	}
	public static function onInsert($r){
		$row = $r["row"];
		if($row["vat"] && "CZ" != substr($row["vat"],0,2)){
			DB::u("usersettings",$row["id2"],array("vatrate"=>VAT_RATE_ZERO));
		}else{
			DB::u("usersettings",$row["id2"],array("vatrate"=>VAT_RATE_STANDARD));
		}
	}
	public function initTemplate(){
        if(!Auth::userId()){
            $this->template = "<h1>Unathenticated</h1>";
            return;
        }
		$this->formSettings =         $data2 = array(
			"table" => "users",
            "uid" => "settings",             
			"col"=>array(
				array("name" => Language::get("Email"),  "editable"=>false,"data" => array("col" => "email" ), "usage" => array("MFi", "MFu", "MFd", "DBVs", "DBVe")), 
				array("name" => Language::get("First name"), "data" => array("col" => "firstname", "validation" => array("Modules" => "required")), "usage" => array("MFi", "MFu", "MFd", "DBVs", "DBVe")), 
				array("name" => Language::get("Last name"), "data" => array("col" => "lastname", "validation" => array("Modules" => "required")), "usage" => array("MFi", "MFu", "MFd", "DBVs", "DBVe")), 
				array("name" => Language::get("Mobile"), "data" => array("col" => "mobile"), "usage" => array("MFi", "MFu", "MFd", "DBVs", "DBVe")), 
				array("name"=>Language::get("Language"),"form"=>array("type"=>"select",),"texts"=>array("default"=>"en-US"),"data"=>array("col"=>"locale"),"usage"=>array("MFi","MFu","MFd","DBVs","DBVe"),"filter"=>array("type"=>"option","option"=>array(
                   "en-US"=>"English language","cs-CZ"=>"Czech language","sk-SK"=>"Slovak language"
				),),),/**/
				array("form" => array("type" => "submit"), "texts" => array("insert" => "Update your profile", "update" => "Update your profile"), "usage" => array("MFi", "MFu", "MFd")), 
			),
			"texts" => array("insertSuccess" => Language::get("Thank you for filling basic account information"), "updateSuccess" => Language::get("Thank you for filling basic account information")), 
            "bootstrap" => "1", 
			"where" => array(
                "id2" => Auth::userId(),
                //"email"=>\AsyncWeb\Objects\User::getEmailOrId(),
                //"login"=>\AsyncWeb\Objects\User::getEmailOrId()
                ), 
                
                "show_export" => true, "allowInsert" => true, "allowUpdate" => true, "allowDelete" => false, "useForms" => true, "iter" => array("per_page" => "30"), "MakeDVView" => 5, "show_filter" => true,
			);

		$row = DB::gr("users", array("id2" => Auth::userId()));
		$_REQUEST["settings___ID"] = $row["id"];

		$this->initTemplateForm();
        $this->header();
	}
    private function header(){
        $this->template = '<div class="container"><h1>Uživatelské nastavení</h1>'.$this->template.'</div>';

    }
}