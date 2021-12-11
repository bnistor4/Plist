<?php /** @noinspection PhpVoidFunctionResultUsedInspection */

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FirstCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'testing:fcommand';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This is a first command';

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
     */
    public function handle()
    {
        return $this->line('Hello');
    }
}
