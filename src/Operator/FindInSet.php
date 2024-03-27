<?php

namespace PhpActiveRecordQueryBuilder\Operator;

use function ltrim;
use function sprintf;

class FindInSet implements Operator
{
    public function __construct(
        protected readonly string $set,
        private readonly mixed    $value,
        private readonly bool     $not = false
    )
    {
    }

    public static function create(string $set, mixed $value, bool $not = false): static
    {
        return new static($set, $value, $not);
    }

    public function __toString()
    {
        if ($this->not) {
            return sprintf('NOT FIND_IN_SET(?, %s)', $this->set);
        }
        return sprintf('FIND_IN_SET(?, %s)', $this->set);
    }

    public function hasValue(): bool
    {
        return true;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
