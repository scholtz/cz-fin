<?php
use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;
require_once("/cron2/settingsCZ.php");

Cron::start(24*3600);




$processor = new OpenDataProcessor();
$processor->processValidICO();
$processor->processDistributionFile();
//$processor->downloadData();

class OpenDataProcessor{
    private $icos = [];
    private $webstable = "data_opendata_api_webs";
    private $fullwebstable = "data_opendata_data_webs";

    public function processValidICO(){
        
        $this->icos = [];
        echo "hladam platne ico ".date("c")."\n";
        $res = DB::qb("data_arescz_company_core",["cols"=>["ico"]]);
        while($row=DB::f($res)){
            $this->icos[$row["ico"]] = true;
        }
        echo "spolu mam ".count($this->icos)." platnych ico ".date("c")."\n";

    }
    public function processDistributionFile(){

        $row = 0;
        if (($handle = fopen("distribuce.csv", "r")) !== FALSE) {
            $n2k = [];
            while (($data = fgetcsv($handle, 16384, ",")) !== FALSE) {$row++;
                if($row==1){
                    
                    foreach($data as $k=>$v){
                        $k2n[$k] = Texts::clear($v);
                        $n2k[Texts::clear($v)] = $k;
                    }
                    
                }else{
                    if($row % 100 == 0){echo ".";}
                    if($row % 10000 == 0){echo $row."/".date("c")."\n";}
                                        
                    $update = [];
                    foreach($data as $k=>$v){
                        $update[$k2n[$k]] = $v;
                    }
                    /*
                    if($update["odkazkestazeni"] != "https://data.army.cz/sites/default/files/Uhrazen%C3%A9%20faktury%20k%2031.12.2017%20k%20nahr%C3%A1n%C3%AD.csv"){                        
                        continue;
                    }
                    /**/
                    if(strpos($update["odkazkestazeni"],"http://dataor.justice.cz") === 0){
                        continue;
                    }
                    $link = $update["odkazkestazeni"];
                    //echo "$row $link\n";
                    $line = "";
                    $shortContent= "";
                    
                    
                    if(substr(strtolower($update["odkazkestazeni"]),-8) == ".csv.tgz"){
                        //var_dump("tgz header: $header");
                    }else if(substr(strtolower($update["odkazkestazeni"]),-7) == ".csv.gz"){
                        //var_dump("tgz header: $header");
                    }else if(substr(strtolower($update["odkazkestazeni"]),-8) == ".csv.zip"){
                        //var_dump("tgz header: $header");
                    }else{
                        if(strtolower($update["format"]) != "csv") continue;
                    }
                    
                            
                    $path = $update["odkazkestazeni"]."#headers";
                    $header = Page::load($path,$this->webstable);
                    if(!$header){
                        echo "\ndownloading headers: ".$update["odkazkestazeni"];
                        $curl = curl_init();
                        curl_setopt($curl, CURLOPT_URL, $update["odkazkestazeni"]);
                        curl_setopt($curl, CURLOPT_FILETIME, true);
                        curl_setopt($curl, CURLOPT_NOBODY, true);
                        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // follow redirects
                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($curl, CURLOPT_HEADER, true);
                        $header = curl_exec($curl);
                        curl_close($curl);
                        if($header){
                            Page::save($path,$header,$this->webstable);
                        }
                    }
                    //var_dump($header);
                    
                    if(!$header) {
                        echo "no header provided for: $path";
                        continue;
                    }
                    $charset = "";
                    
                    $contenttype = "";
                    foreach(explode("\n",$header) as $headerline){
                        $headerline = strtolower(trim($headerline));
                        if(substr($headerline,0,13)=="content-type:"){
                            $data = explode(":",$headerline);
                            $type = explode(";",trim($data[1]));
                            if(count($type) > 1 && substr($type[1],0,8)=="charset="){
                                $charset = trim(substr($type[1],8));
                            }
                            $contenttype = $type[0];
                            
                        }
                    }
                    switch($contenttype){
                        case "application/gzip":

#                            continue;
                        DB::u("data_opendata_sady_core",md5($update["datova-sada"]),["datova-sada"=>$update["datova-sada"],"gzipcsv"=>$update["odkazkestazeni"]]);
                        $retdata = $this->processGZIPCSV($update["odkazkestazeni"]);
                        $line = $retdata["line"];
                        $shortContent = $retdata["shortContent"];
                        
                        break;
                        case "application/zip":
                        case "application/x-zip-compressed":

#                            continue;
                        DB::u("data_opendata_sady_core",md5($update["datova-sada"]),["datova-sada"=>$update["datova-sada"],"zipcsv"=>$update["odkazkestazeni"]]);
                        $retdata = $this->processZIPCSV($update["odkazkestazeni"]);
                        $line = $retdata["line"];
                        $shortContent = $retdata["shortContent"];
                        
                        break;
                        case "application/vnd.ms-excel":
                        case "application/octet-stream":
                        case "text/csv":
                        case "text/plain":

                            DB::u("data_opendata_sady_core",md5($update["datova-sada"]),["datova-sada"=>$update["datova-sada"],"plaincsv"=>$update["odkazkestazeni"]]);
#                            continue;
                        
                        $retdata = $this->processPlainCSV($update["odkazkestazeni"]);
                        $line = $retdata["line"];
                        $shortContent = $retdata["shortContent"];

                        
                        break;
                        case "application/xml":
                        case "application/json":
                        case "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet":
                            echo "\n".$update["odkazkestazeni"]." is not csv file\n$header";
                        break;
                        case "text/html":
                        
                        if(substr(strtolower($update["odkazkestazeni"]),-4) == ".csv"){
                            
#                            continue;

                            DB::u("data_opendata_sady_core",md5($update["datova-sada"]),["datova-sada"=>$update["datova-sada"],"plaincsv"=>$update["odkazkestazeni"]]);
                            
                            $retdata = $this->processPlainCSV($update["odkazkestazeni"]);
                            $line = $retdata["line"];
                            $shortContent = $retdata["shortContent"];
                        }else{
                            echo "\n".$update["odkazkestazeni"]." is not data file\n$header";
                        }
                        
                        break;
                        default:
                        
                        echo "unable to determine content type of the datafile: $path";
//                                var_dump($data);
                        var_dump($header);
                        var_dump($type);
                        exit;
                    }
                    
                    
                    if($line){
                        
                        
                        $delimiter = ",";
                        $semi = count(explode(";",$line));
                        $comma = count(explode(",",$line));
                        $tab = count(explode("\t",$line));
                        if($semi > $comma && $semi > $tab) $delimiter = ";";
                        if($comma > $semi && $comma > $tab) $delimiter = ",";
                        if($tab > $semi && $tab > $comma) $delimiter = "\t";
                        


                        if (!preg_match('!!u', $line)) { 
                            //$line = iconv("ISO-8859-2","UTF-8",$line);
                            $win1250 = iconv("windows-1250","UTF-8",$line);
                            $columns = str_getcsv($line,$delimiter);
                            $win1250L = 0;
                            foreach($columns as $c){
                                $c = Texts::clear($c);
                                $win1250L+=strlen($c);
                            }
                            
                            $iso = iconv("ISO-8859-2","UTF-8",$line);
                            $columns = str_getcsv($line,$delimiter);
                            $isoL = 0;
                            foreach($columns as $c){
                                $c = Texts::clear($c);
                                $isoL+=strlen($c);
                            }
                            if($win1250L > $isoL){
                                $line = $win1250;
                                $shortContent = iconv("windows-1250","UTF-8",$shortContent);
                                DB::u("data_opendata_distribuce_core",md5($update["distribuce"]),["charset"=>"windows-1250"]);
                            }else{
                                $line = $iso;
                                $shortContent = iconv("ISO-8859-2","UTF-8",$shortContent);
                                DB::u("data_opendata_distribuce_core",md5($update["distribuce"]),["charset"=>"ISO-8859-2"]);
                            }
                        }
                    

                        $columns = str_getcsv($line,$delimiter);
                        $n2k = []; 
                        foreach($columns as $k=>$c){
                            $c = Texts::clear($c);
                            $n2k[$k] = $c;
                            DB::u("data_opendata_meta_core",md5($c.$update["datova-sada"].$update["odkazkestazeni"]),
                                [
                                    "column"=>$c,
                                    "columnNumber"=>$k,
                                    "datova-sada"=>$update["datova-sada"],
                                    "odkazkestazeni"=>$update["odkazkestazeni"],
                                ]);
                        }

                        $this->processShortContent($shortContent,$update,$n2k,$delimiter);
                    }
                }
            }
        }
        fclose($handle);
    }
    
