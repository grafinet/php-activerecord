<?php

namespace PhpActiveRecordQueryBuilder\Operator;

use function implode;

class OrOperator extends AndOperator
{
    public function __toString()
    {
        return \sprintf('(%s)', implode(' OR ', $this->items));
    }
}
