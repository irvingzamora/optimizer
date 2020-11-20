<?php
    spl_autoload_register('autoloaderclasses');

function autoloaderclass($className)
    {
        $path = "";
        $extension = ".php";
        $fullPath = $path . $className . $extension;
        include_once $fullPath;
    }