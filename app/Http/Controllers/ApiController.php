<?php

namespace App\Http\Controllers;

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
            return '';
        }

        if (in_array(self::BADGE_KING, $this->userBadgesIssued)) {
            return 'Hail to the king, baby!';
        }

        if (in_array(self::BADGE_ROCKET, $this->userBadgesIssued)) {
            return 'Trying to outrun the light, ' . $this->getLoginName($this->user->login) . '?';
        }

        return '';
    }

    private function getBadges() {
        $badges = UserBadge::where('tag_id', $this->userTag->tag_id)
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
                    'tag_id' => $this->userTag->tag_id,
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

            $kingBadge = UserBadge::create(array('user_id' => $this->userTag->user_id, 'tag_id' => $this->userTag->tag_id, 'badge' => self::BADGE_KING));
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
            $kingBadge = UserBadge::create(array('user_id' => $this->userTag->user_id, 'tag_id' => $this->userTag->tag_id, 'badge' => self::BADGE_ROCKET));
            $kingBadge->save();
            $this->userBadgesIssued []= self::BADGE_ROCKET;
        }

    }



    /** @var  User */
    private $user;
    /** @var  User */
    private $originUser;
    /** @var  Tag */
    private $tag;
    /** @var  UserTagHistory */
    private $userTagHistory;

    private function addPoints($userLogin, $userType, $tagName, $points, $originUserLogin, $avatarUrl = '') {
        if (!$userLogin) {
            throw new Exception('Undefined user');
        }

        if (!$tagName) {
            throw new Exception('Undefined tag');
        }

        if (!$points) {
            throw new Exception('Undefined points');
        }

        $this->user = User::where('login', $userLogin)->where('type', $userType)->first();
        if (!$this->user) {
            $this->user = User::create(array('type' => $userType, 'login' => $userLogin, 'avatar_url' => $avatarUrl));
        }

        $this->originUser = $originUserLogin
            ? User::firstOrCreate(array('type' => $userType, 'login' => $originUserLogin))
            : null;
        $this->tag = Tag::firstOrCreate(array('name' => $tagName));

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









    private function batchPromote($data, $demote = false) {
        $result = array();
        foreach ($data as $item) {
            try {

                $userLogin = isset($item['user']) ? $item['user'] : null;
                $userType = isset($item['account_type']) ? $item['account_type'] : 'email';
                $originUserLogin = isset($item['origin_user']) ? $item['origin_user'] : null;
                $tagName = isset($item['tag']) ? $item['tag'] : null;
                $points = isset($item['points']) ? $item['points'] : 1;
                $points = $demote ? -abs($points) : abs($points);
                $avatarUrl = isset($item['avatar_url']) ? $item['avatar_url'] : null;;

                $this->addPoints($userLogin, $userType, $tagName, $points, $originUserLogin, $avatarUrl);
                $res['status'] = 'ok';
                $res['message'] = $this->getMessage();
                $res['badges_issued'] = $this->userBadgesIssued;
                $result []= $res;
            }
            catch (\Exception $e) {
                $res['status'] = 'error';
                $res['message'] = $e->getMessage();
                $res['code'] = $e->getCode();
                $result []= $res;
            }
        }

        return json_encode($result);
    }

    /**
     * Promote user for tag with points
     *
     * @return Response
     */
    public function promote(Request $request, $demote = false)
    {
        file_put_contents('/tmp/hb.log', file_get_contents('php://input'), FILE_APPEND);


        header("Content-Type: application/json");
        $result = array();
        $result['status'] = 'ok';


        // array of records
        if (isset($_POST[0])) {
            return $this->batchPromote($_POST, $demote);
        }

        try {
            $userLogin = $request->get('user');
            $userType = $request->get('account_type', 'email');
            $originUserLogin = $request->get('origin_user');
            $tagName = $request->get('tag');
            $points = $request->get('points', 1);
            $points = $demote ? -abs($points) : abs($points);
            $avatarUrl = $request->get('avatar_url');

            $this->addPoints($userLogin, $userType, $tagName, $points, $originUserLogin, $avatarUrl);
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
        $points = (int)$text[0];
        $userLogin = isset($text[1]) ? substr($text[1], 1) : '';
        $tagName = isset($text[2]) ? $text[2] : 'karma';
        $userType = 'slack';

        try {
            if ($points) {
                $this->addPoints($userLogin, $userType, $tagName, $points, $originUserLogin);
                //$userInfo = file_get_contents('https://slack.com/api/users.info?token=' . $_REQUEST['token'] . '&');
            }
            else {
                if ('top' === $text[0]) {
                    $tagText = $text[1];

                    $tag = $this->getTag($text[1]);
                    if (!$tag) {
                        $this->slackResponse($text[1] . ' not found.');
                        return 'oops one';
                    }
                    $userTag = UserTag::where('tag_id', $tag->id)->orderBy('points', 'desc')->take(1)->first();
                    if (!$userTag) {
                        $this->slackResponse($text[1] . ' not found.');
                        return 'oops two';
                    }
                    $user = User::where('id', $userTag->user_id)->first();
                    if ($user) {
                        $this->slackResponse('@' . $user->login . ' is the top about ' . $tagText . ' with ' . $userTag->points . ' points.');
                        return;
                        // for the kaaaarma!
                    }
                    else {
                        return 'oops three';
                    }

                }

                elseif ('info' === $text[0]) {
                    $user = User::where('login', $userLogin)->where('type', $userType)->first();
                    if (!$user) {
                        return 'Not found.';
                    }
                    $userTags = UserTag::where('user_id', $user->id)->get();
                    $userBadges = UserBadge::where('user_id', $user->id)->get();

                    $tagData = array();
                    foreach ($userTags as $userTag) {
                        $tagData[$userTag->tag_id] = array('name' => Tag::where('id', $userTag->tag_id)->first()->name, 'points' => $userTag->points, 'badges' => '');
                    }
                    foreach ($userBadges as $userBadge) {
                        $tagData[$userBadge->tag_id]['badges'] .= ' ' . $userBadge->badge;
                    }

                    $report = '@' . $user->login . ' is recognized for ' . "\n";
                    foreach ($tagData as $tagId => $tagInfo) {
                        $report .= $tagInfo['name'] . ' with ' . $tagInfo['points'] . ' points '
                            . ($tagInfo['badges']
                                ? 'and is rewarded with: ' . str_replace(' ', ', ', trim($tagInfo['badges']))
                                : '')
                            . "\n";
                    }
                    $this->slackResponse($report);
                    return 'Thank you for curiosity. See you in the library.';
                }

                elseif ('help' === $text[0]) {
                    return '`/hb +1 @username topic` to promote' . "\n"
                    . '`/hb info @username` to get achievements' . "\n"
                    . '`/hb top topic` to get the master of topic';
                }
                else {
                    throw new \Exception('Try `/hb help`');
                }
            }
        }
        catch (\Exception $e) {
            return $e->getMessage();
        }

        $this->slackResponse($_REQUEST['user_name'] . " gave $points to $userLogin for $tagName" . "\n" . $this->getMessage());
        return 'Your opinion really matters. Thank you!' . "\n" . print_r($_REQUEST, 1);
    }


    private function slackResponse($text) {
        // create a new cURL resource
        $ch = \curl_init();
        \curl_setopt($ch,CURLOPT_POSTFIELDS, $text);
        $url = "https://". $_REQUEST['team_domain'] .".slack.com/services/hooks/slackbot?token=s3KBEGSbzeKI6maAEFtZEus2&channel=%23".$_REQUEST['channel_name'] ;
        // set URL and other appropriate options
        \curl_setopt($ch, CURLOPT_URL, $url);
        \curl_setopt($ch, CURLOPT_HEADER, 0);
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // grab URL and pass it to the browser
        \curl_exec($ch);

        // close cURL resource, and free up system resources
        \curl_close($ch);

    }


    /**
     * @param $tagName
     * @return Tag|null
     */
    private function getTag($tagName) {
        $tag = Tag::where('name', $tagName)->first();
        return $tag;
    }

}
