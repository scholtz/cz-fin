<?php

namespace AT\Block;


use AsyncWeb\DB\DB;
use AsyncWeb\Frontend\URLParser;
use AsyncWeb\Security\Auth;
use AsyncWeb\Connectors\Page;

class HTML2RSS extends \AsyncWeb\Frontend\Block{
	public function initTemplate(){
        $code = URLParser::v("code");
        
        if($config = DB::gr("dev02.config_html2rss",["code"=>$code])){
            $base = $config["base"];
            if(!$base) $base = $config["web"];
            if(substr($base,-1) != "/") $base.="/";
            header("Content-Type: text/xml; charset=UTF-8");
            echo '<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"><channel><title>'.$config["web"].'</title><link>'.$config["web"].'</link><description>HTML2RSS from '.$config["web"].'</description><ttl>2</ttl>
';
            $last = Page::getLastTime($config["web"],"html2rss_webs");
            if(!$last || $last < time() - 60*5 ){
                Page::downloadWithEtag($config["web"],"html2rss_webs");
                
            }
            $page = Page::load($config["web"],"html2rss_webs");
            $dom = new \DOMDocument();
            $page = str_replace('<meta charset="utf-8" />','<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />',$page);
            @$dom->loadHTML($page);
//            
            if($dom){
                $xpath = new \DomXpath($dom);
                //var_dump($config);
                //var_dump($config["rule_iter"]);
                
                $nodes = $xpath->query($config["rule_iter"]);
                echo '<!-- '.$nodes->length.' -->';
                foreach($nodes as $node){
                    $link = $perex = "";
                    $linknode = $xpath->query($config["rule_link"],$node)->item(0);
                    if($linknode){
                        $link = $linknode->nodeValue;
                    }
                    $perexnode = $xpath->query($config["rule_perex"],$node)->item(0);
                    if($perexnode){
                        $perex = $perexnode->nodeValue;
                    }
                    
                    if(substr($link,0,1) == "/"){
                        $link = substr($link,1);
                    }
                    if(substr($link,0,5) == "http:" || substr($link,0,6) == "https:"){
                        if(substr($link,0,strlen($base)) != $base){
                        echo '<!-- 
Link to other site: '.$link.' 
-->';
                            continue;
                        }
                    }else{
                        $link = $base.$link;
                    }
                    
                    if($link && $perex){
                 /*       
            if($base == "https://www.marianne.cz/"){
                var_dump($config["rule_iter"]);
                var_dump($link);
                var_dump($perex);
                exit;
            }/**/
                        echo '<item><title>'.htmlspecialchars($perex).'</title><description>'.htmlspecialchars($perex).'</description><link>'.$link.'</link></item>'."\n";
                        //var_dump($link);
                        //var_dump($perex);
                    }else{
                        echo '<!-- 
Link: '.$link.'
Perex: '.$perex.'

-->';

                    }
                }
            }
            echo "</channel></rss>";
            //var_dump($dom->savexml());
            
            exit;
            
        }
        exit;
        
	}
	
}