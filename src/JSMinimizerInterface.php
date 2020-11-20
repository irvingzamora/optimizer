<?php

interface JSMinimizerInterface
{
    // Returns path of minimized js file or empty string is minimization failed
    public function minifyJS($filepath) : string;
}