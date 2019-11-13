<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;

class ContextSearch extends \AsyncWeb\Frontend\Block{
    
    protected $requiresAuthenticatedUser = true;

	public function init(){
        $pageBuilder = new \AT\Classes\PageBuilder();
        $config = $pageBuilder->makeConfig();
        $t = URLParser::v("t");
        $T = "";
        $C = [];
        
        if($_POST["text"] !== null){
            $add = "";
            if(URLParser::v("t")) $add .= "/t=".URLParser::v("t");
            header("Location: /ContextSearch".$add."/s=".$_POST["text"]);
            exit;
        }
        
        foreach($config as $table=>$conf){
            if(str_replace(["devcz.","devczfast."],"",$table) == $t){
                if($conf["search-col"]){
                    $C = $conf;
                    $T = $table;
                }
                break;
            }
        }
        if($T){
            $search = \AsyncWeb\Text\Texts::clear(URLParser::v("s"));
            $col = $conf["search-col"];
            $colTime = "od";
            if($C["date-col"]){
                $colTime = $C["date-col"];
            }
            //var_dump($colTime);
            $qb = ["limit"=>30,"order"=>[$colTime=>"desc"]];
            if($search){
                $qb["where"] = [["col"=>$col,"op"=>"like","value"=>"%$search%"]];
            }
            if(is_numeric($search) && strlen($search) == 8){
                $col = "ico";
                if($C["ico-col"]) $col = $C["ico-col"];
                if($search){
                    $qb["where"] = [["col"=>$col,"op"=>"eq","value"=>"$search"]];
                }
            }
            if(isset($C["search-table"])){
                $T = $C["search-table"];
            }
            //var_dump($qb);
            $res = DB::qb($T,$qb);
            //var_dump(DB::error());
            //var_dump($qb);exit;
            $cols = [];
            $TableData = [];
            $data = [];
            $usedCols = [];
            $customCols = [];
            $customCols[$colTime] = "Dátum";
            $customCols[$col] = "Text";
            
            while($row=DB::f($res)){
                unset($row["id"]);
                unset($row["id2"]);
                if($colTime != "od"){
                    unset($row["od"]);
                }
                unset($row["do"]);
                unset($row["lchange"]);
                unset($row["edited_by"]);
                
                foreach($row as $col=>$v){
                    $use = true;
                    if(strpos($col,"data-") === 0) {$use = false;}
                    if($row[$col] === null) {$use = false;}
                    if($row[$col] === null) {$use = false;}
                    if($use){
                        $usedCols[$col] = true;
                    }
                }
                if(count($row) > 2){
                    $data[] = $row;
                }
            }
            //var_dump(count($data));exit;
            
            $row = reset($data);
            if($row){
                
                foreach($customCols as $col=>$name){
                    if($usedCols[$col]) unset($usedCols[$col]);
                    $cols[] = ["HeaderName"=>$name];
                }
                
                foreach($row as $col=>$v){     
                    if(!isset($usedCols[$col])) continue;
                    $cols[] = ["HeaderName"=>$col];
                }
            }
            foreach($data as $row){
                $R = [];
                
                foreach($customCols as $col=>$name){
                    $v = $row[$col];
                    if($col == $colTime){
                        $v = date("d.m.Y",$v);
                    }
                    $R[] = ["Value"=>$v];
                }
                
                //var_dump($usedCols);var_dump($row);exit;
                foreach($row as $col=>$v){     
                    if(!isset($usedCols[$col])) continue;
                    
                    $R[] = ["Value"=>$v];
                }
                $TableData[] = ["Column" => $R];
            }
            //var_dump($TableData);exit;
            $this->setData(["Name"=>$C["name"],"TableHeader"=>$cols,"TableData"=>$TableData,"Search"=>$search]);
        }
	}
    
}