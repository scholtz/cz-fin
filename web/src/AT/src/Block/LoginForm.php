<?php
namespace AT\Block;
use AsyncWeb\Security\Auth;
class LoginForm extends \AsyncWeb\Frontend\Block {
    public static $DICTIONARY = array(
        "sk-SK" => array("Authentication succeess" => "Úspešne ste sa prihlásili", "You are authenticated as" => "Ste prihlásený ako", "Web requires authentication" => "Webstránka vyžaduje prihláseného užívateľa",), 
        "en-US" => array("Authentication succeess" => "Authentication succeess", "You are authenticated as:" => "You are authenticated as", "Web requires authentication" => "Web requires authentication",),
        "cs-CZ" => array("Authentication succeess" => "Úspěšně jste se přihlásili", "You are authenticated as:" => "Jste přihlášen jako", "Web requires authentication" => "Je vyžadováno ověření uživatele",),);
    protected function initTemplate() {
        if (!Auth::check(true)) {
            //$ret = '<h1>{{Web requires authentication}}</h1>';
            $ret.= Auth::loginForm();
        } elseif (true !== Auth::checkControllers()) {
            $ret.= Auth::showControllerForm();
        } else {
            $ret = '<h1>{{Authentication succeess}}</h1><p>{{You are authenticated as}}: ' . \AsyncWeb\Objects\User::getEmail() . '.</p>';
        }
        $this->template = $ret;
    }
}