    public function processShortContent($shortContent,$update,$n2k,$delimiter){
        $count = [];
        
        $handle3 = fopen('data://text/plain;base64,' . base64_encode($shortContent),'r');
        if($handle3){
            $n = 0;
            while (($data = fgetcsv($handle3, 1024*16, $delimiter)) !== FALSE) {
                //echo ",";
                $n++;
                foreach($data as $k=>$v){
                    if(isset($this->icos[$v])){
                        if(!isset($count[$k])){
                            $count[$k] = 1;
                        }else{
                            $count[$k]++;
                        }
                    }
                }
            }
            
            foreach($count as $k=>$v){
                $insert = [];
                $insert["column"] = $n2k[$k];
                $insert["count"] = $v;
                $insert["columnNumber"] = $k;
                $insert["from"] = $n;
                $insert["datova-sada"]=$update["datova-sada"];
                $insert["odkazkestazeni"]=$update["odkazkestazeni"];
                $id = md5($insert["column"].$insert["odkazkestazeni"]);
                
                DB::u("data_opendata_meta_ico_core",$id,$insert);
            }
            
        }
    }
    
    public function processPlainCSV($origpath){
        $path = $origpath."#firstline";
        $line = Page::load($path,$this->webstable);
        if(!$line){
            $handle2 = fopen($origpath, "r");
            if ($handle2) {
                $line = fgets($handle2, 4096);
                Page::save($path,$line,$this->webstable);
                fclose($handle2);
            }
        }

        $path = $origpath."#shortContent";
        $shortContent = Page::load($path,$this->webstable);
        if(!$shortContent){
            $handle2 = fopen($origpath, "r");
            if ($handle2) {
                $shortContent = fread($handle2, 1024*128*2); // download 200kb from file
                Page::save($path,$shortContent,$this->webstable);
                fclose($handle2);
            }
        }
        return ["line"=>$line,"shortContent"=>$shortContent];
        
    }
    public function processZIPCSV($path){

        $file = Page::load($path,$this->fullwebstable);
        if(!$file){
            $file = $this->downloadWithChucks($path,$this->fullwebstable);
        }

        $zip = new ZipArchive();
        $zippath = "data.zip";
        file_put_contents($zippath,$file);
        $done = false;
        if ($zip->open($zippath) === true) {
            
            for($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                $fileinfo = pathinfo($filename);
                if($fileinfo["extension"] == ""){
                    continue;
                }
                if($done){
                    echo "Subor $path obsahuje viac suborov!!\n";
                    break;
                }
                $done = true;
                copy("zip://".$zippath."#".$filename, "out.csv");
                
                
            }
            $zip->close();                  
        }
        
        $handle2 = fopen("out.csv", "r");
        if ($handle2) {
            $line = fgets($handle2, 4096);
            fclose($handle2);
        }
        
            
        $handle2 = fopen("out.csv", "r");
        if ($handle2) {
            $shortContent = fread($handle2, 1024*128*2); // download 200kb from file
            fclose($handle2);
        }
    }
    
