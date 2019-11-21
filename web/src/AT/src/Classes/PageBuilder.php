<?php

namespace AT\Classes;

use AsyncWeb\System\Language;
use AsyncWeb\Text\Texts;
use AsyncWeb\DB\DB;
use AsyncWeb\Frontend\URLParser;


class PageBuilder{
    private $config = null;
    public function makeConfig(){
        if($this->config) return $this->config;
        return $this->config = [
            "devcz.data_mfcr_dic_bad_core"=>[
                "context"=>"baddic",    
                "name"=>("Registr spolehlivosti plátců DPH"),
                "type"=>"ram",
                "ico-col"=>"dic",
                "date-col"=>"od",
                "multiplier"=>-0.2,
            ],
            "devcz.data_czfin_rating"=>[
                "context"=>"rating",    
                "name"=>("CZ-FIN Rating firem"),
                "ico-col"=>"id2",
                "date-col"=>"od",
                "multiplier"=>0,
                "do-not-show-at-profile-page"=>true,
            ],
            "devcz.data_arescz_company_core"=>[
                "context"=>"ares",    
                "name"=>("ARES"),
                "ico-col"=>"ico",
                "date-col"=>"od",
                "multiplier"=>0,
                "do-not-show-at-profile-page"=>true,
            ],
            "devcz.data_firmy_ares02_core"=>[
                "context"=>"res",    
                "name"=>("Registr ekonomických subjektů"),
                "ico-col"=>"id2",
                "date-col"=>"od",
                "type"=>"db",
                
            ],
            "devcz.data_posta_datovaschranka"=>[
                "context"=>"datastorage",    
                "name"=>("Datové schránky"),
                "ico-col"=>"ico",
                "date-col"=>"od",
                "type"=>"db",
                
            ],
            "devczfast.data_all_core_faktury"=>[
                "context"=>"invoices",    
                "name"=>("Faktury"),
                "type"=>"db",
                "multiplier"=>2,
                "sum-col"=>"data-castka-s-dph",
                "search-col"=>"data-ucel-platby",
                "date-col"=>"data-date",
                "date-col-stats"=>"od",
            ],
            
            "devczfast.data_all_core_smlouvy"=>[
                "context"=>"smlouvy",    
                "name"=>("Smlouvy"),
                "type"=>"db",
                "multiplier"=>2,
                "search-col"=>"data-popis",
                "sum-col"=>"data-celkova-castka",
            ],
            
            
            "devcz.data_smlouvy_core"=>[
                "context"=>"registr_smlouv",    
                "name"=>("Registr smlouv"),
                "type"=>"db",
                "multiplier"=>1,
                "sum-col"=>"smlouva-hodnotaBezDph",
                "date-col"=>"data-date",
                "ico-col"=>"smlouvni-strana-ico",
                "search-col"=>"data-smlouva-predmet-clear",
                "search-table"=>"out.data_smlouvy_core",
            ],
            
            "devcz.data_mze_dpb_core"=>[
                "context"=>"mze_vymery",    
                "name"=>("Výměry zemědělských oblastí"),
                "ico-col"=>"ico",
                "date-col"=>"od",
                "type"=>"db",
                "multiplier"=>2,
                "sum-col"=>"vymera",
            ],
            "devczfast.data_all_core_objednavky"=>[
                "context"=>"orders",    
                "name"=>("Objednávky"),
                "type"=>"ram",
                "sum-col"=>"data-celkova-castka",
                "search-col"=>"data-popis",
                "date-col-stats"=>"od",
            ],
            "devczfast.data_all_core_pokuty"=>[
                "context"=>"pokuty",    
                "name"=>("Pokuty"),
                "type"=>"ram",
                "multiplier"=>-0.5,
            ],
            "devczfast.data_all_core_rozhodnuti"=>[
                "context"=>"rozhodnuti",
                "name"=>("Rozhodnutí"),
                "type"=>"ram",
            ],
            "devczfast.data_all_core_prostory"=>[
                "context"=>"rozhodnuti",    
                "name"=>("Prostory"),
                "type"=>"ram",
            ],
            "devczfast.data_all_core_setreni"=>[
                "context"=>"setreni",    
                "name"=>("Šetrení"),
                "type"=>"ram",
                "multiplier"=>-0.2,
            ],
            "devczfast.data_all_core_seznamy_podnikatelov"=>[
                "context"=>"seznamy",    
                "name"=>("Seznamy podnikatelov"),
                "type"=>"ram",
                "date-col"=>"od",
            ],
            "devczfast.data_all_core_seznam_vladnich_instituci"=>[
                "context"=>"instituce",    
                "name"=>("Státne instituce"),
                "type"=>"ram",
            ],
            "devczfast.data_all_core_sport"=>[
                "context"=>"sport",    
                "name"=>("Šport"),
                "type"=>"ram",
                "date-col"=>"od",
            ],
            "devczfast.data_all_core_skoly"=>[
                "context"=>"skoly",    
                "name"=>("Školy"),
                "type"=>"ram",
                "date-col"=>"od",
            ],
            "devczfast.data_all_core_granty"=>[
                "context"=>"granty",    
                "name"=>("Granty"),
                "type"=>"ram",
                "date-col"=>"od",
            ],
            ];
    }
    
