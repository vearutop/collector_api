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

    private static $badgeLevels = array(self::BADGE_BRONZE => 5, self::BADGE_SILVER => 50, self::BADGE_GOLD => 500);

    /**
     * @var UserTag
     */
    private $userTag;
    /** @var UserBadge[] */
    private $userBadges = array();
    /** @var string[] */
    private $userBadgesIssued;

    private $levelBadge;

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

        if (!isset($this->userBadges[$currentLevel])) {
            $levelBadge = UserBadge::create(array('user_id' => $this->userTag->user_id, 'tag_id' => $this->userTag->id, 'badge' => $currentLevel));
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

        $king = UserTag::where('tag_id', $this->userTag->tag_id)->orderBy('points', 'desc')->take(1)->get();
        if ($king->id === $this->userTag->id) {
            $kingBadge = UserBadge::create(array('user_id' => $this->userTag->user_id, 'tag_id' => $this->userTag->id, 'badge' => self::BADGE_KING));
            $kingBadge->save();
            $this->userBadgesIssued []= self::BADGE_KING;
        }
    }


    private function checkRocketBadge() {
        die('!');
        if (isset($this->userBadges[self::BADGE_ROCKET])) {
            return;
        }

        if (!in_array(self::BADGE_BRONZE, $this->userBadgesIssued)) {
            return;
        }

        if ($this->userTag->created_at) {

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

    private function addPoints($userLogin, $userType, $issuerName, $tagName, $points, $originUserLogin) {
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
            ? User::firstOrCreate(array('type' => $userType, 'login' => $originUserLogin))
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
