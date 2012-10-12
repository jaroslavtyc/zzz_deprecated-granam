<?php
namespace granam;

/**
 * Every exception created in any Granam script is based on GranamException.
 *
 * Specific exceptions exists for better and quicker determination of problem
 * by human.
 *
 * @author Jaroslav Týc <mail@jaroslavtyc.com>
 */
class Exception extends \granam\Failure {

	const
		GENERIC = 2147483648,						// 1000 0000 0000 0000 0000 0000 0000 0000
		ACCESS = 2149515264,							// 1000 0000 0001 1111 0000 0000 0000 0000
			ACCESS_READING = 2148597760,			// 1000 0000 0001 0001 0000 0000 0000 0000
			ACCESS_WRITING = 2148663296,			// 1000 0000 0001 0010 0000 0000 0000 0000
			ACCESS_EXECUTION = 2148794368,		// 1000 0000 0001 0100 0000 0000 0000 0000
	//		ACCESS_ = 2149056512,					//	1000 0000 0001 1000 0000 0000 0000 0000
		CONTENT = 2150563840,						// 1000 0000 0010 1111 0000 0000 0000 0000
			CONTENT_TYPE = 2149646336,				// 1000 0000 0010 0001 0000 0000 0000 0000
			CONTENT_VALUE = 2149711872,			// 1000 0000 0010 0010 0000 0000 0000 0000
	//		CONTENT_ = 2149842944,					// 1000 0000 0010 0100 0000 0000 0000 0000
	//		CONTENT_ = 2150105088,					//	1000 0000 0010 1000 0000 0000 0000 0000
		SERVICE = 2152660992,						// 1000 0000 0100 1111 0000 0000 0000 0000
			SERVICE_LAUNCHING = 2151743488,		// 1000 0000 0100 0001 0000 0000 0000 0000
			SERVICE_REGISTERING = 2151809024,	// 1000 0000 0100 0010 0000 0000 0000 0000
			SERVICE_UNREGISTERING = 2151940096,	// 1000 0000 0100 0100 0000 0000 0000 0000
	//		SERVICE_ = 2152202240,					// 1000 0000 0100 1000 0000 0000 0000 0000
		PROCESS = 2156855296,						// 1000 0000 1000 1111 0000 0000 0000 0000
			PROCESS_START = 2155937792,			//	1000 0000 1000 0001 0000 0000 0000 0000
			PROCESS_STATE = 2156003328,			//	1000 0000 1000 0010 0000 0000 0000 0000
			PROCESS_END = 2156134400,				//	1000 0000 1000 0100 0000 0000 0000 0000
	//		PROCESS_ = 2156396544,					//	1000 0000 1000 1000 0000 0000 0000 0000
		ALL_EXCEPTIONS = 4294901760;				// 1111 1111 1111 1111 0000 0000 0000 0000

	/**
	 * Throws new \granam\Exception class or her subclass object, witch is
	 * determined by code.
	 *
	 * @see http://en.wikipedia.org/wiki/Factory_method_pattern for answers
	 * @throws \granam\Exception
	 * @return void
	 */
	final public function __construct(
		$message,
		$code = NULL,
		\Exception $previous = NULL
	) {
		if (!$this->isMoreSpecificExceptionNameRequired($code)) {
			parent::__construct($message, $code);
		} else {
			$specificExceptionName = self::getSpecificExceptionName($code);
			try {
				$this->ensureExceptionClassAccessibility($specificExceptionName);
			} catch (\Exception $ensuranceException) {
				$ensuranceException->setPrevious(
					new \Exception($message, $code, $previous) // adding input exception
					// as previous one
				);
				throw $ensuranceException;
			}

			$specificallyNamedException = new $specificExceptionName($message, $code, $previous);
			// transhiping exception call information
			$specificallyNamedException->setFile($this->file);
			$specificallyNamedException->setLine($this->line);

			throw $specificallyNamedException; // passing trough exception of specified
			// name; file and line are overwritten by original exception values, so
			// actually thrown exception is de facto differing only in name
		}
	}

	// ---- LOCAL HELPERS ----

