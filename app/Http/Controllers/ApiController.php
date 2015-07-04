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
    private function addPoints($userLogin, $userType, $issuerName, $tagName, $points) {
        if (!$userLogin) {
            throw new Exception('Undefined user');
        }

        if (!$tagName) {
            throw new Exception('Undefined tag');
        }

        if (!$points) {
            throw new Exception('Undefined points');
        }

        if (!$issuerName) {
            throw new Exception('Undefined issuer');
        }


        $user = User::firstOrCreate(array('type' => $userType, 'login' => $userLogin));
        $issuer = Issuer::firstOrCreate(array('name' => $issuerName));
        $tag = Tag::firstOrCreate(array('name' => $tagName, 'issuer_id' => $issuer->id));

        $userTag = UserTag::firstOrCreate(array('user_id' => $user->id, 'tag_id' => $tag->id));
        $userTag->points += $points;
        $userTag->save();

        $userTagHistory = UserTagHistory::create(array('user_id' => $user->id, 'tag_id' => $tag->id, 'points' => $points));
    }


    /**
     * Promote user for tag with points
     *
     * @return Response
     */
    public function promote(Request $request)
    {
        header("Content-Type: application/json");
        $result = array();
        $result['status'] = 'ok';


        try {
            $userLogin = $request->get('user');
            $userType = $request->get('account_type', 'email');
            $tagName = $request->get('tag');
            $points = $request->get('points', 1);
            $issuerName = $request->get('issuer');

            $this->addPoints($userLogin, $userType, $issuerName, $tagName, $points);
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
        header("Content-Type: application/json");
        $result = array();
        $result['status'] = 'ok';


        try {
            $userLogin = $request->get('user');
            $userType = $request->get('account_type', 'email');
            $tagName = $request->get('tag');
            $points = -$request->get('points', 1);
            $issuerName = $request->get('issuer');

            $this->addPoints($userLogin, $userType, $issuerName, $tagName, $points);
        }
        catch (\Exception $e) {
            $result['status'] = 'error';
            $result['message'] = $e->getMessage();
            $result['code'] = $e->getCode();
        }

        return json_encode($result);
    }

}
