<?php

if(!file_exists('vendor/autoload.php')) {
	echo "Autoload is missing. Please run command 'composer update'\n";
	exit;
}
require 'vendor/autoload.php';

$commands = array("CollectPhrases");
if(in_array($argv[1],$commands)){
	echo "launching command ".$argv[1]."\n";
	$cmdstr = "\\AsyncWeb\\CLI\\Command\\".$argv[1];
	$cmd = new $cmdstr();
	echo $cmd->execute();
}else if($argv[1] == "help" || $argv[1] == "?"){
	if(in_array($argv[2],$commands)){
		$cmdstr = "\\AsyncWeb\\CLI\\Command\\".$argv[2];
		$cmd = new $cmdstr();
		echo $cmd->help();
	}else{
		echo "Command ".$argv[2]." not found\n";
		print_usage();
	}
}else{
	if($argv[1]){	
		echo "Command ".$argv[1]." not found\n";
	}
	print_usage();
}
function print_usage(){
	global $commands;
	echo "Usage: \n"."php bin/Command.php [COMMAND]\n"."php bin/Command.php help [COMMAND]";
	echo "\n\nAvailable commands: ".implode(",",$commands);
	echo "\n";
}