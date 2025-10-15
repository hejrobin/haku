<?php
declare(strict_types=1);

namespace Testing\Models;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Database\Model;
use Haku\Database\Attributes\{
	Entity,
	PrimaryKey,
	Validates,
	Timestamp
};

/**
 * Test model for SchemaParser tests
 */
#[Entity('test_users')]
class TestUser extends Model
{

	#[PrimaryKey]
	protected readonly int $id;

	#[Validates('len:..255')]
	protected string $name;

	#[Validates('unique | emailAddress')]
	protected string $email;

	#[Validates('len:..500')]
	protected ?string $bio;

	#[Timestamp]
	protected readonly int $createdAt;

	#[Timestamp]
	protected readonly ?int $updatedAt;

}