    private function makeHeader($row,$live){
        $sidlo = DB::gr("devcz.data_arescz_adr_core",["id2"=>$row["sidlo"]]);
        $body = 0;
        if(strpos($row["clear"],"v-likvi") !== false){
            $body+=100;
        }
        if($time = strtotime($row["datumzapisu"])){
            if($time < strtotime("1900")){
                
            }elseif($time < strtotime("1990-01-01")){
                $body+=10;
            }elseif($time < strtotime("1995-01-01")){
                $body+=200;
            }elseif($time < strtotime("2000-01-01")){
                $body+=100;
            }elseif($time < strtotime("2000-01-01")){
                $body+=50;
            }elseif($time < strtotime("2010-01-01")){
                $body+=20;
            }elseif($time < strtotime("2015-01-01")){
                $body+=10;
            }elseif($time < strtotime("2018-01-01")){
                $body+=5;
            }
        }
        if(strlen($row["clear"]) < 10){
                $body+=107;
        }elseif(strlen($row["clear"]) < 15){
                $body+=53;
        }elseif(strlen($row["clear"]) < 20){
                $body+=21;
        }
        
        $page = '<h1>'.$row["obchodnifirma"].($live?' <span title="LIVE VIEW">*</span>':'').'</h1>';
        
        $baddic = DB::qbr("devcz.data_mfcr_dic_bad_core",["where"=>$w= ["dic"=>$row["ico"],["col"=>"datumzukonceninespolehlivosti","op"=>"is","value"=>null]]]);
        
        if($baddic){
            $page .= '<div class="alert alert-danger">{{record-in-unreliable-vat-register}}</div>';
            $body -= 100;
        }else{
            $baddic = DB::qbr("devcz.data_mfcr_dic_bad_core",["where"=>$w= ["dic"=>$row["ico"]]]);
            if($baddic){
                $page .= '<div class="alert alert-warning">{{was-record-in-unreliable-vat-register}}</div>';
                $body -= 20;
            }
            
        }
        
        $page .= '<table class="table table-striped table-hover table-sm table-bordered">';
        
        
        $page .= '<tr><th>{{IČO}}</th><td>'.$row["ico"].'</td><td></td></tr>';
        
        if($sidlo["text"]){
            $page .= '<tr><th>{{Adresa}}</th><td>'.$sidlo["text"].'</td><td></td></tr>';
        }
        if($row["datumzapisu"]){
            $page .= '<tr><th>{{Datum zápisu}}</th><td>'.date("d.m.Y",strtotime($row["datumzapisu"])).'</td><td></td></tr>';
        }
        if($row["datumvzniku"]){
            $page .= '<tr><th>{{Datum vzniku}}</th><td>'.$row["datumvzniku"].'</td><td></td></tr>';
        }
        if($row["datumvymazu"]){
            $page .= '<tr><th>{{Datum výmazu}}</th><td>'.date("d.m.Y",strtotime($row["datumvymazu"])).'</td><td></td></tr>';
        }
        if($row["newscount"] > 0){
            $page .= '<tr><th>{{Monitoring médií}}</th><td>';
            $name = str_replace("-"," ",$row["clearfirma"]);
            $name = ucwords($name);

            $body += 23;
            
            $page .= '<a href="/Spravy/search='.$name.'" title="'.htmlspecialchars($name).' {{v médiích}}">'.$name.'</a>';
            $page .= '</td><td></td></tr>';
        }
        
        $fosobares = DB::qb("devcz.data_arescz_fosoba_core",["where"=>["ico"=>$row["ico"],["col"=>"dvy","op"=>"eq","value"=>""]],"cols"=>["newscount","funkce","dza","titulpred","jmeno","prijmeni","titulza"]]);
        while($fosobarow = DB::f($fosobares)){
            
            $page .= '<tr><th>';
            $page .= '{{'.mb_strtoupper(mb_substr($fosobarow["funkce"],0,1)).mb_substr($fosobarow["funkce"],1).'}}';
            $page .= '</th><td>';
            if($fosobarow["newscount"] > 0){
                $page .= '<a href="/Spravy/search='.urlencode($fosobarow["jmeno"].' '.$fosobarow["prijmeni"]).'" title="'.htmlspecialchars($fosobarow["jmeno"].' '.$fosobarow["prijmeni"]).' v médiích">';
                $body+=101;
            }
            $page .= trim($fosobarow["titulpred"].' '.$fosobarow["jmeno"].' '.$fosobarow["prijmeni"].' '.$fosobarow["titulza"]);
            if($fosobarow["newscount"] > 0){
                $page.='</a>';
            }
            $page.='</td><td>od '.date("d.m.Y",strtotime($fosobarow["dza"])).'</td></tr>';
           
        }
        $posobares = DB::qb("devcz.data_arescz_posoba_core",["where"=>["ico"=>$row["ico"],["col"=>"dvy","op"=>"is","value"=>null]],"cols"=>["funkce","dza","firma"]]);
        while($fosobarow = DB::f($posobares)){
            $page .= '<tr><th>'.$fosobarow["funkce"].'</th><td>'.$fosobarow["firma"].'</td><td>od '.date("d.m.Y",strtotime($fosobarow["dza"])).'</td></tr>';
        }
        
        $rating = DB::qbr("devcz.data_czfin_rating",["where"=>["id2"=>$row["ico"]],"cols"=>["rating","ratingmax","body"]]);
        if($rating){
            if(!$rating["ratingmax"]) $rating["ratingmax"] = 800000;
            $r = 100*($rating["rating"] / $rating["ratingmax"]);
            if($r > 90){$text = "AAA";}
            else if($r > 80){$text = "AA";}
            else if($r > 70){$text = "A";}
            else if($r > 50){$text = "B";}
            else if($r > 30){$text = "C";}
            else if($r > 20){$text = "D";}
            else if($r > 10){$text = "E";}
            else {$text = "F";}
            
            $page .= '<tr><th><a href="/Cenik#rating">{{Rating firmy}}</a></th><td><b>'.$text.'</b> - '.number_format($r,2).' {{percentil v ČR}}</td><td></td></tr>';
        }
        
        $page .= '</table>';
        
        return ["points"=>$body,"text"=>$page];
    }
    private function fixWeb($string){
        if(!$string) return $string;
        if(substr($string,0,4) != "http"){
            return "http://".$string;
        }
        return $string;
    }
    private function fixPhone($string){
        $string = str_replace(" ","",$string);
        $string = htmlentities($string);
        $string = str_replace("&nbsp;","",$string);
        if(!$string) return $string;
        if(substr($string,0,1) != "+" && substr($string,0,1) != "0"){
            return "+420".$string;
        }
        return $string;
    }
    