    public function processGZIPCSV($path){

        $file = Page::load($path,$this->fullwebstable);
        if(!$file){
            $file = $this->downloadWithChucks($path,$this->fullwebstable);
        }
        
        $fileinfo = pathinfo($path);
        var_dump($fileinfo);

        $zippath = "data.gz";
        file_put_contents($zippath,$file);
        $archive = new PharData($zippath);

        $done = false;
        foreach($archive as $file) {
            $fileinfo = pathinfo($file);
            var_dump($fileinfo);
            if($fileinfo["extension"] == ""){
                continue;
            }
            if($done){
                echo "Subor $path obsahuje viac suborov!!\n";
                break;
            }
            $done = true;
            
            copy('phar://'.$file.'/'.$file, "out.csv");
        }        
        
        exit;
        $handle2 = fopen("out.csv", "r");
        if ($handle2) {
            $line = fgets($handle2, 4096);
            fclose($handle2);
        }
        
            
        $handle2 = fopen("out.csv", "r");
        if ($handle2) {
            $shortContent = fread($handle2, 1024*128*2); // download 200kb from file
            fclose($handle2);
        }
    }
    public function downloadWithChucks($path,$webstable){
        $handle2 = fopen($path, "r");
        if ($handle2) {
            echo "\ndownloading $path";
            $text = "";
            $ii = 0;
            
            $last = 0;
            while(!feof($handle2))
            {
                
                $current = round(strlen($text) / (1024*128));
                if($current > $last){
                    $i++;
                    if($i % 1 == 0){echo ",";}
                    if($i % 100 == 0){echo "    ".number_format(strlen($text),0,"."," ")." ".date("c")."\n";}
                    $last = $current;
                }
                $text.= fread($handle2, 1024);
                
            }
            echo " Size: ".number_format(strlen($text),0,"."," ")."\n";
            if($text){
                Page::save($path,$text,$webstable);
                fclose($handle2);
            }
        }
        return $text;
    }
    public function downloadData(){
        
        /**/

        echo "\nidem stiahnut data\n";

        $res = DB::qb("data_opendata_meta_core",["where"=>[["col"=>"column","op"=>"like","value"=>"%ico%"]]]);
        $cc = DB::num_rows($res);
        $i = 0;
        $processed = 0;
        while($row=DB::f($res)){$i++;
            if($i % 1 == 0){echo ".";}
            if($i % 100 == 0){echo $i."/$cc/".date("c")."\n";}

            $path = $row["odkazkestazeni"];
            if(strpos($path,"dataor.justice.cz") !== false){
                continue;
            }
            echo ($processed++)." ".$path."\n";
           
        //    echo "$path\n";
            $text = Page::load($path,$this->fullwebstable);
            if(!$text){
                if(strpos(strtolower($path),"fakt") === false && strpos(strtolower($row["datova-sada"]),"fakt") === false){
        //            continue;
                }
                echo "idem stiahnut $path\n";
                $text = $this->downloadWithChucks($path,$this->fullwebstable);
            }
        }

    }
}


