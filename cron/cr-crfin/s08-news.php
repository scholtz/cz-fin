<?php

use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;
require_once("/cron2/settingsCZ.php");
echo "starting.. ".date("c")."\n";

Cron::start(24*3600);


$ctys = ["sk","cz"];
$periods = ["1h","3h","12h","24h","w"];


require_once("/ocz/vhosts/cz-fin.com/prod01/src/AT/src/Classes/PageBuilder.php");

$PageBuilder = new \AT\Classes\PageBuilder();

foreach($ctys as $cty){
    foreach($periods as $period){
        echo "$cty $period ".date("c")."\n";
        $PageBuilder->makeNewsPage($cty,$period,true);
    }
}

Cron::end();

echo "finished ".date("c")."\n";
