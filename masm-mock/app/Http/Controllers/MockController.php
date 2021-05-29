<?php

namespace App\Http\Controllers;

use DateTime;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MockController extends Controller
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
     * Slack Channel.
     *
     * @param  Request $request
     * @return Response
     */
    public function slackChannel(Request $request)
    {
        $this->validate($request, [
            'device_id' => 'required|uuid',
            'app_id' => 'required|uuid',
            'info' => 'required|string',
        ]);

        if (rand(1,100) % 2) {
            return [ 'data' => null, 'status' => true, 'error' => null ];
        }
        else {
            return [ 'data' => null, 'status' => false, 'error' => null ];
        }
    }

    /**
     * Google Verification.
     *
     * @param  Request $request
     * @return Response
     */
    public function googleVerification(Request $request)
    {
        return $this->verification($request);
    }

    /**
     * Ios Verification.
     *
     * @param  Request $request
     * @return Response
     */
    public function iosVerification(Request $request)
    {
        return $this->verification($request);
    }

    /**
     * Verification.
     *
     * @param  Request $request
     * @return Response
     */
    protected function verification(Request $request)
    {
        $this->validate($request, [
            'token' => 'required|uuid',
            'receipt' => 'required|uuid',
        ]);

        $lastChar = substr(((String)$request->input('receipt')), -1);

        if (is_numeric($lastChar) && $lastChar % 2) {
            $now = new DateTime();
            $now->modify('-6 hour'); // UTC -6
            $expireAt = $now->modify('+1 day')->format('Y-m-d H:i:s');
            return [ 'data' => [ 'status' => true, 'expire-date' => $expireAt ], 'status' => true, 'error' => null ];
        }
        else {
            return [ 'data' => [ 'status' => false, 'expire-date' => null ], 'status' => true, 'error' => null ];
        }
    }

}
