<?php

class MK_Filter_AlnumWithUnderscore extends Zend_Filter_Alnum {

    /**
     * Sets default option values for this instance
     *
     * @param  boolean $allowWhiteSpace
     * @return void
     */
    public function __construct($allowWhiteSpace = false) {
        parent::__construct($allowWhiteSpace);
    }

    /**
     * Defined by Zend_Filter_Interface
     *
     * Returns the string $value, removing all but alphabetic and digit characters
     *
     * @param  string $value
     * @return string
     */
    public function filter($value) {
        $whiteSpace = $this->allowWhiteSpace ? '\s' : '';
        if (!self::$_unicodeEnabled) {
            // POSIX named classes are not supported, use alternative a-zA-Z0-9 match
            $pattern = '/[^a-zA-Z0-9_' . $whiteSpace . ']/';
        } else if (self::$_meansEnglishAlphabet) {
            //The Alphabet means english alphabet.
            $pattern = '/[^a-zA-Z0-9_' . $whiteSpace . ']/u';
        } else {
            //The Alphabet means each language's alphabet.
            $pattern = '/[^\p{L}\p{N}_' . $whiteSpace . ']/u';
        }

        return preg_replace($pattern, '', (string) $value);
    }

}
