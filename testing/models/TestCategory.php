<?php
declare(strict_types=1);

namespace Testing\Models;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Database\Model;
use Haku\Database\Attributes\{
	Entity,
	PrimaryKey,
	Validates
};

/**
 * Test model for SchemaParser relation tests
 */
#[Entity('test_categories')]
class TestCategory extends Model
{

	#[PrimaryKey]
	protected readonly int $id;

	#[Validates('len:..100')]
	protected string $name;

}
