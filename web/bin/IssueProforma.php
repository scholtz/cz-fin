<?php
require 'vendor/autoload.php';

var_dump("a");
$_SERVER["HTTP_HOST"] = "www.cz-fin.com";
$_SERVER["HTTPS"] = "on";
require "conf/prod01/settings.php";


$ordObj = new \AT\Classes\Order("test@gmail.com");
$ord = $ordObj->newOrder("premium","month","PZ","w43b177");
$html = $ordObj->getInvoiceHTML($ord);
$pdf = $ordObj->getInvoicePDF($ord,"return");

rename($pdf,"./out.pdf");
