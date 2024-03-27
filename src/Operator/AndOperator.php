<?php

namespace PhpActiveRecordQueryBuilder\Operator;

use function implode;

class AndOperator implements Operator
{
    protected array $items = [];
    private array $values = [];

    public function __construct(string|Operator ...$fields)
    {
        foreach ($fields as $item) {
            if ($item instanceof Operator && $item->hasValue()) {
                $this->values[] = $item->getValue();
            }
            $this->items[] = (string)$item;
        }
    }

    public static function create(string|Operator ...$fields): static
    {
        return new static(...$fields);
    }

    public function __toString()
    {
        return \sprintf('(%s)', implode(' AND ', $this->items));
    }

    public function hasValue(): bool
    {
        return !empty($this->values);
    }

    public function getValue(): ?ValueBag
    {
        return $this->values ? new ValueBag($this->values): null;
    }
}
