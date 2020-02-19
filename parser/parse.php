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

  $scanner = new Scanner();
  $tokens = $scanner->scan();

} catch (my_Exception $err) {
  $err->last_words();
}


?>
