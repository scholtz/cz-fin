<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;

class GoPay extends ProformaInvoice{
    protected $requiresAuthenticatedUser = false;
    public function postProcess(){
        
        // full configuration
        $gopay = \GoPay\Api::payments([
            'goid' => \GoPaySettings::$goPayID,
            'clientId' => \GoPaySettings::$goPayClientId,
            'clientSecret' => \GoPaySettings::$goPaySecret,
            'isProductionMode' => \GoPaySettings::$goPayIsProductionMode,
            'scope' => \GoPay\Definition\TokenScope::ALL,
            'language' => \GoPay\Definition\Language::CZECH,
            'timeout' => 60
        ]);
        
        $email = \AsyncWeb\Objects\User::getEmailOrId();
        if(URLParser::v("OrderId")){
            $order = DB::gr("fin_orders",["id2"=>URLParser::v("OrderId")]);
            $email = $order["email"];
        }
        
        if(!$email){
            \AsyncWeb\Text\Msg::mes(\AsyncWeb\System\Language::get("Před provedením platby se prosím zaregistrujte abychom mohli spolehlivě spárovat Vaši platbu s Vaším účtem. Děkujeme"));
            $this->template = " ";
            $this->requiresAuthenticatedUser = true;
            return true; // sprocesovana
        }
        
        if($email){
            $client = DB::gr("user_invoicedata",["email"=>$email]);
            if(!$client){
                DB::u("user_invoicedata",md5(uniqid()),["name"=>$email,"email"=>$email]);
                $client = DB::gr("user_invoicedata",["email"=>$email]);
            }
            $usr = DB::gr("users",["email"=>$email]);
        }        
        
        $orderMgr = new \AT\Classes\Order($email);
        
        if(URLParser::v("OrderId")){
            $order = $orderMgr->getInvoiceData(URLParser::v("OrderId"));
        }else{
            $orderInfo = $orderMgr->getLastOrder();
            $order = $orderMgr->getInvoiceData($orderInfo["id2"]);
        }
        
        
        $amount = $order["totalAmount"]*100;
        $paymentInfo = [
            'payer' => [
                'allowed_payment_instruments' => [
                    \GoPay\Definition\Payment\PaymentInstrument::PAYMENT_CARD,
                    \GoPay\Definition\Payment\PaymentInstrument::BANK_ACCOUNT,
                    \GoPay\Definition\Payment\PaymentInstrument::PAYSAFECARD,
                    \GoPay\Definition\Payment\PaymentInstrument::GOPAY,
                    \GoPay\Definition\Payment\PaymentInstrument::PAYPAL,
                    \GoPay\Definition\Payment\PaymentInstrument::BITCOIN,
                    \GoPay\Definition\Payment\PaymentInstrument::ACCOUNT,
                    \GoPay\Definition\Payment\PaymentInstrument::GPAY,
                    ],
                //'default_swift' => \GoPay\Definition\Payment\BankSwiftCode::FIO_BANKA,
                'contact' => [
                    'first_name' => $usr["firstname"],
                    'last_name' => $usr["lastname"],
                    'email' => $email,
                    'phone_number' => $usr["mobile"],
                    'city' => $client["mesto"],
                    'street' => $client["ulica"],
                    'postal_code' => $client["psc"],
                ]
            ],
            'amount' => $amount,
            'currency' => strtoupper($order["currency"]),
            'order_number' => $order["vs"],
            'order_description' => $order["msg"],
            'items' => [
                [
                    'type' => 'ITEM',
                    'name' => $order["msg"],
                    'product_url' => 'https://'.$_SERVER["HTTP_HOST"].'/'.ucfirst(URLParser::v("type")),
                    'amount' => $amount,
                    'count' => 1,
                ],
            ],
            'additional_params' => [
                ['name' => 'ss','value' => $order["ss"] ],
            ],
            'callback' => [
                'return_url' => 'https://'.$_SERVER["HTTP_HOST"].'/GoPayReturn',
                'notification_url' => 'https://'.$_SERVER["HTTP_HOST"].'/GoPayCallback'
            ],
            'lang' => \GoPay\Definition\Language::CZECH
                    
        ];
        //var_dump($paymentInfo);exit;
        
        if(URLParser::v("paymenttype") == "bank"){
            $paymentInfo['payer']['default_payment_instrument'] = \GoPay\Definition\Payment\PaymentInstrument::BANK_ACCOUNT;
        }
        if(URLParser::v("paymenttype") == "bitcoin"){
            $paymentInfo['payer']['default_payment_instrument'] = \GoPay\Definition\Payment\PaymentInstrument::BITCOIN;
        }
        if(URLParser::v("paymenttype") == "card"){
            $paymentInfo['payer']['default_payment_instrument'] = \GoPay\Definition\Payment\PaymentInstrument::PAYMENT_CARD;
        }
        //var_dump($paymentInfo);exit;
        $response = $gopay->createPayment($paymentInfo);
        if ($response->hasSucceed()) {
            //echo "hooray, API returned {$response}";
            //return $response->json['gw_url']; // url for initiation of gateway
            header("Location: ".$response->json['gw_url']);
            exit;
        } else {
            \AsyncWeb\Text\Msg::err("oops, API returned {$response->statusCode}: {$response}");
            header("Location: https://".$_SERVER["HTTP_HOST"]."/Buy/type=".URLParser::v("type"));
            // errors format: https://doc.gopay.com/en/?shell#http-result-codes
            exit;
        }
    }	
}