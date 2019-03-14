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
        $storagePath = storage_path() . "/samlidp";
        $days = $this->option('days');
        $keyname = $this->option('keyname');
        $certname = $this->option('certname');

        // Create storage/samlidp directory
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        if (file_exists("$storagePath/$keyname") || file_exists("$storagePath/$certname")) {
            if (!$this->confirm('The name chosen for the certificate and key already exist, would you like to overwrite the existing certificate and key?')) {
                return;
            }
        }

        exec("openssl req -x509 -sha256 -nodes -days {$days} -newkey rsa:2048 -keyout {$storagePath}/{$keyname} -out {$storagePath}/{$certname}");
    }
}
