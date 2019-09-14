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
            switch($row["name"]){
                case "ARES":
                    $stats[] = ["Title"=>"Firem z DB ARES: ".number_format($row["all"],0,","," "),"FirstLine"=>"Zavretých: ".number_format($row["deleted"],0,","," "),"SecondLine"=>"Aktuálne v likvidaci: ".number_format($row["akt-v-likv"],0,","," ")];
                break;
                case "RES":
                    $stats[] = ["Title"=>"Register Ekonomických Subjektov","FirstLine"=>"Spolu: ".number_format($row["all"]),"SecondLine"=>"ICO s NACE: ".number_format($row["ico-comb"],0,","," ")];
                break;
                case "Výmery poľnohospodárskych oblastí":
                    $stats[] = ["Title"=>$row["name"],"FirstLine"=>"Spracovaná pôda: ".number_format($row["valuesum"],0,","," ")." ha",
                    "SecondLine"=>"Počet spracovaných parciel: ".number_format($row["all"],0,","," ")
                    ];
                break;
                case "Faktúry":
                    $stats[] = ["Title"=>$row["name"],"FirstLine"=>"Hodnota všetkých faktúr: ".number_format($row["valuesum"],0,","," ")." CZK",
                    "SecondLine"=>"Počet faktúr: ".number_format($row["all"],0,","," ")
                    ];
                break;
                case "Objednávky":
                    $stats[] = ["Title"=>$row["name"],"FirstLine"=>"Hodnota všetkých objednávok: ".number_format($row["valuesum"],0,","," ")." CZK",
                    "SecondLine"=>"Počet objednávok: ".number_format($row["all"],0,","," ")
                    ];
                break;
                default:
                    $stats[] = ["Title"=>$row["name"],"FirstLine"=>"Spolu: ".number_format($row["all"],0,","," ")];
                break;
            }
            
        }
        $this->setData(["Stats"=>$stats]);
	}
}