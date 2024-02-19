<?php
declare(strict_types=1);

namespace Haku\Database\Mixins;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;


trait Dateable
{

	protected function hasCreatedAt(): bool
	{
		return property_exists($this, 'createdAt');
	}

	public function setCreatedAt(int $createdAt)
	{
		if ($this->hasCreatedAt() && !isset($this->createdAt)) {
			$this->createdAt = $createdAt;
		}
	}

	protected function hasUpdatedAt(): bool
	{
		return property_exists($this, 'updatedAt');
	}

	public function setUpdatedAt(?int $updatedAt)
	{
		if ($this->hasUpdatedAt()) {
			$this->updatedAt = $updatedAt;
		}
	}

	protected function hasDeletedAt(): bool
	{
		return property_exists($this, 'deletedAt');
	}

	public function setDeletedAt(?int $deletedAt)
	{
		if ($this->hasDeletedAt()) {
			$this->deletedAt = $deletedAt;
		}
	}

}
