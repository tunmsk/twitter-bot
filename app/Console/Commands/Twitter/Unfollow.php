<?php

namespace App\Console\Commands\Twitter;

use App\Models\Twitter\User as TwitterUser;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Thujohn\Twitter\Facades\Twitter;

class Unfollow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'twitter:unfollow {--throttle=60}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unfollow users that don\'t follow me';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $friends = TwitterUser::friends()->exceptVip()->pluck('id')->toArray();

        foreach (array_chunk($friends, 100) as $chunk) {
            $friendships = Twitter::getFriendshipsLookup(['format' => 'array', 'user_id' => $chunk]);

            foreach ($friendships as $user) {
                if (// does this user follows me?
                    !in_array('followed_by', $user['connections']) ||

                    // de we speak the same language(s)?
                    !in_array(($user = TwitterUser::findOrFail($user['id']))->lang, ['en', 'fr'])
                ) {
                    if (isset($timeOfLastUnfollow) && ($seconds = time() - $timeOfLastUnfollow) < 6) {
                        sleep($seconds);
                    }

                    $this->info("unfollowing @{$user['screen_name']}");
                    TwitterUser::findOrFail($user['id'])->unfollow();
                    $timeOfLastUnfollow = time();
                }
            }

            if ($this->option('throttle')) {
                $this->comment("sleep before next chunk");
                sleep((int) $this->option('throttle'));
            }
        }
    }
}
