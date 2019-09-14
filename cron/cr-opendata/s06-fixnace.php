<?php


use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;

require_once("/cron2/settingsCZ.php");


Cron::start(24*3600);
echo "started ".date("c")."\n";


$res = DB::g($table = "dev01.sknace");
while($row=DB::f($res)){
    echo ".";
    $text = Texts::clear($row["CZ_text"]);
    if(!$text){$text = Texts::clear($row["SK_text"]);}
    if(!$text){$text = Texts::clear($row["EN_text"]);}
    if($row["idLevel"] <= 3) $text = $row["idLevel"]."-".$text;
    DB::u($table,$row["id2"],["id5cz"=>$text],false,false,false);
}

 $r = DB::g($table = "sknace");
	if(DB::num_rows($r) > 0){
		

		$rand = rand(10000,99999);
		echo "\ncopying to prod tmp table schema `out`.`${table}_tmp$rand`\n";
		DB::query("CREATE TABLE `out`.`${table}_tmp$rand` LIKE `dev01`.`$table`");
		echo DB::error();
		echo "\ncopying to prod tmp table data\n";
		DB::query("INSERT INTO `out`.`${table}_tmp$rand` SELECT * FROM `dev01`.`$table` where do = 0");
		echo DB::error();
		echo "\ndropping table\n";
		DB::query("drop table `out`.`$table`");
		echo DB::error();
		echo "\nrenaming table `${table}_tmp$rand` TO `out`.`$table`\n";
		DB::query("RENAME TABLE `out`.`${table}_tmp$rand` TO `out`.`$table`");
		echo DB::error();
		echo "DONE";	
	}


echo "finished ".date("c")."\n";

Cron::end();