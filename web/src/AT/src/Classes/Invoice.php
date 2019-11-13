<?php
namespace AT\Classes;

use AsyncWeb\System\Language;
use AsyncWeb\Text\Texts;
use AsyncWeb\DB\DB;
use AsyncWeb\Frontend\URLParser;

class Invoice{
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

    public function issueInvoiceFromOrder(
        $orderId,
        $discount = 0,
        $paidOn = null
        
    ){
        $order = new \AT\Classes\Order($this->email);
        $orderData = $order->get($orderId);
        if(!$orderData){
            throw new \Exception("Order not found");
        }
        $order = new \AT\Classes\Order($orderData["email"]);
        $orderData = $order->get($orderId);
        
        
        $data = $order->getInvoiceData($orderId);
        $currency = $orderData["currency"];
        $invoicenumber = $this->getNextNumber($currency);
        
        
        DB::u("fin_invoices",$ret = md5($orderId),[
            "email"=>$orderData["email"],
            "invoicenumber"=>$invoicenumber,
            "created"=>time(),
            "datepaid"=>$paidOn > 0 ? $paidOn : time(),
            "ordernumber"=>$orderData["vs"],
            "specificsymbol"=>$orderData["ss"],
            "grossvalue"=>$data["totalAmount"],
            "vat"=>"0",
            "discount"=>$discount > 0 ? $discount : $data["discountAmount"],
            "currency"=>$currency,
            "itemname"=>$data["msg"],
        ]);
        return $ret;
    }
    
    
    public function issueInvoice(
        $email,
        $ordernumber,
        $specificsymbol,
        $grossvalue,
        $vat,
        $discount,
        $currency,
        $itemname,
        $send = false
    ){
        $invoicenumber = $this->getNextNumber($currency);
        DB::u("fin_invoices",$ret = md5(uniqid()),[
            "email"=>$email,
            "invoicenumber"=>$invoicenumber,
            "created"=>time(),
            "datepaid"=>time(),
            "ordernumber"=>$ordernumber,
            "specificsymbol"=>$specificsymbol,
            "grossvalue"=>$grossvalue,
            "vat"=>$vat,
            "discount"=>$discount,
            "currency"=>$currency,
            "itemname"=>$itemname,
        ]);
        if($send){
            $this->getInvoicePDF($ret,true);
        }
        return $ret;
    }
    public function getInvoicePDF($id,$email = false,$returnPath = false){
        $invoice = DB::gr("fin_invoices",["id2"=>$id]);
        if(!$invoice) return false;
        $html = $this->getInvoiceHTML($id);
        $name = "invoice-".$invoice["invoicenumber"]."-".md5($html).".pdf";
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
        
        if($email){
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
                $email,
                "Fakura: ".$invoice["invoicenumber"],
                "<p>Dobrý deň,\nV prílohe Vám zasielame faktúru č. ".$invoice["invoicenumber"]."</p><p>Ďakujeme</p><p>CZ-FIN</p>",
                "info@cz-fin.com",
                $att,
                "html"
                );
        }else{
            
            header("Content-Type: application/pdf");
            if($returnPath) return $path;
            echo file_get_contents($path);
            exit;
        }
    }
    public function getInvoiceHTML($id){
        $invoice = DB::gr("fin_invoices",["id2"=>$id]);
        if(!$invoice) return false;
        $client = ["name"=>"Klient z IP: ".$_SERVER["REMOTE_ADDR"]];
        if($email = $invoice["email"]){
            $client = DB::gr("user_invoicedata",["email"=>$email]);
            if(!$client){
                DB::u("user_invoicedata",md5(uniqid()),["name"=>$email,"email"=>$email]);
                $client = DB::gr("user_invoicedata",["email"=>$email]);
            }
        }
        if($invoice["discount"] > 0){
            $hasd = true;
        }else{
            $hasd = false;
        }
        
        
        return \AsyncWeb\Text\Template::loadTemplate("Accounting_InvoicePDF",[
                "InvoiceNumber"=>$invoice["invoicenumber"],
                "SpecificSymbol"=>$invoice["specificsymbol"],
                "a"=>number_format($invoice["grossvalue"],2,","," "),
                "OrderNumber"=>$invoice["ordernumber"],
                "ta"=>number_format($invoice["grossvalue"]-$invoice["discount"],2,","," "),
                "d"=>number_format($invoice["discount"],2,","," "),
                "n"=>$invoice["itemname"],
                "Currency"=>strtoupper($invoice["currency"]),
                "date"=>date("d.m.Y",$invoice["created"]),
                "datepaid"=>date("d.m.Y",$invoice["datepaid"]),
                "client"=>[$client],
                "hasd"=>$hasd
                ]);    
    }
    public function getNextNumber($currency){
        $code = "";
        switch(strtolower($currency)){
            case "czk":
                $code = "112";
            break;
            case "eur":
                $code = "113";
            break;
            case "usd":
                $code = "114";
            break;
            default: 
                $code = "115";
        }
        $y = date("y");
        $row = DB::qbr("fin_invoices",["col"=>["max(`invoice_number`)"=>"c"],"where"=>["currency"=>$currency]]);
        if($row["c"]){
           $latest = $row["c"];
        }else{
           $latest = $y.$code."00395";
        }
        $latestnumber = substr($latest,-5);
        return $y.$code.str_pad($latestnumber+1,5,"0",STR_PAD_LEFT);
    }
}