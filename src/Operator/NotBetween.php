<?php

namespace PhpActiveRecordQueryBuilder\Operator;

use function sprintf;

class NotBetween extends Between
{
    public function __toString()
    {
        return sprintf('%s NOT BETWEEN ? AND ?', $this->columnName);
    }
}
