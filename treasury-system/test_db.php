<?php
$c = new mysqli('localhost','root','','treasurer_management');
$r=$c->query("SELECT id, username, role, name FROM users");
while($row=$r->fetch_assoc()) { print_r($row); }
