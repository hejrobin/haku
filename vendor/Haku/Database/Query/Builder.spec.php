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

use Haku\Database\Query\{
	Builder
};

spec('Haku/Database/Query/Builder', function()
{

	describe('simple select queries', function()
	{
		it('can create a select', function()
		{
			$query = (new Builder())
				->select('task.id', 'task.content')
				->from('tasks')
				->toSql();

			$expected = 'SELECT task.id, task.content FROM tasks';

			return expect($query)->toBe($expected);
		});

		it('can create a select with where clause', function()
		{
			$query = (new Builder())
				->select('task.id', 'task.content')
				->from('tasks')
				->where('task.id', 1)
				->toSql();

			$expected = 'SELECT task.id, task.content FROM tasks WHERE task.id = :task_id_1';

			return expect($query)->toBe($expected);
		});

		it('can create a select with limit and offset', function()
		{
			$query = (new Builder())
				->select('task.id', 'task.content')
				->from('tasks')
				->limit(10)
				->offset(10)
				->toSql();

			$expected = 'SELECT task.id, task.content FROM tasks LIMIT 10, 10';

			return expect($query)->toBe($expected);
		});
	});

	describe('search queries', function()
	{
		it('can create a LIKE query', function()
		{
			$query = (new Builder())
				->select('task.id', 'task.title', 'task.content')
				->from('tasks')
				->where('task.title', "'build%'", 'LIKE')
				->toSql();

			$expected = 'SELECT task.id, task.title, task.content FROM tasks WHERE task.title LIKE :task_title_1';

			return expect($query)->toBe($expected);
		});

		it('can create a fulltext query', function()
		{
			$query = (new Builder())
				->select('task.id', 'task.title', 'task.content')
				->from('tasks')
				->whereRaw("MATCH(task.content) AGAINST(? IN BOOLEAN MODE)", '+refactor +db')
				->toSql();

			$expected = 'SELECT task.id, task.title, task.content FROM tasks WHERE MATCH(task.content) AGAINST(:param_1 IN BOOLEAN MODE)';

			return expect($query)->toBe($expected);
		});
	});

});
