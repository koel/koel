<?php

namespace App\Console\Commands;

use App\Application;
use App\Models\Artist;
use App\Models\Interaction;
use App\Models\Playlist;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;
use Lastfm;
use YouTube;

class InitCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'koel:init-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Speed up login (requires APC cache enabled)';

    /**
     * The progress bar.
     *
     * @var \Symfony\Component\Console\Helper\ProgressBar
     */
    protected $bar;

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
     * @return mixed
     */
    public function handle()
    {
        if (env('CACHE_DRIVER') != 'apc') {
            $this->error('Requires APC cache driver.');
            
            return;
        }
        $this->info('Starting to cache profiles, playlists and settings...');
        $users = User::all();
        $this->createProgressBar(count($users));
        foreach ($users as $user) {
            $playlists = Playlist::where('user_id', $user->id)->orderBy('name')->with('songs')->get()->toArray();

            // We don't need full song data, just ID's
            foreach ($playlists as &$playlist) {
                $playlist['songs'] = array_pluck($playlist['songs'], 'id');
            }

            $response = response()->json([
                'artists' => Artist::orderBy('name')->with('albums', with('albums.songs'))->get(),
                'settings' => $user->is_admin ? Setting::pluck('value', 'key')->all() : [],
                'playlists' => $playlists,
                'interactions' => Interaction::where('user_id', $user->id)->get(),
                'users' => $user->is_admin ? User::all() : [],
                'currentUser' => $user,
                'useLastfm' => Lastfm::used(),
                'useYouTube' => YouTube::enabled(),
                'allowDownload' => config('koel.download.allow'),
                'cdnUrl' => app()->staticUrl(),
                'currentVersion' => Application::VERSION,
                'latestVersion' => $user->is_admin ? app()->getLatestVersion() : Application::VERSION,
            ]);

            apc_store($user->id.'_load', $response, 86400);
            $this->updateProgressBar();
        }
        $this->finishProgressBar();
    }

    /**
     * Create a progress bar.
     *
     * @param int $max Max steps
     */
    public function createProgressBar($max)
    {
        $this->bar = $this->getOutput()->createProgressBar($max + 1);
        $this->bar->setProgress(1);
    }

    /**
     * Update the progress bar (advance by 1 step).
     */
    public function updateProgressBar()
    {
        $this->bar->advance();
    }

    /**
     * End the progress bar.
     */
    public function finishProgressBar()
    {
        $this->bar->finish();
    }
}
