<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;

class GoPayReturn extends \AsyncWeb\Frontend\Block{
    public function initTemplate(){
        var_dump($_POST);exit;
        $response = $gopay->getStatus('payment id');
        if ($response->hasSucceed()) {
            echo "hooray, API returned {$response}<br />\n";
        } else {
            echo "oops, API returned {$response->statusCode}: {$response}";
        }
	}
}