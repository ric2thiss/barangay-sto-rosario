<?php
session_start();
unset($_SESSION['lockout_active']);
http_response_code(200);
