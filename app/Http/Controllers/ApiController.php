<?php

namespace App\Http\Controllers;

use App\Issuer;
use App\Tag;
use App\User;
use App\UserTag;
use App\UserTagHistory;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Mockery\CountValidator\Exception;

class ApiController extends Controller
{
    private function addPoints($userLogin, $userType, $issuerName, $tagName, $points, $originUserLogin, $avatarUrl) {
        if (!$userLogin) {
            throw new Exception('Undefined user');
        }

        if (!$tagName) {
            throw new Exception('Undefined tag');
        }

        if (!$points) {
            throw new Exception('Undefined points');
        }

        /*
        if (!$issuerName) {
            throw new Exception('Undefined issuer');
        }
        */


        $user = User::firstOrCreate(array('type' => $userType, 'login' => $userLogin));
        $originUser = $originUserLogin
            ? User::firstOrCreate(array('type' => $userType, 'login' => $originUserLogin))
            : null;
        $issuer = $issuerName ? Issuer::firstOrCreate(array('name' => $issuerName)) : null;
        $tag = Tag::firstOrCreate(array('name' => $tagName, 'issuer_id' => $issuer->id));

        $userTag = UserTag::firstOrCreate(array('user_id' => $user->id, 'tag_id' => $tag->id));
        $userTag->points += $points;
        $userTag->save();

        UserTagHistory::create(array(
            'user_id' => $user->id,
            'tag_id' => $tag->id,
            'points' => $points,
            'origin_user_id' => $originUser ? $originUser->id : null,
            'avatar_url' => $avatarUrl,
            ));
    }


    /**
     * Promote user for tag with points
     *
     * @return Response
     */
    public function promote(Request $request, $demote = false)
    {
        header("Content-Type: application/json");
        $result = array();
        $result['status'] = 'ok';


        try {
            $userLogin = $request->get('user');
            $userType = $request->get('account_type', 'email');
            $originUserLogin = $request->get('origin_user');
            $tagName = $request->get('tag');
            $points = $request->get('points', 1);
            $points = $demote ? -abs($points) : abs($points);
            $issuerName = $request->get('issuer');
            $avatarUrl = $request->get('avatar_url');
            
            $this->addPoints($userLogin, $userType, $issuerName, $tagName, $points, $originUserLogin);
        }
        catch (\Exception $e) {
            $result['status'] = 'error';
            $result['message'] = $e->getMessage();
            $result['code'] = $e->getCode();
        }

        return json_encode($result);
    }

    /**
     * Demote a user for tag with points.
     *
     * @return Response
     */
    public function demote(Request $request)
    {
        return $this->promote($request, true);
    }

    public function slack(Request $request)
    {
        //file_put_contents('/tmp/slack.log', print_r($_REQUEST,1), FILE_APPEND);
        $originUserLogin = $_REQUEST['user_name'];
        /**
         *   [token] => tXCT7j2VkyWJD1nbjgePr3YS
        [team_id] => T076QNKKQ
        [team_domain] => hb-acme
        [channel_id] => C076R0U1Z
        [channel_name] => general
        [user_id] => U076QV8L8
        [user_name] => vearutop
        [command] => /hb
        [text] => -1 @mdzor
         */

        $text = explode(' ', $_REQUEST['text']);
        $points = $text[0];
        $userLogin = substr($text[1], 1);
        $tagName = isset($text[2]) ? $text[2] : 'karma';
        $userType = 'slack';
        $issuerName = 'slack/' . $_REQUEST['team_domain'];

        try {
            $this->addPoints($userLogin, $userType, $issuerName, $tagName, $points, $originUserLogin);
        }
        catch (\Exception $e) {
            return $e->getMessage();
        }

        return 'Your opinion really matters, thank you!';
    }

}
