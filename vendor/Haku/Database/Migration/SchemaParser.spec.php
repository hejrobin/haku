<?php
declare(strict_types=1);

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use function Haku\Spec\{
	spec,
	describe,
	it,
	expect,
	expectAll,
	beforeEach,
};

use Haku\Database\Migration\SchemaParser;

spec('Database/Migration/SchemaParser', function()
{

	describe('Basic schema parsing', function()
	{

		it('can parse a model with basic properties', function()
		{
			$parser = new SchemaParser();
			$parser->parse('TestUser');

			$sql = $parser->toCreateSQL();

			return expectAll(
				expect($sql)->toContain('CREATE TABLE IF NOT EXISTS `test_users`'),
				expect($sql)->toContain('`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY'),
				expect($sql)->toContain('`name` VARCHAR(255) NOT NULL'),
				expect($sql)->toContain('`email` VARCHAR(255) UNIQUE NOT NULL')
			);
		});

		it('can parse a model with nullable properties', function()
		{
			$parser = new SchemaParser();
			$parser->parse('TestUser');

			$sql = $parser->toCreateSQL();

			return expect($sql)->toContain('`bio` VARCHAR(500) NULL');
		});

		it('can parse timestamp attributes', function()
		{
			$parser = new SchemaParser();
			$parser->parse('TestUser');

			$sql = $parser->toCreateSQL();

			return expectAll(
				expect($sql)->toContain('`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL'),
				expect($sql)->toContain('`updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP')
			);
		});

		it('can generate drop SQL', function()
		{
			$parser = new SchemaParser();
			$parser->parse('TestUser');

			$sql = $parser->toDropSQL();

			return expect($sql)->toEqual('DROP TABLE IF EXISTS `test_users`;');
		});

	});

	describe('Foreign key parsing', function()
	{

		it('can parse BelongsTo relations and create foreign keys', function()
		{
			$parser = new SchemaParser();
			$parser->parse('TestPost');

			$sql = $parser->toCreateSQL();

			return expectAll(
				expect($sql)->toContain('`author_id` BIGINT UNSIGNED NOT NULL'),
				expect($sql)->toContain('CONSTRAINT `fk_test_posts_author_id`'),
				expect($sql)->toContain('FOREIGN KEY (`author_id`)'),
				expect($sql)->toContain('REFERENCES `test_users`(`id`)'),
				expect($sql)->toContain('ON DELETE CASCADE ON UPDATE CASCADE')
			);
		});

		it('can parse nullable BelongsTo relations', function()
		{
			$parser = new SchemaParser();
			$parser->parse('TestPost');

			$sql = $parser->toCreateSQL();

			return expect($sql)->toContain('`category_id` BIGINT UNSIGNED NULL');
		});

		it('uses custom foreignKey when specified', function()
		{
			$parser = new SchemaParser();
			$parser->parse('TestPost');

			$sql = $parser->toCreateSQL();

			return expectAll(
				expect($sql)->toContain('`author_id` BIGINT UNSIGNED NOT NULL'),
				expect($sql)->toContain('CONSTRAINT `fk_test_posts_author_id`')
			);
		});

		it('creates multiple foreign keys for multiple relations', function()
		{
			$parser = new SchemaParser();
			$parser->parse('TestPost');

			$sql = $parser->toCreateSQL();

			return expectAll(
				expect($sql)->toContain('CONSTRAINT `fk_test_posts_author_id`'),
				expect($sql)->toContain('CONSTRAINT `fk_test_posts_category_id`')
			);
		});

	});

	describe('Schema attribute parsing', function()
	{

		it('respects custom Schema attribute', function()
		{
			$parser = new SchemaParser();
			$parser->parse('TestPost');

			$sql = $parser->toCreateSQL();

			return expect($sql)->toContain('`slug` VARCHAR(100) UNIQUE NOT NULL');
		});

	});

	describe('Validates attribute parsing', function()
	{

		it('parses length validation rules', function()
		{
			$parser = new SchemaParser();
			$parser->parse('TestUser');

			$sql = $parser->toCreateSQL();

			return expectAll(
				expect($sql)->toContain('`name` VARCHAR(255) NOT NULL'),
				expect($sql)->toContain('`email` VARCHAR(255) UNIQUE NOT NULL')
			);
		});

	});

});
