<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class CheckWebsitesPhp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'work:phpcheck';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command check from a list of urls if php get errors';

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

        $client = new Client();

        $response = $client->get('www.endoniamo.it/.well-known/sg-php-try-v74');
        $cookieJar = $client->getConfig('cookies');

        $this->comment($response->getBody());

        $this->comment(str_contains($response->getBody(),'<b>Warning</b>') ? 'error' : 'clear');
        $this->comment(str_contains($response->getBody(),'</b> on line <b>') ? 'error' : 'clear');
        $this->comment(str_contains($response->getBody(),'Uncaught Error:')? 'error' : 'clear');
        $this->comment(str_contains($response->getBody(),'<b>Fatal error</b>')? 'error' : 'clear');

    }
}
