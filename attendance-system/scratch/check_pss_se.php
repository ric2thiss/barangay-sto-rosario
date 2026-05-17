<?php
$p=new PDO('mysql:host=localhost;dbname=pss_db', 'root', '');
$s2=$p->query("DESCRIBE `schedule_events`");
print_r($s2->fetchAll(PDO::FETCH_ASSOC));
