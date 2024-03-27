<?php

namespace PhpActiveRecordQueryBuilder\Operator;

interface Operator
{
    public function __toString();
    public function hasValue(): bool;
    public function getValue(): mixed;
}
