<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;
use AsyncWeb\Text\Texts;

class Index extends \AsyncWeb\Frontend\Block{
    
    private function listdir_by_date($pathtosearch)
    {
        
        foreach (scandir($pathtosearch) as $filename)
        {
            if($filename == ".") continue;
            if($filename == "..") continue;
            $file_array[filemtime($pathtosearch.$filename)]=basename($filename); 
        }
        krsort($file_array);
        return $file_array;
    }
    
	public function init(){

        $cssfiles = $this->listdir_by_date($cssd = 'dist/css/');
        $files = $this->listdir_by_date($jsd = 'dist/js/');
        $usr = \AsyncWeb\Objects\User::get();
        $this->setData($d = [
            "URI"=>$_SERVER["REQUEST_URI"],
            "CSSURL"=>'/'.$cssd.str_replace(".gz","",reset($cssfiles)),
            "ScriptsURL"=>'/'.$jsd.str_replace(".gz","",reset($files)),
            "UserEmail"=>$usr["email"],
            "UserName"=>trim($usr["firstname"]." ".$usr["lastname"]),
        ]);
	}
}