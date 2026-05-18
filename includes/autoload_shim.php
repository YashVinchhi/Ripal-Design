<?php
// Temporary shim: maps old manual requires to Composer autoloaded classes.
// Remove each line below once all callers are updated to use namespaced imports.
use App\Core\Permissions\PermissionService;
use App\Core\Database\QueryLogger;
use App\Core\Http\ApiValidator;
use App\Shared\View;

if (!class_exists('PermissionService') && class_exists(\App\Core\Permissions\PermissionService::class)) {
	class_alias(\App\Core\Permissions\PermissionService::class, 'PermissionService');
}

if (!class_exists('QueryLogger') && class_exists(\App\Core\Database\QueryLogger::class)) {
	class_alias(\App\Core\Database\QueryLogger::class, 'QueryLogger');
}

if (!class_exists('ApiValidator') && class_exists(\App\Core\Http\ApiValidator::class)) {
	class_alias(\App\Core\Http\ApiValidator::class, 'ApiValidator');
}