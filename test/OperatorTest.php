<?php

use PhpActiveRecordQueryBuilder\Operator\ValueBag;
use PhpActiveRecordQueryBuilder\QueryBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @group QueryBuilder
 */
class OperatorTest extends TestCase
{
    public function testAndOperator(): void
    {
        $qb = QueryBuilder::create();
        $and = $qb->and(
            '1 = 1',
            '2 <> 3'
        );
        $this->assertFalse($and->hasValue());
        $this->assertEquals('(1 = 1 AND 2 <> 3)', (string)$and);

        $and2 = $qb->and(
            '1 = 1',
            $qb->and('2 <> 3', '3 < 4')
        );
        $this->assertEquals('(1 = 1 AND (2 <> 3 AND 3 < 4))', (string)$and2);
        $and3 = $qb->and(
            $qb->eq('a', 1),
            $qb->neq('b', 2),
        );
        $this->assertTrue($and3->hasValue());
        $this->assertEquals('(a = ? AND b != ?)', (string)$and3);
        $this->assertInstanceOf(ValueBag::class, $and3->getValue());
        $this->assertEquals([1, 2], $and3->getValue()->getValues());
    }

    public function testBetweenOperator(): void
    {
        $qb = QueryBuilder::create();
        $between = $qb->between('year', 2001, 2009);
        $this->assertEquals('year BETWEEN ? AND ?', (string)$between);
        $this->assertTrue($between->hasValue());
        $this->assertInstanceOf(ValueBag::class, $between->getValue());
        $this->assertEquals([2001, 2009], $between->getValue()->getValues());
    }

    public function testEqOperator(): void
    {
        $qb = QueryBuilder::create();
        $eq = $qb->eq('name', 'John');
        $this->assertEquals('name = ?', (string)$eq);
        $this->assertTrue($eq->hasValue());

        $eq2 = $qb->eq('ranking', null);
        $this->assertEquals('ranking IS NULL', (string)$eq2);
        $this->assertFalse($eq2->hasValue());
    }

    public function testFindInSetOperator(): void
    {
        $qb = QueryBuilder::create();
        $findInSet = $qb->findInSet('ids', 42);
        $this->assertEquals('FIND_IN_SET(?, ids)', (string)$findInSet);
        $this->assertTrue($findInSet->hasValue());
        $this->assertEquals(42, $findInSet->getValue());

        $findInSet = $qb->findInSet('ids', 42, true);
        $this->assertEquals('NOT FIND_IN_SET(?, ids)', (string)$findInSet);
    }

    public function testGtOperator(): void
    {
        $qb = QueryBuilder::create();
        $gt = $qb->gt('id', 33);
        $this->assertEquals('id > ?', (string)$gt);
        $this->assertTrue($gt->hasValue());
        $this->assertEquals(33, $gt->getValue());

        $this->expectException(RuntimeException::class);
        $qb->gt('age', null);
    }

    public function testGteOperator(): void
    {
        $qb = QueryBuilder::create();
        $gte = $qb->gte('id', 33);
        $this->assertEquals('id >= ?', (string)$gte);
        $this->assertTrue($gte->hasValue());
        $this->assertEquals(33, $gte->getValue());

        $this->expectException(RuntimeException::class);
        $qb->gte('age', null);
    }

    public function testInOperator(): void
    {
        $qb = QueryBuilder::create();
        $in = $qb->in('id', [1,2,3]);
        $this->assertEquals('id IN(?)', (string)$in);
        $this->assertTrue($in->hasValue());
        $this->assertEquals([1,2,3], $in->getValue());

        $this->expectException(RuntimeException::class);
        $qb->in('age', []);
    }

    public function testLikeOperator(): void
    {
        $qb = QueryBuilder::create();
        $like = $qb->like('description', 'lorem ipsum');
        $this->assertEquals('description LIKE ?', (string)$like);
        $this->assertTrue($like->hasValue());
        $this->assertEquals('%lorem ipsum%', $like->getValue());

        $like2 = $qb->like('full_name', 'J_hn D_e', true);
        $this->assertEquals('J_hn D_e', $like2->getValue());
    }

    public function testLtOperator(): void
    {
        $qb = QueryBuilder::create();
        $lt = $qb->lt('age', 18);
        $this->assertEquals('age < ?', (string)$lt);
        $this->assertTrue($lt->hasValue());
        $this->assertEquals(18, $lt->getValue());

        $this->expectException(RuntimeException::class);
        $qb->lt('age', null);
    }

    public function testLteOperator(): void
    {
        $qb = QueryBuilder::create();
        $lte = $qb->lte('age', 36);
        $this->assertEquals('age <= ?', (string)$lte);
        $this->assertTrue($lte->hasValue());
        $this->assertEquals(36, $lte->getValue());

        $this->expectException(RuntimeException::class);
        $qb->lte('age', null);
    }

    public function testNeqOperator(): void
    {
        $qb = QueryBuilder::create();
        $neq = $qb->neq('name', 'John');
        $this->assertEquals('name != ?', (string)$neq);
        $this->assertTrue($neq->hasValue());

        $neq2 = $qb->neq('ranking', null);
        $this->assertEquals('ranking IS NOT NULL', (string)$neq2);
        $this->assertFalse($neq2->hasValue());
    }

    public function testNotBetweenOperator(): void
    {
        $qb = QueryBuilder::create();
        $notBetween = $qb->notBetween('year', 2005, 2007);
        $this->assertEquals('year NOT BETWEEN ? AND ?', (string)$notBetween);
        $this->assertTrue($notBetween->hasValue());
        $this->assertInstanceOf(ValueBag::class, $notBetween->getValue());
        $this->assertEquals([2005, 2007], $notBetween->getValue()->getValues());
    }

    public function testNotInOperator(): void
    {
        $qb = QueryBuilder::create();
        $notIn = $qb->notIn('id', [7,8,8]);
        $this->assertEquals('id NOT IN(?)', (string)$notIn);
        $this->assertTrue($notIn->hasValue());
        $this->assertEquals([7,8,8], $notIn->getValue());

        $this->expectException(RuntimeException::class);
        $qb->notIn('age', []);
    }

    public function testNotLikeOperator(): void
    {
        $qb = QueryBuilder::create();
        $notLike = $qb->notLike('description', 'lorem ipsum');
        $this->assertEquals('description NOT LIKE ?', (string)$notLike);
    }

    public function testOrOperator(): void
    {
        $qb = QueryBuilder::create();
        $and = $qb->or(
            '1 = 1',
            '2 <> 3'
        );
        $this->assertFalse($and->hasValue());
        $this->assertEquals('(1 = 1 OR 2 <> 3)', (string)$and);

        $and2 = $qb->or(
            '1 = 1',
            $qb->or('2 <> 3', '3 < 4')
        );
        $this->assertEquals('(1 = 1 OR (2 <> 3 OR 3 < 4))', (string)$and2);
        $and3 = $qb->or(
            $qb->eq('a', 1),
            $qb->neq('b', 2),
        );
        $this->assertTrue($and3->hasValue());
        $this->assertEquals('(a = ? OR b != ?)', (string)$and3);
        $this->assertInstanceOf(ValueBag::class, $and3->getValue());
        $this->assertEquals([1, 2], $and3->getValue()->getValues());
    }
}
