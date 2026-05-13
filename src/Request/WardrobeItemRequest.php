<?php

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class WardrobeItemRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public readonly string $name,
        #[Assert\NotBlank]
        public readonly string $type,
        public readonly ?string $color = null,
        public readonly ?string $season = null,
        public readonly ?string $imageUrl = null,
    ) {
    }
}
