<?php

namespace PhpActiveRecordQueryBuilder\Operator;

use function sprintf;

class NotLike extends Like
{
    public function __toString()
    {
        return sprintf('%s NOT LIKE ?', $this->columnName);
    }
}
