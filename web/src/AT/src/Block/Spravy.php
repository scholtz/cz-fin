<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;
use AsyncWeb\System\Language;

class Spravy extends \AsyncWeb\Frontend\Block{
	
	public function init(){
        
        if($search = URLParser::v("text")){
            header("Location: https://".$_SERVER["HTTP_HOST"]."/Spravy/search=".$search);
            exit;
        }
        
        $empty = true;
        if($search = Texts::clear($term=URLParser::v("search"))){
            
            \AsyncWeb\Frontend\BlockManagement::get("Content_HTMLHeader_Title")->changeData(array("title" => "$term - Monitoring médií | CZ-FIN"));
            
            //var_dump($search);
            //var_dump(md5($search));
            //var_dump($row = DB::qbr("dev02fast.cs_word_combinations_1_out",["where"=>["id2"=>md5($search)]]));
            
            $newsObj = new \AT\Classes\News();
            
            $allnewsByTime = $newsObj->getNewsByTime($search);
            
            $news = [];
            $count = 30;
            
            $i = 0;
            if($allnewsByTime){
                foreach($allnewsByTime as $k=>$newsAtTime){
                    foreach($newsAtTime as $k2=>$news1){
                        //var_dump($k);exit;
                        if($i >= $count) break;
                        $news1["Time"] = date("d.m.Y H:i",$news1["time"]);
                        if(!isset($news1["id2"])) $news1["id2"] = md5($news1["web"]);
                        if(!$news1["headline"]) $news1["headline"] = "[?]";
                        $i++;
                        
                        if(!$news1["lang"]){
                            if(strpos($news1["web"],".cz")){
                                $news1["lang"] = "cs";
                            }elseif(strpos($news1["web"],".sk")){
                                $news1["lang"] = "cs";
                            }else{
                                $news1["lang"] = "en";
                            }
                        }
                        
                        $news[] = $news1;
                        
                    }
                }
            }
            
            //var_dump(json_decode(base64_decode($row["data"]),true));exit;
            if($news){
                $data =["Term"=>$term,"News"=>$news,"html"=>""];
                $data["IsAdmin"] = \AsyncWeb\Objects\Group::is_in_group("admin");
                if($data["IsAdmin"]){
                    //var_dump($news);exit;
                }
                $this->setData($data);
                $empty = false;
            }
        }
        if($empty){
            $pageBuilder = new \AT\Classes\News();
            
            $count = 30;
            $lang = URLParser::v("lang");
            if(!$lang) $lang = Language::getLang();
            if($lang) $lang = substr($lang,0,2);
            //var_dump($lang);
            $allnewsByTime = $pageBuilder->getAllNews($count,$lang);
            
            $news = [];
            
            $i = 0;
            if($allnewsByTime){
                foreach($allnewsByTime as $k=>$newsAtTime){
                    foreach($newsAtTime as $k2=>$news1){
                        if($i >= $count) break;
                        $news1["Time"] = date("H:i",$news1["time"]);
                        if(!$news1["headline"]) $news1["headline"] = "[?]";
                        if(!$news1["lang"]){
                            if(strpos($news1["web"],".cz")){
                                $news1["lang"] = "cs";
                            }elseif(strpos($news1["web"],".sk")){
                                $news1["lang"] = "cs";
                            }else{
                                $news1["lang"] = "en";
                            }
                        }
                        $i++;
                        $news[] = $news1;
                    }
                }
            }
            
            $t = URLParser::v("t");
            if(!$t) $t = "24h";
            $keywords = $pageBuilder->makeNewsPage($lang,$t,URLParser::v("refresh") == "1",URLParser::v("max"),URLParser::v("cache"));
            $data = ["News"=>$news, "Term"=>$term,"Keywords"=>$keywords["msgs"],"html"=>"","Time"=>date("c",$keywords["time"])];
            $data["is7d"] = $data["is24h"] = $data["is12h"] = $data["is3h"] = $data["is1h"] = false;
            switch($t){
                case "1h":
                    $data["is1h"] = true;
                break;
                case "3h":
                    $data["is3h"] = true;
                break;
                case "12h":
                    $data["is12h"] = true;
                break;
                case "24h":
                    $data["is24h"] = true;
                break;
                case "w":
                    $data["is7d"] = true;
                break;
            }
            switch($lang){
                case "en":
                    $data["Lang"] = "en-US";
                break;
                case "sk":
                    $data["Lang"] = "sk-SK";
                break;
                default:
                    $data["Lang"] = false;
                break;
            }
            $data["IsAdmin"] = \AsyncWeb\Objects\Group::is_in_group("admin");
            $this->setData($data);
            \AsyncWeb\Frontend\BlockManagement::get("Content_HTMLHeader_Title")->changeData(array("title" => "Monitoring médií | $t | CZ-FIN"));
        }
        
        /**/
        
	}
	
}