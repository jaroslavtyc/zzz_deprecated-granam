<?php
;/* DO NOT DELETE ME PLEASE, I AM IMPORTANT ROW - JUST LET ME EVERYTIME AT BEGINNING
;
; Autoload class configuration
; - regular expressions here are matched against names of classes or interfaces
; loaded by \granam\Autoload
; For detail information about PHP-like ini format see
; http://php.net/manual/en/function.parse-ini-file.php

[basename]
	case_sensitive = TRUE ; base filename with script will be expected exactly as
		; mentioned in code
	equal_to_object_name = TRUE ; base filename will be expected equal to name of
		; class or interface

	; If file names are different to class or interface name on beggining of name,
	; specify differences here.
	; - index of parameter is regular expression representing condition when preffix
	; will be added to searched filename (note than brackets [] specify array in
	; ini file, do not belong to regular expression)
	[prefixes]
		;case_sensitive = FALSE
		;.*[] = class.
		;.*[] = interface.

	[postfixes]
		;case_sensitive = FALSE
		;.*[] = _inner
		;.*[] = _global

; Index of suffix is represented by regular expression, value of suffix is suffix
; itself with optional dot(.) on start (if missing, is added automatically).
; If more suffixes are used (not recommended) for same regular expression
; representative, separate them by dot like this .php.lib.
[suffixes]
	case_sensitive = TRUE
	.*[] = .php
	;.*[] = .lib

; DO NOT DELETE ME PLEASE, I AM IMPORTANT ROW TOO - JUST LET ME EVERYTIME AT END */