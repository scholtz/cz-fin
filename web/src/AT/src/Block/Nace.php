<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;

class Nace extends \AsyncWeb\Frontend\Block{
	
	public function initTemplate(){
        $current = URLParser::v("n");
        
        $currentNace = DB::gr("sknace",["id5cz"=>$current]);
        if(!$currentNace){
            $ret .= '<div class="container">';
            $ret = "<h1>Kategorie činností</h1>";
            $res = DB::g("sknace",["idLevel"=>"1"]);
            $i = -1;
            while($row=DB::f($res)){
                if($row["id3"] == "T") continue;
                $i++;
                $lower = mb_strtolower($row["CZ_text"]);
                $content = explode(";",$lower);
                $text = "";
                foreach($content as $item){
                    $item = trim($item);
                    $text .= mb_strtoupper(mb_substr($item,0,1)).mb_substr($item,1)."; ";
                }
                $text = trim($text);
                $text = trim($text,";");
                if($i%2 == 0) $ret.='<div class="row">';
                $ret.='<div class="col-6"><img src="/img/nace-'.strtolower($row["id3"]).'.jpg" width="50" height="36" alt="'.$row["CZ_text"].'" title="'.$row["CZ_text"].'" /> <a href="/Content_Cat:Nace/n='.$row["id5cz"].'/">'.$text.'</a></div>';
                if($i%2 == 1) $ret.='</div>';
            }
            $ret .= '</div>';
            
            $this->template = $ret;
            return;
        }
        
        if($currentNace["idLevel"]==1){
            $ret.='<a class="btn btn-light float-right" href="/Content_Cat:Nace/">←</a>'."\n";
        }else{
            if($parent = DB::gr("sknace",$where = ["id4"=>$currentNace["parent"]])){

                $text = $parent["CZ_text"];
                if(!$text) $text = $parent["SK_text"];
                if(!$text) $text = $parent["EN_text"];

                $ret.='<a class="btn btn-light float-right" href="/Content_Cat:Nace/n='.$parent["id5cz"].'" title="'.$text.'">←</a>'."\n";
            }else{
                $ret.='<a class="btn btn-light float-right" href="/Content_Cat:Nace/">←</a>'."\n";
            }
        }
        

        $res = DB::g("sknace",["parent"=>$currentNace["id4"]]);
        $i = -1;
        
        if(DB::num_rows($res)){
            $ret .= '<div class="card"><div class="card-header">Kategorie činností</div><div class="list-group">';
            while($row=DB::f($res)){
                $i++;
                $text = $row["CZ_text"];
                if(!$text) $text = $row["SK_text"];
                if(!$text) $text = $row["EN_text"];

                $counter = DB::qbr("data_czfin_nace2firma",["where"=>["id2"=>$row["id4"]],"cols"=>["counter"]]);
                
                $ret.='<a class="list-group-item list-group-item-action" href="/Content_Cat:Nace/n='.$row["id5cz"].'/">';
                $ret.='<span class="badge float-right badge-primary badge-pill">'.(number_format($counter["counter"],0,",","&nbsp;")??"0").'</span>';
                $ret.=$text.'</a>'."\n";
            }
            $ret .= '</div></div>';
        }
        
        $row = DB::gr("data_czfin_nace2firma",["id2"=>$currentNace["id4"]]);
        $data = json_decode(base64_decode($row["data"]),true);
        if(count($data) > 0){
            
            $ret .= '<br><div class="card"><div class="card-header">Firmy</div><div class="list-group">';
            foreach($data as $firma){
                $ret.='<a class="list-group-item list-group-item-action" href="/Content_Cat:Firma/ico='.$firma["id2"].'/'.$firma["clear"].'">'.$firma["obchodnifirma"].'</a>'."\n";

            }
            $ret .= '</div></div>';
        }
        

        

        
        $this->template = $ret;
        
	}
	
}