<?php
namespace granam;

interface FailureSingleton extends \granam\Singleton {

	static function initializeInstance(\granam\Snitcher $snitcher);
}