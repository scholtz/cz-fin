<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;

class GoPay extends ProformaInvoice{
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
        
        
        if($email = \AsyncWeb\Objects\User::getEmailOrId()){
            $client = DB::gr("user_invoicedata",["email"=>\AsyncWeb\Objects\User::getEmailOrId()]);
            if(!$client){
                DB::u("user_invoicedata",md5(uniqid()),["name"=>\AsyncWeb\Objects\User::getEmailOrId(),"email"=>\AsyncWeb\Objects\User::getEmailOrId()]);
                $client = DB::gr("user_invoicedata",["email"=>\AsyncWeb\Objects\User::getEmailOrId()]);
            }
            
            $usr = DB::gr("users",["email"=>$email]);
            
        }        
        
        $amount = str_replace(" ","",str_replace(",",".",URLParser::v("ta")))*100;
        
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
            'currency' => \GoPay\Definition\Payment\Currency::CZECH_CROWNS,
            'order_number' => URLParser::v("vs"),
            'order_description' => URLParser::v("n"),
            'items' => [
                [
                    'type' => 'ITEM',
                    'name' => URLParser::v("n"),
                    'product_url' => 'https://www.cz-fin.com/'.ucfirst(URLParser::v("type")),
                    'amount' => $amount,
                    'count' => 1,
                ],
            ],
            'additional_params' => [
                ['name' => 'ss','value' => URLParser::v("ss"),],
            ],
            'callback' => [
                'return_url' => 'https://www.cz-fin.com/GoPayReturn',
                'notification_url' => 'https://www.eshop.cz/GoPayCallback'
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
        
        $response = $gopay->createPayment($paymentInfo);
        if ($response->hasSucceed()) {
            //echo "hooray, API returned {$response}";
            //return $response->json['gw_url']; // url for initiation of gateway
            header("Location: ".$response->json['gw_url']);
            exit;
        } else {
            \AsyncWeb\Text\Msg::err("oops, API returned {$response->statusCode}: {$response}");
            header("Location: https://www.cz-fin.com/Buy/type=".URLParser::v("type"));
            // errors format: https://doc.gopay.com/en/?shell#http-result-codes
            exit;
        }
    }	
}