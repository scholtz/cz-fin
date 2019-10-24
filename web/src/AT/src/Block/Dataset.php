<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;

class Dataset extends \AsyncWeb\Frontend\Block{
    protected $requiresAuthenticatedUser = true;
	public function init(){
        
        $row = DB::gr("data_czfin_datasets",["tt"=>URLParser::v("f")]);
        if($row){
            $licence = \AT\Classes\Licence::highestUserLicence();            
            
            if($licence == "enterprise" || $licence == "premium"){
                $this->sendFile($row);
            }
        }
        
        $this->setData(["Stats"=>$stats]);
	}
    private function sendFile($dataset){
        if(file_exists($dataset["file"])){
            header("Content-Type: application/x-gzip");
            header("Content-Length: ".$dataset["size_compressed"]);
            if($dataset["etag"]) header("ETag: ".$dataset["etag"]);
            header('Content-Disposition: attachment; filename="'.$dataset["tt"].'.csv.gz"');
            header("Last-Modified: ".gmdate( "D, d M Y H:i:s", $dataset["od"] )." GMT" );


            $etagHeader     = ( isset( $_SERVER["HTTP_IF_NONE_MATCH"] ) ? trim( $_SERVER["HTTP_IF_NONE_MATCH"] ) : false );
            $modified_since = ( isset( $_SERVER["HTTP_IF_MODIFIED_SINCE"] ) ? strtotime( $_SERVER["HTTP_IF_MODIFIED_SINCE"] ) : false );
            if ( (int)$modified_since === (int)$last_modified && $etag === $etagHeader ) {
              header( "HTTP/1.1 304 Not Modified" );
              exit;
            }
            $fd = fopen($dataset["file"], "rb");
            if($fd){
                while(!feof($fd)) {
                    $buffer = fread($fd, 1*(1024*1024));
                    echo $buffer;
                    ob_flush();
                    flush();    //These two flush commands seem to have helped with performance
                }
            }
            exit;
        }
    }
}