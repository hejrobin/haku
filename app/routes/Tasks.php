<?php
declare(strict_types=1);

namespace App\Routes;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Delegation\Route;

use Haku\Http\{
	Method,
	Status,
	Message,
	Messages\Json
};

// @NOTE The attribute below is optional
#[Route('/tasks')]
class Tasks
{

	#[Route('/')]
	public function index(): Message
	{
		return Json::from([
			'message' => "Hello from Tasks!",
		], Status::ConfoundedByPonies);
	}

}
