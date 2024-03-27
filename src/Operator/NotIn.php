<?php

namespace PhpActiveRecordQueryBuilder\Operator;

use function sprintf;

class NotIn extends In
{
    public function __toString()
    {
        return sprintf('%s NOT IN(?)', $this->columnName);
    }

}
