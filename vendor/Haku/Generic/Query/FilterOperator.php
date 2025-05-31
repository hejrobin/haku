<?php
declare(strict_types=1);

namespace Haku\Generic\Query;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

enum FilterOperator: string
{

	case Is = 'is';
	case IsNot = 'isNot';

	case GreaterThan = 'greaterThan';
	case NotGreaterThan = 'notGreaterThan';
	case GreaterThanOrEqualTo = 'greaterThanOrEqualTo';

	case LessThan = 'lessThan';
	case NotLessThan = 'notLessThan';
	case LessThanOrEqualTo = 'lessThanOrEqualTo';

	case Like = 'like';
	case NotLike = 'notLike';

	case Null = 'null';
	case NotNull = 'notNull';

	case Contains = 'contains';
	case Custom = 'custom';

}
