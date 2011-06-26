<?php

class TestResult
{
	protected $name;
	protected $type;
	protected $expected;
	protected $result;
	protected $expected_setted = false;
	protected $types_compare = false;
	protected $description;

	const type_success = 'success';
	const type_fail = 'fail';

	public function __construct($name)
	{
		$this->name = $name;
		$this->Success();
		return $this;
	}

	public function Expected($expected)
	{
		$this->expected = $expected;
		$this->expected_setted = true;
		return $this;
	}

	public function Result($result)
	{
		$this->result = $result;
		if ($this->expected_setted)
		{
			if ($this->types_compare)
			{
				if ($result===$this->expected) $this->Success();
				else $this->Fail();
			}
			else
			{
				if ($result==$this->expected) $this->Success();
				else $this->Fail();
			}
		}
		return $this;
	}

	public function getExpected()
	{ return $this->expected; }

	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}

	public function getName()
	{ return $this->name; }

	public function getResult()
	{ return $this->result; }

	public function Success()
	{
		$this->type = self::type_success;
		return $this;
	}

	public function Fail()
	{
		$this->type = self::type_fail;
		return $this;
	}

	public function getType()
	{ return $this->type; }

	public function setTypesCompare($types_compare = true)
	{
		if (is_bool($types_compare)) $this->types_compare = $types_compare;
		return $this;
	}

	public function getTypesCompare()
	{ return $this->types_compare; }

	public function addDescription($description)
	{
		if (empty($description)) return false;
		if (!empty($this->description)) $this->description .= PHP_EOL.$description;
		else $this->description = $description;
		return $this;
	}

	public function getDescription()
	{ return $this->description; }

}

class MemoryObject_Test
{
	protected $results = array();

	public function __construct(\Jamm\Memory\IMemoryStorage $mem)
	{
		Memory::ini($mem);
	}

	/**
	 * @return array
	 */
	public function RunTests()
	{
		$this->test_add();
		$this->test_save();
		$this->test_read();
		$this->test_del();
		$this->test_del_by_tags();
		$this->test_select();
		$this->test_select_fx();
		$this->test_lock_key();
		$this->test_unlock_key();
		$this->test_increment();
		$this->test_del_old();
		$this->test_get_keys();
		$this->test_get_stat();

		return $this->results;
	}

	public function test_add()
	{
		$this->results[] = $result = new TestResult(__METHOD__.' call1 (add)');
		Memory::del('t1');
		$call1 = Memory::add('t1', 1);
		$result->Expected(true)->Result($call1)->addDescription(Memory::getLastErr());

		$this->results[] = $result = new TestResult(__METHOD__.' call2 (replace)');
		$call2 = Memory::add('t1', 2);
		$result->Expected(false)->Result($call2)->addDescription(Memory::getLastErr());

		$this->results[] = $result = new TestResult(__METHOD__.' call3 (ttl)');
		Memory::del('t3');
		$call3 = Memory::add('t3', 3, 10);
		$result->Expected(true)->Result($call3)->addDescription(Memory::getLastErr());

		$this->results[] = $result = new TestResult(__METHOD__.' call4 (tags string)');
		Memory::del('t4');
		$call = Memory::add('t4', 1, 10, 'tag');
		$result->Expected(true)->Result($call)->addDescription(Memory::getLastErr());

		$this->results[] = $result = new TestResult(__METHOD__.' call4 (tags array)');
		Memory::del('t5');
		$call = Memory::add('t5', 1, 10, array('tag1', 'tag2'));
		$result->Expected(true)->Result($call)->addDescription(Memory::getLastErr());
	}

