<?php

namespace CodeGreenCreative\SamlIdp\Events;

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
    public function __construct(\LightSaml\Model\Assertion\AttributeStatement &$attribute_statement, $guard = null)
    {
        $this->attribute_statement = &$attribute_statement;
        $this->attribute_statement
            ->addAttribute(new Attribute(ClaimTypes::EMAIL_ADDRESS, auth($guard)->user()->__get(config('samlidp.email_field', 'email'))))
            ->addAttribute(new Attribute(ClaimTypes::COMMON_NAME, auth($guard)->user()->__get(config('samlidp.name_field', 'name'))));
    }
}
