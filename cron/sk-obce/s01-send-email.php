<?php
use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;
require_once("/cron2/settings.php");

Cron::start(24*3600);



$obec = "Rimavská Sobota";
$email = "ludkosk@gmail.com";
$row = 1;
if (($handle = fopen("emaily.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $email = $data[0];
        $obec = $data[1];
        
$text = '
<html>
<head><title></title>
</head>
<body>
Dobrý deň,<br><br>
podľa pokynov pre <b>voľby do Národnej rady</b> Slovenskej republiky v roku 2020, ktoré <b>zverejnilo MV SR</b> na svojich stránkach: <a href="https://www.minv.sk/?nr20-posta2">https://www.minv.sk/?nr20-posta2</a> môže <b>volič požiadať obec</b>, v ktorej má trvalý pobyt o: <br>
<br>
1. voľbu poštou<br>
2. hlasovací preukaz, <br>
<br>
<b>zaslaním</b> žiadosti "<b>na elektronickú (e-mailovú) adresu</b>, ktorú obec zverejnila na svojom webovom sídle alebo na úradnej tabuli obce".<br>
<br>
<b>Prosím potvrďte e-mailovú adresu pre obec '.$obec.' na webovej stránke: <a href="https://volby.srdcomdoma.sk/volby.php?u='.$email.'">https://volby.srdcomdoma.sk/volby.php</a></b><br>
<br>
Vaše potvrdenie umožní rýchlejšie doručovanie žiadostí od voličov ku Vám, vďaka našej online aplikácii <a href="https://volby.srdcomdoma.sk">https://volby.srdcomdoma.sk</a>. Testovacia aplikácia nám už funguje na adrese <a href="https://volby.srdcomdoma.sk/testnrsr.html">https://volby.srdcomdoma.sk/testnrsr.html</a>.<br>
<br>
<br>
Veľmi pekne ďakujeme za pomoc!<br>
<br>
<br>
Prajem Vám úspešný deň!<br>
<br>
Ing. Samuel Zubo<br>
predseda občianskeho združenia Srdcom doma<br>
volby@srdcomdoma.sk<br>
+420724578078<br>
</body>
</html>
';

    echo $obec." : $email : ";
    echo (\AsyncWeb\Email\Email::send($email,"Potvrdenie adresy pre voľbu poštou: ".$obec,$text,"volby@srdcomdoma.sk",[],"html"));
    sleep(1);
    echo "\n";
}}
echo "\nDONE ".date("c")."\n";
Cron::end();