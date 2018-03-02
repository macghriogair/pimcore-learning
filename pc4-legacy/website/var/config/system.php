<?php 

return [
    "general" => [
        "timezone" => "Europe/Berlin",
        "language" => "en",
        "validLanguages" => "en",
        "debug" => "1",
        "debugloglevel" => "debug",
        "custom_php_logfile" => "1",
        "extjs6" => "1"
    ],
    "database" => [
        "adapter" => "Pdo_Mysql",
        "params" => [
            "username" => "root",
            "password" => "root",
            "dbname" => "pimcore4",
            "host" => "127.0.0.1",
            "port" => "3306"
        ]
    ],
    "documents" => [
        "versions" => [
            "steps" => "10"
        ],
        "default_controller" => "default",
        "default_action" => "default",
        "error_pages" => [
            "default" => "/"
        ],
        "createredirectwhenmoved" => "",
        "allowtrailingslash" => "no",
        "generatepreview" => "1"
    ],
    "objects" => [
        "versions" => [
            "steps" => "10"
        ]
    ],
    "assets" => [
        "versions" => [
            "steps" => "10"
        ]
    ],
    "services" => [

    ],
    "cache" => [
        "excludeCookie" => ""
    ],
    "httpclient" => [
        "adapter" => "Zend_Http_Client_Adapter_Socket"
    ]
];
