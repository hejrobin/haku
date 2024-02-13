<?php
declare(strict_types=1);

namespace App\Routes;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Delegation\Route;

use Haku\Http\{
	Method,
	Message,
	Messages\Json
};

use function Haku\package;

class Root
{

	#[Route('/')]
	public function index(): Message
	{
		return Json::from(package());
	}

}
