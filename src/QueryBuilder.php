<?php

namespace PhpActiveRecordQueryBuilder;

use ActiveRecord\Inflector;
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
use RuntimeException;

use function array_diff_key;
use function array_flip;
use function array_merge;
use function basename;
use function get_class;
use function implode;
use function ltrim;
use function sprintf;
use function str_replace;

/** @template T */
final class QueryBuilder
{
    public const ORDER_ASC = 'ASC';
    public const ORDER_DESC = 'DESC';

    private array $select = [];
    private ?string $from = null;
    private array $joins = [];
    private array $joinsParameters = [];
    private array $where = [];
    private array $whereParameters = [];
    private array $orWhere = [];
    private array $orWhereParameters = [];
    private array $group = [];
    private array $having = [];
    private array $havingParameters = [];
    private array $order = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $include = [];

    /**
     * @param class-string<T>|null $modelClass
     * @param string|null $tableAlias
     */
    public function __construct(
        private readonly ?string $modelClass,
        private readonly ?string $tableAlias,
    )
    {
        if ($this->tableAlias) {
            $tableName = $this->modelClass::$table_name ?: Inflector::instance()->tableize(basename(str_replace('\\', '/', $this->modelClass)));
            $this->from = sprintf('`%s` `%s`', $tableName, $this->tableAlias);
            $this->select(sprintf('`%s`.*', $this->tableAlias));
        }
    }

    /**
     * @param class-string<T>|null $modelClass
     * @return self<T>
     */
    public static function create(?string $modelClass = null, ?string $tableAlias = null): self
    {
        return new self($modelClass, $tableAlias);
    }

    private function getStaticMethodToCall(string $methodName): string
    {
        if (!$this->modelClass) {
            throw new RuntimeException('Model class not specified');
        }
        return "{$this->modelClass}::{$methodName}";
    }

    /**
     * @return T[]
     */
    public function all(): array
    {
        $func = $this->getStaticMethodToCall('all');
        return $func($this->toOptionsArray());
    }

    /**
     * @return T|null
     */
    public function first()
    {
        $func = $this->getStaticMethodToCall('first');
        return $func($this->toOptionsArray());
    }

    /**
     * @return T|null
     */
    public function last()
    {
        $func = $this->getStaticMethodToCall('last');
        return $func($this->toOptionsArray());
    }

    /**
     * @see Model::delete_all()
     * @return int
     */
    public function deleteAll(): int
    {
        $options = $this->toOptionsArray();
        if ($diff = array_diff_key($options, array_flip(['conditions', 'limit', 'order']))) {
            throw new RuntimeException('Unsupported options array keys for deleteAll: ' . implode(', ', $diff));
        }
        $func = $this->getStaticMethodToCall('delete_all');
        return $func($options);
    }

    /**
     * @see Model::update_all()
     * @param array $setValues
     * @return int
     */
    public function updateAll(array $setValues = []): int
    {
        $options = $this->toOptionsArray();
        if ($diff = array_diff_key($options, array_flip(['conditions', 'limit', 'order']))) {
            throw new RuntimeException('Unsupported options array keys for updateAll: ' . implode(', ', array_keys($diff)));
        }
        $options['set'] = $setValues;
        $func = $this->getStaticMethodToCall('update_all');
        return $func($options);
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
        $params = array_merge($this->joinsParameters, $this->whereParameters, $this->orWhereParameters, $this->havingParameters);
        if ($params && !$this->where) {
            $this->where[] = '1=1';
        }
        if ($this->where) {
            $options['conditions'] = [
                '(' . implode(' AND ', $this->where) . ')',
                ...$params
            ];
        }
        if ($this->orWhere) {
            $options['conditions'][0] .= ' AND (' . implode(' OR ', $this->orWhere) . ')';
        }
        return $options;
    }

