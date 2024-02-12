<?php
declare(strict_types=1);

namespace Haku\Spec;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Closure;

use Haku\Spec\Expectations\ExpectationResult;

class Test
{

	final const SleepDelayNS = 1000;

	protected array $cases = [];

	protected array $report = [];

	protected int $numSkipped = 0;
	protected int $numPassed = 0;
	protected int $numFailed = 0;

	public function __construct(
		public readonly string $description,
		protected ?Closure $beforeCallback = null,
		protected ?Closure $afterCallback = null,
		protected ?Closure $beforeEachCallback = null,
		protected ?Closure $afterEachCallback = null,
	) {}

	public function before(
		Closure $beforeCallback
	): void
	{
		$this->beforeCallback = $beforeCallback;
	}

	protected function invokeBefore(): void
	{
		if (is_callable($this->beforeCallback))
		{
			call_user_func($this->beforeCallback);
		}
	}

	public function after(
		Closure $afterCallback
	): void
	{
		$this->afterCallback = $afterCallback;
	}

	protected function invokeAfter(): void
	{
		if (is_callable($this->afterCallback))
		{
			call_user_func($this->afterCallback);
		}
	}

	public function beforeEach(
		Closure $beforeEachCallback
	): void
	{
		$this->beforeEachCallback = $beforeEachCallback;
	}

	protected function invokeBeforeEach(): void
	{
		if (is_callable($this->beforeEachCallback))
		{
			call_user_func($this->beforeEachCallback);
		}
	}

	public function afterEach(
		Closure $afterEachCallback
	): void
	{
		$this->afterEachCallback = $afterEachCallback;
	}

	protected function invokeAfterEach(): void
	{
		if (is_callable($this->afterEachCallback))
		{
			call_user_func($this->afterEachCallback);
		}
	}

	public function numCases(): int
	{
		return count($this->cases);
	}

	public function numSkipped(): int
	{
		return $this->numSkipped;
	}

	public function numPassed(): int
	{
		return $this->numPassed;
	}

	public function numFailed(): int
	{
		return $this->numFailed;
	}

	public function skip(): void
	{
		$this->numSkipped += 1;
	}

	public function pass(): void
	{
		$this->numPassed += 1;
	}

	public function fail(): void
	{
		$this->numFailed += 1;
	}

	/**
	 *	Returns true if t least one test is skipped.
	 */
	public function skipped(): bool
	{
		return $this->numSkipped === $this->numCases();
	}

	/**
	 *	Returns true if no tests in this spec failed.
	 */
	public function passed(): bool
	{
		return $this->numFailed === 0;
	}

	/**
	 *	Returns true if any test failed.
	 */
	public function failed(): bool
	{
		return $this->numFailed > 0;
	}

	/**
	 *	Registers a test case.
	 */
	public function it(
		string $caseDescription,
		Closure $caseCallback
	): void
	{
		$this->cases[$caseDescription] = $caseCallback;
	}

	/**
	 *	Runs through all cases associated with
	 */
	public function run(): void
	{
		$timeStart = microtime(true);

		$this->invokeBefore();

		$specReport = [];

		if ($this->numCases() > 0) {
			foreach ($this->cases as $caseDescription => $caseCallback)
			{
				$this->invokeBeforeEach();

				$callbackResult = call_user_func($caseCallback);

				if ($callbackResult instanceof ExpectationResult)
				{
					[$caseCallbackResult, $caseCallbackResultText] = $callbackResult->toArray();
				}
				else
				{
					$caseCallbackResult = null;
					$caseCallbackResultText = $callbackResult;
				}

				if ($caseCallbackResult === true)
				{
					$this->pass($caseDescription);
					$specReport[$caseDescription] = ['pass', $caseDescription, null];
				}
				elseif ($caseCallbackResult === false)
				{
					$this->fail($caseDescription);
					$specReport[$caseDescription] = ['fail', $caseDescription, $caseCallbackResultText];
				}
				else
				{
					$this->skip($caseDescription);
					$specReport[$caseDescription] = ['skip', $caseDescription, null];
				}

				$this->invokeAfterEach();

				usleep(self::SleepDelayNS);
			}
		}

		$timeEnd = microtime(true);
		$timeElapsed = $timeEnd - $timeStart;

		$this->report = [
			'timeStart' => $timeStart,
			'timeEnd' => $timeEnd,
			'timeElapsed' => $timeElapsed,
			'skipped' => $this->skipped(),
			'passed' => $this->passed(),
			'failed' => $this->failed(),
			'numCases' => $this->numCases(),
			'numSkipped' => $this->numSkipped,
			'numPassed' => $this->numPassed,
			'numFailed' => $this->numFailed,
			'cases' => $specReport,
		];

		$this->invokeAfter();
	}

	public function report(): array
	{
		return $this->report;
	}

}
