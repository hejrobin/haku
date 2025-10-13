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

	protected array $specTags = [];

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

	protected array $filterTags = [];
	protected array $excludeTags = [];

	protected int $actualTestsToRun = 0;

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

	public function setFilterTags(array $tags): void
	{
		$this->filterTags = $tags;
	}

	public function setExcludeTags(array $tags): void
	{
		$this->excludeTags = $tags;
	}

	public function getFilterTags(): array
	{
		return $this->filterTags;
	}

	public function getExcludeTags(): array
	{
		return $this->excludeTags;
	}

	public function hasFilters(): bool
	{
		return !empty($this->filterTags) || !empty($this->excludeTags);
	}

	public function countActualTestsToRun(): int
	{
		$count = 0;

		foreach ($this->specs as $specDescription => $tests)
		{
			if (!$this->shouldRunSpec($specDescription))
			{
				continue;
			}

			foreach ($tests as $test)
			{
				$count += $test->numCases();
			}
		}

		return $count;
	}

	public function describeSpec(
		string $specDescription,
		array $tags = []
	): void
	{
		$this->specDescription = $specDescription;
		$this->specTags[$specDescription] = $tags;

		$this->specs[$specDescription] = [];
		$this->specReport[$specDescription] = [];
	}

	protected function shouldRunSpec(string $specDescription): bool
	{
		$tags = $this->specTags[$specDescription] ?? [];

		// If filter tags are set, spec must have at least one matching tag
		if (!empty($this->filterTags))
		{
			$hasMatchingTag = false;
			foreach ($this->filterTags as $filterTag)
			{
				if (in_array($filterTag, $tags, true))
				{
					$hasMatchingTag = true;
					break;
				}
			}

			if (!$hasMatchingTag)
			{
				return false;
			}
		}

		// If exclude tags are set, spec must not have any excluded tag
		if (!empty($this->excludeTags))
		{
			foreach ($this->excludeTags as $excludeTag)
			{
				if (in_array($excludeTag, $tags, true))
				{
					return false;
				}
			}
		}

		return true;
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
				// Check if this spec should run based on tags
				if (!$this->shouldRunSpec($description))
				{
					continue;
				}

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
