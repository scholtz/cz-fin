<?php
namespace AT\Block\Content;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;

class Main extends \AsyncWeb\Frontend\Block{
	
	public function initTemplate(){
		var_dump("a");exit;
		$ret = "a";
		$this->template = $ret;
	}
	
}