<?php

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class WardrobeItemRequest
{
    public function __construct(
        #[Assert\NotBlank(null, 'name is required')]
        public readonly string $name,
        #[Assert\NotBlank(null, 'type is required')]
        public readonly string $type,
        public readonly ?string $color = null,
        public readonly ?string $season = null,
        public readonly ?string $imageUrl = null,
        #[Assert\NotBlank(null, 'user is required')]
        public readonly ?int $user = null,
    ) {
    }
}
