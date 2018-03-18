<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Marvel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marvel {character : Name of the character} {type : Type of the data} {path? : path to file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches data related to Marvel character and stores it into a file';

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
        $this->info('invoked');
        return array();
    }
}
