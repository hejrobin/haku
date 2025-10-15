<?php
declare(strict_types=1);

namespace Testing\Models;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Database\Model;
use Haku\Database\Attributes\{
	Entity,
	PrimaryKey,
	Schema,
	Validates,
	Timestamp,
	Relation
};
use Haku\Database\RelationType;

/**
 * Test model for SchemaParser relation tests
 */
#[Entity('test_posts')]
class TestPost extends Model
{

	#[PrimaryKey]
	protected readonly int $id;

	#[Validates('len:..255')]
	protected string $title;

	#[Schema('VARCHAR(100) UNIQUE')]
	protected string $slug;

	#[Validates('text')]
	protected string $content;

	// BelongsTo relation with custom foreignKey
	#[Relation(model: 'TestUser', type: RelationType::BelongsTo, foreignKey: 'author_id')]
	protected int $authorId;

	// Nullable BelongsTo relation
	#[Relation(model: 'TestCategory', type: RelationType::BelongsTo)]
	protected ?int $categoryId;

	#[Timestamp]
	protected readonly int $createdAt;

	#[Timestamp]
	protected readonly ?int $updatedAt;

}
