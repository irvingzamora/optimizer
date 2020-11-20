<?php

interface CSSCleanerInterface
{
    // Returns path of optimized css file or empty string if optimization failed
    public function removeUnusedCSS($inputfile_path, $outputfile_path) : string;

}