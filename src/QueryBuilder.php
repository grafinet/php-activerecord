<?php

namespace PhpActiveRecordQueryBuilder;

use PhpActiveRecordQueryBuilder\Operator\AndOperator;
use PhpActiveRecordQueryBuilder\Operator\Between;
use PhpActiveRecordQueryBuilder\Operator\Eq;
use PhpActiveRecordQueryBuilder\Operator\FindInSet;
use PhpActiveRecordQueryBuilder\Operator\Gt;
use PhpActiveRecordQueryBuilder\Operator\Gte;
use PhpActiveRecordQueryBuilder\Operator\In;
use PhpActiveRecordQueryBuilder\Operator\Like;
use PhpActiveRecordQueryBuilder\Operator\Lt;
use PhpActiveRecordQueryBuilder\Operator\Lte;
use PhpActiveRecordQueryBuilder\Operator\Neq;
use PhpActiveRecordQueryBuilder\Operator\NotBetween;
use PhpActiveRecordQueryBuilder\Operator\NotIn;
use PhpActiveRecordQueryBuilder\Operator\NotLike;
use PhpActiveRecordQueryBuilder\Operator\Operator;
use PhpActiveRecordQueryBuilder\Operator\OrOperator;
use PhpActiveRecordQueryBuilder\Operator\ValueBag;
use function array_merge;
use function get_class;
use function implode;
use function ltrim;
use function sprintf;

/**
 * @psalm-template T
 */
final class QueryBuilder
{
    public const ORDER_ASC = 'ASC';
    public const ORDER_DESC = 'DESC';

    private array $select = [];
    private ?string $from = null;
    private array $joins = [];
    private array $where = [];
    private array $whereParameters = [];
    private array $group = [];
    private array $having = [];
    private array $havingParameters = [];
    private array $order = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $include = [];

    /**
     * @param string|null $modelClass
     * @psalm-param class-string<T> $modelClass
     */
    public function __construct(private readonly ?string $modelClass)
    {
    }

    public static function create(?string $modelClass = null): self
    {
        return new self($modelClass);
    }

    /**
     * @return T[]
     */
    public function all(): array
    {
        $func = "{$this->modelClass}::all";
        return $func($this->toOptionsArray());
    }

    /**
     * @return T|null
     */
    public function first()
    {
        $func = "{$this->modelClass}::first";
        return $func($this->toOptionsArray());
    }

    /**
     * @return T|null
     */
    public function last()
    {
        $func = "{$this->modelClass}::last";
        return $func($this->toOptionsArray());
    }

    /**
     * @return T[]
     */
    public function find(): array
    {
        $func = "{$this->modelClass}::all";
        return $func($this->toOptionsArray());
    }

    public function count(): int
    {
        $func = "{$this->modelClass}::count";
        return $func($this->toOptionsArray());
    }

    public function exists(): bool
    {
        $func = "{$this->modelClass}::exists";
        return $func($this->toOptionsArray());
    }

    public function toOptionsArray(): array
    {
        $options = [];
        $params = [];
        if ($this->select) {
            $options['select'] = implode(', ', $this->select);
        }
        if ($this->from) {
            $options['from'] = $this->from;
        }
        if ($this->joins) {
            $options['joins'] = implode("\n", $this->joins);
        }
        if ($this->group) {
            $options['group'] = implode(", ", $this->group);
        }
        if ($this->having) {
            $options['having'] = implode(" AND ", $this->having);
            if ($this->havingParameters) {
                $params = $this->havingParameters;
            }
        }
        if ($this->order) {
            $options['order'] = implode(", ", $this->order);
        }
        if ($this->limit) {
            $options['limit'] = $this->limit;
        }
        if ($this->offset) {
            $options['offset'] = $this->offset;
        }
        if ($this->include) {
            $options['include'] = $this->include;
        }
        $params = array_merge($this->whereParameters, $params);
        if ($params && !$this->where) {
            $this->where[] = '1=1';
        }
        if ($this->where) {
            $options['conditions'] = [
                implode(' AND ', $this->where),
                ...$params
            ];
        }
        return $options;
    }

