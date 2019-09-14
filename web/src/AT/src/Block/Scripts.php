<?php
namespace AT\Block;

class Scripts extends \AsyncWeb\Frontend\Block{
	protected function initTemplate(){
		$this->template = ' ';
        header("Content-Type: text/javascript");
        
        if($txt = \AsyncWeb\Text\Messages::getInstance()->show()){
            $txt = addslashes($txt);
            $txt = '$("#messages").html(\''.trim($txt).'\');';
            $txt = str_replace("\r","",$txt);
            $txt = str_replace("\n","",$txt);
            echo $txt;
        }
        exit;
	}
}