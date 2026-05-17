<?php
$p=new PDO('mysql:host=localhost;dbname=pss_db', 'root', '');
$s=$p->query('SHOW TABLES');
$tables=$s->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);
foreach ($tables as $t) {
    echo "TABLE: $t\n";
    $s2=$p->query("DESCRIBE `$t`");
    print_r($s2->fetchAll(PDO::FETCH_ASSOC));
}
