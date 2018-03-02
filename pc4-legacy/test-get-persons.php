<?php

include_once("pimcore/config/startup.php");

//require_once __DIR__ . '/vendor/autoload.php';

use \Pimcore\Model\Object;

$entries = new Object\Person\Listing;

foreach ($entries as $e) {
	print_r ($e->getFirstname() . ' ' . $e->getLastname() . PHP_EOL);
}