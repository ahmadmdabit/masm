<?php

namespace App\Http\Controllers;

use App\Helpers\CurlHelper;
use DateTime;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use GuzzleHttp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

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
     * Register.
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

    /**
     * Purchase new subscription.
     *
     * @param  Request $request
     * @return Response
     */
    public function purchase(Request $request)
    {
        $this->validate($request, [
            'token' => 'required|uuid',
            'receipt' => 'required|uuid',
        ]);

        $os = 0;
        $data = json_decode(json_encode(DB::select(
            'SELECT * FROM `devices` WHERE `app_id` =
             (SELECT `app_id` FROM `tokens` WHERE `token` = ? LIMIT 1) LIMIT 1;', [$request->input('token')])), true);
        if (count($data)) {
            $os = $data[0]['os'];
        }
        else {
            return [ 'data' => null, 'status' => false, 'error' => 'Token error!' ];
        }

        $data_purchase = json_decode(json_encode(DB::select(
            'SELECT * FROM `purchases` WHERE `status` = 1 AND `app_id` =
             (SELECT `app_id` FROM `tokens` WHERE `token` = ? LIMIT 1) LIMIT 1;', [$request->input('token')])), true);

        if (count($data_purchase)) {
            return [ 'data' => null, 'status' => false, 'error' => 'Sorry, but you have active subscription!' ];
        }

        $response = CurlHelper::post(
            env('MOCK_URL').($os == 0 ? '/mock/google-verification' : '/mock/ios-verification'),
            $request->all());

        if (isset($response)) {
            $response = json_decode($response, true);

            $status = $response['status'] && $response['data']['status'] ? 1 : 0;
            try {
                DB::insert(
                    'INSERT INTO `purchases`(`uid`, `app_id`, `receipt`, `expire_date`, `status`, `state`)
                     VALUES (?,?,?,?,?,?)', [
                        (string) Str::uuid(),
                        $data[0]['app_id'],
                        $request->input('receipt'),
                        $response['data']['expire-date'],
                        $status,
                        $status ? 0 : null,
                    ]
                );
            } catch (\Throwable $th) {
                return [ 'data' => null, 'status' => false, 'error' => $th->getMessage() ];
            }

            return [ 'data' => [ 'expire-date' => $response['data']['expire-date']], 'status' => boolval($status), 'error' => $status ? null : 'Verification Error!' ];
        }
    }

    /**
     * Check the subscription.
     *
     * @param  Request $request
     * @return Response
     */
    public function check(Request $request)
    {
        $this->validate($request, [
            'token' => 'required|uuid',
        ]);

        $data = json_decode(json_encode(DB::select(
            'SELECT * FROM `purchases` WHERE `status` = 1 AND `app_id` =
             (SELECT `app_id` FROM `tokens` WHERE `token` = ? LIMIT 1) LIMIT 1;', [$request->input('token')])), true);

        if (count($data)) {
            return [ 'data' => $data, 'status' => true, 'error' => null ];
        }
        else {
            return [ 'data' => null, 'status' => false, 'error' => 'Token error!' ];
        }
    }
}