    private function makeContacts($row,$membership){
        $webs = [];
        $emails = [];
        $phones = [];
        $retnace = "";
        
        
        $body = 0; $page = "";
        $ico = $row["ico"];
        $page.= '<table class="table table-striped table-hover table-sm table-bordered">';
        $page.= '<tr><th>{{Hlidac Statu}}</th><td><a class="btn btn-xs btn-light" href="https://www.hlidacstatu.cz/subjekt/'.$ico.'" target="_blank">https://www.hlidacstatu.cz/subjekt/'.$ico.'</a></td></tr>';
        
        if($info = DB::qbr("devcz.data_katfirem_core",["where"=>["ic"=>$ico],"order"=>["id"=>"desc"]])){
            if($url = $info["source"]){
                $page.= '<tr><th>{{"Katalog-Firem}}</th><td><a class="btn btn-xs btn-light" href="'.$url.'" target="_blank">'.$url.'</a></td></tr>';
            }
        }
        
        
        if($info = DB::qbr("devcz.data_sucz_core_out2",["where"=>["id2"=>$ico]])){
            $naceid4 = rtrim($info["cznace_kod"],'0');
            
            if($naceid4){
                
                if($nace = DB::gr("sknace",["id4"=>$naceid4])){
                    if(!$retnace) $retnace = $naceid4;
                    if($url = "https://www.k-f.cz/".$nace["id5"]."/strana-1/$ico"){
                        $page.= '<tr><th>K-F</th><td><a class="btn btn-xs btn-light" href="'.$url.'" target="_blank">'.$url.'</a></td></tr>';
                    }
                }
            }
        }
        
        if($info = DB::qbr("devcz.data_zivefirmy_core",["where"=>["id2"=>$ico]])){
            
            $web = $this->fixWeb($info["url"]);
            if($web){
                if(isset($webs[$web])){
                    $webs[$web]++;
                }else{
                    $webs[$web] = 1;
                }
            }
            
            for($i = 2;$i <= 26;$i++){
                $web = $this->fixWeb($info["url_$i"]);
                if($web){
                    if(isset($webs[$web])){
                        $webs[$web]++;
                    }else{
                        $webs[$web] = 1;
                    }
                }
            }
            if($info["email"]){
                $e = $info["email"];
                if(isset($emails[$e])){
                    $emails[$e]++;
                }else{
                    $emails[$e] = 1;
                }
            }
            for($i = 2;$i <= 14;$i++){
                $e = trim($info["email_$i"]);
                if($e){
                    if(isset($emails[$e])){
                        $emails[$e]++;
                    }else{
                        $emails[$e] = 1;
                    }
                }
            }
            $phone = $this->fixPhone($info["telephone"]);
            if($phone){
                if(isset($phones[$phone])){
                    $phones[$phone]++;
                }else{
                    $phones[$phone] = 1;
                }
            }
            for($i = 2;$i <= 17;$i++){
                $phone = $this->fixPhone($info["telephone_$i"]);
                if($phone){
                    if(isset($phones[$phone])){
                        $phones[$phone]++;
                    }else{
                        $phones[$phone] = 1;
                    }
                }
            }
            
            if($url = $info["source"]){
                $page.= '<tr><th>Zive-Firmy</th><td><a class="btn btn-xs btn-light" href="'.$url.'" target="_blank">'.$url.'</a></td></tr>';
            }
        }
        
        if($info = DB::qbr("devcz.data_abccz_core",["where"=>["ico"=>$ico]])){
            
            
            for($i = 1;$i <= 3;$i++){
                $web = $this->fixWeb($info["web$i"]);
                if($web){
                    if(isset($webs[$web])){
                        $webs[$web]++;
                    }else{
                        $webs[$web] = 1;
                    }
                }
            }
            for($i = 1;$i <= 5;$i++){
                $e = trim($info["email$i"]);
                if($e){
                    if(isset($emails[$e])){
                        $emails[$e]++;
                    }else{
                        $emails[$e] = 1;
                    }
                }
            }
            for($i = 1;$i <= 3;$i++){
                $phone = $this->fixPhone($info["tel$i"]);
                if($phone){
                    if(isset($phones[$phone])){
                        $phones[$phone]++;
                    }else{
                        $phones[$phone] = 1;
                    }
                }
            }
            
            if($url = $info["source"]){
                $page.= '<tr><th>ABC</th><td><a class="btn btn-xs btn-light" href="'.$url.'" target="_blank">'.$url.'</a></td></tr>';
            }
        }
        
        $firmyczMatched = false;
        $firmyczinfo = [];
        foreach($webs as $web=>$t){
            if($firmyczMatched) continue;
            $w = str_replace(["http://","https://"],"",$web);
            if($firmyczinfo = DB::gr("devcz.data_firmycz_core",["web1"=>$w])){
                $firmyczMatched = true;
                $url = $firmyczinfo["source"];
                $page.= '<tr><th>Firmy.cz</th><td><a class="btn btn-xs btn-light" href="'.$url.'" target="_blank">'.$url.'</a></td></tr>';
            }
        }
        foreach($emails as $email=>$t){
            if($firmyczMatched) continue;
            if($firmyczinfo = DB::gr("devcz.data_firmycz_core",["email1"=>$email])){
                $firmyczMatched = true;
                $url = $firmyczinfo["source"];
                $page.= '<tr><th>Firmy.cz</th><td><a class="btn btn-xs btn-light" href="'.$url.'" target="_blank">'.$url.'</a></td></tr>';
            }
        }
        if($firmyczMatched){
            $info = $firmyczinfo;
            for($i = 1;$i <= 3;$i++){
                $web = $this->fixWeb($info["web$i"]);
                if($web){
                    if(isset($webs[$web])){
                        $webs[$web]++;
                    }else{
                        $webs[$web] = 1;
                    }
                }
            }
            for($i = 1;$i <= 5;$i++){
                $e = trim($info["email$i"]);
                if($e){
                    if(isset($emails[$e])){
                        $emails[$e]++;
                    }else{
                        $emails[$e] = 1;
                    }
                }
            }
            for($i = 1;$i <= 3;$i++){
                $phone = $this->fixPhone($info["tel$i"]);
                if($phone){
                    if(isset($phones[$phone])){
                        $phones[$phone]++;
                    }else{
                        $phones[$phone] = 1;
                    }
                }
            }
        }
        
        
        $url = 'https://www.detail.cz/firma/cz-'.ltrim($ico,"0");
        $page.= '<tr><th>MERK</th><td><a class="btn btn-xs btn-light" href="'.$url.'" target="_blank">'.$url.'</a></td></tr>';

        $url = 'https://rejstrik-firem.kurzy.cz/'.$ico.'/';
        $page.= '<tr><th>Kurzy.cz</th><td><a class="btn btn-xs btn-light" href="'.$url.'" target="_blank">'.$url.'</a></td></tr>';


        $page.= '</table>';
        
        $add = "";
        arsort($webs);
        if($webs){
            $add .= '<div class="col-4"><table class="table table-striped table-hover table-sm table-bordered"><thead><th><h3>{{Webstránka}}</h3></th></thead><tbody>';
            foreach($webs as $web=>$t){
                if($membership == "free"){
                    $add .= '<tr><td><a class="btn btn-xs btn-light" target="_blank" href="/Premium"><img src="/img/premium.jpg" alt="{{Vyžaduje se PREMIUM účet}}" title="{{Vyžaduje se PREMIUM účet}}" /></a></td></tr>';
                }else{
                    $add .= '<tr><td><a class="btn btn-xs btn-light" target="_blank" href="'.$web.'">'.$web.'</a></td></tr>';
                }
            }
            $add.='</table></div>';
        }
        arsort($phones);
        if($phones){
            $add .= '<div class="col-4"><table class="table table-striped table-hover table-sm table-bordered"><thead><th><h3>{{Telefón}}</h3></th></thead><tbody>';
            foreach($phones as $web=>$t){
                if($membership == "free"){
                    $add .= '<tr><td><a class="btn btn-xs btn-light" target="_blank" href="/Premium"><img src="/img/premium.jpg" alt="Vyžaduje se PREMIUM účet" title="Vyžaduje se PREMIUM účet" /></a></td></tr>';
                }else{
                    $add .= '<tr><td><a class="btn btn-xs btn-light" target="_blank" href="tel:'.$web.'">'.$web.'</a></td></tr>';
                }
            }
            $add.='</table></div>';
        }
        arsort($emails);
        if($emails){
            $add .= '<div class="col-4"><table class="table table-striped table-hover table-sm table-bordered"><thead><th><h3>{{Email}}</h3></th></thead><tbody>';
            foreach($emails as $web=>$t){
                if($membership == "free"){
                    $add .= '<tr><td><a class="btn btn-xs btn-light" target="_blank" href="/Premium"><img src="/img/premium.jpg" alt="{{Vyžaduje se PREMIUM účet}}" title="{{Vyžaduje se PREMIUM účet}}" /></a></td></tr>';
                }else{
                    $add .= '<tr><td><a class="btn btn-xs btn-light" target="_blank" href="mailto:'.$web.'">'.$web.'</a></td></tr>';
                }
            }
            $add.='</table></div>';
        }
        if($add){
            $add = '<div class="row">'.$add.'</div>';
        }
        
        $page = '<h2>{{Kontakty a další zdroje}}</h2>'.$add.'<h3>{{Katalogy firem}}</h3>'.$page;
        reset($emails);
        $email = key($emails);
        reset($phones);
        $tel = key($phones);
        reset($webs);
        $web = key($webs);
        
        return ["points"=>$body,"text"=>$page,"email"=>$email,"tel"=>$tel,"web"=>$web,"nace"=>$retnace];

    }
    private function makeCinnosti($row,$nacedb){
        $body = 0; $page = "";
        $retnace = "";
        $invdatares = DB::qb("devcz.data_firmy_ares02_list_core",[
            "where"=>["ico"=>$row["ico"]],
        ]
        );
        $spoluCiastka=0;
        $count = 0;
        $table = [];
        $cols = [];
        while($invdata = DB::f($invdatares)){
            $count++;
            $spoluCiastka+=$invdata["data-pokuta-v-kc"];
            $datarow = [];
            foreach($invdata as $col=>$value){
                if(!trim($value)) continue;
                if($col == "id") continue;
                if($col == "id2") continue;
                if($col == "od") continue;
                if($col == "do") continue;
                if($col == "lchange") continue;
                if($col == "edited_by") continue;
                if(strpos($col,"data-") === 0) continue;
                $datarow[$col] = $value;
                
                if($col == "source") continue;
                
                if($col == "nace"){
                    if(strlen($value) == 5) $value = substr($value,0,4);
                    
                    if($nacedb){
                        if(isset($nacedb[$value])){
                            if(!$retnace) $retnace = $value;
                            $datarow["id5cz"] = $nacedb[$value]["id5cz"];
                        }
                    }else{
                        $nace = DB::gr("sknace",["id4"=>$value]);
                        if(!$retnace) $retnace = $value;

                        $datarow["id5cz"] = $nace["id5cz"];
                    }
                }
                
                $cols[$col] = true;
            }
            if($datarow){
                $table[] = $datarow;
            }
        }
        $cols["source"] = true;
        
        if($count > 0){
            $body += 200;
        }
        
        if($count > 20 ){
            $body += 2;
        }else
        if($count > 10 ){
            $body += 10;
        }else
        if($count > 5 ){
            $body += 20;
        }else
        if($count > 3 ){
            $body += 50;
        }else
        if($count > 0 ){
            $body += 100;
        }
        
        if($table){
            $page .= '<div class="card"><h5 class="card-header">
            <a data-toggle="collapse" href="#nace" aria-expanded="true" aria-controls="nace" id="heading-nace" class="d-block">
                <i class="fa fa-chevron-down pull-right"></i>
                {{Činnosti}}
            </a>
            </h5>
            <div id="nace" class="collapse show" aria-labelledby="heading-nace">
            <div class="table-responsive">
            <table class="table table-striped table-hover table-sm table-bordered scroll-table"><thead><tr>';
            foreach($cols as $col=>$t){
                $page.='<th>'.ucfirst(str_replace("-"," ",$col)).'</th>';
            }
            $page .= '</tr></thead><tbody class="tbody">';
            foreach($table as $datarow){
                $page .= '<tr>';
                foreach($cols as $col=>$t){
                    $value = "";
                    if(isset($datarow[$col])){
                        $value = $datarow[$col];
                    }
                    if($col == "source"){
                        $value = "https://wwwinfo.mfcr.cz/cgi-bin/ares/darv_res.cgi?ico=".$row["ico"];
                        $value = '<a class="btn btn-light" target="_blank" href="'.$value.'">[ZDROJ]</a>';
                    }
                    if($col == "nazev-nace")
                    {
                        if(isset($datarow["id5cz"])){
                            $value = '<a class="btn btn-light" href="/Nace/n='.$datarow["id5cz"].'">'.$value.'</a>';
                        }
                    }

                    $page .= '<td title="'.$col.'">'.$value.'</td>';
                }
                $page .= '</tr>';
            }
            $page .='</tbody></table></div></div></div>';
        }
        return ["points"=>$body,"text"=>$page,"nace"=>$retnace];

    }
    public function makeCompanyPage($row,$ramtables,$nacedb,$membership="free",$live=false){
        $process = $this->makeConfig();
        $body = 0;
        
        $email = "";
        $tel = "";
        $web = "";
        $size = $nace = $kraj = $okres = $mesto = null;

        $ret = $this->makeHeader($row,$live);
        $page .= $ret["text"];
        $body += $ret["points"];
        
        $ret = $this->makeContacts($row,$membership);
        $page .= $ret["text"];
        $body += $ret["points"];
        $nace = $ret["nace"];
        
        $email = $ret["email"];
        $web = $ret["web"];
        $tel = $ret["tel"];
        
        $ret = $this->makeCinnosti($row,$nacedb);
        $page .= $ret["text"];
        $body += $ret["points"];
        if(!$nace) $nace = $ret["nace"];
     
        foreach($process as $T=>$dataconfig){ ######################## process ram or db tables
            $icocol = "data-ico-clear";
            $datecol = "data-date";
            $multiplier = 1;
            $sumcol = false;
            if(isset($dataconfig["ico-col"])) $icocol = $dataconfig["ico-col"];
            if(isset($dataconfig["date-col"])) $datecol = $dataconfig["date-col"];
            if(isset($dataconfig["multiplier"])) $multiplier = $dataconfig["multiplier"];
            if(isset($dataconfig["sum-col"])) $sumcol = $dataconfig["sum-col"];
            if(isset($dataconfig["do-not-show-at-profile-page"])){
                continue;
            }
            
            
            $spoluCiastka=0;
            $count = 0;
            $table = [];
            $cols = [];
            $hassource = false; 
            
            $tt = str_replace("devczfast.","",$T);
            $tt = str_replace("devcz.","",$T);

            if($dataconfig["type"] == "ram" && $ramtables){
                
                if(isset($ramtables[$T][$row["ico"]]))
                while($invdata = array_pop($ramtables[$T][$row["ico"]])){
                    $count++;
                    
                    if($sumcol && isset($invdata[$sumcol]) && $invdata[$sumcol]){
                        $spoluCiastka+=$invdata[$sumcol];
                    }
                    
                    $datarow = [];
                    if($tt == "data_posta_datovaschranka"){
                        $datarow["source"]="https://www.mojedatovaschranka.cz/sds/detail.do?dbid=".$invdata["id2"];
                        $hassource = true;
                    }else if ($tt == "data_firmy_ares02_core"){
                        $datarow["source"]="https://apl.czso.cz/irsw/hledat.jsp?run_rswquery=Hledej&amp;ico=".$invdata["id2"];
                        $hassource = true;
                        if(isset($datarow["su-kpp"]) && $datarow["su-kpp"]){
                            $size = $datarow["su-kpp"];
                        }
                    }else if ($tt == "data_all_core_objednavky"){

                    }
                    
                    foreach($invdata as $col=>$value){
                        if(!trim($value)) continue;
                        if($col == "id") continue;
                        if($col == "id2") continue;
                        if($col == "od") continue;
                        if($col == "do") continue;
                        if($col == "lchange") continue;
                        if($col == "edited_by") continue;
                        if($col == "source" && $value) {$hassource = true;$datarow[$col] = $value;continue;}
                        if(strpos($col,"data-") === 0) continue;
                        if(strlen($value) > 50) continue;
                        $datarow[$col] = $value;
                        
                        $cols[$col] = true;
                    }
                    if($datarow){
                        $table[] = $datarow;
                    }
                }
                if ($tt == "data_all_core_objednavky"){
                    //var_dump($hassource);exit;
                }
            }else{
                $invdatares = DB::qb($T,$c = [
                    "where"=>$w = [$icocol=>$row["ico"]],
                    "order"=>$o = [$datecol=>"desc"],
                    "limit"=>200,
                ]
                );/**/
                while($invdata = DB::f($invdatares)){
                    $count++;
                    
                    if($sumcol && isset($invdata[$sumcol]) && $invdata[$sumcol]){
                        $spoluCiastka+=$invdata[$sumcol];
                    }

                    
                    $datarow = [];
                    
                    if($tt == "data_posta_datovaschranka"){
                        $datarow["source"]="https://www.mojedatovaschranka.cz/sds/detail.do?dbid=".$invdata["id2"];
                        $hassource = true;
                    }else if ($tt == "data_firmy_ares02_core"){
                        $datarow["source"]="https://apl.czso.cz/irsw/hledat.jsp?run_rswquery=Hledej&amp;ico=".$invdata["id2"];
                        $hassource = true;
                        if(isset($invdata["su-kpp"]) && $invdata["su-kpp"]){
                            $size = $invdata["su-kpp"];
                        }
                        
                        if(isset($invdata["zuj-zuj-kod-orig"]) && $invdata["zuj-zuj-kod-orig"]){
                            if($utj = DB::gr("devcz.czutj",["zuj_kod"=>$invdata["zuj-zuj-kod-orig"]])){
                                $mesto = $utj["zuj"];
                                $okres = $utj["okres"];
                                $kraj = $utj["kraj"];
                            }
                        }
                        
                    }else if ($tt == "data_all_core_objednavky"){

                    }
                    
                    foreach($invdata as $col=>$value){
                        if(!trim($value)) continue;
                        if($col == "id") continue;
                        if($col == "id2") continue;
                        if($col == "od") continue;
                        if($col == "do") continue;
                        if($col == "lchange") continue;
                        if($col == "edited_by") continue;
                        if($col == "source" && $value) {$hassource = true;$datarow[$col] = $value;continue;}
                        if(strpos($col,"data-") === 0) continue;
                        if(strlen($value) > 50) continue;
                        $datarow[$col] = $value;
                        
                        $cols[$col] = true;
                    }
                    if($datarow){
                        $table[] = $datarow;
                    }
                }
            }
            

            
            if($hassource){
                $cols["source"] = true;
            }
            if($count > 100){
                $body += 159 * $multiplier;
            }elseif($count > 10){
                $body += 53 * $multiplier;
            }elseif($count > 0){
                $body += 17 * $multiplier;
            }
            
            if($spoluCiastka > 100000000){
                $body += 537;
            }elseif($spoluCiastka > 1000000){
                $body += 231;
            }elseif($spoluCiastka > 10000){
                $body += 83;
            }elseif($spoluCiastka > 10){
                $body += 33;
            }

            
            
            if($table){
                $page .= '<div class="card"><h5  class="card-header">
                <a data-toggle="collapse" href="#'.$dataconfig["context"].'" aria-expanded="false" aria-controls="'.$dataconfig["context"].'" id="heading-'.$dataconfig["context"].'" class="d-block">
                    <i class="fa fa-chevron-down pull-right"></i>
                    {{'.$dataconfig["name"].'}}
                </a>
                </h5>
                <div id="'.$dataconfig["context"].'" class="collapse" aria-labelledby="heading-'.$dataconfig["context"].'">
                <div class="table-responsive">
                <table class="table table-striped table-hover table-sm table-bordered scroll-table"><thead><tr>';
                foreach($cols as $col=>$t){
                    $page.='<th>'.ucfirst(str_replace("-"," ",$col)).'</th>';
                }
                $page .= '</tr></thead><tbody class="tbody">';
                foreach($table as $datarow){
                    $page .= '<tr>';
                    foreach($cols as $col=>$t){
                        $value = "";
                        if(isset($datarow[$col])){
                            $value = $datarow[$col];
                        }
                        
                        if($col == "source" && $value){
                            $value = '<a class="btn btn-light" target="_blank" href="'.$value.'">[{{ZDROJ}}]</a>';
                        }
                        $page .= '<td title="'.$col.'">'.$value.'</td>';
                        
                        
                        /*
                        if($T == "data_posta_datovaschranka"){
                            var_dump($datarow);
                        }/**/

                    }
                    $page .= '</tr>';
                }
                $page .='</tbody></table></div></div></div>';
            }
        }
        $ret = ["points"=>$body,"text"=>$page,"email"=>$email,"tel"=>$tel,"web"=>$web,"size"=>$size,"nace"=>$nace,"kraj"=>$kraj,"okres"=>$okres,"mesto"=>$mesto];
        //var_dump($ret);
        return $ret;
    }
    
