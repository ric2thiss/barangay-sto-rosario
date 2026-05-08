<?php

require_once __DIR__ ."/config.php";

spl_autoload_register(function($class){
    $paths = [
        BASE_PATH . "app/utils/",
        BASE_PATH . "app/utils/styles",
        BASE_PATH . "app/utils/js",
        BASE_PATH . "app/database/",
        BASE_PATH . "app/models/",
        BASE_PATH . "app/query/",
        BASE_PATH . "app/repositories/",
        BASE_PATH . "app/controller/",

    ];

    foreach($paths as $path) {
        $file = $path . $class . ".php";
        if(file_exists($file)) {
            require_once $file;
            return;
        }
    } 
});