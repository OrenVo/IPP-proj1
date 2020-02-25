<?php
/************************************************************
 * @file parse.php                                          *
 * @author Vojtěch Ulej (xulejv00)                          *
 * @brief IPP projekt 1                                     *
 * Skript který provede lexikální a syntakticou analýzu.    *
 ************************************************************/
declare(strict_types = 1); // Pro jistotu zapnout datovou kontrolu :D

include_once __DIR__ . '/error_handler.php';
include_once __DIR__ . '/scanner.php';
include_once __DIR__ . '/parser.php';

try {
    $parser = new Parser();
    $parser->parse();
    $parser->output_XML();
} catch (my_Exception $err) {
  $err->last_words();
}

exit(ErrVal::ALL_OK);
?>
