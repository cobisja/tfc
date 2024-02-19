<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class TaxCodeFormat extends Constraint
{
    public string $message = 'The code has an invalid format';
    public string $mode = 'strict';

    public function __construct(string $mode = null, string $message = null, array $groups = null, $payload = null)
    {
        parent::__construct([], $groups, $payload);

        $this->mode = $mode ?? $this->mode;
        $this->message = $message ?? $this->message;
    }

    public function validatedBy(): string
    {
        return \get_class($this) . 'Validator';
    }
}