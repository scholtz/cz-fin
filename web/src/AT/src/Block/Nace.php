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
                $ret.='<div class="col-6"><img src="/img/nace-'.strtolower($row["id3"]).'.jpg" width="50" height="36" alt="'.$row["CZ_text"].'" title="'.$row["CZ_text"].'" /> <a href="/Nace/n='.$row["id5cz"].'">'.$text.'</a></div>';
                if($i%2 == 1) $ret.='</div>';
            }
            $ret .= '</div>';
            
            $this->template = $ret;
            return;
        }
        
        if($_SERVER["REQUEST_URI"] != "/Nace/n=".$currentNace["id5cz"]){
            header("HTTP/1.1 301 Moved Permanently"); 
            header("Location: https://www.cz-fin.com/Nace/n=".$currentNace["id5cz"]);
            exit();
        }
        
        if($currentNace["idLevel"]==1){
            $ret.='<a class="btn btn-light float-right" href="/Nace/">←</a>'."\n";
        }else{
            if($parent = DB::gr("sknace",$where = ["id4"=>$currentNace["parent"]])){

                $text = $parent["CZ_text"];
                if(!$text) $text = $parent["SK_text"];
                if(!$text) $text = $parent["EN_text"];

                $ret.='<a class="btn btn-light float-right" href="/Nace/n='.$parent["id5cz"].'" title="'.$text.'">←</a>'."\n";
            }else{
                $ret.='<a class="btn btn-light float-right" href="/Nace/">←</a>'."\n";
            }
        }
        
        $currenttext = $currentNace["CZ_text"];
        if(!$currenttext) $currenttext = $currentNace["SK_text"];
        if(!$currenttext) $currenttext = $currentNace["EN_text"];
        $currentcontent = $currentNace["CZ_content"];
        if(!$currentcontent) $currentcontent = $currentNace["SK_content"];
        if(!$currentcontent) $currentcontent = $currentNace["EN_content"];
        $res = DB::g("sknace",["parent"=>$currentNace["id4"]]);
        $i = -1;
        $ret.='<h1>'.$currenttext.'</h1><p>'.$currentcontent.'</p>';
        if(DB::num_rows($res)){
            $ret .= '<div class="card"><div class="card-header">Kategorie činností '.$currenttext.'</div><div class="list-group">';
            while($row=DB::f($res)){
                $i++;
                $text = $row["CZ_text"];
                if(!$text) $text = $row["SK_text"];
                if(!$text) $text = $row["EN_text"];

                $counter = DB::qbr("data_czfin_nace2firma",["where"=>["id2"=>$row["id4"]],"cols"=>["counter"]]);
                
                $ret.='<a class="list-group-item list-group-item-action" href="/Nace/n='.$row["id5cz"].'/">';
                $ret.='<span class="badge float-right badge-primary badge-pill">'.(number_format($counter["counter"],0,",","&nbsp;")??"0").'</span>';
                $ret.=$text.'</a>'."\n";
            }
            $ret .= '</div></div>';
        }
        
        $row = DB::gr("data_czfin_nace2firma",["id2"=>$currentNace["id4"]]);
        $data = json_decode(base64_decode($row["data"]),true);
        $licence = \AT\Classes\Licence::highestUserLicence();
        if(count($data) > 0){
            
            $ret .= '<br><div class="card"><div class="card-header">Firmy</div><table class="table"><thead><tr><th>Firma</th><th>Rating</th><th>Zaměstnanci</th><th>Web</th><th>Tel</th><th>Email</th></tr></thead><tbody>';
            foreach($data as $firma){
                //$ret.= '<!-- '.print_r($firma,true).' -->';
                $ret.='<tr><td><a class="btn btn-light btn-xs btn-outline-primary" href="/Firma/ico='.$firma["id2"].'/n='.$firma["clear"].'">'.($firma["obchodnifirma"] ?? $firma["clear"]).'</a></td>';

                if($firma["ratingmax"]){
                    $ret.='<td>'.number_format(100*$firma["rating"]/$firma["ratingmax"],1,",","&nbsp;").'</td>';
                }else{
                    $ret.='<td></td>';
                }                
                $ret.='<td>'.$firma["size"].'</td>';
                if($firma["web"]){
                    $ret.='<td><a target="_blank" href="'.$firma["web"].'">'.$firma["web"].'</a></td>';
                }else{
                    $ret.='<td></td>';
                }
                
                if($firma["tel"]){
                    if($licence){
                        $ret.='<td><a href="callto:'.$firma["tel"].'">'.$firma["tel"].'</a></td>';
                    }else{
                        $ret.='<td><a href="/Personal"><img src="/img/premium.jpg" alt="Vyžaduje se licence" title="Vyžaduje se min Fin PERSONAL licence" /></a></td>';
                    }
                }else{
                    $ret.='<td></td>';
                }
                
                if($firma["email"]){
                    if($licence){
                        $ret.='<td><a href="mailto:'.$firma["email"].'">'.$firma["email"].'</a></td>';
                    }else{
                        $ret.='<td><a href="/Personal"><img src="/img/premium.jpg" alt="Vyžaduje se licence" title="Vyžaduje se min Fin PERSONAL licence" /></a></td>';
                    }
                }else{
                    $ret.='<td></td>';
                }
                
                $ret.='</tr>'."\n";

            }
            $ret .= '</tbody></table></div>';
        }
        

        

        
        $this->template = $ret;
        
	}
	
}