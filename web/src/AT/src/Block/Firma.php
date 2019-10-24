<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;

class Firma extends \AsyncWeb\Frontend\Block{
	public function setTitle($page){
        if(($titlestart = strpos($page,'<h1>')) !== false){
            if($titleend = strpos($page,'</h1>')){
                $title = substr($page,$titlestart,$titleend-$titlestart);
                $title = str_replace([", s. r. o.","s. r. o.", ", s.r.o.",  "s.r.o.", ", a. s.", "a. s.", "a.s.", ", a.s."], "",$title);
                $title = strip_tags($title);
                $title = trim($title);
                $title = str_replace([" *"], "",$title);
                $title = trim($title);
                \AsyncWeb\Frontend\BlockManagement::get("Content_HTMLHeader_Title")->changeData(array("title" => "$title | CZ-FIN"));
            }                
        }
    }
    public function setEtag($content, $last_modified){
        $etag = sprintf( '"%s-%s"', $last_modified, crc32( $content ) );
        header( "Etag: ".$etag );
        header( "Last-Modified: ".gmdate( "D, d M Y H:i:s", $last_modified )." GMT" );
        $etagHeader     = ( isset( $_SERVER["HTTP_IF_NONE_MATCH"] ) ? trim( $_SERVER["HTTP_IF_NONE_MATCH"] ) : false );
        $modified_since = ( isset( $_SERVER["HTTP_IF_MODIFIED_SINCE"] ) ? strtotime( $_SERVER["HTTP_IF_MODIFIED_SINCE"] ) : false );
        if ( (int)$modified_since === (int)$last_modified && $etag === $etagHeader ) {
          header( "HTTP/1.1 304 Not Modified" );
          exit;
        }

    }
	public function initTemplate(){
        $ico = URLParser::v("ico");
        $licence = \AT\Classes\Licence::highestUserLicence();
        if($licence){
            // if we are logged in, do not use cached website
            $builder = new \AT\Classes\PageBuilder();
            if($row = DB::qbr("devcz.data_arescz_company_core",["where"=>["ico"=>$ico],["col"=>"datumvymazu","op"=>"is"]])){
                $ret = $builder->makeCompanyPage($row,false,false,$membership="premium",true);
                $this->setTitle($ret["text"]);
                $this->template = $ret["text"];
                
                $this->setEtag($ret["text"],time());
                if($this->template){
                    return;
                }
            }
        }
            
        if($row = DB::gr("data_czfin_pages",["id2"=>$ico])){
            
            $page = $row["page"];


            $this->setTitle($page);
            $this->template = $page;
            $this->setEtag($page,$row["od"]);
            
            return;
        }
        
        $ret = '<h1>Not found</h1><p>Firma nebola nájdená</p>';
        
        $this->template = $ret;
    
        
	}
	
}