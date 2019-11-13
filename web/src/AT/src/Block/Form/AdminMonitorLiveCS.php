<?php

namespace AT\Block\Form;

use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\DB\DB;

class AdminMonitorLiveCS extends MonitorLiveCS{
    protected $requiresAuthenticatedUser = true;
    protected $requiresAllGroups = ["admin"];

    public $type = "CS";
    public function preProcess(){        
        if(\AsyncWeb\Objects\Group::is_in_group("admin")){
            unset($this->formSettings["where"]["email"]);
        }
    }
    public function postProcess(){
        $this->template = '<h1>Admin LIVE monitoring médií - CS</h1><p>Nastavení sledovaní výrazú s diakritikou a sensibilitou na veľké a malé písmená (case-sensitive)</p>'.$this->template;
    }
}