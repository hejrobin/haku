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

	public static function help(): array
	{
		$help = [];
		$list = self::list();

		foreach ($list as $item)
		{
			switch ($item)
			{
				case 'spec':
					$help[$item] = 'creates a spec test file | [--target]';
					break;
				case 'model':
				case 'middleware':
					$help[$item] = sprintf('creates a new %s in app/%s | [name]', $item, $item);
					break;
				case 'route':
					$help[$item] = 'creates a new route in app/routes | [--path] [--class]';
					break;
				case 'migration':
					$help[$item] = 'creates a new migration in app/migrations | [name] [--from]';
					break;
				default:
					$help[$item] = null;
			}
		}

		return $help;
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
