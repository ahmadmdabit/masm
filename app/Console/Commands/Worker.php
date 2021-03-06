<?php

namespace App\Console\Commands;

use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Events\SubscriptionStateEvent;
use App\Models\EventModel;

class Worker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'worker:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Worker for updating the subscriptions states.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $condition = true;
        $round = 0;
        do {
            $this->info('Round: ' . ++$round);
            $condition = $this->process();
        } while ($condition);

        $this->info('The command was successful!');

        return 0;
    }

    protected function process()
    {
        $now = new DateTime();
        $now->modify('-6 hour'); // UTC -6

        $data_purchases = json_decode(json_encode(DB::select(
            'SELECT uid, app_id FROM `purchases` WHERE `status` = 1 AND `state` <> 2 AND `expire_date` IS NOT NULL AND `expire_date` <= ?;', [$now->format('Y-m-d H:i:s')])
        ), true);

        $result = false;
        if (count($data_purchases)) {
            foreach ($data_purchases as $key => $value) {
                try {
                    DB::update('UPDATE `purchases` SET `state` = 2 where `uid` = ?', [$value['uid']]);
                } catch (\Throwable $th) {
                    $this->error($th->getMessage());
                    throw $th;
                }
                echo $value['app_id'];
                $device = json_decode(json_encode(DB::select('SELECT `uid` FROM devices WHERE `app_id` = ?', [$value['app_id']])), true);
                $eventModel = new EventModel();
                $eventModel->device_id = $device[0]['uid'];
                $eventModel->app_id = $value['app_id'];
                $eventModel->info = ['purchase' => ['uid' => $value['uid'], 'state' => 2]];
                event(new SubscriptionStateEvent($eventModel));
            }
            $result = true;
        }
        else {
            $this->info('There is no obsolete subscriptions.');
        }

        return $result;
    }
}
