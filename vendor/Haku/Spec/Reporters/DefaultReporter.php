<?php
declare(strict_types=1);

namespace Haku\Spec\Reporters;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Spec\Runner;

use Haku\Console\Ansi;
use Haku\Console\Output;

enum TestResult: string
{

	case Skip = 'skip';
	case Pass = 'pass';
	case Fail = 'fail';

	public function status(): string
	{
		return match ($this)
		{
			static::Skip => 'skip',
			static::Pass => 'pass',
			static::Fail => 'fail'
		};
	}

	public function statusColor(): Ansi
	{
		return match ($this)
		{
			static::Skip => Ansi::Blue,
			static::Pass => Ansi::Green,
			static::Fail => Ansi::Red
		};
	}

	public function icon(): string
	{
		return match ($this)
		{
			static::Skip => '■',
			static::Pass => '✔',
			static::Fail => '✘'
		};
	}

}

final class DefaultReporter extends Reporter
{

	public function __construct(
		protected readonly Runner $runner,
		protected readonly Output $output,
	) {}

	protected function header(): void
	{
		$this->output->info('loaded test config');

		$this->output->info(
			sprintf(
				'waiting for %s specs...',
				$this->output->format(
					$this->runner->numTests(),
					Ansi::Yellow
				)
			)
		);

		$this->output->break();
	}

	protected function footer(): void
	{
		$report = $this->runner->report();

		$this->output->info(
			sprintf(
				'result: %s passed, %s failed, %s skipped',
				$this->output->format($report->numPassed, Ansi::Green),
				$this->output->format($report->numFailed, Ansi::Red),
				$this->output->format($report->numSkipped, Ansi::Blue),
			)
		);

		$this->output->info(
			sprintf(
				'suite finished in %s seconds!',
				$this->output->format(
					round($report->timeElapsed, 6),
					Ansi::Yellow
				)
			)
		);
	}

	protected function heading(
		string $heading,
		int $numTests
	): void
	{
		$this->output->send(
			sprintf(
				'▶ %s (%s specs)',
				$heading,
				$this->output->format(
					$numTests,
					Ansi::Yellow
				),
			)
		);
	}

	protected function spec(
		string $title,
		\stdClass $test
	): void
	{
		$type = null;

		if ($test->skipped) {
			$type = TestResult::Skip;
		} elseif ($test->passed) {
			$type = TestResult::Pass;
		} elseif ($test->failed) {
			$type = TestResult::Fail;
		}

		$status = $this->output->format(
			$type->status(),
			$type->statusColor()
		);

		$this->output->send(
			$this->output->indent(),
			sprintf('[%s]: %s', $status, $title),
			sprintf(' (%s seconds)', $this->output->format(
				round($test->timeElapsed, 6),
				Ansi::Yellow
			)),
		);

		foreach ($test->cases as $case)
		{
			$actual = null;
			$expect = null;

			[$state, $description, $hint] = $case;

			if (count($case) === 5) {
				[$state, $description, $hint, $actual, $expect] = $case;
			}

			$result = TestResult::from(strtolower($state));

			$this->output->send(
				$this->output->indent(2),
				$this->output->format(
					$result->icon(),
					$result->statusColor()
				),
				' ',
				$description,
			);

			if ($hint) {
				$this->output->send(
					$this->output->indent(3),
					$this->output->format('↪ ', Ansi::Cyan),
					$hint
				);

				if ($actual && $expect) {
					$this->output->send(
						$this->output->indent(3),
						sprintf('%s: ', $this->output->format('expected', Ansi::Green)),
						$expect
					);

					$this->output->send(
						$this->output->indent(3),
						sprintf('%s: ', $this->output->format('recieved', Ansi::Red)),
						(string) $actual
					);
				}
			}
		}

		$this->output->break();
	}

	public function report(): void
	{
		$this->header();

		$report = $this->runner->report();

		foreach ($report->specs as $spec => $specs)
		{
			$this->heading($spec, count($specs));
			$this->output->break();

			foreach ($specs as $description => $spec)
			{
				$this->spec($description, (object) $spec);
			}

			$this->output->break();
		}

		$this->footer();
	}
}
