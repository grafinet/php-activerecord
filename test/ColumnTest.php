<?php

use ActiveRecord\Column;
use ActiveRecord\DateTime;
use ActiveRecord\DatabaseException;

#[\AllowDynamicProperties]
class ColumnTest extends SnakeCase_PHPUnit_Framework_TestCase
{
	public function set_up()
	{
		$this->column = new Column();
		try {
			$this->conn = ActiveRecord\ConnectionManager::get_connection(ActiveRecord\Config::instance()->get_default_connection());
		} catch (DatabaseException $e) {
			$this->mark_test_skipped('failed to connect using default connection. '.$e->getMessage());
		}
	}

	public function assert_mapped_type($type, $raw_type)
	{
		$this->column->raw_type = $raw_type;
		$this->assert_equals($type,$this->column->map_raw_type());
	}

	public function assert_cast($type, $casted_value, $original_value)
	{
		$this->column->type = $type;
		$value = $this->column->cast($original_value,$this->conn);

		if ($original_value != null && ($type == Column::DATETIME || $type == Column::DATE))
			$this->assert_true($value instanceof DateTime);
		else
			$this->assert_same($casted_value,$value);
	}

	public function test_map_raw_type_dates()
	{
		$this->assert_mapped_type(Column::DATETIME,'datetime');
		$this->assert_mapped_type(Column::DATE,'date');
	}

	public function test_map_raw_type_integers()
	{
		$this->assert_mapped_type(Column::INTEGER,'integer');
		$this->assert_mapped_type(Column::INTEGER,'int');
		$this->assert_mapped_type(Column::INTEGER,'tinyint');
		$this->assert_mapped_type(Column::INTEGER,'smallint');
		$this->assert_mapped_type(Column::INTEGER,'mediumint');
		$this->assert_mapped_type(Column::INTEGER,'bigint');
	}

	public function test_map_raw_type_decimals()
	{
		$this->assert_mapped_type(Column::DECIMAL,'float');
		$this->assert_mapped_type(Column::DECIMAL,'double');
		$this->assert_mapped_type(Column::DECIMAL,'numeric');
		$this->assert_mapped_type(Column::DECIMAL,'dec');
	}

	public function test_map_raw_type_strings()
	{
		$this->assert_mapped_type(Column::STRING,'string');
		$this->assert_mapped_type(Column::STRING,'varchar');
		$this->assert_mapped_type(Column::STRING,'text');
	}

	public function test_map_raw_type_default_to_string()
	{
		$this->assert_mapped_type(Column::STRING,'bajdslfjasklfjlksfd');
	}

	public function test_map_raw_type_changes_integer_to_int()
	{
		$this->column->raw_type = 'integer';
		$this->column->map_raw_type();
		$this->assert_equals('int',$this->column->raw_type);
	}

	public function test_cast()
	{
		$datetime = new DateTime('2001-01-01');
		$this->assert_cast(Column::INTEGER,1,'1');
		$this->assert_cast(Column::INTEGER,1,'1.5');
		$this->assert_cast(Column::DECIMAL,1.5,'1.5');
		$this->assert_cast(Column::DATETIME,$datetime,'2001-01-01');
		$this->assert_cast(Column::DATE,$datetime,'2001-01-01');
		$this->assert_cast(Column::DATE,$datetime,$datetime);
		$this->assert_cast(Column::STRING,'bubble tea','bubble tea');
		$this->assert_cast(Column::INTEGER,4294967295,'4294967295');
		$this->assert_cast(Column::INTEGER,'18446744073709551615','18446744073709551615');

		// 32 bit
		if (PHP_INT_SIZE === 4)
			$this->assert_cast(Column::INTEGER,'2147483648',(((float) PHP_INT_MAX) + 1));
		// 64 bit
		elseif (PHP_INT_SIZE === 8)
			$this->assert_cast(Column::INTEGER,'9223372036854775808',(((float) PHP_INT_MAX) + 1));

		$this->assert_cast(Column::INTEGER, 0, '');
		$this->assert_cast(Column::INTEGER, 0, '1e-1');
		$this->assert_cast(Column::INTEGER, 0, 'string');
		$this->assert_cast(Column::INTEGER, 3, '3rd street');
		$this->assert_cast(Column::INTEGER, 1_000_000, '1e6');
	}

	public function test_cast_leave_null_alone()
	{
		$types = array(
			Column::STRING,
			Column::INTEGER,
			Column::DECIMAL,
			Column::DATETIME,
			Column::DATE);

		foreach ($types as $type) {
			$this->assert_cast($type,null,null);
		}
	}

	public function test_empty_and_null_date_strings_should_return_null()
	{
		$column = new Column();
		$column->type = Column::DATE;
		$this->assert_equals(null,$column->cast(null,$this->conn));
		$this->assert_equals(null,$column->cast('',$this->conn));
	}

	public function test_empty_and_null_datetime_strings_should_return_null()
	{
		$column = new Column();
		$column->type = Column::DATETIME;
		$this->assert_equals(null,$column->cast(null,$this->conn));
		$this->assert_equals(null,$column->cast('',$this->conn));
	}

	public function test_native_date_time_attribute_copies_exact_tz()
	{
		$dt = new \DateTime('now', new \DateTimeZone('America/New_York'));

		$column = new Column();
		$column->type = Column::DATETIME;

		$dt2 = $column->cast($dt, $this->conn);

		$this->assert_equals($dt->getTimestamp(), $dt2->getTimestamp());
		$this->assert_equals($dt->getTimeZone(), $dt2->getTimeZone());
		$this->assert_equals($dt->getTimeZone()->getName(), $dt2->getTimeZone()->getName());
	}

	public function test_ar_date_time_attribute_copies_exact_tz()
	{
		$dt = new DateTime('now', new \DateTimeZone('America/New_York'));

		$column = new Column();
		$column->type = Column::DATETIME;

		$dt2 = $column->cast($dt, $this->conn);

		$this->assert_equals($dt->getTimestamp(), $dt2->getTimestamp());
		$this->assert_equals($dt->getTimeZone(), $dt2->getTimeZone());
		$this->assert_equals($dt->getTimeZone()->getName(), $dt2->getTimeZone()->getName());
	}
}
?>