	public function test_del()
	{
		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		Memory::add(__METHOD__.'d1', 1);
		$call = Memory::del(__METHOD__.'d1');
		$result->Expected(true)->Result($call)->addDescription(Memory::getLastErr());
		$check = Memory::read(__METHOD__.'d1');
		if (!empty($check)) $result->Fail()->addDescription('variables still in cache');

		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		Memory::add(__METHOD__.'d1', 1);
		Memory::add(__METHOD__.'d2', 1);
		$call = Memory::del(array(__METHOD__.'d1', __METHOD__.'d2'));
		$result->Expected(true)->Result($call)->addDescription(Memory::getLastErr());
		$check = Memory::read(array(__METHOD__.'d1', __METHOD__.'d2'));
		if (!empty($check)) $result->Fail()->addDescription('variables still in cache')->addDescription(print_r($check, 1));
	}

	public function test_del_by_tags()
	{
		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		Memory::add(__METHOD__.'d1', 1, 10, 'tag');
		$call = Memory::del_by_tags('tag');
		$result->Expected(true)->Result($call)->addDescription(Memory::getLastErr());
		$check = Memory::read(__METHOD__.'d1');
		if (!empty($check)) $result->Fail()->addDescription('variables still in cache');

		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		Memory::add(__METHOD__.'d1', 1, 10, 'tag1');
		Memory::add(__METHOD__.'d2', 1, 10, 'tag2');
		$call = Memory::del_by_tags(array('tag1', 'tag2'));
		$result->Expected(true)->Result($call)->addDescription(Memory::getLastErr());
		$check = Memory::read(array(__METHOD__.'d1', __METHOD__.'d2'));
		if (!empty($check)) $result->Fail()->addDescription('variables still in cache');

	}

	public function test_del_old()
	{
		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		Memory::save(__METHOD__, 11, 1);
		sleep(3);
		$call = Memory::del_old();
		$result->Expected(true)->Result($call)->addDescription(Memory::getLastErr());
		$check = Memory::read(__METHOD__);
		if (!empty($check)) $result->Fail()->addDescription('variable still exists');
	}

