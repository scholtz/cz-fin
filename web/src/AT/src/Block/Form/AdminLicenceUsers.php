<?php

namespace AT\Block\Form;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\DB\DB;

class AdminLicenceUsers extends LicenceUsers{
    protected $requiresAuthenticatedUser = true;
    protected $requiresAllGroups = ["admin"];
    
    public function verifyRights(){
        
        if(!\AsyncWeb\Objects\Group::is_in_group("admin")){
            $this->template = '<div class="alert alert-danger">'.Language::get("You are not allowed to manage users for this licence").'</div>';
            $this->postProcess();
            return false;
        }
        
        return true;
    }
    public function preProcess(){
        
    }
    public function postProcess(){
        $this->template = '<h2>Administrátorský zoznam užívateľov pre licenciu</h2>'.$this->template;
        
    }
}