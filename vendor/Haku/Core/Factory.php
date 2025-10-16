<?php
declare(strict_types=1);

namespace Haku\Core;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use ReflectionClass;

use Haku\Exceptions\FrameworkException;

trait Factory
{

	protected array $__instances = [];

	public function has(string $instanceName): bool
	{
		return array_key_exists($instanceName, $this->__instances);
	}

	/**
	 *	Attempts to set/store instance.
	 *
	 *	@throws \Haku\Exceptions\FrameworkException
	 */
	protected function set(string $instanceName, object $instance): void
	{
		if ($this->has($instanceName))
		{
			throw new FrameworkException(
				sprintf('Class %s already initialized.', get_class($instance))
			);
		}

		$this->__instances[$instanceName] = $instance;
	}

	/**
	 *	Returns a regestered interface, if it exists.
	 *
	 *	@param string $instanceName
	 *
	 *	@return mixed
	 */
	public function get(string $instanceName): ?object
	{
		if ($this->has($instanceName))
		{
			return $this->__instances[$instanceName];
		}

		return null;
	}

	/**
	 *	Creates a new instance and returns the newly instanicated class.
	 *	This is only recommended when class instances needs to be global.
	 *
	 *	@throws \Haku\Exceptions\FrameworkException
	 */
	public function initialize(
		string $className,
		?string $instanceName = null,
		?string $classMethodName = null,
		array $classMethodArguments = [],
	): object
	{
		if ($instanceName === null)
		{
			$namespaceSegments = explode('\\', $className);
			$instanceName = mb_lcfirst(array_pop($namespaceSegments));
		}

		if ($this->get($instanceName) !== null)
		{
			throw new FrameworkException(
				sprintf('Class %s already initialized.', $instanceName)
			);
		}

		$classInstance = call_user_func_array(
			[new ReflectionClass($className), $classMethodName ?? 'newInstance'],
			$classMethodArguments,
		);

		$this->set($instanceName, $classInstance);

		return $classInstance;
	}

}
