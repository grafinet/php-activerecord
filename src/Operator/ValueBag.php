<?php

namespace PhpActiveRecordQueryBuilder\Operator;

class ValueBag
{
    public function __construct(private readonly array $values)
    {

    }

    public function getValues(): array
    {
        return $this->values;
    }
}
