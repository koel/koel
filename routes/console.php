<?php

use App\Jobs\RunCommandJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new RunCommandJob('koel:scan'))->daily();
Schedule::job(new RunCommandJob('koel:prune'))->daily();
Schedule::job(new RunCommandJob('koel:podcasts:sync'))->daily();
Schedule::job(new RunCommandJob('koel:clean-up-temp-files'))->daily();
Schedule::job(new RunCommandJob('model:prune'))->daily();
