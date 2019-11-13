<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;

class Search extends \AsyncWeb\Frontend\Block{
	
	public function init(){
        
        if($_POST["text"] !== null){
            $add = "";
            if($_POST["kraj"]) $add .= "/kraj=".$_POST["kraj"];
            if($_POST["size"]) $add .= "/size=".$_POST["size"];
            if($_POST["nace"]) $add .= "/nace=".$_POST["nace"];
            header("Location: https://".$_SERVER["HTTP_HOST"]."/Search".$add."/s=".$_POST["text"]);
            exit;
        }
        
        $licence = \AT\Classes\Licence::highestUserLicence();
        //var_dump($licence);
        
        $current = URLParser::v("s");
        
        if(strlen($current) == 8 && $row = DB::gr("data_czfin_pages",["id2"=>$current])){
            header("Location: https://".$_SERVER["HTTP_HOST"]."/Content_Cat:Firma/ico=$current/");
            exit;
        }
        
        $kraj = URLParser::v("kraj");
        $size = URLParser::v("size");
        $nace = URLParser::v("nace");
        
        $kraje = [];
        if($licence){
            $res = DB::qb("czutj",["distinct"=>true,"order"=>["kraj_name"=>"asc"],"cols"=>["kraj","kraj_name"]]);
            $done = [];
            while($row=DB::f($res)){
                $kraje[] = ["Name" => $row["kraj_name"],"ID"=>$row["kraj"],"Selected"=>$row["kraj"] == $kraj];
            }
        }

        $velkosti = [];
        if($licence){
            $res = DB::qb("data_czfin_rating",["distinct"=>true,"order"=>["size"=>"asc"],"cols"=>["size"]]);
            while($row=DB::f($res)){
                if(!$row["size"]) continue;
                
                $velkosti[] = ["Name" => $row["size"],"ID"=>$row["size"], "Selected"=>$row["size"] == $size];
            }
        }

        $naces = [];
        if($licence){
            $res = DB::qb("sknace",["distinct"=>true,"order"=>["CZ_text"=>"asc"],"cols"=>["id4","CZ_text"]]);
            $done = [];
            while($row=DB::f($res)){
                if(!$row["CZ_text"]) continue;
                $naces[] = ["Name" => $row["CZ_text"],"ID"=>$row["id4"],"Selected"=>$row["id4"] == $nace];
            }
        }
        $clear = Texts::clear($current);
        if($clear || $kraj || $size || $nace){
            
            $query = ["where"=>[],"order"=>["rating"=>"desc"]];
            if($clear){
                $query["where"][] = ["col"=>"clear","op"=>"like","value"=>"%$clear%"];
            }
            if($kraj){
                $query["where"][] = ["col"=>"kraj","op"=>"eq","value"=>"$kraj"];
            }
            if($size){
                $query["where"][] = ["col"=>"size","op"=>"eq","value"=>"$size"];
            }
            if(URLParser::v("export") == "csv"){
                // do not limit size
                $query["limit"] = 1000;
            }else{
                $query["limit"] = 50;
            }
            $res = DB::qb("data_czfin_rating",$query);
            $firmy = [];
            while($row=DB::f($res)){
                $email = $row["email"];
                if(\AsyncWeb\Objects\User::getEmailOrId()){
                    // if we are logged in, show email
                    
                }else{
                    if($email){
                        $email = '<a class="btn btn-xs btn-light" href="/Premium"><img src="/img/premium.jpg" alt="Vyžaduje se PREMIUM účet" title="Vyžaduje se PREMIUM účet" /></a>';
                    }else{
                        $email = '<a href="mailto:'.$email.'">'.$email.'</a>';
                    }
                }
                
                $firmy[] = [
                    "Name"=>$row["obchodnifirma"] ?? $row["clear"] ?? "?",
                    "ICO"=>$row["id2"],
                    "clear"=>$row["clear"],
                    "Rating"=>number_format(100*$row["rating"]/$row["ratingmax"],2,",","&nbsp;"),
                    "rating"=>$row["rating"],
                    "ratingmax"=>$row["ratingmax"],
                    "Tel"=>$row["tel"],
                    "Size"=>$row["size"],
                    "Web"=>$row["web"],
                    "Email"=>$email,
                    "email"=>$row["email"],
                    ];
            }
            
            if($licence){
                if(URLParser::v("export") == "csv"){
                    $this->exportCSV($firmy,$clear);
                }
            }
            
            $this->setData(["Cinnosti"=>$naces,"Velkost"=>$velkosti,"Kraje"=>$kraje,"Term"=>$current,"Firmy"=>$firmy,"Email"=>"","HasLicence" => $licence || $licence]);
        }else{
            $this->setData(["Cinnosti"=>$naces,"Velkost"=>$velkosti,"Kraje"=>$kraje,"Email"=>"","HasLicence" => $licence || $licence]);
        }
	}
    private function exportCSV($firmy,$clear){
        ob_clean();
        $filename = "export-csv-$clear-".date("Y-m-d").".csv";
        header('Content-Type: application/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="'.$filename.'";');
        $out = '"Firma";"ICO";"Rating";"MaxRating";"Tel";"Web";"Email"'."\n";
        foreach($firmy as $firma){
            $out .= '"'.str_replace('"','""',$firma["Name"]).'",';
            $out .= '"'.str_replace('"','""',$firma["ICO"]).'",';
            $out .= '"'.str_replace('"','""',$firma["rating"]).'",';
            $out .= '"'.str_replace('"','""',$firma["ratingmax"]).'",';
            $out .= '"'.str_replace('"','""',$firma["Tel"]).'",';
            $out .= '"'.str_replace('"','""',$firma["Web"]).'",';
            $out .= '"'.str_replace('"','""',$firma["email"]).'"';
            $out .= "\n";
        }
        header("Content-Length: ".strlen($out));
        echo $out;
        exit;
    }
}