<?php
namespace AT\Classes;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\DB\DB;

class Licence{
    public static function highestUserLicence($email = ""){
        if(!$email) $email = \AsyncWeb\Objects\User::getEmailOrId();
        $res = DB::qb("fin_licence_users",[ 
            "distinct"=>true,
            "cols"=>["licence"],
            "where"=>["email"=>$email]
            ]);
        $ret = false;
        while($row=DB::f($res)){
            $licence = DB::qbr("fin_licences",["cols"=>["type","end"],"where"=>["id2"=>$row["licence"]]]);
            if($licence["end"] < time()){
                continue;
            }
            switch($licence["type"]){
                case "enterprise":
                    return "enterprise";
                break;
                case "premium":
                    $ret = $licence["type"];
                break;
                case "personal":
                    if(!$ret) $ret = $licence["type"];
                break;
            }
        }
        return $ret;
    }
    public static function availableUserLicences($showHistory = false){
        $ret = [];
        $res = DB::qb("fin_licence_users",[ 
            "distinct"=>true,
            "cols"=>["licence"],
            "where"=>["email"=>\AsyncWeb\Objects\User::getEmailOrId()]
            ]);
        while($row=DB::f($res)){
            $licence = DB::qbr("fin_licences",["cols"=>["name","end"],"where"=>["id2"=>$row["licence"]]]);
            if(!$showHistory){
                if(is_numeric($licence["end"])){
                    if($licence["end"] < time()){
                        continue;
                    }
                }else{
                    if(strtotime($licence["end"]) < time()){
                        continue;
                    }
                }
            }
            $ret[$row["licence"]] = $licence["name"];
        }
        
        return $ret;
    }
    public static function availableUserLicencesIds($showHistory = false){
        return array_keys(self::availableUserLicences($showHistory));
    }

