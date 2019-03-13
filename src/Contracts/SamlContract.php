<?php

namespace CodeGreenCreative\SamlIdp\Contracts;

use LightSaml\SamlConstants;
use LightSaml\Model\Protocol\Response;
use LightSaml\Model\Protocol\AuthnRequest;
use Symfony\Component\HttpFoundation\Request;

interface SamlContract
{
    public function handle();

    public function response();

    public function send($binding_type);
}
