<?php

namespace PhpActiveRecordQueryBuilder\Operator;

use function sprintf;

class Neq extends Eq
{
    public function __toString()
    {
        if ($this->value === null) {
            return sprintf('%s IS NOT NULL', $this->columnName);
        }
        return sprintf('%s != ?', $this->columnName);
    }
}
