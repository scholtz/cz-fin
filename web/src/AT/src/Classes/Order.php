<?php
namespace AT\Classes;

use AsyncWeb\System\Language;
use AsyncWeb\Text\Texts;
use AsyncWeb\DB\DB;
use AsyncWeb\Frontend\URLParser;

class Order{
    private $email;
    public function __construct($email = null){
        if(!$email){
            $this->email = \AsyncWeb\Objects\User::getEmailOrId();
        }else{
            $this->email = $email;
        }
        if(!$this->email){
            $this->email = $_SERVER["REMOTE_ADDR"];
        }
    }
    public function get($id){
        $where = ["id2"=>$id];
        if($this->email) $where["email"]=$this->email;
        
        return DB::qbr("out.fin_orders",["where"=>$where]);
    }
    public function newOrder($type,$period,$name,$coupon,$send = false,$currency = "czk",$customprice = null,$payable="+7 days"){
        
        $payment = new \AT\Classes\Payment($this->email);
        $vs = $payment->getVS();
        $ss = $payment->getSS();
        
        $currency = strtoupper($currency);
        if($currency != "CZK" && $currency != "USD" && $currency != "EUR"){
            $currency = "CZK";
        }
        if(strtotime($payable) < 0){
            $payable="+7 days";
        }
        if($type != "personal" && $type != "premium" && $type != "enterprise"){
            $type = "personal";
        }
        if($period != "month" && $period != "year"){
            $period = "year";
        }
        
        
        DB::u("out.fin_orders",$id = md5(uniqid()),$d = [
            "email"=>$this->email,
            "type"=>$type,
            "period"=>$period, 
            "name"=>$name,
            "coupon"=>$coupon,
            "vs"=>$vs,
            "ss"=>$ss,
            "created"=>time(),
            "payable"=>strtotime($payable),
            "currency"=>$currency,
            "customprice"=>$customprice,
        ]);
        if($send){
            $this->getInvoicePDF($id,true);
        }
        return $id;
    }
    public function getLastOrder($type = ""){
        if($type){
            $where = ["email"=>$this->email,"type"=>$type];
        }else{
            $where = ["email"=>$this->email];
        }
        //var_dump($this->email);exit;
        $row=DB::qbr("out.fin_orders",["order"=>["od"=>"desc"],"where"=>$where]);
        //var_dump($row);exit;
        return $row;
    }
    
    public function getCoupon($orderId,$code){
        $invoice = $this->get($orderId);
        if(!$invoice) return false;
        
        $coderow = DB::gr("fin_referal",["code"=>$code]);
        if($coderow["user"] == $this->email){
            return false;
        }
        if($coderow["expire"] < time()){
            return false;
        }
        return $coderow;
    }
    
