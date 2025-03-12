<?php

namespace Feral\Agent\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
class AiContext
{
    public function __construct(
        public string $description,        // Description of the context or purpose
        public ?string $intent = null,     // Optional intent for additional guidance
        public ?array $examples = null     // Optional examples for more context
    ) {}
}
