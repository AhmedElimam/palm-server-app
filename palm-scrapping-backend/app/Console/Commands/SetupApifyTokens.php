<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupApifyTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:apify-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup Apify API tokens for Amazon and Jumia';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Apify tokens are configured in config/apify.php');
        $this->info('Amazon Token: ' . config('apify.tokens.amazon'));
        $this->info('Jumia Token: ' . config('apify.tokens.jumia'));
        
        return 0;
    }
}