	public function test_increment()
	{

		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		Memory::save(__METHOD__, 100);
		$call = Memory::increment(__METHOD__, 10);
		$result->addDescription(Memory::getLastErr());
		$check = Memory::read(__METHOD__);
		$result->Expected(array(110, 110))->Result(array($call, $check));

		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		$call = Memory::increment(__METHOD__, -10);
		$result->addDescription(Memory::getLastErr());
		$check = Memory::read(__METHOD__);
		$result->Expected(array(100, 100))->Result(array($call, $check));

		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		Memory::save(__METHOD__, 'string');
		$call = Memory::increment(__METHOD__, 10);
		$result->addDescription(Memory::getLastErr());
		$check = Memory::read(__METHOD__);
		$result->Expected(array('string10', 'string10'))->Result(array($call, $check));

		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		Memory::save(__METHOD__, array(1, 2));
		$call = Memory::increment(__METHOD__, 3);
		$result->addDescription(Memory::getLastErr());
		$check = Memory::read(__METHOD__);
		$result->Expected(array(array(1, 2, 3), array(1, 2, 3)))->Result(array($call, $check));

		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		Memory::increment(__METHOD__.'inc', array('a'));
		Memory::increment(__METHOD__.'inc', array('b'));
		$result->addDescription(Memory::getLastErr());
		$check = Memory::read(__METHOD__.'inc');
		$result->Expected(array('a', 'b'))->Result($check);

		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		Memory::increment(__METHOD__.'inc', array('k1' => 'a'));
		Memory::increment(__METHOD__.'inc', array('k2' => 'b'));
		Memory::increment(__METHOD__.'inc', array('k2' => 'c'));
		$result->addDescription(Memory::getLastErr());
		$check = Memory::read(__METHOD__.'inc');
		$result->Expected(array('a', 'b', 'k1' => 'a', 'k2' => 'c'))->Result($check);

		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		Memory::save(__METHOD__, array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11));
		$call = Memory::increment(__METHOD__, 100, 10);
		$result->addDescription(Memory::getLastErr());
		$check = Memory::read(__METHOD__);
		$result->Expected(array(array(3, 4, 5, 6, 7, 8, 9, 10, 11, 100), array(3, 4, 5, 6, 7, 8, 9, 10, 11, 100)))->Result(array($call, $check));

	}

	public function test_lock_key()
	{
		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);

		Memory::save(__METHOD__, 1);
		$call = Memory::lock_key(__METHOD__, $l);
		$result->Expected(true)->Result($call)->addDescription(Memory::getLastErr());
		if ($call)
		{
			$check = Memory::lock_key(__METHOD__, $l1);
			$result->addDescription(Memory::getLastErr());
			if ($check) $result->Fail()->addDescription('key was not locked');
			Memory::unlock_key($l);
			$result->addDescription(Memory::getLastErr());
		}

	}

	public function test_read()
	{
		//Read 10
		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		Memory::save(__METHOD__.'t1', 10);
		$call = Memory::read(__METHOD__.'t1');
		$result->Expected(10)->Result($call)->addDescription(Memory::getLastErr());

		//Read float key
		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		$key = microtime(true)*100;
		Memory::save($key, 10);
		$call = Memory::read($key);
		$result->Expected(10)->Result($call)->addDescription(Memory::getLastErr());

		//Read negative float
		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		$key = -10.987;
		Memory::save($key, 10);
		$call = Memory::read($key);
		$result->Expected(10)->Result($call)->addDescription(Memory::getLastErr());

		//Read string
		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		Memory::save(__METHOD__.'t1', 'string', 10);
		$call = Memory::read(__METHOD__.'t1');
		$result->setTypesCompare()->Expected('string')->Result($call)->addDescription(Memory::getLastErr());

		//Read and check ttl
		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		$call = Memory::read(__METHOD__.'t1', $ttl_left);
		$result->Expected(array('string', 'TTL: 10'))->Result(array($call, 'TTL: '.$ttl_left))->addDescription(Memory::getLastErr());

		//Read array and check ttl
		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		Memory::save(__METHOD__.'t11', array(10, 'string'), 100);
		$call = Memory::read(__METHOD__.'t11', $ttl_left);
		$result->Expected(array(array(10, 'string'), 'TTL: 100'))->Result(array($call, 'TTL: '.$ttl_left))->addDescription(Memory::getLastErr());

	}

	public function test_save()
	{
		//Save 100
		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		$call = Memory::save(__METHOD__.'s1', 100);
		$result->Expected(true)->Result($call)->addDescription(Memory::getLastErr());
		$check = Memory::read(__METHOD__.'s1');
		if ($check!=100) $result->Fail()->addDescription('value mismatch, should be 100, result: '.$check);

		//Save 100 with ttl 10
		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		$call = Memory::save(__METHOD__.'s2', 100, 10);
		$result->Expected(true)->Result($call)->addDescription(Memory::getLastErr());
		$check = Memory::read(__METHOD__.'s2', $ttl_left);
		if ($check!=100) $result->Fail()->addDescription('value mismatch: '.$check);
		if ($ttl_left!=10) $result->Fail()->addDescription('ttl mismatch: '.$ttl_left);

		//Save float key
		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		$key = microtime(true)*100;
		$call = Memory::save($key, 100, 10);
		$result->Expected(true)->Result($call)->addDescription(Memory::getLastErr());
		$check = Memory::read($key, $ttl_left);
		if ($check!=100) $result->Fail()->addDescription('value mismatch: '.$check);
		if ($ttl_left!=10) $result->Fail()->addDescription('ttl mismatch: '.$ttl_left);

		//Save negative float
		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		$key = -10.12;
		$call = Memory::save($key, 100, 10);
		$result->Expected(true)->Result($call)->addDescription(Memory::getLastErr());
		$check = Memory::read($key, $ttl_left);
		if ($check!=100) $result->Fail()->addDescription('value mismatch: '.$check);
		if ($ttl_left!=10) $result->Fail()->addDescription('ttl mismatch: '.$ttl_left);

		//Save with float ttl
		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		$call = Memory::save(__METHOD__.'s21', 100, 0.000001);
		$result->Expected(true)->Result($call)->addDescription(Memory::getLastErr());
		$check = Memory::read(__METHOD__.'s21', $ttl_left);
		if ($check!=100) $result->Fail()->addDescription('value mismatch: '.$check);
		if ($ttl_left < 10) $result->Fail()->addDescription('ttl mismatch: '.$ttl_left);

		//Save with string ttl
		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		$call = Memory::save(__METHOD__.'s22', 100, 'stringttl');
		$result->Expected(true)->Result($call)->addDescription(Memory::getLastErr());
		$check = Memory::read(__METHOD__.'s22', $ttl_left);
		if ($check!=100) $result->Fail()->addDescription('value mismatch: '.$check);

		//Save with tag
		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		$call = Memory::save(__METHOD__.'s3', 100, 10, 'tag');
		$result->Expected(true)->Result($call)->addDescription(Memory::getLastErr());
		$check = Memory::read(__METHOD__.'s3', $ttl_left);
		if ($check!=100) $result->Fail()->addDescription('value mismatch');
		if ($ttl_left!=10) $result->Fail()->addDescription('ttl mismatch: '.$ttl_left.' instead of 10');

		//Save with array of tags
		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		$call = Memory::save(__METHOD__.'s4', array('z' => 1), 10, array('tag', 'tag1'));
		$result->Expected(true)->Result($call)->addDescription(Memory::getLastErr());
		$check = Memory::read(__METHOD__.'s4', $ttl_left);
		if ($check!==array('z' => 1)) $result->Fail()->addDescription('value mismatch');
		if ($ttl_left!=10) $result->Fail()->addDescription('ttl mismatch: '.$ttl_left.' instead of 10');
	}

	public function test_select()
	{
		Memory::del(Memory::get_keys());
		Memory::save('key1', array('kk1' => 5, 'kk2' => 7));
		Memory::save('key2', array('kk1' => 4, 'kk2' => 6));
		Memory::save('key3', array('kk1' => 5, 'kk2' => 5));
		Memory::save('key4', array('kk1' => 2, 'kk2' => 4));

		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		$call = Memory::select(array(array('k' => 'kk1', 'r' => '=', 'v' => 5)));
		$result->Expected(array('kk1' => 5, 'kk2' => 7))->Result($call)->addDescription(Memory::getLastErr());

		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		$call = Memory::select(array(array('k' => 'kk1', 'r' => '>', 'v' => 2),
									array('k' => 'kk2', 'r' => '<', 'v' => 6)));
		$result->Expected(array('kk1' => 5, 'kk2' => 5))->Result($call)->addDescription(Memory::getLastErr());

		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		$call = Memory::select(array(array('k' => 'kk1', 'r' => '=', 'v' => 5)), true);
		$result->Expected(array('key1' => array('kk1' => 5, 'kk2' => 7), 'key3' => array('kk1' => 5, 'kk2' => 5)))->Result($call)->addDescription(Memory::getLastErr());
	}

	public function test_select_fx()
	{
		Memory::del(Memory::get_keys());
		Memory::save('key1', array('kk1' => 5, 'kk2' => 7));
		Memory::save('key2', array('kk1' => 4, 'kk2' => 6));
		Memory::save('key3', array('kk1' => 5, 'kk2' => 5));
		Memory::save('key4', array('kk1' => 2, 'kk2' => 4));
		Memory::save('key5', array('id' => 0, 'kk1' => 6, 'kk2' => 5));
		Memory::save('key6', array('id' => 1, 'kk1' => 9, 'kk2' => 5));
		Memory::save('key7', array('id' => 0, 'kk1' => 7, 'kk2' => 4));

		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		$call = Memory::select_fx(create_function('$s,$index', "if (\$index=='key1' || \$s['kk2']==7) return true; else return false;"));
		$result->Expected(array('kk1' => 5, 'kk2' => 7))->Result($call)->addDescription(Memory::getLastErr());

		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		$call = Memory::select_fx(create_function('$s,$index', "if (\$s['kk1']==\$s['kk2']) return true; else return false;"));
		$result->Expected(array('kk1' => 5, 'kk2' => 5))->Result($call)->addDescription(Memory::getLastErr());

		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		$call = Memory::select_fx(create_function('$s,$index', "if (\$s['kk1']==\$s['kk2'] || \$index=='key4') return true; else return false;"), true);
		$result->Expected(array('key3' => array('kk1' => 5, 'kk2' => 5), 'key4' => array('kk1' => 2, 'kk2' => 4)))->Result($call)->addDescription(Memory::getLastErr());

		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		$call = Memory::select_fx(create_function('$s,$index', "if (\$s['kk1']>7 || (\$s['id']==0 && \$s['kk2']<5)) return true; else return false;"), true);
		$result->Expected(array('key4' => array('kk1' => 2, 'kk2' => 4), 'key6' => array('id' => 1, 'kk1' => 9, 'kk2' => 5), 'key7' => array('id' => 0, 'kk1' => 7, 'kk2' => 4)))->Result($call)->addDescription(Memory::getLastErr());
	}

	public function test_unlock_key()
	{
		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);

		Memory::save(__METHOD__, 1);
		if (Memory::lock_key(__METHOD__, $l))
		{
			$call = Memory::unlock_key($l);
			$result->Expected(true)->Result($call)->addDescription(Memory::getLastErr());
			$check = Memory::lock_key(__METHOD__, $l1);
			if (!$check) $result->Fail()->addDescription('can not lock key again')->addDescription(Memory::getLastErr());
			else Memory::unlock_key($l1);
		}
		else $result->Fail()->addDescription('key was not acquired')->addDescription(Memory::getLastErr());
	}

	public function test_get_keys()
	{
		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);

		Memory::save(__METHOD__.':1', 1);
		Memory::save(__METHOD__.':2', 1);
		Memory::save(__METHOD__.':3', 1);
		$arr = array(__METHOD__.':1', __METHOD__.':2', __METHOD__.':3');
		$call = Memory::get_keys();
		if (is_array($call)) $c = count($call);
		else $c = 0;
		$result->setTypesCompare()->Expected(true, $arr)->Result(is_array($call), $call)->addDescription(Memory::getLastErr());
		Memory::del($call);
		$check = Memory::get_keys();
		$result->addDescription(Memory::getLastErr());
		if (!empty($check)) $result->Fail()->addDescription('not all keys was deleted')->addDescription('Left: '.count($check).' from '.$c);
	}

	public function test_get_stat()
	{
		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);

		$call = Memory::get_stat();
		$result->setTypesCompare()->Expected(true)->Result(is_array($call))->addDescription(Memory::getLastErr());
	}
}

class MemStorageTesting
{
	public static function PrintResults($results, $newline = PHP_EOL)
	{
		/** @var TestResult $result */
		foreach ($results as $result)
		{
			print $newline.$result->getName();
			print $newline.$result->getType();
			if ($result->getDescription()!='') print $newline.$result->getDescription();
			print $newline.'Expected: ';
			var_dump($result->getExpected());
			print 'Result: ';
			var_dump($result->getResult());
			print $newline.$newline.$newline;
		}
	}

	public static function MakeTest(\Jamm\Memory\IMemoryStorage $testing_object)
	{
		$start_time = microtime(true);
		$t = new MemoryObject_Test($testing_object);

		self::PrintResults($t->RunTests());

		print PHP_EOL.round(microtime(true)-$start_time, 6).PHP_EOL;
		print_r($testing_object->getErrLog());
	}
}
