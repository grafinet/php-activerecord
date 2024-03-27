<?php

namespace PhpActiveRecordQueryBuilder\Operator;

use function sprintf;

class Lte extends Lt
{
    public function __toString()
    {
        return sprintf('%s <= ?', $this->columnName);
    }
}
