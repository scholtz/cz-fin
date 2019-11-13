<?php
require 'vendor/autoload.php';

var_dump("a");
$_SERVER["HTTP_HOST"] = "www.cz-fin.com";
$_SERVER["HTTPS"] = "on";
require "conf/prod01/settings.php";

$inv = new \AT\Classes\Invoice();

$ret = $inv->issueInvoiceFromOrder("31f4f6fa81a5579f3a014522b5a763e5",1332.4,strtotime("2019-11-06"));
var_dump($path = $inv->getInvoicePDF($ret,false,true));
copy($path,"./inv.pdf");
        
