<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;

class Firma extends \AsyncWeb\Frontend\Block{
	
	public function initTemplate(){
        $current = URLParser::v("ico");
        if($row = DB::gr("data_czfin_pages",["id2"=>$current])){
            $this->template = $row["page"];
            return;
        }
        
        $ret = '<h1>Not found</h1><p>Firma nebola nájdená</p>';
        
        $this->template = $ret;
        
	}
	
}