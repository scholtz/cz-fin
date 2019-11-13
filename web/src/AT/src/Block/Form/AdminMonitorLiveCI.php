<?php

namespace AT\Block\Form;

use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\DB\DB;

class AdminMonitorLiveCI extends MonitorLiveCI{
    protected $requiresAuthenticatedUser = true;
    protected $requiresAllGroups = ["admin"];

    public $type = "CI";
    public function preProcess(){        
        if(\AsyncWeb\Objects\Group::is_in_group("admin")){
            
            $this->formSettings["col"]["lic"] = array("name"=>Language::get("Licence"),"data"=>array("col"=>"licence"),"filter"=>array("type"=>"option","option"=>\AT\Classes\Licence::availableAllLicences()),"usage"=>array("MFi","MFu","DBVs","DBVe"));
            
            unset($this->formSettings["where"]["email"]);
        }
    }
    public function postProcess(){
        $this->template = '<h1>Admin LIVE monitoring médií</h1><p>Nastavení sledovaní výrazú bez diakritiky a bez sensibility na veľké a malé písmená (case-insensitive)</p>'.$this->template;
    }
}