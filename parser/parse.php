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



parse_args($argc, $argv);
try {
    $parser = new Parser();
    $parser->parse();
    $parser->output_XML();
} catch (my_Exception $err) {
  /*******************************/
  fwrite(STDERR,$parser->XML->get_XML()); // Jen pro debugging
  /*******************************/
  $err->last_words();
}

exit(ErrVal::ALL_OK);




?>
