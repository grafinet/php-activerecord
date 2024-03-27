<?php

namespace PhpActiveRecordQueryBuilder\Operator;

use function sprintf;

class Gte extends Gt
{
    public function __toString()
    {
        return sprintf('%s >= ?', $this->columnName);
    }
}
