<?php

namespace CodeGreenCreative\SamlIdp\Console;

use Illuminate\Console\Command;

class CreateServiceProvider extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'samlidp:sp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create new service provider config';

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
        $acsUrl = $this->ask('What is the service provider ACS URL?');
        $logoutUrl = $this->ask('What is the service provider logout URL?');

        $encodedAcsUrl = base64_encode($acsUrl);

        $this->line('SamlIdp config:');
        $this->line('');
        $this->line("'{$encodedAcsUrl}' => [");
        $this->line("    'destination' => '{$acsUrl}',");
        $this->line("    'logout' => '{$logoutUrl}',");
        $this->line("]");
    }
}
