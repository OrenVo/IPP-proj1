<?php
/************************************************************
 * @file parse.php                                          *
 * @author Vojtěch Ulej (xulejv00)                          *
 * @brief IPP projekt 1                                     *
 * Skript který provede lexikální a syntakticou analýzu.    *
 ************************************************************/
declare(strict_types = 1); // Pro jistotu zapnout datovou kontrolu :D

include_once __DIR__ . '/inc/error_handler.php';
include_once __DIR__ . '/inc/parser.php';

try {
    $stats  = args_parse($argc, $argv, basename(__FILE__));
    $parser = new Parser($stats);
    $parser->parse();
    if (count($stats) > 0) {
      $parser->output_stats();
    }
    $parser->output_XML();
} catch (my_Exception $err) {
  $err->last_words();
}

exit(ErrVal::ALL_OK);




?>
