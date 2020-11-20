<?php

interface OptimizerInterface {
    public function optimizeAllPages(Array $filepathsArray);
    public function optimizeCurrentPage();
}
