<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;

class Datasety extends \AsyncWeb\Frontend\Block{
	
	public function init(){
        $stats = [];
        $res = DB::qb("data_czfin_datasetsstats");
        $data = [];
        while($row=DB::f($res)){
            $data[$row["group"]][$row["name"]] = $row["date"];
        }        

        uasort($data, function($a, $b){
            return count($a) < count($b);
        });
        
        $stats = [];
        foreach($data as $k1=>$arr1){
            $datasets = [];
            foreach($arr1 as $k2=>$date){
                $datasets[] = ["DatasetName"=>$k2,"Date"=>date("d.m.Y",$date)];
            }
            $stats[] = ["Group"=>$k1,"Datasets"=>$datasets];
        }

        
        $this->setData(["Stats"=>$stats]);
    }
}