	private function isMoreSpecificExceptionNameRequired($exceptionCode) {
		if (get_class($this) !== __CLASS__) { // exception is not of the base class,
			// so has been already builded from more specific class and should be
			// filled and thrown
			return FALSE;
		}

		$validatedCode = $this->validateExceptionCode($exceptionCode); // check if
		// code is known
		if (empty($validatedCode)) { // this exception is closely unspecified
			return FALSE;
		}

		return TRUE;
	}

	private function ensureExceptionClassAccessibility($exceptionClassName) {
		if (!Observances_Utilities::isAvailable($exceptionClassName)) {
			// Exception class of this type does not exists yet
			Objects_Utilities::buildClass(
				$exceptionClassName,
				get_called_class(),
				FALSE
			);

			if (!Observances_Utilities::isAvailable($exceptionClassName)) {
				throw new \Exception( // desired Exception type could not been created
					'Exception of name [' . $exceptionClassName . '] could not be built',
					self::ACCESS_EXECUTION
				);
			}
		}
	}

	private function validateExceptionCode($code) {
		if (is_null($code)) {
			return $code;
		} elseif (!is_numeric($code)) {
			trigger_error(
				'Given Exception code is not a number but [' . gettype($code) . ']',
				E_USER_WARNING
			);

			return NULL;
		} elseif ($code < self::GENERIC) {
			trigger_error(
				'Given Exception code is lower then minimal accepted value of [' .
					 self::GENERIC . ']',
				E_USER_WARNING
			);

			return NULL;
		} elseif ($code > self::ALL_EXCEPTIONS) {
			trigger_error(
				'Given Exception code is higher then maximal accepted value of [' .
					 self::ALL_EXCEPTIONS . ']',
				E_USER_WARNING
			);

			return NULL;
		} elseif ($code == self::ALL_EXCEPTIONS) {
			trigger_error(
				'Given Exception code [' . self::ALL_EXCEPTIONS . '] represents ' .
					 'group of all exception types and is therefore meaned to be ' .
					 'used for exception handling',
				E_USER_WARNING
			);

			return NULL;
		}

		return (int)$code;
	}

	private function setPrevious(\Exception $previous) {
		$this->previous = $previous;
	}

	/**
	 * Builds name of exception in dependency on given exception code
	 *
	 * @param int $code
	 * @return string full name of exception
	 */
	private static function getSpecificExceptionName($code) {
		$nameOfException = '\\' . get_called_class();
		$groupsDelimiter = '_';
		$firstUpper = function($name) {
			return ucfirst(strtolower($name)); // ACCESS => Access
		};
		foreach (self::getListOfExceptionNamesByCode($code) as $type => $subtypes) {
			$nameOfException .= $groupsDelimiter . $firstUpper($type);
			$nameOfException .= implode('And', array_map($firstUpper, $subtypes));
		}

		// example of result: "\granam\Exception_AccessReadingAndExecution"
		return $nameOfException;
	}

	/**
	 * According to exception code are determined exception types and subtypes
	 * which are returned in two-dimensional array with type as key indexing
	 * array with subtypes as values
	 *
	 * @param int $code
	 * @return array list of exception types, grouped by first-level exception
	 *	 types code
	 */
	private static function getListOfExceptionNamesByCode($code) {
		$exceptionNames = array();
		if ($code & self::ACCESS) {
			$exceptionNames['ACCESS'] = array();
			if ($code & self::ACCESS_READING) {
				$exceptionNames['ACCESS'][] = 'READING';
			}

			if ($code & self::ACCESS_WRITING) {
				$exceptionNames['ACCESS'][] = 'WRITING';
			}

			if ($code & self::ACCESS_EXECUTION) {
				$exceptionNames['ACCESS'][] = 'EXECUTION';
			}
		}

		if ($code & self::CONTENT) {
			$exceptionNames['CONTENT'] = array();
			if ($code & self::CONTENT_TYPE) {
				$exceptionNames['CONTENT'][] = 'TYPE';
			}

			if ($code & self::CONTENT_VALUE) {
				$exceptionNames['CONTENT'][] = 'VALUE';
			}
		}

		return $exceptionNames;
	}
}