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
	Write,
	Where
};

spec('Haku/Database/Query/Write', function()
{

	describe('row alteration queries', function()
	{

		it('can generate a proper INSERT query', function()
		{
			[$actual] = Write::insert(
				tableName: 'tasks',
				values: [ 'title' => 'Write specs' ]
			);

			$expect = "INSERT INTO tasks SET tasks.title = :title";

			return expect($actual)->toEqual($expect);
		});

		it('can generate a proper UPDATE query', function()
		{
			[$actual] = Write::update(
				tableName: 'tasks',
				values: [ 'title' => 'Write db specs' ],
				where: [
					Where::is('id', 1)
				]
			);

			$expect = "UPDATE tasks SET tasks.title = :title WHERE tasks.id = :where_tasks_id_0";

			return expect($actual)->toEqual($expect);
		});

	});

	describe('row deletion queries', function()
	{

		it('can generate a proper DELETE query', function()
		{
			[$actual] = Write::delete(
				tableName: 'tasks',
				where: [
					Where::is('id', 1)
				]
			);

			$expect = "DELETE FROM tasks WHERE tasks.id = :where_tasks_id_0";

			return expect($actual)->toEqual($expect);
		});

		it('can generate a "soft delete" query', function()
		{
			[$actual] = Write::softDelete(
				tableName: 'tasks',
				where: [
					Where::is('id', 1)
				]
			);

			$expect = "UPDATE tasks SET tasks.deleted_at = :deletedAt WHERE tasks.id = :where_tasks_id_0";

			return expect($actual)->toEqual($expect);
		});

		it('can create a valid transform query', function()
		{
			[$actual] = Write::update(
				tableName: 'tasks',
				values: [ 'location' => 'POINT(44.80243702247711 -68.7850096478809)' ],
				where: [
					Where::is('id', 1)
				],
				transform: [
					'location' => 'ST_GeomFromText(:location)'
				]
			);

			$expect = "UPDATE tasks SET tasks.location = ST_GeomFromText(:location) WHERE tasks.id = :where_tasks_id_0";

			return expect($actual)->toEqual($expect);
		});

	});

});
