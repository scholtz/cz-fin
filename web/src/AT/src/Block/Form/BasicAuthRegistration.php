<?php

namespace AT\Block\Form;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\DB\DB;

class BasicAuthRegistration extends \AsyncWeb\DefaultBlocks\Form\BasicAuthRegistration{
    public function postInit(){
        $this->template = '<div class="container"><h1>Registrace</h1>'.$this->template.'</div>';
    }
}