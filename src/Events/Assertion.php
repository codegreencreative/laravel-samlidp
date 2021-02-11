<?php

namespace CodeGreenCreative\SamlIdp\Events;

use Illuminate\Support\Facades\Auth;
use LightSaml\ClaimTypes;
use Illuminate\Queue\SerializesModels;
use LightSaml\Model\Assertion\Attribute;
use LightSaml\Model\Assertion\AttributeStatement;

class Assertion
{
    use SerializesModels;

    /**
     * The SAML assertion attribute statement
     *
     * @var object
     */
    public AttributeStatement $attribute_statement;

    /**
     * Optional guard name
     *
     * @var string|null
     */
    public string $guard;

    /**
     * Assertion constructor.
     * @param AttributeStatement $attribute_statement
     * @param string|null $guard
     */
    public function __construct(AttributeStatement &$attribute_statement, string $guard = null)
    {
        $this->attribute_statement = &$attribute_statement;
        $this->guard = $guard;
        $this->attribute_statement
            ->addAttribute(new Attribute(ClaimTypes::EMAIL_ADDRESS, Auth::guard($this->guard)->user()->email))
            ->addAttribute(new Attribute(ClaimTypes::COMMON_NAME, Auth::guard($this->guard)->user()->name));
    }
}