    public function reset(string ...$options): self
    {
        foreach ($options as $option) {
            match ($option) {
                'from', 'limit', 'offset' => $this->{$option} = null,
                'select', 'group', 'order', 'include' => $this->{$option} = [],
                'joins', 'having' => $this->{$option} = $this->{$option . 'Parameters'} = [],
                'where' => $this->orWhere = $this->orWhereParameters = $this->where = $this->whereParameters = [],
                default => throw new RuntimeException(sprintf('Unsupported parameter "%s" for method %s', $option, __METHOD__)),
            };
        }
        return $this;
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

    public function join(string $tableName, null|string|Operator $on = null, string $type = ''): self
    {
        $join = ltrim(sprintf('%s JOIN %s', $type, $tableName));
        if (null !== $on) {
            $join .= " ON({$on})";
            if ($on instanceof Operator and $on->hasValue()) {
                if ($on instanceof In) {
                    throw new RuntimeException(
                        sprintf('Unsupported operator type %s for method %s', get_class($on), __METHOD__)
                    );
                }
                $value = $on->getValue();
                if ($value instanceof ValueBag) {
                    $values = $value->getValues();
                } else {
                    $values = [$value];
                }
                if ($values) {
                    $this->joinsParameters = array_merge($this->joinsParameters, $values);
                }
            }
        }
        $this->joins[] = $join;
        return $this;
    }

    public function leftJoin(string $tableName, null|string|Operator $on = null): self
    {
        return $this->join($tableName, $on, 'LEFT');
    }

    public function rightJoin(string $tableName, null|string|Operator $on = null): self
    {
        return $this->join($tableName, $on, 'RIGHT');
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

    public function andWhere(string|Operator $where, ...$values): self
    {
        return $this->where($where, ...$values);
    }

    public function orWhere(string|Operator $where, ...$values): self
    {
        if ($where instanceof Operator && $where->hasValue()) {
            $value = $where->getValue();
            if ($value instanceof ValueBag) {
                $values = $value->getValues();
            } else {
                $values = [$value];
            }
        }
        $this->orWhere[] = (string)$where;
        if ($values) {
            $this->orWhereParameters = array_merge($this->orWhereParameters, $values);
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
                throw new RuntimeException(
                    sprintf('Unsupported operator type %s for method %s', get_class($having), __METHOD__)
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

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function include(array|string ...$include): self
    {
        $this->include = array_merge(
            $this->include,
            ...array_map(
                fn($item) => is_array($item) ? $item : [$item],
                $include
            )
        );
        return $this;
    }

    public static function and(string|Operator ...$andFields): AndOperator
    {
        return AndOperator::create(...$andFields);
    }

    public static function or(string|Operator ...$orFields): OrOperator
    {
        return OrOperator::create(...$orFields);
    }

    public static function eq(string $columnName, mixed $value): Eq
    {
        return Eq::create($columnName, $value);
    }

    public static function neq(string $columnName, mixed $value): Neq
    {
        return Neq::create($columnName, $value);
    }

    public static function between(string $columnName, mixed $valueMin, mixed $valueMax): Between
    {
        return Between::create($columnName, $valueMin, $valueMax);
    }

    public static function findInSet(string $set, mixed $value, bool $not = false): FindInSet
    {
        return FindInSet::create($set, $value, $not);
    }

    public static function gt(string $columnName, mixed $value): Gt
    {
        return Gt::create($columnName, $value);
    }

    public static function gte(string $columnName, mixed $value): Gte
    {
        return Gte::create($columnName, $value);
    }

    public static function in(string $columnName, array $values): In
    {
        return In::create($columnName, $values);
    }

    public static function like(string $columnName, string $value, bool $rawValue = false): Like
    {
        return Like::create($columnName, $value, $rawValue);
    }

    public static function lt(string $columnName, mixed $value): Lt
    {
        return Lt::create($columnName, $value);
    }

    public static function lte(string $columnName, mixed $value): Lte
    {
        return Lte::create($columnName, $value);
    }

    public static function notBetween(string $columnName, mixed $valueMin, mixed $valueMax): NotBetween
    {
        return NotBetween::create($columnName, $valueMin, $valueMax);
    }

    public static function notIn(string $columnName, array $values): NotIn
    {
        return NotIn::create($columnName, $values);
    }

    public static function notLike(string $columnName, string $value, bool $rawValue = false): NotLike
    {
        return NotLike::create($columnName, $value, $rawValue);
    }
}
