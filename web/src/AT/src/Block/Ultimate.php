<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;

class Ultimate extends \AsyncWeb\Frontend\Block{
    protected $requiresAuthenticatedUser = true;
	public function init(){
        header("Location: /Personal");
        exit;
        $this->setData(["Stats"=>$stats]);
        
	}
}