    public function makeNewsPage($cty = "cz",$type="24h", $refresh = false){
        $lang = "cs";
        if($cty == "sk") $lang = $cty;
        if($cty == "en") $lang = $cty;
        
        $min = 100;
        switch($type){
            case "1h":
            $t = strtotime("-1 hours");
            $min = 20;
                break;
            case "3h":
            $t = strtotime("-3 hours");
            $min = 40;
                break;
            case "12h":
            $t = strtotime("-12 hours");
            $min = 50;
                break;
            case "w":
            $t = strtotime("-7 days");
            $min = 100;
                break;
            default:
            $t = strtotime("-24 hours");
            $min = 100;
                break;
        }
        
        
        $f = "/dev/shm/$cty-fin-news-$type.html";
        $page = "/Spravy/";
        if($cty == "sk") $page = "/Page:Spravy/";
        
        
        if(!$refresh){
            if(file_exists($f)){
                return ["msgs"=>json_decode(file_get_contents($f),true),"time"=>filemtime($f)];
                /*
                $mtime = filemtime($f);
                if($mtime > time() - 10*60){
                    return json_decode(file_get_contents($f),true);
                }/**/
            }
        }
        require_once("/cron/watchdogsk/ProcessHtml2Text.php");
        
        
        
        $res = \AsyncWeb\DB\DB::qb("dev02fast.${cty}_spravy_texts_clean",array(
            "order"=>array("od"=>"desc"),
            "where"=>[["col"=>"time","op"=>"gt","value"=>$t]],
        ));
        $count = DB::num_rows($res);
        if($count < $min){
            $res = \AsyncWeb\DB\DB::qb("dev02fast.${cty}_spravy_texts_clean",array(
                "limit"=>$min,
                "order"=>array("od"=>"desc")
            ));
            $count = DB::num_rows($res);
        }
        $ret = [];
        while($row=\AsyncWeb\DB\DB::f($res)){
             $result = \ProcessHtml2Text::ProcessWords($row["web"],$row["text"],$lang,true,true);
             foreach($result as $k=>$v){
                 if(isset($ret[$k])){
                     $ret[$k] += $v;
                 }else{
                     if(isset($old[$k])){
                        $ret[$k] = $v + $old[$k];
                     }else{
                         $ret[$k] = $v;
                     }
                 }
             }
             $result = \ProcessHtml2Text::ProcessWords($row["web"],$row["headline"],$lang,true,true);
             foreach($result as $k=>$v){
                 if(isset($ret[$k])){
                     $ret[$k] += $v;
                 }else{
                     if(isset($old[$k])){
                        $ret[$k] = $v + $old[$k];
                     }else{
                         $ret[$k] = $v;
                     }
                 }
             }
        }

        
        $res = \AsyncWeb\DB\DB::qb("dev02fast.${cty}_spravy_texts_clean",array("limit"=>round($count*1.5),"offset"=>$count,"order"=>array("od"=>"desc")));
        $old = [];
        while($row=\AsyncWeb\DB\DB::f($res)){
             $result = \ProcessHtml2Text::ProcessWords($row["web"],$row["text"],$lang,true,true);
             foreach($result as $k=>$v){
                 if(isset($old[$k])){
                     $old[$k] += $v;
                 }else{
                     $old[$k] = $v;
                 }
             }
             $result = \ProcessHtml2Text::ProcessWords($row["web"],$row["headline"],$lang,true,true);
             foreach($result as $k=>$v){
                 if(isset($old[$k])){
                     $old[$k] += $v;
                 }else{
                     $old[$k] = $v;
                 }
             }
        }
        arsort($ret);
        $n = 0;
        
        $weights = [];
        
        //if(URLParser::v("debug") == "1"){
        $wc = DB::qbr("dev02fast.${lang}_spravy_texts_wordcount",["where"=>$w = ["type"=>"month","date"=>(date("Y-m",strtotime("-1 months")))],"cols"=>["clear"]]);
        $clear = gzuncompress($wc["clear"]);
        
        $clear = json_decode($clear,true);
        $max2 = reset($clear);
        
        foreach($ret as $k=>$v){$i++;
            $c = Texts::clear($k);
            if(!isset($clear[$c])){
                $weights[$c] = 0.5;
                continue;
            }
            
            $weights[$c] = ($max2 - $clear[$c]) / $max2;
        }
//        }
        
        $max = reset($ret);
        $i = 0;$c = count($ret);
        foreach($ret as $k=>$v){$i++;
//            if($i < 30) continue;
            $clear = Texts::clear($k);
            $weight = (10 * $weights[$clear] + 5*($max - $v + 30)/$max + 2*(($c - $i) / $c) + min(strlen($clear),7) / 7) /18;
            $sort[$k] = $weight;
        }
        if(URLParser::v("debug") == "1"){
            arsort($sort);
        }
/*
        $i = 0;$c = count($old);
        foreach($old as $k=>$v){$i++;
            if(isset($weights[$clear])){
                $weight = $weights[$clear];
            }else{
                $weight = 0.5;
            }
            $old[$k] = ( $weight*10+5*($max - $v + 30)/$max + 2*(($c - $i) / $c) + min(strlen($k),7) / 7) /18;
        }
        /**/
        arsort($sort);
        $sort2=[];
        $size = [];
        $s = 45;
        foreach($sort as $k=>$v){
            $n++;
            if($n > 150) break;
            $sort2[$k] = $v;
            
            if($n%20==0) $s = round($s / 10 * 8.5);
            $size[$k] = $s;
        }
        ksort($sort2,SORT_STRING | SORT_FLAG_CASE);
        /*
        var_dump(reset($ret));
        var_dump($old);
        var_dump($ret);
        exit;
        /**/
         
        foreach($sort2 as $k=>$v){
            /*
          var_dump($ret[$k]);
          var_dump($old[$k]);
          exit;
          /**/
            if(!isset($old[$k]) || ($old[$k] * 2 <= $ret[$k])){
                $color = "#00".bin2hex(chr(255-round(($old[$k] * 2)/$ret[$k]*255/2)))."00";
            }else{
                $color = "#".bin2hex(chr(255-round($ret[$k]/($old[$k] * 2)*255/2)))."0000";
            }

            $ret2[] = ["html"=>'<a href="'.$page.'search='.urlencode($k).'" style="font-size:'.$size[$k].'px; color:'.$color.'" >'.$k.'</a> '];
        }
        //ksort($ret2);
        //shuffle($ret2);
        //unlink($f);
        $result = file_put_contents($f,json_encode($ret2));
        if($refresh){
            echo " $f $result\n";
        }
        return ["msgs"=>$ret2,"time"=>filemtime($f)];
    }
    
}