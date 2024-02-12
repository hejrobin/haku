<?php
declare(strict_types=1);

namespace Haku\Spec;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Closure;


final class Runner
{

	final const SleepDelayNS = 1000;

	protected array $specs = [];

	protected array $report = [];

	protected array $specReport = [];

	protected int $numSkipped = 0;
	protected int $numPassed = 0;
	protected int $numFailed = 0;

	protected Test $current;

	protected string $specDescription;
	protected string $testDescription;

	protected static $__instance;

	protected ?Closure $beforeAllCallback = null;
	protected ?Closure $afterAllCallback = null;

	public static function getInstance(): self {
		if (is_object(self::$__instance) === false)
		{
			self::$__instance = new self();
		}

		return self::$__instance;
	}

	private function __construct() {}

	final public function __wakeup() {}

	final public function __clone() {}

	public function beforeAll(
		Closure $beforeAllCallback
	): void
	{
		$this->beforeAllCallback = $beforeAllCallback;
	}

	public function afterAll(
		Closure $afterAllCallback
	): void
	{
		$this->afterAllCallback = $afterAllCallback;
	}

	/**
	 *	Passes before handler to current test.
	 */
	public function before(
		Closure $before
	): void
	{
		$this->current?->before($before);
	}

	/**
	 *	Passes after handler to current test.
	 */
	public function after(
		Closure $after
	): void
	{
		$this->current?->after($after);
	}

	/**
	 *	Passes before each handler to current test.
	 */
	public function beforeEach(
		Closure $beforeEach
	): void
	{
		$this->current?->beforeEach($beforeEach);
	}

	/**
	 *	Passes after each handler to current test.
	 */
	public function afterEach(
		Closure $afterEach
	): void
	{
		$this->current?->afterEach($afterEach);
	}

	public function numSpecs(): int
	{
		return count($this->specs);
	}

	public function numTests(): int
	{
		return count($this->specs, COUNT_RECURSIVE) - $this->numSpecs();
	}

	public function describeSpec(
		string $specDescription
	): void
	{
		$this->specDescription = $specDescription;

		$this->specs[$specDescription] = [];
		$this->specReport[$specDescription] = [];
	}

	public function registerTest(
		Test $test
	): void {
		$this->current = $test;
		$this->testDescription = $test->description;

		$this->specs[$this->specDescription][$this->testDescription] = $this->current;
	}

	public function registerTestCase(
		string $description,
		Closure $container,
	): void
	{
		if ($this->current) {
			$this->current->it($description, $container);
		}
	}

	public function runAll(): void
	{
		$timeStart = microtime(true);

		if (is_callable($this->beforeAllCallback))
		{
			call_user_func($this->beforeAllCallback);
		}

		if ($this->numTests() > 0)
		{
			foreach ($this->specs as $description => $tests)
			{
				foreach ($tests as $test)
				{
					$test->run();

					if ($test->skipped() === true)
					{
						$this->numSkipped += $test->numSkipped();
					}

					if ($test->passed() === true)
					{
						$this->numPassed += $test->numPassed();
					}

					if ($test->failed() === true)
					{
						$this->numFailed += $test->numFailed();
					}

					$this->specReport[$description][$test->description] = $test->report();

					usleep(self::SleepDelayNS);
				}
			}
		}

		$timeEnd = microtime(true);
		$timeElapsed = $timeEnd - $timeStart;

		$this->report = [
			'timeStart' => $timeStart,
			'timeEnd' => $timeEnd,
			'timeElapsed' => $timeElapsed,
			'numSkipped' => $this->numSkipped,
			'numPassed' => $this->numPassed,
			'numFailed' => $this->numFailed,
			'specs' => $this->specReport,
		];

		if (is_callable($this->afterAllCallback))
		{
			call_user_func($this->afterAllCallback);
		}
	}

	public function report(): \stdClass
	{
		return (object) $this->report;
	}

}
