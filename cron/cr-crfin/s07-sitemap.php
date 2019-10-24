<?php

use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;
require_once("/cron2/settingsCZ.php");
echo "starting.. ".date("c")."\n";

Cron::start(24*3600);



$res = DB::qb("out.data_czfin_nace2firma",["cols"=>["id2","od"],"order"=>["od"=>"asc"]]);
var_dump(DB::error());
var_dump($cc = DB::num_rows($res));
$ret = "";
$i = 0;
$priority = 1;
$items = 0;
echo "\NACE:";

$ret = "";
$ret.='<url><loc>https://www.cz-fin.com/</loc><lastmod>'.date("Y-m-d").'</lastmod><changefreq>yearly</changefreq><priority>1</priority></url>'."\n";
$ret.='<url><loc>https://www.cz-fin.com/Stats</loc><lastmod>'.date("Y-m-d").'</lastmod><changefreq>monthly</changefreq><priority>1</priority></url>'."\n";
$ret.='<url><loc>https://www.cz-fin.com/Datasety</loc><lastmod>'.date("Y-m-d").'</lastmod><changefreq>monthly</changefreq><priority>1</priority></url>'."\n";
$ret.='<url><loc>https://www.cz-fin.com/Search/</loc><lastmod>'.date("Y-m-d").'</lastmod><changefreq>monthly</changefreq><priority>1</priority></url>'."\n";
$ret.='<url><loc>https://www.cz-fin.com/PersonalData</loc><lastmod>'.date("Y-m-d").'</lastmod><changefreq>monthly</changefreq><priority>0</priority></url>'."\n";
$ret.='<url><loc>https://www.cz-fin.com/Cookies</loc><lastmod>'.date("Y-m-d").'</lastmod><changefreq>monthly</changefreq><priority>0</priority></url>'."\n";


while($row=DB::f($res)){$i++;
    
    $nace = DB::gr("sknace",["id4"=>$row["id2"]]);
    $priority = round($i / $cc,2);
    $web = "https://www.cz-fin.com/Nace/n=".$nace["id5cz"];
    
    $ret.='<url><loc>'.$web.'</loc><lastmod>'.date("Y-m-d",$row["od"]).'</lastmod><changefreq>monthly</changefreq><priority>1</priority></url>'."\n";
    $items++;
    if(strlen($ret) > 90000000 || $items > 40000){
        $out[$n++] = $ret;
        $items= 0;
        $ret = "";
    }
    if($i%100==0) echo ".";
    if($i%10000==0) echo "$i/$cc/".date("c")."\n";
    /*
    if($i > 10){
        echo $ret;
        exit;
        var_dump($row);exit;
    }/**/
    
    $maxdates[$n] = $row["od"];
    
}
$out[$n++] = $ret;

$res = DB::qb("out.data_czfin_pages",["cols"=>["id2","od"],"order"=>["od"=>"asc"]]);
var_dump(DB::error());
var_dump($cc = DB::num_rows($res));
$i = 0;
$priority = 1;
$items = 0;
$out = [];
$n = 1;
$maxdates = [];
echo "\nFIRMY:";
while($row=DB::f($res)){$i++;
    $rating = DB::qbr("out.data_czfin_rating",["where"=>["id2"=>$row["id2"]],"cols"=>["clear","rating","ratingmax"]]);
    
    $priority = round($i / $cc,2);
    
    $web = "https://www.cz-fin.com/Firma/ico=".$row["id2"]."/n=".$rating["clear"];
    $rating = 0;
    if($rating["ratingmax"]){
        $rating = round($rating["rating"]/$rating["ratingmax"],2);
    }
    $ret.='<url><loc>'.$web.'</loc><lastmod>'.date("Y-m-d",$row["od"]).'</lastmod><changefreq>yearly</changefreq><priority>'.$rating.'</priority></url>'."\n";
    $items++;
    if(strlen($ret) > 90000000 || $items > 40000){
        $out[$n++] = $ret;
        $items= 0;
        $ret = "";
    }
    if($i%100==0) echo ".";
    if($i%10000==0) echo "$i/$cc/".date("c")."\n";
    /*
    if($i > 10){
        echo $ret;
        exit;
        var_dump($row);exit;
    }/**/
    
    $maxdates[$n] = $row["od"];
    
}
$out[$n++] = $ret;











$sitemaps = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
foreach($out as $i=>$data){
    $data = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n".$data.'</urlset>';

    $gzfile = "/ocz/vhosts/cz-fin.com/prod01/htdocs/sitemap-$i.xml.gz";
    echo "\n$gzfile " . date("c");
    $fp = gzopen ($gzfile, 'w9');
    gzwrite ($fp, $data);
    gzclose($fp);
    $sitemaps .= '<sitemap><loc>https://www.cz-fin.com/sitemap-'.$i.'.xml.gz</loc><lastmod>'.date("c",$maxdates[$i]).'</lastmod></sitemap>'."\n";
}

$sitemaps .= '</sitemapindex>';
file_put_contents("/ocz/vhosts/cz-fin.com/prod01/htdocs/sitemap.xml",$sitemaps);


Cron::end();

echo "finished ".date("c")."\n";
