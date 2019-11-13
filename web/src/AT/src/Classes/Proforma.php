<?php
namespace AT\Classes;

use AsyncWeb\System\Language;
use AsyncWeb\Text\Texts;
use AsyncWeb\DB\DB;
use AsyncWeb\Frontend\URLParser;

class Proforma{
    private $email;
    public function __construct($email = null){
        if(!$email){
            $this->email = \AsyncWeb\Objects\User::getEmailOrId();
        }else{
            $this->email = $email;
        }
    }
    public function issueInvoice(   
        $orderId,
        $send = false
    ){
        $orderObj = new \AT\Classes\Order($this->email);
        $order = $orderObj->get($orderId);
        
    }
    public function getInvoicePDF($id,$email = false){
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
                $this->email,
                "Fakura: ".$invoice["invoicenumber"],
                "<p>Dobrý deň,\nV prílohe Vám zasielame faktúru č. ".$invoice["invoicenumber"]."</p><p>Ďakujeme</p><p>CZ-FIN</p>",
                "info@cz-fin.com",
                $att,
                "html"
                );
        }else{
            
            header("Content-Type: application/pdf");
            echo file_get_contents($path);
            exit;
        }
    }
    public function getInvoiceHTML($id){
        $invoice = DB::gr("fin_invoices",["id2"=>$id]);
        if(!$invoice) return false;
        $client = ["name"=>"Klient z IP: ".$_SERVER["REMOTE_ADDR"]];
        if($email = $this->email){
            $client = DB::gr("user_invoicedata",["email"=>$this->email]);
            if(!$client){
                DB::u("user_invoicedata",md5(uniqid()),["name"=>\AsyncWeb\Objects\User::getEmailOrId(),"email"=>$this->email]);
                $client = DB::gr("user_invoicedata",["email"=>$this->email]);
            }
        }
        if($invoice["discount"] > 0){
            $hasd = true;
        }else{
            $hasd = false;
        }
        
        
        return \AsyncWeb\Text\Template::loadTemplate("Accounting_InvoicePDF",[
                "vs"=>$invoice["ordernumber"],
                "ss"=>$invoice["specificsymbol"],
                "a"=>$invoice["grossvalue"]+$invoice["discount"],
                "c"=>$invoice["specificsymbol"],
                "ta"=>$invoice["grossvalue"],
                "d"=>$invoice["discount"],
                "n"=>$invoice["itemname"],
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
           $latest = $y.$code."00000";
        }
        $latestnumber = substr($latest,-5);
        return $y.$code.str_pad($latestnumber+1,5,"0",STR_PAD_LEFT);
    }
}