echo "\nDONE ".date("c")."\n";
Cron::end();

exit;



$path = "https://opendata.mzcr.cz/api/3/action/package_list";
$text = Page::load($path,$webstable);
if(!$text){
    $data = json_decode($text = file_get_contents($path),true);
    Page::save($path,$text,$webstable);

}else{
    $data = json_decode($text,true);
}

foreach($data["result"] as $item){
    echo "$item\n";
    $path = "https://opendata.mzcr.cz/api/3/action/package_show?id=$item";
    $text = Page::load($path,$webstable);
    if(!$text){
        $text = file_get_contents($path);
        Page::save($path,$text,$webstable);
    }
    
    $res = json_decode($text,true);
    
    //file_put_contents("01.json",$text);
    foreach($res["result"]["resources"] as $resource){
        if(strtolower($resource["format"]) == "csv"){   
            
            $table = Texts::clear_("data_mzcr_".$item);//."_".$resource["name"]);
            for($year = 2010;$year <= date("Y")+1;$year++){
                $table = Texts::clear_(str_replace($year,"",$table));
            }
            if(strpos(Texts::clear($resource["name"]),"polozky") !== false){
                $table .= "_polozky";
            }
            $table= substr($table,0,50);
            //var_dump();
            if($resource["url"]){
                $path = $resource["url"];
                $text = Page::load($path,$webstable);
                if(!$text){
                    $text = file_get_contents($path);
                    Page::save($path,$text,$webstable);
                }
                \AsyncWeb\Text\CSV2DB::Process($text,$table,["source"=>$path,"sourcename"=>$resource["name"],"publisher"=>$res["result"]["publisher_uri"],"licence"=>$resource["license_link"]]);
                /*
                file_put_contents("data.csv",$text);
                
                if($path != "https://opendata.mzcr.cz/data/azvcr/faktury/2018/faktury.csv" 
                && $path != "https://opendata.mzcr.cz/data/azvcr/faktury/2018/polozky.csv"){
                    var_dump($resource);
                    exit;
                }
                /**/
            }
            
        }
    }
}
/*
class CSV2DB{
    
    public static function Process($text,$table,$add){
        $i = 0;
        $delimiter = ",";
        foreach(explode("\n",$text) as $line){$i++;
            if($i==1){
                // header
                
                $semi = count(explode(";",$line));
                $comma = count(explode(",",$line));
                $tab = count(explode("\t",$line));
                if($semi > $comma && $semi > $tab) $delimiter = ";";
                if($comma > $semi && $comma > $tab) $delimiter = ",";
                if($tab > $semi && $tab > $comma) $delimiter = "\t";

                $data = str_getcsv($line,$delimiter);
                foreach($data as $k=>$col){
                    $col = Texts::clear($col);
                    if($col == "id") $col = "identifier";
                    if($col == "id2") $col = "identifier2";
                    if($col == "od") $col = "od_od";
                    if($col == "do") $col = "do_do";
                    $n2k[$k] = $col;
                }
            }else{
                $data = str_getcsv($line,$delimiter);
                if(count($data) < 3) continue;
                $update = [];
                $id = "";
                foreach($data as $k=>$value){
                    $update[$n2k[$k]] = $value;
                    $id = md5($id.$k.$value);
                }
                foreach($add as $c=>$v){
                    $update[$c] = $v;
                }
                DB::u($table,$id,$update);
            }
        }
        
    }
}
/**/
echo "\nDONE ".date("c")."\n";
Cron::end();