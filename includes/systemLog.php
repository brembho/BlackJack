<?php

function systemLog($message) {
    $logFile = 'logs/game_log.txt';


    $timestamp = date("Y-m-d H:i:s"); 
    $user = isset($_SESSION['username']) ? $_SESSION['username'] : 'GUEST';

    $entry = "[$timestamp] [USER:$user] -> $message" . PHP_EOL;

    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}
?>