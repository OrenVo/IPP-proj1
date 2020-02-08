<?php
if ($argc == 2 && $argv[1] == "--help") {
  printf("Skript parse.php načítá vstup ze standardního vstupu a výstup vypisuje na standardní výstup.\nNa vstupu očekává zdrojový kód napsaný v jazyce IPPcode20.
Na výstup vypíše reprezentaci programu ve formátu XML.\n");
}

/** Definice konstant tokenů **/
// INSTRUKCE
  const  TOKEN_MOVE = 0;
  const  TOKEN_CREATEFRAME = 1;
  const  TOKEN_PUSHFRAME = 2;
  const  TOKEN_POPFRAME = 3;
  const  TOKEN_DEFVAR = 4;
  const  TOKEN_CALL = 5;
  const  TOKEN_RETURN = 6;
  const  TOKEN_PUSHS = 7;
  const  TOKEN_POPS = 8;
  const  TOKEN_ADD = 9;
  const  TOKEN_SUB = 10;
  const  TOKEN_MUL = 11;
  const  TOKEN_IDIV = 12;
  const  TOKEN_LT = 13;
  const  TOKEN_GT = 14;
  const  TOKEN_EQ = 15;
  const  TOKEN_AND = 16;
  const  TOKEN_OR = 17;
  const  TOKEN_NOT = 18;
  const  TOKEN_INT2CHAR = 19;
  const  TOKEN_STRI2INT = 20;
  const  TOKEN_READ = 21;
  const  TOKEN_WRITE = 22;
  const  TOKEN_CONCAT = 23;
  const  TOKEN_STRLEN = 24;
  const  TOKEN_GETCHAR = 25;
  const  TOKEN_SETCHAR = 26;
  const  TOKEN_TYPE = 27;
  const  TOKEN_LABEL = 28;
  const  TOKEN_JUMP = 29;
  const  TOKEN_JUMPIFEQ = 30;
  const  TOKEN_JUMPIFNEQ = 31;
  const  TOKEN_EXIT = 32;
  const  TOKEN_DPRINT = 33;
  const  TOKEN_BREAK = 34;
// Ostatní klíčová slova + znaky
  const  TOKEN_EOL = 35;
  const  TOKEN_EOF = 36;
  const  TOKEN_IPPcode20 = 37;
  const  TOKEN_AT = 38;
// Paměťové rámce
  const  TOKEN_GF = 39;
  const  TOKEN_GF = 40;
  const  TOKEN_GF = 41;
// Datové typy
  const TOKEN_bool = 42;
  const TOKEN_nil = 43;
  const TOKEN_string = 44;
  const TOKEN_int = 45;

?>