    public function select(string ...$select): self
    {
        $this->select = array_merge($this->select, $select);
        return $this;
    }

    public function from(string $table): self
    {
        $this->from = $table;
        return $this;
    }

    public function join(string $tableName, string $type = '', ?string $on = null): self
    {
        $join = ltrim(sprintf('%s JOIN %s', $type, $tableName));
        if (null !== $on) {
            $join .= " ON({$on})";
        }
        $this->joins[] = $join;
        return $this;
    }

    public function leftJoin(string $tableName, ?string $on = null): self
    {
        return $this->join($tableName, 'LEFT', $on);
    }

    public function rightJoin(string $tableName, ?string $on = null): self
    {
        return $this->join($tableName, 'RIGHT', $on);
    }

    public function where(string|Operator $where, ...$values): self
    {
        if ($where instanceof Operator && $where->hasValue()) {
            $value = $where->getValue();
            if ($value instanceof ValueBag) {
                $values = $value->getValues();
            } else {
                $values = [$value];
            }
        }
        $this->where[] = (string)$where;
        if ($values) {
            $this->whereParameters = array_merge($this->whereParameters, $values);
        }
        return $this;
    }

    public function groupBy(string ...$group): self
    {
        $this->group = array_merge($this->group, $group);
        return $this;
    }

    public function having(string|Operator $having, ...$values): self
    {
        if ($having instanceof Operator && $having->hasValue()) {
            if ($having instanceof In) {
                throw new \RuntimeException(
                    \sprintf('Unsupported operator type %s for method %s', get_class($having), __METHOD__)
                );
            }
            $value = $having->getValue();
            if ($value instanceof ValueBag) {
                $values = $value->getValues();
            } else {
                $values = [$value];
            }
        }
        $this->having[] = (string)$having;
        if ($values) {
            $this->havingParameters = array_merge($this->havingParameters, $values);
        }
        return $this;
    }

    public function orderBy(string $columnName, $direction = self::ORDER_ASC): self
    {
        $this->order[] = sprintf('%s %s', $columnName, $direction);
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(string $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function include(string ...$include): self
    {
        $this->include = array_merge($this->include, $include);
        return $this;
    }

    public function and(string|Operator ...$andFields): AndOperator
    {
        return AndOperator::create(...$andFields);
    }

    public function or(string|Operator ...$orFields): OrOperator
    {
        return OrOperator::create(...$orFields);
    }

    public function eq(string $columnName, ?string $value): Eq
    {
        return Eq::create($columnName, $value);
    }

    public function neq(string $columnName, ?string $value): Neq
    {
        return Neq::create($columnName, $value);
    }

    public function between(string $columnName, mixed $valueMin, mixed $valueMax): Between
    {
        return Between::create($columnName, $valueMin, $valueMax);
    }

    public function findInSet(string $set, mixed $value, bool $not = false): FindInSet
    {
        return FindInSet::create($set, $value, $not);
    }

    public function gt(string $columnName, mixed $value): Gt
    {
        return Gt::create($columnName, $value);
    }

    public function gte(string $columnName, mixed $value): Gte
    {
        return Gte::create($columnName, $value);
    }

    public function in(string $columnName, array $values): In
    {
        return In::create($columnName, $values);
    }

    public function like(string $columnName, string $value, bool $rawValue = false): Like
    {
        return Like::create($columnName, $value, $rawValue);
    }

    public function lt(string $columnName, mixed $value): Lt
    {
        return Lt::create($columnName, $value);
    }

    public function lte(string $columnName, mixed $value): Lte
    {
        return Lte::create($columnName, $value);
    }

    public function notBetween(string $columnName, mixed $valueMin, mixed $valueMax): NotBetween
    {
        return NotBetween::create($columnName, $valueMin, $valueMax);
    }

    public function notIn(string $columnName, array $values): NotIn
    {
        return NotIn::create($columnName, $values);
    }

    public function notLike(string $columnName, string $value, bool $rawValue = false): NotLike
    {
        return NotLike::create($columnName, $value, $rawValue);
    }
}
