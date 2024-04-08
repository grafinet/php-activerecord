<?php

use PhpActiveRecordQueryBuilder\QueryBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @group QueryBuilder
 */
class QueryBuilderTest extends TestCase
{
    public function testHasSelectOption()
    {
        $options = QueryBuilder::create()
            ->select('id')
            ->select('name', 'age')
            ->select('is_enabled, COUNT(*)')
            ->toOptionsArray();
        $this->assertArrayHasKey('select', $options);
        $this->assertEquals('id, name, age, is_enabled, COUNT(*)', $options['select']);
    }

    public function testHasFromOption()
    {
        $options = QueryBuilder::create()
            ->from('first_name')
            ->from('table_name t1')
            ->toOptionsArray();
        $this->assertArrayHasKey('from', $options);
        $this->assertEquals('table_name t1', $options['from']);
    }

    public function testHasJoinsOption()
    {
        $qb = QueryBuilder::create();
        $options = $qb->join('table')
            ->leftJoin('table_left', 'table_left.id = table.id')
            ->rightJoin('table_right')
            ->join('table_inner', $qb->eq('table_inner.id', 42), 'INNER')
            ->toOptionsArray();
        $this->assertArrayHasKey('joins', $options);
        $this->assertArrayHasKey('conditions', $options);
        $this->assertEquals(<<<EOJ
JOIN table
LEFT JOIN table_left ON(table_left.id = table.id)
RIGHT JOIN table_right
INNER JOIN table_inner ON(table_inner.id = ?)
EOJ,
            $options['joins']
        );
        $this->assertEquals(['1=1', 42], $options['conditions']);

        $this->expectException(RuntimeException::class);
        $qb->leftJoin('bad_table', $qb->in('e', [1,2,3]));
    }

    public function testHasGroupOption()
    {
        $options = QueryBuilder::create()
            ->groupBy('id')
            ->groupBy('name', 'age')
            ->groupBy('is_enabled WITH ROLLUP')
            ->toOptionsArray();
        $this->assertArrayHasKey('group', $options);
        $this->assertEquals('id, name, age, is_enabled WITH ROLLUP', $options['group']);
    }

    public function testHasHavingOption()
    {
        $qb = QueryBuilder::create();
        $options = QueryBuilder::create()
            ->having('COUNT(id) > 1')
            ->toOptionsArray();
        $this->assertArrayHasKey('having', $options);
        $this->assertEquals('COUNT(id) > 1', $options['having']);

        $options2 = QueryBuilder::create()
            ->having('cnt > ?', 10)
            ->toOptionsArray();

        $this->assertArrayHasKey('conditions', $options2);
        $this->assertEquals(['1=1', 10], $options2['conditions']);

        $options3 = QueryBuilder::create()
            ->having($qb->eq('c', 30))
            ->toOptionsArray();
        $this->assertEquals('c = ?', $options3['having']);

        $options4 = QueryBuilder::create()
            ->having(
                $qb->and(
                    $qb->neq('a', 3),
                    $qb->neq('b', 4),
                )
            )
            ->toOptionsArray();
        $this->assertEquals('(a != ? AND b != ?)', $options4['having']);

        $this->expectException(RuntimeException::class);
        QueryBuilder::create()
            ->having(
                $qb->in('e', [1,2,3])
            )
            ->toOptionsArray();
    }

    public function testHasOrderOption()
    {
        $options = QueryBuilder::create()
            ->orderBy('first_col')
            ->orderBy('second_col', QueryBuilder::ORDER_DESC)
            ->toOptionsArray();
        $this->assertArrayHasKey('order', $options);
        $this->assertEquals('first_col ASC, second_col DESC', $options['order']);
    }

    public function testHasLimitOption()
    {
        $options = QueryBuilder::create()
            ->limit(10)
            ->toOptionsArray();
        $this->assertArrayHasKey('limit', $options);
        $this->assertEquals(10, $options['limit']);
    }

    public function testHasOffsetOption()
    {
        $options = QueryBuilder::create()
            ->offset(100)
            ->toOptionsArray();
        $this->assertArrayHasKey('offset', $options);
        $this->assertEquals(100, $options['offset']);
    }

    public function testHasIncludeOption()
    {
        $options = QueryBuilder::create()
            ->include('table1')
            ->include('table2', 'table3')
            ->toOptionsArray();
        $this->assertArrayHasKey('include', $options);
        $this->assertEquals(['table1', 'table2', 'table3'], $options['include']);
    }

