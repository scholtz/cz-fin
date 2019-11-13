<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;
use AsyncWeb\System\Language;

class Buy extends \AsyncWeb\Frontend\Block{
    //protected $requiresAuthenticatedUser = true;

	public function init(){
        
        $usr = \AsyncWeb\Objects\User::getEmailOrId();
        
        $orderMgr = new \AT\Classes\Order($usr);
        $order = $orderMgr->getLastOrder();
        
        $type = "personal";
        if($order && $order["type"]){
            $type = $order["type"];
        }
        if(URLParser::v("type")){
            $type = URLParser::v("type");
            
        }
        $name = URLParser::v("name");
        if($order && $order["name"]){
            $name = $order["name"];
        }
        $code = "";
        if(\AsyncWeb\Storage\Session::get("ReferalCode")){
            $code = \AsyncWeb\Storage\Session::get("ReferalCode");
        }
        if($order && $order["code"]){
            $code = $order["code"];
        }
        $currency = "CZK";
        if($order && $order["currency"]){
            $currency = $order["currency"];
        }
        if(URLParser::v("currency")){
            $currency = URLParser::v("currency");
        }
        
        $period = "year";
        if($order && $order["period"]){
            $period = $order["period"];
        }
        if(URLParser::v("period")){
            $period = URLParser::v("period");
        }
        
        if(!$order 
            || URLParser::v("name") 
            || (URLParser::v("type")  && $order["type"] != $type) 
            || (URLParser::v("currency")  && $order["currency"] != $currency)
            || (URLParser::v("period")  && $order["period"] != $period)){
            
            $orderMgr->newOrder($type,$period,$name,$code,$send = false,$currency);
            $order = $orderMgr->getLastOrder();
            header("Location: https://".$_SERVER["HTTP_HOST"]."/Buy/");
            exit;
        }
        
        if(URLParser::v("type")){
            header("Location: https://".$_SERVER["HTTP_HOST"]."/Buy/");
            exit;
        }
        
        $data = $orderMgr->getInvoiceData($order["id2"]);
        
        $promo = "";
        if($order["type"] == "personal"){
            $promo = '<div class="alert alert-info"><i class="fa fa-question-circle" aria-hidden="true"></i> <i class="fa fa-question-circle" aria-hidden="true"></i> <i class="fa fa-question-circle" aria-hidden="true"></i> Věděli jste, že s účtem <a href="/Buy/type=premium">Fin PREMIUM</a> můžete mít 4 další uživatelské účty, o 9 denních monitorů výrazů více, o 9 monitorů měsíčních výrazů více a používat ŽIVÉ monitorování médií? S účtem <a href="/Buy/type=premium">Fin PREMIUM</a> si také můžete stáhnout naše CSV datové sady.</div>';
        }
        if($order["type"] == "premium"){
            $promo = '<div class="alert alert-info"><i class="fa fa-question-circle" aria-hidden="true"></i> <i class="fa fa-question-circle" aria-hidden="true"></i> <i class="fa fa-question-circle" aria-hidden="true"></i> Věděli jste, že s účtem <a href="/Buy/type=enterprise">Fin ENTERPRISE</a> můžete mít nemezený počet uživatelských účtů a neomezené monitorování médií?</div>';
        }
        
        

        $qrCode = new \Endroid\QrCode\QrCode($code = 'SPD*1.0*ACC:SK3983300000002801709852*AM:'.number_format($data["totalAmount"],2,".","").'*CC:'.$data["currency"].'*MSG:'.$data["msg"].'*X-VS:'.$data["vs"].'*X-SS:'.$data["ss"]);
        
        $currencies = [
            ["Name"=>"EUR","Value"=>"EUR","Selected"=>$order["currency"] == "EUR" ? " selected":""],
            ["Name"=>"CZK","Value"=>"CZK","Selected"=>$order["currency"] == "CZK" ? " selected":""],
        ];
        $periods = [
            ["Name"=>Language::get("Roční"),"Value"=>"year","Selected"=>$order["period"] == "year" ? " selected":""],
            ["Name"=>Language::get("Měsíční"),"Value"=>"month","Selected"=>$order["period"] == "month" ? " selected":""],
        ];
        
        $this->setData([
            "HasDiscount"=>$data["discount"] > 0,
            "Amount"=>number_format($data["amount"],2,","," "),
            "Discount"=>number_format($data["discountAmount"],2,","," "),
            "TotalAmount"=>number_format($data["totalAmount"],2,","," "),
            "Currency"=>$data["currency"],
            "VS"=>$data["vs"],
            "SS"=>$data["ss"],
            "Name"=>$order["name"],
            "Note"=>$data["msg"],
            "Account"=>$data["bank"],
            "QRB64"=>base64_encode($qrCode->writeString()),
            "QRCode"=>$code,
            "type"=>$data["type"],
            "Promo"=>$promo,
            "GoPay"=>\GoPaySettings::$goPayIsProductionMode,
            "GoPayAllowedUser"=>\GoPaySettings::IsAllowedUser(),
            "OrderId"=>$data["id2"],
            "Currencies"=>$currencies,
            "Periods"=>$periods,
            ]);
        
	}
	
}