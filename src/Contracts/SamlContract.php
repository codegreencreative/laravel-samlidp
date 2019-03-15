<?php

namespace CodeGreenCreative\SamlIdp\Contracts;

interface SamlContract
{
    public function handle();

    public function response();

    public function send($binding_type);

    public function getServiceProvider($request);
}
