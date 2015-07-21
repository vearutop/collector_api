<?php

namespace HackerBadge\Router;


use HackerBadge\Controller;
use Yaoi\BaseClass;
use Yaoi\String\Utils;

class Api extends BaseClass
{
    public function route($request = null) {
        $path = $_SERVER['REDIRECT_URI'];

        switch (true) {
            case Utils::starts($path, '/api/promote'):
                Controller\Api::create()->promote($request);
                break;

            case Utils::starts($path, '/api/demote'):
                Controller\Api::create()->demote($request);
                break;

            case Utils::starts($path, '/api/slack'):
                Controller\Api::create()->slack($request);
                break;

            case Utils::starts($path, '/api/github-issues'):
                Controller\Api::create()->githubIssues();
                break;
        }
    }
}