    public function getInvoicePDF($id,$display = false, $send = false){
        $invoice = $this->get($id);
        if(!$invoice) return false;
        $html = $this->getInvoiceHTML($id);
        $name = "proforma-".$invoice["ss"]."-".md5($html).".pdf";
        $path = '/ocz/vhosts/cz-fin.com/prod01/htdocs/invoices/'.$name;
        if(!is_file($path)){ 
            $mpdf = new \Mpdf\Mpdf(
                [
                    'mode' => 'utf-8',
                    'setAutoTopMargin'=>true,
                ]
            );
            $mpdf->WriteHTML($html);
            
            $out = $mpdf->Output($path);
        }
        
        if($send){
            $att = [    
                [ 
                "content-type"=> "application/pdf", 
                "name"=>$name,
                "data"=>file_get_contents($path)],
                [ 
                "content-type"=> "application/pdf", 
                "name"=>"obchodne-podmienky-2019-10-18.pdf",
                "data"=>file_get_contents("/ocz/vhosts/cz-fin.com/prod01/htdocs/files/obchodne-podmienky-2019-10-18.pdf")],
            
            ];
            
            \AsyncWeb\Email\Email::send(    
                $this->email,
                "Proforma fakura: ".$invoice["ss"],
                "<p>Dobrý deň,\nV prílohe Vám zasielame zálohovú faktúru č. ".$invoice["ss"]."</p><p>Ďakujeme</p>",
                "info@cz-fin.com",
                $att,
                "html"
                );
        }
        
        if($display){
            header("Content-Type: application/pdf");
            echo file_get_contents($path);
            exit;
        }
        return $path;
    }
    public function getInvoiceData($id){
        $invoice = $this->get($id);
        if(!$invoice) return false;
        $coupon = false;
        if($invoice["coupon"]){
            $coupon = $this->getCoupon($id,$invoice["coupon"]);
        }
        
        $invoice["discount"] = 0;
        switch($coupon["type"]){
            case "prenament":
                $invoice["discount"] = 0.1;
            break;
            case "week":
                $invoice["discount"] = 0.2;
            break;
        }
        
        switch($invoice["period"]){
            case "month":
                                
                switch($invoice["type"]){
                    case "personal":
                        $invoice["msg"] = 'Fin PERSONAL 1M';
                        if(strtoupper($invoice["currency"]) == "EUR"){
                            $invoice["amount"] = 39.99;
                        }else{
                            $invoice["amount"] = 999;
                        }
                        $invoice["currency"] = strtoupper($invoice["currency"]);
                    break;
                    case "premium":
                        $invoice["msg"] = 'Fin PREMIUM 1M';
                        if(strtoupper($invoice["currency"]) == "EUR"){
                            $invoice["amount"] = 159.99;
                        }else{
                            $invoice["amount"] = 3999;
                        }
                        $invoice["currency"]= strtoupper($invoice["currency"]);
                    break;
                    case "enterprise":
                        $invoice["msg"] = 'Fin ENTERPRISE 1M';
                        if(strtoupper($invoice["currency"]) == "EUR"){
                            $invoice["amount"] = 799.99;
                        }else{
                            $invoice["amount"] = 19999;
                        }
                        $invoice["currency"] = strtoupper($invoice["currency"]);
                    break;
                }

            break;
            default:
                    
                switch($invoice["type"]){
                    case "personal":
                        $invoice["msg"] = 'Fin PERSONAL';
                        if(strtoupper($invoice["currency"]) == "EUR"){
                            $invoice["amount"] = 399.99;
                        }else{
                            $invoice["amount"] = 9999;
                        }
                        $invoice["currency"] = strtoupper($invoice["currency"]);
                    break;
                    case "premium":
                        $invoice["msg"] = 'Fin PREMIUM';
                        if(strtoupper($invoice["currency"]) == "EUR"){
                            $invoice["amount"] = 1599.99;
                        }else{
                            $invoice["amount"] = 39999;
                        }
                        $invoice["currency"]= strtoupper($invoice["currency"]);
                    break;
                    case "enterprise":
                        $invoice["msg"] = 'Fin ENTERPRISE';
                        if(strtoupper($invoice["currency"]) == "EUR"){
                            $invoice["amount"] = 7999.99;
                        }else{
                            $invoice["amount"] = 199999;
                        }
                        $invoice["currency"] = strtoupper($invoice["currency"]);
                    break;
                }
            break;
        }
        
        if(strtoupper($invoice["currency"]) == "EUR"){
            $invoice["bank"] = "2201697659 / 8330";
            $invoice["iban"] = "SK5983300000002201697659";
        }else{
            $invoice["bank"] = "2801709852 / 2010";
            $invoice["iban"] = "SK3983300000002801709852";
        }

        if($invoice["customprice"] > 0){
            $invoice["totalAmount"] = round($invoice["customprice"],2);
            $invoice["discountAmount"] = round($invoice["amount"] - $invoice["customprice"],2);
        }else{
            $invoice["discountAmount"] = round($invoice["amount"] * $invoice["discount"],2);
            $invoice["totalAmount"] = round($invoice["amount"] - $invoice["discountAmount"],2);
        }

        $invoice["msg"] .= " ".$invoice["name"];
        
         
        if(!$invoice["vs"] || !$invoice["ss"]){
            $payment = new \AT\Classes\Payment($this->email);
            $vs = $payment->getVS();
            $ss = $payment->getSS();
            
            
            $invoice["vs"]=$vs;
            $invoice["ss"]=$ss;
        }
        
        
        $qr = new \Endroid\QrCode\QrCode($code = 'SPD*1.0*ACC:'.$invoice["iban"].'*AM:'.number_format($invoice["totalAmount"],2,".","").'*CC:'.$invoice["currency"].'*MSG:'.$invoice["msg"].'*X-VS:'.$invoice["vs"].'*X-SS:'.$invoice["ss"]);
        $invoice["QRB64"] = base64_encode($qr->writeString());
        $invoice["QRCode"] = $code;
        
        $invoice["totalAmountFormatted"] = number_format($invoice["totalAmount"],2,","," ");
        
        return $invoice;
    }
    public function getInvoiceHTML($id){
        $invoice = $this->getInvoiceData($id);
        if(!$invoice) return false;
        $client = ["name"=>"Klient z IP: ".$_SERVER["REMOTE_ADDR"]];
        if($email = $this->email){
            $client = DB::gr("out.user_invoicedata",["email"=>$this->email]);
            if(!$client){
                DB::u("out.user_invoicedata",md5(uniqid()),["name"=>$this->email,"email"=>$this->email]);
                $client = DB::gr("out.user_invoicedata",["email"=>$this->email]);
            }
        }
        if($invoice["discountAmount"] > 0){
            $hasd = true;
        }else{
            $hasd = false;
        }
        
        
        return \AsyncWeb\Text\Template::loadTemplate("Accounting_ProformaInvoicePDF",[
                "vs"=>$invoice["vs"],
                "ss"=>$invoice["ss"],
                "a"=>number_format($invoice["totalAmount"]+$invoice["discountAmount"],2,","," "),
                "c"=>$invoice["ss"],
                "Currency"=>strtoupper($invoice["currency"]),
                "ta"=>number_format($invoice["totalAmount"],2,","," "),
                "d"=>number_format($invoice["discountAmount"],2,","," "),
                "n"=>$invoice["msg"],
                "client"=>[$client],
                "hasd"=>$hasd,
                "date"=>date("d.m.Y",$invoice["created"]),
                "payable"=>date("d.m.Y",$invoice["payable"]),
                "Bank"=>$invoice["bank"],
                "IBAN"=>$invoice["iban"],
                "QRB64"=>$invoice["QRB64"],
                "QRCode"=>$invoice["QRCode"],
                "OrderId"=>$invoice["id2"],
                ]);    
    }
}