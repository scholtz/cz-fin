<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;

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
                    $stats[] = ["Dataset"=>$dataset,"Title"=>"Firem z DB ARES: ".number_format($row["all"],0,","," "),
                        "FirstLine"=>"Aktívnych: ","FirstLineNumber"=>number_format($row["all"] - $row["deleted"],0,","," "),
                        "SecondLine"=>"Aktuálne v likvidaci: ","SecondLineNumber"=>number_format($row["akt-v-likv"],0,","," "),
                    "Last"=>date("d.m.Y",$row["last"])
                    ];
                break;
                case "RES":
                    $stats[] = ["Dataset"=>$dataset,"Title"=>"Registr Ekonomických Subjektů","FirstLine"=>"Spolu: ".number_format($row["all"]),"SecondLine"=>"ICO s NACE: ","FirstLineNumber"=>number_format($row["ico-comb"],0,","," ")
                    ,"Last"=>date("d.m.Y",$row["last"])

                    ];
                break;
                case "Výmery poľnohospodárskych oblastí":
                    $stats[] = ["Dataset"=>$dataset,"Title"=>$row["name"],"FirstLine"=>"Spracovaná pôda: ","FirstLineNumber"=>number_format($row["valuesum"],0,","," ")." ha",
                    "SecondLine"=>"Počet spracovaných parciel: ","SecondLineNumber"=>number_format($row["all"],0,","," ")
                    ,"Last"=>date("d.m.Y",$row["last"])

                    ];
                break;
                case "Faktury":
                    $stats[] = ["Dataset"=>$dataset,"Title"=>$row["name"],"FirstLine"=>"Hodnota faktur: ","FirstLineNumber"=>number_format($row["valuesum"],0,","," ")." CZK",
                    "SecondLine"=>"Počet faktúr: ","SecondLineNumber"=>number_format($row["all"],0,","," ")
                    ,"Last"=>date("d.m.Y",$row["last"])

                    ];
                break;
                case "Objednávky":
                    $stats[] = ["Dataset"=>$dataset,"Title"=>$row["name"],"FirstLine"=>"Hodnota objednávek: ","FirstLineNumber"=>number_format($row["valuesum"],0,","," ")." CZK",
                    "SecondLine"=>"Počet objednávok: ","SecondLineNumber"=>number_format($row["all"],0,","," ")
                    ,"Last"=>date("d.m.Y",$row["last"])

                    ];
                break;
                default:
                    $stats[] = ["Dataset"=>$dataset,"Title"=>$row["name"],"FirstLine"=>"Spolu: ","FirstLineNumber"=>number_format($row["all"],0,","," "),"Last"=>date("d.m.Y",$row["last"])];
                break;
            }
            
        }
        $this->setData(["Stats"=>$stats]);
	}
}