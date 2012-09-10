<?php
namespace granam;

class TimidScriptfileAnalyzer
 extends \granam\TimidScriptfileAnalyzer_InnerFacilities {

	private $namespace;
	private $observanceBasename;
	private $observanceType;

	public function __construct($scriptFilename) {
		parent::__construct();
		$this->setScriptFilename($scriptFilename);
	}

	public function getObservanceType()
	{
		$this->ensureAnalysis();

		return $this->observanceType;
	}

	public function getObservanceName()
	{
		$this->ensureAnalysis();

		return
			(!empty($this->namespace)
				? '\\' . $this->namespace
				: ''
			) .
			'\\' . $this->observanceBasename;
	}
}