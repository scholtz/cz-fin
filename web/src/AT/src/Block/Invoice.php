<?php


namespace AT\Block;

use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\DB\DB;
use AsyncWeb\Frontend\URLParser;

class Invoice extends \AsyncWeb\DefaultBlocks\Form{
    protected $requiresAuthenticatedUser = true;
    public function initTemplate(){
        $inv = DB::gr("fin_invoices",["id2"=>URLParser::v("inv")]);
        
        if(!$inv){
            $this->template = '<h1>Not found</h1><p>Invoice has not been found</p>';
            return;
        }
        
        if($inv["email"] != \AsyncWeb\Objects\User::getEmailOrId()){
            $this->template = '<h1>Unathorized</h1><p>Admin has been notified. You are not allowed to see this invoice.</p>';
            return;
        }
        $invObj = new \AT\Classes\Invoice();
        
        if(URLParser::v("type") == "pdf"){
            $this->template = $invObj->getInvoicePDF($inv["id2"]);
        }else{
            $this->template = $invObj->getInvoiceHTML($inv["id2"]);
        }
        //var_dump("a");exit;
    }
}