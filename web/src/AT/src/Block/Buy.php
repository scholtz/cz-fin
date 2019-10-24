<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;

class Buy extends \AsyncWeb\Frontend\Block{
    //protected $requiresAuthenticatedUser = true;

	public function init(){
        
        
        
        if(URLParser::v("name") && URLParser::v("type")){
            DB::u("fin_orders",md5(uniqid()),[
                "email"=>\AsyncWeb\Objects\User::getEmailOrId(),
                "type"=>URLParser::v("type"),
                "name"=>URLParser::v("name"),
                "coupon"=>\AsyncWeb\Storage\Session::get("ReferalCode"),
                ]);
            header("Location: /Buy/type=".URLParser::v("type"));
            exit;
        }
        if(isset($_POST["name"])){
            header("Location: /Buy/type=".URLParser::v("type"));
            exit;
        }
        
        $lastOrder = DB::qbr("fin_orders",["order"=>["od"=>"desc"],"where"=>["email"=>\AsyncWeb\Objects\User::getEmailOrId(),"type"=>URLParser::v("type")]]);
        
        if($lastOrder && $lastOrder["coupon"] != \AsyncWeb\Storage\Session::get("ReferalCode")){
            DB::u("fin_orders",$lastOrder["id2"],["coupon"=>\AsyncWeb\Storage\Session::get("ReferalCode")]);
        }
        if($code = \AsyncWeb\Storage\Session::get("ReferalCode")){
            $coderow = DB::gr("fin_referal",["code"=>$code]);
            if($coderow["user"] == \AsyncWeb\Objects\User::getEmailOrId()){
                $coderow = false;
            }
            if($coderow["expire"] < time()){
                $coderow = false;
            }
        }
        $promo = "";
        if(URLParser::v("type") == "personal"){
            $promo = '<div class="alert alert-info"><i class="fa fa-question-circle" aria-hidden="true"></i> <i class="fa fa-question-circle" aria-hidden="true"></i> <i class="fa fa-question-circle" aria-hidden="true"></i> Věděli jste, že s účtem <a href="/Buy/type=premium">Fin PREMIUM</a> můžete mít 4 další uživatelské účty, o 9 denních monitorů výrazů více, o 9 monitorů měsíčních výrazů více a používat ŽIVÉ monitorování médií? S účtem <a href="/Buy/type=premium">Fin PREMIUM</a> si také můžete stáhnout naše CSV datové sady.</div>';
        }
        if(URLParser::v("type") == "premium"){
            $promo = '<div class="alert alert-info"><i class="fa fa-question-circle" aria-hidden="true"></i> <i class="fa fa-question-circle" aria-hidden="true"></i> <i class="fa fa-question-circle" aria-hidden="true"></i> Věděli jste, že s účtem <a href="/Buy/type=enterprise">Fin ENTERPRISE</a> můžete mít nemezený počet uživatelských účtů a neomezené monitorování médií?</div>';
        }
        
        $discount = 0;
        switch($coderow["type"]){
            case "prenament":
                $discount = 0.1;
            break;
            case "week":
                $discount = 0.2;
            break;
        }
        
        switch(URLParser::v("type")){
            case "personal":
                $msg = 'Fin PERSONAL';
                $amount = 9999;
                $currency = "CZK";
            break;
            case "premium":
                $msg = 'Fin PREMIUM';
                $amount = 39999;
                $currency = "CZK";
            break;
            case "enterprise":
                $msg = 'Fin ENTERPRISE';
                $amount = 199999;
                $currency = "CZK";
            break;
        }
        
        $discountAmount = round($amount * $discount,2);
        $totalAmount = round($amount - $discountAmount,2);

        $msg.=" ".$lastOrder["name"];
        
        $payment = new \AT\Classes\Payment();
        $vs = $payment->getVS();
        $ss = $payment->getSS();
        
        $updateOrder = [];
        
        
        if($lastOrder["vs"] != $vs){
            $updateOrder["vs"]=$vs;
        }
        if($lastOrder["ss"] != $ss){
            $updateOrder["ss"]=$ss;
        }
        if(\AsyncWeb\Objects\User::getEmailOrId()){
            if($lastOrder["email"] != \AsyncWeb\Objects\User::getEmailOrId()){
                $updateOrder["email"]=\AsyncWeb\Objects\User::getEmailOrId();
            }
        }
        if(URLParser::v("type")){
            if($lastOrder["type"] != URLParser::v("type")){
                $updateOrder["email"]=\AsyncWeb\Objects\User::getEmailOrId();
            }
        }
        if($updateOrder){
            DB::u("fin_orders",$lastOrder["id2"],$updateOrder);
            $lastOrder = DB::qbr("fin_orders",["order"=>["od"=>"desc"],"where"=>["email"=>\AsyncWeb\Objects\User::getEmailOrId(),"type"=>URLParser::v("type")]]);
            //var_dump($lastOrder);exit;
        }

        $qrCode = new \Endroid\QrCode\QrCode($code = 'SPD*1.0*ACC:SK3983300000002801709852*AM:'.number_format($totalAmount,2,".","").'*CC:'.$currency.'*MSG:'.$msg.'*X-VS:'.$vs.'*X-SS:'.$ss);
        
        
        
        $this->setData([
            "HasDiscount"=>$discount > 0,
            "Amount"=>number_format($amount,2,","," "),
            "Discount"=>number_format($discountAmount,2,","," "),
            "TotalAmount"=>number_format($totalAmount,2,","," "),
            "Currency"=>$currency,
            "VS"=>$vs,
            "SS"=>$payment->getSS(),
            "Name"=>$lastOrder["name"],
            "Note"=>$msg,
            "Account"=>"2801709852 / 2010",
            "QRB64"=>base64_encode($qrCode->writeString()),
            "QRCode"=>$code,
            "type"=>URLParser::v("type"),
            "Promo"=>$promo,
            "GoPay"=>\GoPaySettings::$goPayIsProductionMode,
            "GoPayAllowedUser"=>\GoPaySettings::IsAllowedUser(),
            ]);
        
	}
	
}