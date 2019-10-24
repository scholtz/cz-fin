<?php

namespace AT\Block\Form;

use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\DB\DB;

class AdminMonitorMonthCI extends MonitorMonthCI{
    public $type = "CI";
    public function preProcess(){        
        if(\AsyncWeb\Objects\Group::is_in_group("admin")){
            unset($this->formSettings["where"]["email"]);
        }
    }
    public function postProcess(){
        $this->template = '<h1>Admin mesačný monitoring médií</h1><p>Nastavení sledovaní výrazú bez diakritiky a bez sensibility na veľké a malé písmená (case-insensitive)</p>'.$this->template;
    }
}