<?php

namespace PhpActiveRecordQueryBuilder\Operator;

use function sprintf;

class Like implements Operator
{
    public function __construct(
        protected readonly string $columnName,
        private readonly string $value,
        private readonly bool $rawValue = false
    )
    {
    }

    public static function create(string $columnName, string $value, bool $rawValue = false): static
    {
        return new static($columnName, $value, $rawValue);
    }

    public function __toString()
    {
        return sprintf('%s LIKE ?', $this->columnName);
    }

    public function hasValue(): bool
    {
        return true;
    }

    public function getValue(): string
    {
        if ($this->rawValue) {
            return $this->value;
        }
        return '%' . $this->value . '%';
    }
}
