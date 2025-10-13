<?php
declare(strict_types=1);

namespace Haku\Database\Migration;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Database\Connection;

interface Migration
{

	public function up(Connection $db): void;

	public function down(Connection $db): void;

}
