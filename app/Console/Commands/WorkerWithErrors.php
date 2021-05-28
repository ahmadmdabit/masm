<?php

namespace App\Console\Commands;

use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WorkerWithErrors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'worker:run-with-errors';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Worker for updating the subscriptions states. (With Some Errors)';

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
        $start_date = new DateTime();

        $condition = true;
        $round = 0;
        $pass = false;
        do {
            $this->info('Round: ' . ++$round);

            if (!$pass) {
                $pass = true;
                $condition = $this->process();
            }
            else {
                $pass = false;
                $condition = true;
            }
        } while ($condition);

        $this->info('The command was successful!');

        $since_start = $start_date->diff(new DateTime());
        echo $since_start->h.":";
        echo $since_start->i.":";
        echo $since_start->s."\n";

        return 0;
    }

    protected function process()
    {
        $now = new DateTime();
        $now->modify('-6 hour'); // UTC -6

        $data_purchases = json_decode(json_encode(DB::select(
            'SELECT uid FROM `purchases` WHERE `status` = 1 AND `state` <> 2 AND `expire_date` IS NOT NULL AND `expire_date` <= ? LIMIT 100000;', [$now->format('Y-m-d H:i:s')])
        ), true);

        $result = false;
        $this->warn('Purchases: '.count($data_purchases));
        if (count($data_purchases)) {
            foreach ($data_purchases as $key => $value) {
                try {
                    DB::update('UPDATE `purchases` SET `state` = 2 where `uid` = ?', [$value['uid']]);
                } catch (\Throwable $th) {
                    $this->error($th->getMessage());
                    throw $th;
                }
            }
            $result = true;
        }
        else {
            $this->info('There is no obsolete subscriptions.');
        }

        return $result;
    }
}