    public static function availableMonitorsLiveForUser($email = ""){
        if(!$email) $email = \AsyncWeb\Objects\User::getEmailOrId();
        $ret = 0;
        $res = DB::qb("fin_licence_users",[ 
            "distinct"=>true,
            "cols"=>["licence"],
            "where"=>["email"=>$email]
            ]);
        while($row=DB::f($res)){
            $licence = DB::qbr("fin_licences",["cols"=>["type","end"],"where"=>["id2"=>$row["licence"]]]);
            if(strtotime($licence["end"]) < time()){
                continue;
            }

            switch($licence["type"]){
                case "premium":
                    $ret+=5;
                break;
                case "enterprise":
                    $ret+=10000;
                break;
            }
        }
        return $ret;
    }
    public static function availableMonitorsDayForUser($email = ""){
        if(!$email) $email = \AsyncWeb\Objects\User::getEmailOrId();
        $ret = 0;
        $res = DB::qb("fin_licence_users",[ 
            "distinct"=>true,
            "cols"=>["licence"],
            "where"=>["email"=>$email]
            ]);
        while($row=DB::f($res)){
            $licence = DB::qbr("fin_licences",["cols"=>["type","end"],"where"=>["id2"=>$row["licence"]]]);
            if(strtotime($licence["end"]) < time()){
                continue;
            }
            switch($licence["type"]){
                case "personal":
                    $ret+=1;
                break;
                case "premium":
                    $ret+=10;
                break;
                case "enterprise":
                    $ret+=10000;
                break;
            }
        }
        return $ret;
    }
    public static function availableMonitorsMonthForUser($email = ""){
        if(!$email) $email = \AsyncWeb\Objects\User::getEmailOrId();
        $ret = 0;
        $res = DB::qb("fin_licence_users",[ 
            "distinct"=>true,
            "cols"=>["licence"],
            "where"=>["email"=>$email]
            ]);
        while($row=DB::f($res)){
            $licence = DB::qbr("fin_licences",["cols"=>["type","end"],"where"=>["id2"=>$row["licence"]]]);
            if(strtotime($licence["end"]) < time()){
                continue;
            }
            switch($licence["type"]){
                case "personal":
                    $ret+=1;
                break;
                case "premium":
                    $ret+=10;
                break;
                case "enterprise":
                    $ret+=10000;
                break;
            }
        }
        return $ret;
    }
    public static function currentUsageMonitorLive($email = ""){
        if(!$email) $email = \AsyncWeb\Objects\User::getEmailOrId();

        $ret = 0;
        $res = DB::qb("fin_licence_users",[ 
            "distinct"=>true,
            "cols"=>["licence"],
            "where"=>["email"=>$email]
            ]);
        while($row=DB::f($res)){
            $res2 = DB::qb("dev02.spravy_watch",["cols"=>["id2"],"where"=>["licence"=>$row["licence"]]]);
            $ret+=DB::num_rows($res2);
        }
        
        return $ret;
    }
    public static function currentUsageMonitorDay($email = ""){
        if(!$email) $email = \AsyncWeb\Objects\User::getEmailOrId();

        $ret = 0;
        $res = DB::qb("fin_licence_users",[ 
            "distinct"=>true,
            "cols"=>["licence"],
            "where"=>["email"=>$email]
            ]);
        while($row=DB::f($res)){
            $res2 = DB::qb("dev02.spravy_watch_day",["cols"=>["id2"],"where"=>["licence"=>$row["licence"]]]);
            $ret+=DB::num_rows($res2);
        }
        
        return $ret;
    }
    public static function currentUsageMonitorMonth($email = ""){
        if(!$email) $email = \AsyncWeb\Objects\User::getEmailOrId();

        $ret = 0;
        $res = DB::qb("fin_licence_users",[ 
            "distinct"=>true,
            "cols"=>["licence"],
            "where"=>["email"=>$email]
            ]);
        while($row=DB::f($res)){
            $res2 = DB::qb("dev02.spravy_watch_month",["cols"=>["id2"],"where"=>["licence"=>$row["licence"]]]);
            $ret+=DB::num_rows($res2);
        }
        
        return $ret;
    }
    public static function licenceManagers(){
        $ret = [];
        $res = DB::qb("fin_licence_users",["cols"=>["licence"],"where"=>["type"=>"admin","email"=>\AsyncWeb\Objects\User::getEmailOrId()]]);
        while($row=DB::f($res)){
            $ret[$row["licence"]] = $row["licence"];
        }
        return $ret;
    }
    public static function licenceUsersCount($licence = ""){
        if(!$licence) $licence = URLParser::v("licence");
        $ret = [];
        $res = DB::qb("fin_licence_users",["cols"=>["email"],"where"=>["licence"=>$licence]]);
        while($row=DB::f($res)){
            $ret[$row["email"]] = $row["email"];
        }
        return $ret;
    }
    public static function licenceUsersManagers($licence = ""){
        if(!$licence) $licence = URLParser::v("licence");
        $ret = [];
        $res = DB::qb("fin_licence_users",["cols"=>["email"],"where"=>["type"=>"admin","licence"=>$licence]]);
        while($row=DB::f($res)){
            $ret[$row["email"]] = $row["email"];
        }
        return $ret;
    }
    
    
    public static function newLicenceByGoPay($goid,$order){
        if(!$goid) return;
        
        $lic = DB::gr("fin_licences",["payment"=>$goid]);
        $ord = DB::qbr("fin_orders",["where"=>["vs"=>$order],"order"=>["od"=>"desc"]]);
        if($lic){
            \AsyncWeb\Text\Msg::mes(Language::get("Thank you for your payment. Your licence has been already activated."));
            return;
        }
        if($ord){
            \AsyncWeb\Text\Msg::mes(Language::get("Your licence has been activated"));
            DB::u("fin_licences",$lic = md5(uniqid()),[
                "name"=>$ord["name"],
                "type"=>$ord["type"],
                "start"=>time(),
                "end"=>strtotime("+1 years"),
                "email"=>\AsyncWeb\Objects\User::getEmailOrId(),
                "payment"=>$goid,
                "vs"=>$order,
            ]);
            
            DB::u("fin_licence_users",md5(uniqid()),[
                "type"=>"admin",
                "email"=>\AsyncWeb\Objects\User::getEmailOrId(),
                "licence"=>$lic,
            ]);
        }else{
            \AsyncWeb\Text\Msg::err(Language::get("We have received your payment, however we were not able to process it automatically. Please contact us at info@cz-fin.com with payment identifier %id%",["%id%"=>$goid]));
        }
    }
    public static function newLicenceByCoupon($code){
        if($coderow = DB::gr("fin_referal",["code"=>$code])){
            if(!$coderow["used"] && $coderow["type"] == "full"){

                \AsyncWeb\Text\Msg::mes(Language::get("Your licence has been activated"));
                DB::u("fin_licences",$lic = md5(uniqid()),[
                    "name"=>$code,
                    "type"=>$coderow["licencetype"],
                    "start"=>$coderow["start"],
                    "end"=>$coderow["end"],
                    "email"=>\AsyncWeb\Objects\User::getEmailOrId(),
                ]);
                DB::u("fin_referal",$coderow["id2"],["used"=>"1"]);
                
                DB::u("fin_licence_users",md5(uniqid()),[
                    "type"=>"admin",
                    "email"=>\AsyncWeb\Objects\User::getEmailOrId(),
                    "licence"=>$lic,
                ]);
            }
        }
    }
    
}