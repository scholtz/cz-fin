<?php

namespace AT\Block\Form;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\DB\DB;

class LicenceUsers extends \AsyncWeb\DefaultBlocks\Form{
    protected $requiresAuthenticatedUser = true;
    
    public static function onInsert($r){
        $row = $r["new"];
        $usr = DB::qbr("users",["cols"=>["firstname"],"where"=>["login"=>\AsyncWeb\Objects\User::getEmailOrId()]]);
        $newusr = DB::qbr("users",["cols"=>["firstname"],"where"=>["login"=>$row["email"]]]);

        $m = new \Mustache_Engine();
        $data = [];
        $data["Name"] = trim($usr["firstname"]." ".$usr["lastname"]);

        if(!$newusr){
            $data["NewUser"] = $row["email"];
        }else{
            $data["NewUser"] = false;
        }
        $l = DB::gr("fin_licences",["id2"=>$row["licence"]]);

        switch($l["type"]){
            case "personal":
                $data["LicenceName"] = "FinPERSONAL";
                $data["Features"] = '<ul>'
                    .'<li>Denní a měsíční monitoring médií</li>'
                    .'<li>Pokročilé filtrování v ktalógu českých firem</li>'
                    .'<li>Exporty do Excelu</li>'
                    .'</ul>'
                    ;
            break;
            case "premium":
                $data["LicenceName"] = "FinPREMIUM";
                $data["Features"] = '<ul>'
                    .'<li>Monitoring médií - živý, denní nebo měsíční</li>'
                    .'<li>Pokročilé filtrování v ktalógu českých firem</li>'
                    .'<li>Exporty do Excelu</li>'
                    .'<li>CSV Datasety</li>'
                    .'<li>5 uživatelů</li>'
                    .'</ul>'
                    ;
            break;
            case "enterprise":
                $data["LicenceName"] = "FinENTERPRISE";
                $data["Features"] = '<ul>'
                    .'<li>Neomezený monitoring médií - živý, denní nebo měsíční</li>'
                    .'<li>Pokročilé filtrování v ktalógu českých firem</li>'
                    .'<li>Exporty do Excelu</li>'
                    .'<li>CSV Datasety</li>'
                    .'<li>Neomezený počet uživatelů</li>'
                    .'</ul>'
                    ;
            break;
        }
        
        $html =  $m->render(file_get_contents("/ocz/vhosts/cz-fin.com/prod01/src/AT/src/Templates/Email/CS/AssignedToLicence.html"), $data);
        \AsyncWeb\Email\Email::send($row["email"],"Přiřazení k licenci",$html,"info@cz-fin.com",$att,"html");
        
    }
    public static function onUpdate($r){
        $row = $r["new"];
        if($r["new"]["email"] != $r["old"]["email"]){
            self::onInsert($r);
        }
    }
    
    
    public static function beforeInsert(){
        $n = \AT\Classes\Licence::licenceUsersCount();
        $l = DB::gr("fin_licences",["id2"=>URLParser::v("licence")]);
        switch($l["type"]){
            case "personal":
                $limit = 1;
            break;
            case "premium":
                $limit = 5;
            break;
            case "enterprise":
                $limit = 10000;
            break;
            default:
                $limit = 0;
        }
        if(count($n) >= $limit){
            throw new \Exception(Language::get("You have reached limit of users for this licence. Current number of users is %n%. Limit is %limit%.",["%n%"=>count($n),"%limit%"=>$limit]));
        }
        
        if(isset($n[URLParser::v("licenceusers_email")])){
            throw new \Exception(Language::get("This email is already registered for this licence"));
        }
        
		return true;
    }
    
    public static function beforeUpdate($r){
        $n = \AT\Classes\Licence::licenceUsersManagers();
        $row = $r["r"];
        if($row["type"] == "admin"){
            unset($n[$row["email"]]);
        }
        
		if(!\AsyncWeb\Objects\Group::is_in_group("admin")){
            if(count($n) < 1){
                throw new \Exception(Language::get("You must keep at least one licence administrator"));
            }
        }
        
		return true;
    }
    
    public static function beforeDelete($r){
        $n = \AT\Classes\Licence::licenceUsersManagers();
        $row = $r["r"];
        if($row["type"] == "admin"){
            unset($n[$row["email"]]);
        }
		if(!\AsyncWeb\Objects\Group::is_in_group("admin")){
        
            if(count($n) < 1){
                throw new \Exception(Language::get("You must keep at least one licence administrator"));
            }
        }
        
		return true;
    }
    
    public function verifyRights(){
        $allowed = \AT\Classes\Licence::licenceManagers();
        if(!isset($allowed[URLParser::v("licence")]) || !$allowed[URLParser::v("licence")]){
            $this->template = '<div class="alert alert-danger">'.Language::get("You are not allowed to manage users for this licence").'</div>';
            $this->postProcess();
            return false;
        }
        return true;
    }
	public function initTemplate(){
        \AsyncWeb\View\MakeDBView::$repair = true;
        if(!$this->verifyRights()) return;
        
		$this->formSettings = array(
            "uid"=>"licenceusers",
            "table" => "fin_licence_users",
            "col" => array( 
                array("name"=>Language::get("Email"),"data"=>array("col"=>"email"),"usage"=>array("MFi","MFu","DBVs","DBVe")),
                array("name"=>Language::get("User Type"),"data"=>array("col"=>"type"),"filter"=>array("type"=>"option","option"=>array(
                  "standard"=>Language::get("Standard"),
                  "admin"=>Language::get("Admin - Can manage users"),
                )),"usage"=>array("MFi","MFu","DBVs","DBVe")),

            ),
            "texts"=>array("no_data"=>Language::get("This licence does not have any active user")),
            "bootstrap"=>"1","MakeDVView"=>5,
            "where"=>array(
                "licence"=>URLParser::v("licence"),
            ),
            "iter"=>array("per_page"=>"30"),
            
            "execute"=>array(
                "beforeInsert"=>"PHP::\\AT\\Block\\Form\\LicenceUsers::beforeInsert()",
                "onInsert"=>"PHP::\\AT\\Block\\Form\\LicenceUsers::onInsert()",
                "beforeUpdate"=>"PHP::\\AT\\Block\\Form\\LicenceUsers::beforeUpdate()",
                "onUpdate"=>"PHP::\\AT\\Block\\Form\\LicenceUsers::onUpdate()",
                "beforeDelete"=>"PHP::\\AT\\Block\\Form\\LicenceUsers::beforeDelete()",
                ),

        );
        $this->formSettings["show_export"]=true;
        $this->formSettings["allowInsert"]=true;
        $this->formSettings["allowUpdate"]=true;
        $this->formSettings["allowDelete"]=true;
        $this->formSettings["useForms"]=true;
        $this->formSettings["rights"]=["insert"=>"","update"=>"","delete"=>""];
        
        $this->preProcess();
		$this->initTemplateForm();
        $this->postProcess();
	}
    public function preProcess(){
        
    }
    public function postProcess(){
        $this->template = '<h2>Seznam uživatelů pro licenci</h2>'.$this->template;
        
    }
}