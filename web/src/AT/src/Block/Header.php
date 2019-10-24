<?php

namespace AT\Block;


use AsyncWeb\DB\DB;
use AsyncWeb\Frontend\URLParser;
use AsyncWeb\Security\Auth;

class Header extends \AsyncWeb\Frontend\Block{
	public function initTemplate(){
        
        $cat = UrlParser::v("n");
        $add = "";
        if($nace = DB::gr("sknace",["id5cz"=>$cat])){
            if($nace["id5"]){
            $add = '      <li class="nav-item">
        <a class="nav-link" href="https://www.sk-fin.com/Page:Nace/cat='.$nace["id5"].'">SK-FIN</a>
      </li>';
            }
        }/**/
        
        $search = UrlParser::v("search");
        if($search){
            $add = '      <li class="nav-item">
        <a class="nav-link" href="https://www.sk-fin.com/Page:Spravy/search='.urlencode($search).'">SK-FIN</a>
      </li>';
            
        }
        if(substr($_SERVER["REQUEST_URI"],0,$p = strlen("/Spravy")) == "/Spravy"){
            $append = str_replace("/Spravy","",$_SERVER["REQUEST_URI"]);
            $add = '      <li class="nav-item">
        <a class="nav-link" href="https://www.sk-fin.com/Page:Spravy'.$append.'">SK-FIN</a>
      </li>';
        }
        if(!$add){
            $add = '      <li class="nav-item">
        <a class="nav-link" href="https://www.sk-fin.com/">SK-FIN</a>
      </li>';
        }
		if(\AsyncWeb\Objects\Group::is_in_group("admin")){
			$add .='<li class="nav-item dropdown">
      <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
        Admin
      </a>
      <div class="dropdown-menu">
      
        <a class="dropdown-item" href="/Form_AdminCoupons">Kupóny</a>
        <a class="dropdown-item" href="/Form_AdminLicenceOverview">Licencie</a>
        <a class="dropdown-item" href="/AdminMonitoringLive">Monitoring LIVE</a>
        <a class="dropdown-item" href="/Form_AdminMonitorDayCI">Monitoring DAY</a>
        <a class="dropdown-item" href="/Form_AdminMonitorMonthCI">Monitoring MONTH</a>
        <a class="dropdown-item" href="/Form_NewsConfig">News Config</a>
        <a class="dropdown-item" href="/Form_HTML2RSS">HTML2RSS Config</a>
        <a class="dropdown-item" href="/Admin_MissedNews">Missed news</a>
        
        
      </div>
    </li>';
		}
        $add2 = '';
        if(\AsyncWeb\Objects\User::getEmailOrId()){
            $add2 .='
            <li class="nav-item dropdown">
      <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
        <img src="https://www.gravatar.com/avatar/'.md5( strtolower( trim( \AsyncWeb\Objects\User::getEmailOrId() ) ) ).'?s=80&d=mp" height="60px">
      </a>
      <div class="dropdown-menu dropdown-menu-right">';
      $licence = \AT\Classes\Licence::highestUserLicence();
      switch($licence){
          case "personal":
      $add2.='
        <a class="dropdown-item" href="/Personal">Fin PERSONAL</a>';
          break;
          case "premium":
      $add2.='
        <a class="dropdown-item" href="/Premium">Fin PREMIUM</a>';
          break;
          case "enterprise":
      $add2.='
        <a class="dropdown-item" href="/Enterprise">Fin ENTERPRISE</a>';
          break;
      }
      $add2.='
        <a class="dropdown-item" href="/Form_LicenceOverview">Licence</a>
        <a class="dropdown-item" href="/Form_MonitorLiveCI">Monitoring médií LIVE</a>
        <a class="dropdown-item" href="/Form_MonitorDayCI">Monitoring médií - Denný report</a>
        <a class="dropdown-item" href="/Form_MonitorMonthCI">Monitoring médií - Mesačný report</a>
        <a class="dropdown-item" target="_blank" href="https://cs.gravatar.com/'.md5( strtolower( trim( \AsyncWeb\Objects\User::getEmailOrId() ) ) ).'">Nastav avatar</a>
        <a class="dropdown-item" href="/Bonus">Bonus</a>
        
        <a class="dropdown-item" href="/Form_InvoiceSettings">Fakturační údaje</a>
        <a class="dropdown-item" href="/Form_UserSettings">Uživatelské nastavení</a>
        <a class="dropdown-item" href="./logout=1">Odhlášení</a>
      </div>
    </li>';
        }else{
            $add2 .='
            <li class="nav-item">
      <a class="nav-link" href="/Personal">
        <img src="https://www.gravatar.com/avatar/'.md5( strtolower( trim( \AsyncWeb\Objects\User::getEmailOrId() ) ) ).'?s=80&amp;d=mp" height="60px">
      </a>
    </li>';
        }
        
		$this->template = '<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <a class="navbar-brand" href="/"><img height="70" src="/img/logo.png"></a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="collapse navbar-collapse" id="navbarSupportedContent">
    <ul class="navbar-nav mr-auto">
      <li class="nav-item active">
        <a class="nav-link" href="/">Prehled činností</a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="/Spravy">Monitoring médií</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="/Cenik">Ceník</a>
      </li>
    <li class="nav-item dropdown">
      <a class="nav-link dropdown-toggle" href="#" id="navbardrop" data-toggle="dropdown">
        Statistiky
      </a>
      <div class="dropdown-menu">
        <a class="dropdown-item" href="/Stats">Statistiky dat</a>
        <a class="dropdown-item" href="/Datasety">Seznam použitých datových zdrojů</a>
      </div>
    </li>
      '.$add.'
    </ul>
    <ul class="navbar-nav ml-auto">
        <form class="form-inline my-2 my-lg-0" method="post" action="/Search/">
          <input class="form-control mr-sm-2" type="search" name="text" placeholder="Název nebo IČO společnosti" aria-label="Search">
          <button class="btn btn-outline-success my-2 my-sm-0" type="submit">Vyhledat</button>
        </form>'.$add2.'
    </ul>
  </div>
</nav>
';

	}
	
}