<?php

if (!isset($pageTitle) && isset($title)) {
	$pageTitle = (string) $title;
}

?><!DOCTYPE html>
<html lang="en" class="bg-canvas-white">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php require_once dirname(__DIR__) . '/header.php'; ?>
</head>
<body class="font-sans text-foundation-grey bg-canvas-white">
<div class="min-h-screen flex flex-col">
