<?php

class UnCSSProxy implements CSSCleanerInterface {
    public function removeUnusedCSS($inputfile_path, $outputfile_path) : string
    {
        // This is where the logic will be written
        // Check extension
        exec('uncss '. $inputfile_path . ' > ' . $outputfile_path); 
        return "";
    }

}
