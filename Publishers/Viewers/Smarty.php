<?php
namespace granam;
//do not match to GranamAutoloader convention so explicit requirement is needed
require_once GRANAM_ROOT_DIRECTORY . '/libraries/viewers/smarty/Smarty.class.php';

class Smarty extends \granam\Object implements \granam\Viewer, \granam\Singleton {

	private static $instance;
	private $smarty;

	/**
	* Getter of Smarty instance
	*
	* @param bool $recompileAll forces compiling of all templates
	* @return GranamSmarty instance of this class
	*/
	public static function getInstance($recompileAll = NULL) {
		if(!isset(self::$instance)){
			$actualCalledClass = get_called_class();
			self::$instance = new $actualCalledClass($recompileAll);
		}

		return self::$instance;
	}

	// LINEAGE FACILITIES

	protected function __construct($recompileAll) {
		$this->initializeSmarty($recompileAll);
	}

	protected function getSmarty()
	{
		return $this->smarty;
	}

	private function initializeSmarty($recompileAll)
	{
		$this->smarty = new Smarty();
		// at first are templates searched in project folder, in second step in universal templates
		$this->addTemplateDir(DOCUMENT_ROOT . '/templates/smarty');
		// setting self-explanation name of directory for already compiled templates
		$this->setCompileDir(DOCUMENT_ROOT . '/templates_compiled/smarty');
		// optional project specific extensions in project folder
		$this->addPluginsDir('./extensions/smarty/plugins');
		// granam Smarty extensions
		$this->addPluginsDir(GRANAM_ROOT_DIRECTORY . '/extensions/smarty/plugins');
		// configuration can be set on project level via config in project folder
		$this->addConfigDir('./configurations/smarty');
		// if setting is not found in project folder, universal folder is searched for config
		$this->addConfigDir(GRANAM_ROOT_DIRECTORY . '/configurations/smarty');
		if (!empty($recompileAll)) {
			$this->smarty->compileAllTemplates('.tpl', TRUE);
		}
	}
}