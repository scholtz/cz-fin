<?php

namespace AT\Block\Form;

use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\DB\DB;

class Invoice extends \AsyncWeb\DefaultBlocks\Form{
    protected $requiresAuthenticatedUser = true;
	public function getInvoiceLink($row){
		return '<a href="/Form_Invoice/inv='.$row["row"]["id2"].'">'.$row["row"]["myinvoice"].'</a>';
	}
	public function initTemplate(){
		$this->formSettings = array(
            "table" => "deposits",
            "col" => array( 
                array("name"=>Language::get("Date"),"data"=>array("col"=>"created","datatype"=>"date"),"usage"=>array("DBVs","DBVe")),
                array("name"=>Language::get("Invoice"),"data"=>array("col"=>"myinvoice"),"filter"=>array("type"=>"php","function"=>"PHP::\\AT\\Block\\Form\\Invoice::getInvoiceLink()"),"usage"=>array("DBVs","DBVe")),
                array("name"=>Language::get("Order"),"data"=>array("col"=>"invoice"),"usage"=>array("DBVs","DBVe")),
                array("name"=>Language::get("Price incl VAT"),"data"=>array("col"=>"mc_gross"),"usage"=>array("DBVs","DBVe")),
                array("name"=>Language::get("VAT"),"data"=>array("col"=>"tax"),"usage"=>array("DBVs","DBVe")),
                array("name"=>Language::get("Currency"),"data"=>array("col"=>"mc_currency"),"usage"=>array("DBVs","DBVe")),
                array("name"=>Language::get("Credits"),"data"=>array("col"=>"credits"),"usage"=>array("DBVs","DBVe")),
            ),
            "texts"=>array("no_data"=>Language::get("You did not make any deposit to your account yet")),
             "bootstrap"=>"1",
             "where"=>array("users"=>Auth::userId()),
             "uid"=>"invoices",
             "show_export"=>true,
             "iter"=>array("per_page"=>"30"),
             "MakeDVView"=>5,
             "show_filter"=>true,
        );
		if(\AsyncWeb\Objects\Group::is_in_group("admin")){
			$this->formSettings["where"] = array();
		}

		$this->initTemplateForm();
	}
}