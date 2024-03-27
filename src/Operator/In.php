<?php

namespace PhpActiveRecordQueryBuilder\Operator;

use function sprintf;

class In implements Operator
{
    public function __construct(
        protected readonly string $columnName,
        private readonly array $values
    )
    {
        if (empty($this->values)) {
            throw new \RuntimeException("Parameter \$value cannot be empty array for " . static::class);
        }
    }

    public static function create(string $columnName, array $values): static
    {
        return new static($columnName, $values);
    }

    public function __toString()
    {
        return sprintf('%s IN(?)', $this->columnName);
    }

    public function hasValue(): bool
    {
        return !empty($this->values);
    }

    public function getValue(): mixed
    {
        return $this->values;
    }
}