    public function testHasConditionsOption()
    {
        $qb = QueryBuilder::create();
        $options = QueryBuilder::create()
            ->where('1=1')
            ->toOptionsArray();
        $this->assertArrayHasKey('conditions', $options);
        $this->assertEquals(['1=1'], $options['conditions']);

        $options2 = QueryBuilder::create()
            ->where('a = ? and c > ?', 1, 3)
            ->toOptionsArray();
        $this->assertEquals(['a = ? and c > ?', 1, 3], $options2['conditions']);

        $options3 = QueryBuilder::create()
            ->where($qb->eq('b', 2))
            ->toOptionsArray();
        $this->assertEquals(['b = ?', 2], $options3['conditions']);

        $options4 = $qb
            ->where(
                $qb->or(
                    $qb->eq('b', 2),
                    $qb->eq('c', 3)
                )
            )
            ->toOptionsArray();
        $this->assertEquals(['(b = ? OR c = ?)', 2, 3], $options4['conditions']);
    }

    public function testAll()
    {
        $qb = QueryBuilder::create();
        $options = $qb->select('tb.*')
            ->select('COUNT(tb2.*) as cnt', 'tb3.priority')
            ->from('table tb')
            ->leftJoin('table2 tb2')
            ->rightJoin('table3 tb3', 'tb3.table_id = tb1.id')
            ->join('table3 tb4', $qb->and(
                'tb4.id = tb2.id',
                $qb->eq('tb4.group', 'test123')
            ), 'INNER')
            ->where($qb->neq('tb.published_at', null))
            ->where('tb3.active')
            ->where('tb4.priority <> ?', 0)
            ->where($qb->notIn('tb3.status', ['draft', 'pending']))
            ->where($qb->or(
                $qb->eq('tb.deleted_at', null),
                $qb->eq('tb2.user_id', 666)
            ))
            ->where($qb->and(
                $qb->gte('tb.id', 2137),
                $qb->gt('tb.updated_at', '2024-03-21'),
                $qb->lt('tb.created_at', '2024-03-20'),
                $qb->lte('tb4.likes', 1_000_000),
            ))
            ->groupBy('tb3.category')
            ->groupBy('tb2.status')
            ->having($qb->between('cnt', 100, 200))
            ->orderBy('tb3.priority', QueryBuilder::ORDER_DESC)
            ->orderBy('tb1.name')
            ->limit(10)
            ->offset(30)
            ->include('user', 'category')
            ->toOptionsArray();
        $this->assertEquals([
            'select' => 'tb.*, COUNT(tb2.*) as cnt, tb3.priority',
            'from' => 'table tb',
            'joins' => 'LEFT JOIN table2 tb2
RIGHT JOIN table3 tb3 ON(tb3.table_id = tb1.id)
INNER JOIN table3 tb4 ON((tb4.id = tb2.id AND tb4.group = ?))',
            'group' => 'tb3.category, tb2.status',
            'having' => 'cnt BETWEEN ? AND ?',
            'order' => 'tb3.priority DESC, tb1.name ASC',
            'limit' => 10,
            'offset' => 30,
            'include' => ['user', 'category'],
            'conditions' => [
                'tb.published_at IS NOT NULL AND tb3.active AND tb4.priority <> ? AND tb3.status NOT IN(?) AND (tb.deleted_at IS NULL OR tb2.user_id = ?) AND (tb.id >= ? AND tb.updated_at > ? AND tb.created_at < ? AND tb4.likes <= ?)',
                'test123',
                0,
                ['draft', 'pending'],
                666,
                2137,
                '2024-03-21',
                '2024-03-20',
                1000000,
                100,
                200
            ]
        ], $options);
    }

    public function testTableAlias()
    {
        $qb = QueryBuilder::create(Author::class, 't0');
        $options = $qb->toOptionsArray();
        $this->assertArrayHasKey('from', $options);
        $this->assertArrayHasKey('select', $options);
        $this->assertEquals('`t0`.*', $options['select']);
        $this->assertEquals('`authors` `t0`', $options['from']);
        $options2 = QueryBuilder::create(AuthorAttrAccessible::class, 't0')->toOptionsArray();
        $this->assertEquals('`authors` `t0`', $options2['from']);
    }

    public function testModelReturnsResults()
    {
        $qb = QueryBuilder::create(Author::class);
        $qb->where('author_id > 0');
        $this->assertTrue($qb->exists());
        $this->assertTrue($qb->count() > 0);
        $this->assertInstanceOf(Author::class, $qb->first());
        $this->assertInstanceOf(Author::class, $qb->last());
        $this->assertInstanceOf(Author::class, $qb->find()[0]);
        $this->assertInstanceOf(Author::class, $qb->all()[1]);
    }

    public function testResetState()
    {
        $qb = QueryBuilder::create();
        $qb->select('a', 'b');
        $qb->from('table t0');
        $qb->limit(10);
        $qb->offset(20);
        $qb->where('a = ?', 1);
        $qb->reset('from');
        $qb->reset('limit', 'offset');
        $qb->reset('where');
        $options = $qb->toOptionsArray();
        $this->assertArrayHasKey('select', $options);
        $this->assertEquals(['select' => 'a, b'], $options);
        $this->expectException(RuntimeException::class);
        $qb->reset('table');
    }
}
