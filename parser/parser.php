<?php
/**
 * @author Vojtěch Ulej (xulejv00)
 * @file parser.php
 * @brief Soubor obsahující třídu syntaktického analyzátoru
 */
 require_once __DIR__ . '/error_handler.php';
 require_once __DIR__ . '/scanner.php';

 function parse_args(){
  if ($argc == 2 && $argv[1] == "--help") {
  printf("php parse.php [--help]\n --help - Zobrazí tuto nápovědu.\n Skript parse.php načítá vstup ze standardního vstupu a výstup vypisuje na standardní výstup.\n Na vstupu očekává zdrojový kód napsaný v jazyce IPPcode20.\n Poté provede lexikální a syntaktickou analýzu. Pokud vše proběhne bez chyby. Na výstup vypíše reprezentaci programu ve formátu XML.\n Chybové a ladící hlášky sktript vypisuje na standardní chybový výstup.\n ");
  exit(ErrVal::ALL_OK);
  } elseif ($argc > 1) {
    fwrite(STDERR,"Neplatný počet argumentů: $argc\nNebo neznámé argumenty: ");
    foreach ($argv as $arg) {
      if ($arg == basename(__FILE__)) { // Odstraní $argv[0] (název skriptu)
        continue;
      }
      fwrite(STDERR,$arg); fwrite(STDERR," ");
    }
    fwrite(STDERR,"\n");
    exit(ErrVal::ARG_MISS);
  }
 }


?>
