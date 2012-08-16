<?php


/**
 * Loader
 *
 * Klasa obsługująca ładowanie innych klas
 *
 * @category MK
 * @package MK_Loader
 */
class MK_Loader
{

    /**
     * Funkcja ładujaca klasy zrobiona pod Brokera
     * @static
     *
     * @param string $className
     *
     * @throws \Exception
     * @return bool
     */
    public static function autoload($className)
    {

        $className = ltrim($className, '\\');
        $fileName = '';
        $namespace = '';
        if ($lastNsPos = strripos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            if (!empty(static::$changeRootNamespace)){
                $namespace = str_replace(static::$changeRootNamespace['namespace'], static::$changeRootNamespace['directory'], $namespace); //myk niezgodny z PSR-0, bo klasy z namespace /bip są w katalogu app
            }
            $fileName = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }

        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        if (file_exists($a = APP_PATH . DIRECTORY_SEPARATOR . $fileName)) { //to tez myk :)
            require_once $a;
            return true;
        } elseif (file_exists($a = DIR_VENDOR . DIRECTORY_SEPARATOR . $fileName)) {
            require_once $a;
            return true;
        }
        throw new MK_Loader_Exception('Nie można załadować komponentu systemu.' . $className);
    }

}
