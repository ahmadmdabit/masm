<?php

namespace Database\Seeders;

use DateTime;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $this->call('UsersTableSeeder');

        $start_date = new DateTime();

        DB::transaction(function () {

            $index = 0;
            $size = 1000; // 1000000
            $status = 0;
            do {
                $now = new DateTime();
                $now->modify('-6 hour'); // UTC -6
                $expireAt = $now->modify(($status ? '+' : '-').rand(1, 10).' day')->format('Y-m-d H:i:s');

                DB::insert(
                    'INSERT INTO `purchases`(`uid`, `app_id`, `receipt`, `expire_date`, `status`, `state`)
                     VALUES (?,?,?,?,?,?)', [
                        (string) Str::uuid(),
                        (string) Str::uuid(),
                        (string) Str::uuid(),
                        $status ? $expireAt : null,
                        $status,
                        $status ? 0 : null,
                    ]
                );
                $status = $status ? 0 : 1;
            } while (++$index <= $size);

        });
        $since_start = $start_date->diff(new DateTime());
        echo $since_start->h.":";
        echo $since_start->i.":";
        echo $since_start->s."\n";
    }
}
