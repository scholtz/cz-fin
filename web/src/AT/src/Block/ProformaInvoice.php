<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;

class ProformaInvoice extends \AsyncWeb\Frontend\Block{
    protected $requiresAuthenticatedUser = true;

    public function postProcess(){
        
    }
	public function initTemplate(){
        try{
            $client = ["name"=>"Klient z IP: ".$_SERVER["REMOTE_ADDR"]];
            if($email = \AsyncWeb\Objects\User::getEmailOrId()){
                $client = DB::gr("user_invoicedata",["email"=>\AsyncWeb\Objects\User::getEmailOrId()]);
                if(!$client){
                    DB::u("user_invoicedata",md5(uniqid()),["name"=>\AsyncWeb\Objects\User::getEmailOrId(),"email"=>\AsyncWeb\Objects\User::getEmailOrId()]);
                    $client = DB::gr("user_invoicedata",["email"=>\AsyncWeb\Objects\User::getEmailOrId()]);
                }
            }
            
            $usr = \AsyncWeb\Objects\User::getEmailOrId();
            $orderMgr = new \AT\Classes\Order($usr);
            $order = $orderMgr->getInvoiceData(URLParser::v("OrderId"));
            $path = $orderMgr->getInvoicePDF($order["id2"],false,URLParser::v("send"));
            if($this->postProcess()) return;
            header("Content-Type: application/pdf");
            echo file_get_contents($path);
            exit;
        }catch(\Exception $exc){
            var_dump($exc->getMessage());
            exit;
        }
	}
	
}