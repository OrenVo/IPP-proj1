<?php
/**************************************************************
 * @file parse.php                                            *
 * @author Vojtěch Ulej (xulejv00)                            *
 * @brief IPP projekt 1                                       *
 * Skript který provede test skriptů parse.php a interpret.py *
 **************************************************************/
declare(strict_types = 1);

$directory;
$parse;
$int;
$jexamxml;
$parse_only;
$int_only;

function args_parse($argc, $argv){
    if ($argc == 1) {
      return ErrVal::ARG_MISS;
    }
    foreach ($argv as $arg) {
      if ($arg == basename(__FILE__))
        continue;

      if ($arg == '--help')
        if ($argc > 2)
          return ErrVal::ARG_MISS;
        else
          print_help();
    }
}

class ErrVal{
    // Globální návratové hodnoty
    public const ALL_OK = 0;            # Vše proběhlo bez problému
    public const ARG_MISS = 10;         # Chybějící argument
    public const SOURCE_FILE_ERR = 11;  # Chyba při otevírání vstupního souboru
    public const DEST_FILE_ERR = 12;    # Chyba při otvírání výstupního souboru
    public const INTERN_ERR = 99;       # Interní chyba

    // Návratové hodnoty pro tento úkol
    public const HEADER_MISS = 21;
    public const BAD_OPCODE = 22;
    public const LEX_OR_SYN_ERR = 23;

}
?>
