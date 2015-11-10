<?php namespace Cogeco\Build\Task;

use Cogeco\Build\Task;

/**
 * The TimerTask class is a chronometer for measuring script execution time
 */
class TimerTaskTest extends \PHPUnit_Framework_TestCase
{
	public function testPushAndPop()
	{
		$time = TimerTask::getStartTime();
		//$stack = array();
		$this->assertEquals(0, $time);

//		array_push($stack, 'foo');
//		$this->assertEquals('foo', $stack[count($stack)-1]);
//		$this->assertEquals(1, count($stack));
//
//		$this->assertEquals('foo', array_pop($stack));
//		$this->assertEquals(0, count($stack));
	}

}