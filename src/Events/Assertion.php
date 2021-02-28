<?php

namespace Maghonemi\SamlIdp\Events;

use LightSaml\ClaimTypes;
use Illuminate\Queue\SerializesModels;
use LightSaml\Model\Assertion\Attribute;

class Assertion
{
    use SerializesModels;

    /**
     * The SAML assertion attribute statement
     *
     * @var object
     */
    public $attribute_statement;

    /**
     * Create a new event instance.
     *
     * @param  string $guard
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  bool  $remember
     * @return void
     */
    public function __construct(\LightSaml\Model\Assertion\AttributeStatement &$attribute_statement)
    {
        $this->attribute_statement = &$attribute_statement;
        $this->attribute_statement
            ->addAttribute(new Attribute(ClaimTypes::EMAIL_ADDRESS, auth()->user()->email))
            ->addAttribute(new Attribute(ClaimTypes::COMMON_NAME, auth()->user()->name));
    }
}
