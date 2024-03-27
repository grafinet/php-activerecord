<?php

namespace PhpActiveRecordQueryBuilder\Operator;

use function sprintf;

class Lt implements Operator
{
    public function __construct(
        protected readonly string $columnName,
        private readonly mixed $value
    )
    {
        if (null === $this->value) {
            throw new \RuntimeException("Parameter \$value cannot be NULL for " . static::class);
        }
    }

    public static function create(string $columnName, mixed $value): static
    {
        return new static($columnName, $value);
    }

    public function __toString()
    {
        return sprintf('%s < ?', $this->columnName);
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
