<?php

namespace App\Http\Controllers;

use DateTime;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Register new subscription.
     *
     * @param  Request $request
     * @return Response
     */
    public function register(Request $request)
    {
        $this->validate($request, [
            'uid' => 'required|uuid',
            'app_id' => 'required|uuid',
            'language' => 'required|string|size:2',
            'os' => 'required|digits_between:0,1',
        ]);

        try {
            DB::insert(
                'INSERT INTO `devices` (`uid`, `app_id`, `language`, `os`)
                 VALUES (?,?,?,?)', [
                    $request->input('uid'),
                    $request->input('app_id'),
                    $request->input('language'),
                    $request->input('os'),
                ]
            );

            $now = new DateTime();
            $now->modify('-6 hour'); // UTC -6
            $expireAt = $now->modify('+1 day')->format('Y-m-d H:i:s');
            $token = (string) Str::uuid();

            DB::insert(
                'INSERT INTO `tokens`(`token`, `app_id`, `expire_at`)
                 VALUES (?,?,?)', [
                    $token,
                    $request->input('app_id'),
                    $expireAt,
                ]
            );
        } catch (\Throwable $th) {
            return [ 'data' => null, 'status' => false, 'error' => $th->getMessage() ];
        }

        return [ 'data' => [ 'token' => $token, 'expire_at' => $expireAt ], 'status' => true, 'error' => null ];
    }
}
