<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;
use AsyncWeb\System\Language;

class Stats extends \AsyncWeb\Frontend\Block{
	public function init(){
        $stats = [];
        $res = DB::qb("data_czfin_stats",["order"=>["all"=>"desc"]]);
        while($row=DB::f($res)){
            $dataset = [];
            $row2 = DB::gr("data_czfin_datasets",["id2"=>md5($row["table"])]);
            if($row2){
                $row2["LinkToDownload"] = "/Dataset/f=".$row2["tt"];
                $row2["Size"] = "CSV: ".round($row2["size_uncompressed"]/1024/1024)." MB, GZ: ".round($row2["size_compressed"]/1024/1024)." MB";
                $dataset[] = $row2;
            }
            

            
            switch($row["name"]){
                case "ARES":
                    $stats[] = ["Dataset"=>$dataset,"Title"=>Language::get("Firem z DB ARES: %num%",["%num%"=>number_format($row["all"],0,","," ")]),
                        "FirstLine"=>"Aktívnych: ","FirstLineNumber"=>number_format($row["all"] - $row["deleted"],0,","," "),
                        "SecondLine"=>"Aktuálne v likvidaci: ","SecondLineNumber"=>number_format($row["akt-v-likv"],0,","," "),
                    "Last"=>date("d.m.Y",$row["last"])
                    ];
                break;
                case "RES":
                    $stats[] = ["Dataset"=>$dataset,"Title"=>Language::get("Registr Ekonomických Subjektů"),"FirstLine"=>Language::get("Spolu: %num%",["%num%"=>number_format($row["all"])]),"SecondLine"=>Language::get("ICO s NACE").": ","FirstLineNumber"=>number_format($row["ico-comb"],0,","," ")
                    ,"Last"=>date("d.m.Y",$row["last"])

                    ];
                break;
                case "Výmery poľnohospodárskych oblastí":
                    $stats[] = ["Dataset"=>$dataset,"Title"=>Language::get($row["name"]),"FirstLine"=>Language::get("Spracovaná pôda").": ","FirstLineNumber"=>number_format($row["valuesum"],0,","," ")." ha",
                    "SecondLine"=>Language::get("Počet spracovaných parciel: "),"SecondLineNumber"=>number_format($row["all"],0,","," ")
                    ,"Last"=>date("d.m.Y",$row["last"])

                    ];
                break;
                case "Faktury":
                    $stats[] = ["Dataset"=>$dataset,"Title"=>$row["name"],"FirstLine"=>"Hodnota faktur: ","FirstLineNumber"=>number_format($row["valuesum"],0,","," ")." CZK",
                    "SecondLine"=>"Počet faktúr: ","SecondLineNumber"=>number_format($row["all"],0,","," ")
                    ,"Last"=>date("d.m.Y",$row["last"]),
                    "LinkToSearch"=>"/ContextSearch/t=data_all_core_faktury"

                    ];
                break;
                case "Objednávky":
                    $stats[] = ["Dataset"=>$dataset,"Title"=>$row["name"],"FirstLine"=>"Hodnota objednávek: ","FirstLineNumber"=>number_format($row["valuesum"],0,","," ")." CZK",
                    "SecondLine"=>"Počet objednávok: ","SecondLineNumber"=>number_format($row["all"],0,","," ")
                    ,"Last"=>date("d.m.Y",$row["last"]),
                    "LinkToSearch"=>"/ContextSearch/t=data_all_core_objednavky"

                    ];
                break;
                case "Registr smlouv":
                    $stats[] = [
                        "Dataset"=>$dataset,
                        "Title"=>$row["name"],
                        "FirstLine"=>"Hodnota smluv: ",
                        "FirstLineNumber"=>number_format($row["valuesum"],0,","," ")." CZK",
                        "SecondLine"=>"Počet smlouv: ",
                        "SecondLineNumber"=>number_format($row["all"],0,","," "),
                        "Last"=>date("d.m.Y",$row["last"]),
                        "LinkToSearch"=>"/ContextSearch/t=data_smlouvy_core"
                    ];
                break;
                case "Smlouvy":
                    $stats[] = [
                        "Dataset"=>$dataset,
                        "Title"=>$row["name"],
                        "FirstLine"=>"Hodnota mimo registra: ",
                        "FirstLineNumber"=>number_format($row["valuesum"],0,","," ")." CZK",
                        "SecondLine"=>"Počet smlouv: ",
                        "SecondLineNumber"=>number_format($row["all"],0,","," "),
                        "Last"=>date("d.m.Y",$row["last"]),
                        "LinkToSearch"=>"/ContextSearch/t=data_all_core_smlouvy"
                    ];
                break;
                default:
                    $LinkToSearch = false;
                    switch($dataset){
                        case "data_all_core_faktury":
                            $LinkToSearch = "/ContextSearch/t=".$dataset;
                        break;
                    }
                    $stats[] = ["Dataset"=>$dataset,"Title"=>$row["name"],"FirstLine"=>"Spolu: ","FirstLineNumber"=>number_format($row["all"],0,","," "),"Last"=>date("d.m.Y",$row["last"]),"LinkToSearch"=>$LinkToSearch];
                break;
            }
            
        }
        $this->setData(["Stats"=>$stats]);
	}
}