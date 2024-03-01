<?php
declare(strict_types=1);

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use function Haku\Spec\{
	spec,
	describe,
	it,
	expect,
};

use Haku\Database\Query\Find;
use Haku\Database\Query\Where;

spec('Database/Query/Find', function()
{

	describe('Find::all', function()
	{

		it('creates a valid query', function()
		{
			[$actual] = Find::all('tasks', ['id', 'title']);
			$expect = 'SELECT tasks.id, tasks.title FROM tasks LIMIT 0, 50';

			return expect($actual)->toEqual($expect);
		});

		it('creates a valid query with limit', function()
		{
			[$actual] = Find::all('tasks', ['id', 'title'], limit: 10);
			$expect = 'SELECT tasks.id, tasks.title FROM tasks LIMIT 0, 10';

			return expect($actual)->toEqual($expect);
		});

		it('creates a valid query with offset', function()
		{
			[$actual] = Find::all('tasks', ['id', 'title'], offset: 13, limit: 37);
			$expect = 'SELECT tasks.id, tasks.title FROM tasks LIMIT 13, 37';

			return expect($actual)->toEqual($expect);
		});

		it('creates a query with where clause', function()
		{
			[$actual, $parameters] = Find::all('tasks', ['completed'], [
				Where::is('completed', true)
			]);

			$expect = sprintf(
				'SELECT tasks.completed FROM tasks WHERE tasks.completed = :%s LIMIT 0, 50',
				...array_keys($parameters)
			);

			return expect($actual)->toEqual($expect);
		});

	});

	describe('Find::one', function()
	{

		it('creates a valid query', function()
		{
			[$actual] = Find::one('tasks', ['id', 'title']);
			$expect = 'SELECT tasks.id, tasks.title FROM tasks LIMIT 0, 1';

			return expect($actual)->toEqual($expect);
		});

		it('creates a query with where clause', function()
		{
			[$actual, $parameters] = Find::one('tasks', ['completed'], [
				Where::is('completed', true)
			]);

			$expect = sprintf(
				'SELECT tasks.completed FROM tasks WHERE tasks.completed = :%s LIMIT 0, 1',
				...array_keys($parameters)
			);

			return expect($actual)->toEqual($expect);
		});

	});

	describe('Find::count', function()
	{

		it('creates a valid query', function()
		{
			[$actual] = Find::count('tasks');
			$expect = 'SELECT COUNT(tasks.*) FROM tasks';

			return expect($actual)->toEqual($expect);
		});

		it('creates a valid query with were clause', function()
		{
			[$actual, $parameters] = Find::count('tasks', where: [
				Where::lessThan('subTasks', 10)
			]);

			$expect = sprintf(
				'SELECT COUNT(tasks.*) FROM tasks WHERE tasks.sub_tasks < :%s',
				...array_keys($parameters)
			);

			return expect($actual)->toEqual($expect);
		});

		it('creates a valid query with specific field', function()
		{
			[$actual, $parameters] = Find::count('tasks', 'author', [
				Where::is('author', '@admin')
			]);

			$expect = sprintf(
				'SELECT COUNT(tasks.author) FROM tasks WHERE tasks.author = :%s',
				...array_keys($parameters)
			);

			return expect($actual)->toEqual($expect);
		});

	});

});
