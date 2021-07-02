<?php

# this file is the starting point to your application
# the code below is just to verify your connection works and you can receive events.
# you can replace this file completely
# alternatively hook your methods in here


$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);


switch ($_SERVER['REQUEST_URI']) {
    case '/receive':
        // replace me with your own implementation
        receive($input);
        break;
    case '/longest-activity':
        // put your method for longest activity here
        break;
    case '/activity-by-user':
        // put your method for time spent on activity by user here
        break;
    default:
        echo '{"status":"404", "message": "Not Found"}';
}

// remove me
function receive($input)
{
    $response = [
        "status" => "200",
        "request_method" => $_SERVER['REQUEST_METHOD'],
        "input" => $input
    ];

    echo json_encode($response);
}
