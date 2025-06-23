<?php
declare(strict_types=1);

namespace Haku\Console\Commands\Generators;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use ReflectionClass;

use Haku\Console\Output;

/**
 *	Enum for available code generators.
 *	These always have a corresponding generator class.
 *
 *	@enum string AvailableGenerators
 */
enum AvailableGenerators: string
{

	case Spec = 'spec';
	case Route = 'route';
	case Model = 'model';
	case Migration = 'migration';
	case Middleware = 'middleware';

	public static function list(): array
	{
		return array_map('mb_strtolower', array_column(static::cases(), 'name'));
	}

}

/**
 *	Returns a new instance of a generator class.
 *
 *	@param string $generatorClassName
 *	@param object $commandArguments
 *	@param \Haku\Console\Output $output
 *
 *	@return \Haku\Console\Commands\Generators\Generator | null
 */
function getGeneratorInstance(
	string $generatorClassName,
	object $commandArguments,
	Output $output,
): Generator | null
{
	return call_user_func_array(
		[new ReflectionClass($generatorClassName), 'newInstance'],
		[$commandArguments, $output]
	);
}
