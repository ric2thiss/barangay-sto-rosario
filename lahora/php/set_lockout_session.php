<?php
session_start();
$_SESSION['lockout_active'] = true;
http_response_code(200);
