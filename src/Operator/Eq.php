<?php

namespace PhpActiveRecordQueryBuilder\Operator;

use function sprintf;

class Eq implements Operator
{
    public function __construct(
        protected readonly string $columnName,
        protected mixed $value
    )
    {
    }

    public static function create(string $columnName, mixed $value): static
    {
        return new static($columnName, $value);
    }

    public function __toString()
    {
        if ($this->value === null) {
            return sprintf('%s IS NULL', $this->columnName);
        }
        return sprintf('%s = ?', $this->columnName);
    }

    public function hasValue(): bool
    {
        return $this->value !== null;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
