<?php

namespace AT\Block\Form;

use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\DB\DB;

class InvoiceSettings extends \AsyncWeb\DefaultBlocks\Form{
    protected $requiresAuthenticatedUser = true;
	protected $showType = "UPDATE2";
	public function getInvoiceLink($row){
		return '<a href="/Form_Invoice/inv='.$row["row"]["id2"].'">'.$row["row"]["myinvoice"].'</a>';
	}
	public function initTemplate(){
        if(\AsyncWeb\Objects\User::getEmailOrId()){
            $row = DB::gr("user_invoicedata",["email"=>\AsyncWeb\Objects\User::getEmailOrId()]);
            if(!$row){
                DB::u("user_invoicedata",md5(uniqid()),["name"=>\AsyncWeb\Objects\User::getEmailOrId(),"email"=>\AsyncWeb\Objects\User::getEmailOrId()]);
                $row = DB::gr("user_invoicedata",["email"=>\AsyncWeb\Objects\User::getEmailOrId()]);
            }
        }else{
            $this->header();
            return;
        }
        //var_dump($row);exit;
		$this->formSettings = array(
            "table" => "user_invoicedata",
			"uid"=>"invoicesettings",
			"bootstrap"=>true,
			"col" => array(
                array("name"=>Language::get("Název subjektu:"),"data"=>array("col"=>"name"),"usage"=>array("MFi","MFu","MFd","DBVs","DBVe")),
                array("name"=>Language::get("IČ:"),"data"=>array("col"=>"ico"),"usage"=>array("MFi","MFu","MFd","DBVs","DBVe")),
                array("name"=>Language::get("DIČ:"),"data"=>array("col"=>"dic"),"usage"=>array("MFi","MFu","MFd","DBVs","DBVe")),
                array("name"=>Language::get("Ulice 1:"),"data"=>array("col"=>"ulica"),"usage"=>array("MFi","MFu","MFd","DBVs","DBVe")),
                array("name"=>Language::get("Ulice 2:"),"data"=>array("col"=>"ulica2"),"usage"=>array("MFi","MFu","MFd","DBVs","DBVe")),
                array("name"=>Language::get("Město:"),"data"=>array("col"=>"mesto"),"usage"=>array("MFi","MFu","MFd","DBVs","DBVe")),
                array("name"=>Language::get("PSČ:"),"data"=>array("col"=>"psc"),"usage"=>array("MFi","MFu","MFd","DBVs","DBVe")),
                array("name"=>Language::get("Stát:"),"data"=>array("col"=>"stat"),"usage"=>array("MFi","MFu","MFd","DBVs","DBVe")),
                array("name"=>Language::get("IBAN:"),"data"=>array("col"=>"ucet"),"usage"=>array("MFi","MFu","MFd","DBVs","DBVe")),
                array("name"=>Language::get("BIC/SWIFT:"),"data"=>array("col"=>"bic"),"usage"=>array("MFi","MFu","MFd","DBVs","DBVe")),
			),
			"where"=>array(
                "email"=>\AsyncWeb\Objects\User::getEmailOrId(),
			),
			"order" => array(
                "od"=>"asc",
			 ),
			
			"show_export"=>false,"show_filter"=>false,

			"allowInsert"=>false,"allowUpdate"=>true,"allowDelete"=>false,"useForms"=>true,
		    "iter"=>array("per_page"=>"30"),
			"MakeDVView"=>5,
        );
        
		if(false && \AsyncWeb\Objects\Group::is_in_group("admin")){
            $this->showType = "ALL";
			$this->formSettings["where"] = array();
		}else{
            $_REQUEST["invoicesettings___ID"] = $row["id"];
        }
		$this->initTemplateForm();
        $this->header();
	}
    private function header(){
        $this->template = '<div class="container"><h1>Fakturační údaje</h1><p>Pomocí následujících údajů Vám vystavíme následující fakturu. Pokud si vyplníte údaje, můžeme Vám vystavit pro-forma fakturu před realizací úhrady.</p>'.$this->template.'</div>';

    }
    
}