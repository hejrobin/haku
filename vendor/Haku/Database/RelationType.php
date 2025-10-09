<?php
declare(strict_types=1);

namespace Haku\Database;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

enum RelationType: string
{
	case BelongsTo = 'belongsTo';
	case HasMany = 'hasMany';
	case HasOne = 'hasOne';
}
