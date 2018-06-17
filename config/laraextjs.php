<?php
    
    return [
        "extjs"                        => [
            "client_id_property" => "clientId",
            "proxy"              => [
                "filter_param" => "filter",
                "limit_param"  => "limit",
                "start_param"  => "start",
                "page_param"   => "page"
            ],
            
            "sorter" => [
                "sortProperty"   => "sort",
                "directionParam" => "dir"
            ],
            "writer" => [
                "rootProperty" => "data"
            ],
            "reader" => [
                "rootProperty"     => "data",
                "total_property"   => "total",
                "success_property" => "success",
                "message_property" => "message"
            ]
        ]
    ];