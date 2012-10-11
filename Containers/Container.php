<?php
namespace granam;

interface Container {

	public function add($item, $index = NULL);

	public function set($item, $index);
}