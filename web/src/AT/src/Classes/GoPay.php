<?php
namespace AT\Classes;

use AsyncWeb\System\Language;


class GoPay{
    public function Validate($id){
        $gopay = \GoPay\Api::payments([
            'goid' => \GoPaySettings::$goPayID,
            'clientId' => \GoPaySettings::$goPayClientId,
            'clientSecret' => \GoPaySettings::$goPaySecret,
            'isProductionMode' => \GoPaySettings::$goPayIsProductionMode,
            'scope' => \GoPay\Definition\TokenScope::ALL,
            'language' => \GoPay\Definition\Language::CZECH,
            'timeout' => 60
        ]);
        $status = $gopay->getStatus($id);
        $ret = [];
        if ($status->hasSucceed()) {
            
            $order = $status->json["order_number"];
            
            switch (strtoupper($status->json["state"])) {
                case "PAID":
                
                    $ret["msg"] = Language::get("Thank you for your payment. Licence has been granted to your account.");
                    $ret["redirect"] = "https://".$_SERVER["HTTP_HOST"]."/Licences";
                    
                    \AT\Classes\Licence::newLicenceByGoPay($id,$order);
                    
                    break;
                case "CREATED":
                case "CANCELED":
                case "TIMEOUTED":
                    $ret["msg"] = Language::get("Payment status: %state%. We are waiting for the payment notification from GoPay.",["%state%"=>strtoupper($status->json["state"])]);
                    $ret["redirect"] = "https://".$_SERVER["HTTP_HOST"]."/Licences";
                    break;
                default:
                    $ret["msg"] = Language::get("GoPay Payment status: %state%",["%state%"=>strtoupper($status->json["state"])]);
                    $ret["redirect"] = "https://".$_SERVER["HTTP_HOST"]."/Licences";
                    break;
            }
        } else {
            $ret["error"] = Language::get("Unknown payment");
            $ret["redirect"] = "https://".$_SERVER["HTTP_HOST"]."/Licences";
        }
        
        return $ret;
    }
}