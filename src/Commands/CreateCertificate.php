<?php

namespace CodeGreenCreative\SamlIdp\Commands;

use Illuminate\Console\Command;

class CreateCertificate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'samlidp:cert
                            {--days=7300 : Number of days to add from today as the expiration date}
                            {--keyname=key.pem : Full name of the certificate key file}
                            {--certname=cert.pem : Full name to the certificate file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create new certificate and private key for your IdP';

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
        // Create storage/samlidp directory
        if (!file_exists(storage_path() . "/samlidp")) {
            mkdir(storage_path() . "/samlidp", 0755, true);
        }

        $storagePath = storage_path() . "/samlidp";
        $days = $this->option('days');
        $keyname = $this->option('keyname');
        $certname = $this->option('certname');

        exec("openssl req -x509 -sha256 -nodes -days {$days} -newkey rsa:2048 -keyout {$storagePath}/{$keyname} -out {$storagePath}/{$certname}");
    }
}
