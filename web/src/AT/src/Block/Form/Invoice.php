<?php

namespace AT\Block\Form;

use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\DB\DB;

class Invoice extends \AsyncWeb\DefaultBlocks\Form{
    protected $requiresAuthenticatedUser = true;
	public function getInvoiceLink($row){
		return '<a href="/Invoice/type=html/inv='.$row["row"]["id2"].'">'.$row["row"]["invoicenumber"].'</a> ['.'<a href="/Invoice/type=pdf/inv='.$row["row"]["id2"].'">PDF</a>]';
	}
	public function initTemplate(){
        \AsyncWeb\View\MakeDBView::$repair = true;
		$this->formSettings = array(
            "table" => "fin_invoices",
            "col" => array( 
                array("name"=>Language::get("Invoice number"),"data"=>array("col"=>"invoicenumber"),"filter"=>array("type"=>"php","function"=>"PHP::\\AT\\Block\\Form\\Invoice::getInvoiceLink()"),"usage"=>array("DBVs","DBVe")),
                array("name"=>Language::get("Issue Date"),"data"=>array("col"=>"created","datatype"=>"date"),"usage"=>array("DBVs","DBVe")),
                array("name"=>Language::get("Pay Date"),"data"=>array("col"=>"datepaid","datatype"=>"date"),"usage"=>array("DBVs","DBVe")),
                array("name"=>Language::get("Order number"),"data"=>array("col"=>"ordernumber"),"usage"=>array("DBVs","DBVe")),
                array("name"=>Language::get("Specific symbol"),"data"=>array("col"=>"specificsymbol"),"usage"=>array("DBVs","DBVe")),
                array("name"=>Language::get("Price incl VAT"),"data"=>array("col"=>"grossvalue"),"usage"=>array("DBVs","DBVe")),
                array("name"=>Language::get("VAT"),"data"=>array("col"=>"vat"),"usage"=>array("DBVs","DBVe")),
                array("name"=>Language::get("Currency"),"data"=>array("col"=>"currency"),"usage"=>array("DBVs","DBVe")),
                array("name"=>Language::get("Item"),"data"=>array("col"=>"itemname"),"usage"=>array("DBVs","DBVe")),
            ),
            "texts"=>array("no_data"=>Language::get("You did not receive any invoice yet")),
             "bootstrap"=>"1",
             "where"=>array("email"=>\AsyncWeb\Objects\User::getEmailOrId()),
             "uid"=>"invoices",
             "show_export"=>true,
             "iter"=>array("per_page"=>"30"),
             "MakeDVView"=>5,
             "show_filter"=>true,
        );

		$this->initTemplateForm();
	}
}