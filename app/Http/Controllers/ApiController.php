<?php

namespace App\Http\Controllers;

use App\Issuer;
use App\Tag;
use App\User;
use App\UserBadge;
use App\UserTag;
use App\UserTagHistory;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Mockery\CountValidator\Exception;

class ApiController extends Controller
{
    const BADGE_BRONZE = 'bronze';
    const BADGE_SILVER = 'silver';
    const BADGE_GOLD = 'gold';

    const BADGE_KING = 'king';
    const BADGE_ROCKET = 'rocket';

    const ROCKET_TIME = 300;

    private static $badgeLevels = array(self::BADGE_BRONZE => 5, self::BADGE_SILVER => 50, self::BADGE_GOLD => 500);

    /**
     * @var UserTag
     */
    private $userTag;
    /** @var UserBadge[] */
    private $userBadges = array();
    /** @var string[] */
    private $userBadgesIssued = array();

    private $levelBadge;

    private function getLoginName($login) {
        $login = explode('@', $login);
        return $login[0];
    }

    private function getMessage() {
        if (!$this->userBadgesIssued) {
            return 'Your opinion really matters. Thank you!';
        }

        if (in_array(self::BADGE_KING, $this->userBadgesIssued)) {
            return 'Hail to the king, baby!';
        }

        if (in_array(self::BADGE_ROCKET, $this->userBadgesIssued)) {
            return 'Trying to outrun the light, ' . $this->getLoginName($this->user->login) . '?';
        }

        return 'Your opinion really matters. Thank you!';
    }

    private function getBadges() {
        $badges = UserBadge::where('tag_id', $this->userTag->id)
            ->where('user_id', $this->userTag->user_id)->get();

        /** @var \App\UserBadge $badge */
        foreach ($badges as $badge) {
            if (isset($badge->badge, self::$badgeLevels)) {
                $this->levelBadge = $badge;
            }

            $this->userBadges [$badge->badge]= $badge;
        }
    }


    private function checkLevelBadge() {
        $currentLevel = null;
        foreach (self::$badgeLevels as $level => $points) {
            if ($this->userTag->points >= $points) {
                $currentLevel = $level;
            }
        }

        if ($currentLevel && !isset($this->userBadges[$currentLevel])) {
            $levelBadge = UserBadge::create(
                array(
                    'user_id' => $this->userTag->user_id,
                    'tag_id' => $this->userTag->id,
                    'badge' => $currentLevel
                ));
            $levelBadge->save();
            $this->levelBadge = $levelBadge;
            $this->userBadgesIssued []= $currentLevel;
        }
    }

    private function checkKingBadge() {
        if (isset($this->userBadges[self::BADGE_KING])) {
            return;
        }

        if (null === $this->levelBadge) {
            return;
        }

        $king = UserTag::where('tag_id', $this->userTag->tag_id)->orderBy('points', 'desc')->first();
        if ($king->id === $this->userTag->id) {
            $prevKingBadge = UserBadge::where(array('tag_id' => $this->userTag->tag_id, 'badge' => self::BADGE_KING))->first();
            if ($prevKingBadge) {
                $prevKingBadge->delete();
            }

            $kingBadge = UserBadge::create(array('user_id' => $this->userTag->user_id, 'tag_id' => $this->userTag->id, 'badge' => self::BADGE_KING));
            $kingBadge->save();


            $this->userBadgesIssued []= self::BADGE_KING;
        }
    }


    private function checkRocketBadge() {
        if (isset($this->userBadges[self::BADGE_ROCKET])) {
            return;
        }

        if (!in_array(self::BADGE_BRONZE, $this->userBadgesIssued)) {
            return;
        }


        $createdUt = strtotime($this->userTag->created_at);
        $updatedUt = strtotime($this->userTag->updated_at);
        if ($updatedUt - $createdUt < self::ROCKET_TIME) {
            $kingBadge = UserBadge::create(array('user_id' => $this->userTag->user_id, 'tag_id' => $this->userTag->id, 'badge' => self::BADGE_ROCKET));
            $kingBadge->save();
            $this->userBadgesIssued []= self::BADGE_ROCKET;
        }

    }



    /** @var  User */
    private $user;
    /** @var  User */
    private $originUser;
    /** @var  Issuer */
    private $issuer;
    /** @var  Tag */
    private $tag;
    /** @var  UserTagHistory */
    private $userTagHistory;

    private function addPoints($userLogin, $userType, $issuerName, $tagName, $points, $originUserLogin, $avatarUrl = '') {
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


        $this->user = User::firstOrCreate(array('type' => $userType, 'login' => $userLogin));
        $this->originUser = $originUserLogin
            ? User::firstOrCreate(array('type' => $userType, 'login' => $originUserLogin, 'avatar_url' => $avatarUrl))
            : null;
        $this->issuer = $issuerName ? Issuer::firstOrCreate(array('name' => $issuerName)) : null;
        $this->tag = Tag::firstOrCreate(array('name' => $tagName, 'issuer_id' => $this->issuer->id));

        $this->userTag = UserTag::firstOrCreate(array('user_id' => $this->user->id, 'tag_id' => $this->tag->id));
        $this->userTag->points += $points;
        $this->userTag->save();

        $this->userTagHistory = UserTagHistory::create(array(
            'user_id' => $this->user->id,
            'tag_id' => $this->tag->id,
            'points' => $points,
            'origin_user_id' => $this->originUser ? $this->originUser->id : null,
        ));


        $this->getBadges();
        $this->checkLevelBadge();
        $this->checkRocketBadge();
        $this->checkKingBadge();

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

            $this->addPoints($userLogin, $userType, $issuerName, $tagName, $points, $originUserLogin, $avatarUrl);
            $result['message'] = $this->getMessage();
            $result['badges_issued'] = $this->userBadgesIssued;
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


        // create a new cURL resource
        $ch = curl_init();
        \curl_setopt($ch,CURLOPT_POSTFIELDS, $_REQUEST['user_name'] . " gave $points to $userLogin for $tagName");
        $url = "https://". $_REQUEST['team_domain'] .".slack.com/services/hooks/slackbot?token=s3KBEGSbzeKI6maAEFtZEus2&channel=%23".$_REQUEST['channel_name'] ;
        // set URL and other appropriate options
        \curl_setopt($ch, CURLOPT_URL, $url);
        \curl_setopt($ch, CURLOPT_HEADER, 0);

        // grab URL and pass it to the browser
        \curl_exec($ch);

        // close cURL resource, and free up system resources
        \curl_close($ch);


        return $this->getMessage();
    }

}
