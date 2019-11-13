<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;

class ProformaInvoice extends \AsyncWeb\Frontend\Block{
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
            
            $orderObj = new \AT\Classes\Order(\AsyncWeb\Objects\User::getEmailOrId());
            $orderObj->
            
            $d = str_replace(",",".",URLParser::v("d"));
            if($d > 0){
                $hasd = true;
            }else{
                $hasd = false;
            }
            
            $html = \AsyncWeb\Text\Template::loadTemplate("Accounting_ProformaInvoicePDF",[
                "vs"=>URLParser::v("vs"),
                "ss"=>URLParser::v("ss"),
                "a"=>URLParser::v("a"),
                "c"=>URLParser::v("c"),
                "ta"=>URLParser::v("ta"),
                "d"=>URLParser::v("d"),
                "n"=>URLParser::v("n"),
                "client"=>[$client],
                "hasd"=>$hasd
                ]);            
             
            $name = "proforma-".URLParser::v("vs")."-".md5($html).".pdf";
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
                    "Zálohová fakura: ".URLParser::v("vs"),
                    "<p>Dobrý deň,\nV prílohe Vám zasielame zálohovú faktúru č. ".URLParser::v("vs")."</p><p>Ďakujeme</p><p>CZ-FIN</p>",
                    "info@cz-fin.com",
                    $att,
                    "html"
                    );
            }
            
            $this->postProcess();
            
            header("Content-Type: application/pdf");
            echo file_get_contents($path);
            exit;
        }catch(\Exception $exc){
            var_dump($exc->getMessage());
            exit;
        }
	}
	
}