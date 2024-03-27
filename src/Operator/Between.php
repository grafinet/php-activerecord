<?php

namespace PhpActiveRecordQueryBuilder\Operator;

use function sprintf;

class Between implements Operator
{
    public function __construct(
        protected readonly string $columnName,
        private readonly mixed $valueMin,
        private readonly mixed $valueMax
    )
    {
    }

    public static function create(string $columnName, mixed $valueMin, mixed $valueMax): static
    {
        return new static($columnName, $valueMin, $valueMax);
    }

    public function __toString()
    {
        return sprintf('%s BETWEEN ? AND ?', $this->columnName);
    }

    public function hasValue(): bool
    {
        return true;
    }

    public function getValue(): ValueBag
    {
        return new ValueBag([$this->valueMin, $this->valueMax]);
    }
}
