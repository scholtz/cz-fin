<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;

class Main extends \AsyncWeb\Frontend\Block{
	
	public function initTemplate(){
        $ret = "<h1>Hledejte v databáze firem</h1>";
		$this->template = $ret;
	}
	
}