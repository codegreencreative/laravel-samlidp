<?php

namespace CodeGreenCreative\SamlIdp\Contracts;

use LightSaml\Helper;
use LightSaml\SamlConstants;
use LightSaml\Model\Protocol\Status;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Assertion\NameID;
use LightSaml\Model\Protocol\Response;
use LightSaml\Model\Protocol\StatusCode;
use LightSaml\Model\Protocol\AuthnRequest;
use Illuminate\Foundation\Bus\Dispatchable;
use Symfony\Component\HttpFoundation\Request;
use LightSaml\Model\Context\DeserializationContext;
use CodeGreenCreative\SamlIdp\Traits\PerformsSingleSignOn;

interface SamlContract
{
    public function handle();

    public function response();

    public function send($binding_type);

    public function getServiceProvider($request);
}
