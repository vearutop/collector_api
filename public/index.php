<?php

$f = trim(file_get_contents('php://input'));
if (($f[0] === '{') || $f[0] === '[') {
    $_POST = json_decode($f, 1);
}

require_once __DIR__ . '/../vendor/autoload.php';

\HackerBadge\Router\Api::create()->route();