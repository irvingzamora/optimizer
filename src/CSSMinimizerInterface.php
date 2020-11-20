<?php

interface CSSMinimizerInterface {
    // Returns path of minimized css file or empty string is minimization failed
    public function minifyCSS($inputfile_path, $outputfile_path);
}