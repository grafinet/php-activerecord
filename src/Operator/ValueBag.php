<?php

namespace PhpActiveRecordQueryBuilder\Operator;

class ValueBag
{
    public function __construct(private readonly array $values)
    {

    }

    public function getValues($raw = false): array
    {
        if ($raw) {
            return $this->values;
        }

        $return = [];
        foreach ($this->values as $value) {
            if ($value instanceof ValueBag) {
                $return = [...$return, ...$value->getValues()];
            }
            else {
                $return[] = $value;
            }
        }
        return $return;
    }
}
