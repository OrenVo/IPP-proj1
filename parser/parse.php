<?php
/************************************************************
 * @file parse.php                                          *
 * @author Vojtěch Ulej (xulejv00)                          *
 * @brief IPP projekt 1                                     *
 * Skript který provede lexikální a syntakticou analýzu.    *
 ************************************************************/
declare(strict_types = 1); // Pro jistotu zapnout datovou kontrolu :D
function parse_args(){
 if ($argc == 2 && $argv[1] == "--help") {
 printf(
"php parse.php [--help]
--help - Zobrazí tuto nápovědu.
Skript parse.php načítá vstup ze standardního vstupu a výstup vypisuje na standardní výstup.
Na vstupu očekává zdrojový kód napsaný v jazyce IPPcode20.
Poté provede lexikální a syntaktickou analýzu. Pokud vše proběhne bez chyby. Na výstup vypíše reprezentaci programu ve formátu XML.
Chybové a ladící hlášky sktript vypisuje na standardní chybový výstup.
");
 exit(0);
 } elseif ($argc > 1) {
   fwrite(STDERR,"Neplatný počet argumentů: $argc\nNebo neznámé argumenty: ");
   foreach ($argv as $arg) {
     if ($arg == basename(__FILE__)) { // Odstraní $argv[0] (název skriptu)
       continue;
     }
     fwrite(STDERR,$arg); fwrite(STDERR," ");
   }
   fwrite(STDERR,"\n");
   exit(10);
 }
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
  const  TOKEN_IPPcode20 = 37;    // hlavička .IPPcode20
  const  TOKEN_AT = 38;           // @
// Paměťové rámce
  const  TOKEN_GF = 39;
  const  TOKEN_TF = 40;
  const  TOKEN_LF = 41;
// Datové typy
  const TOKEN_bool = 42;
  const TOKEN_nil = 43;
  const TOKEN_string = 44;
  const TOKEN_int = 45;

$KeyWords = array(
  TOKEN_MOVE         => "MOVE",
  TOKEN_CREATEFRAME  => "CREATEFRAME",
  TOKEN_PUSHFRAME    => "PUSHFRAME",
  TOKEN_POPFRAME     => "POPFRAME",
  TOKEN_DEFVAR       => "DEFVAR",
  TOKEN_CALL         => "CALL",
  TOKEN_RETURN       => "RETURN",
  TOKEN_PUSHS        => "PUSHS",
  TOKEN_POPS         => "POPS",
  TOKEN_ADD          => "ADD",
  TOKEN_SUB          => "SUB",
  TOKEN_MUL          => "MUL",
  TOKEN_IDIV         => "IDIV",
  TOKEN_LT           => "LT",
  TOKEN_GT           => "GT",
  TOKEN_EQ           => "EQ",
  TOKEN_AND          => "AND",
  TOKEN_OR           => "OR",
  TOKEN_NOT          => "NOT",
  TOKEN_INT2CHAR     => "INT2CHAR",
  TOKEN_STRI2INT     => "STRI2INT",
  TOKEN_READ         => "READ",
  TOKEN_WRITE        => "WRITE",
  TOKEN_CONCAT       => "CONCAT",
  TOKEN_STRLEN       => "STRLEN",
  TOKEN_GETCHAR      => "GETCHAR",
  TOKEN_SETCHAR      => "SETCHAR",
  TOKEN_TYPE         => "TYPE",
  TOKEN_LABEL        => "LABEL",
  TOKEN_JUMP         => "JUMP",
  TOKEN_JUMPIFEQ     => "JUMPIFEQ",
  TOKEN_JUMPIFNEQ    => "JUMPIFNEQ",
  TOKEN_EXIT         => "EXIT",
  TOKEN_DPRINT       => "DPRINT",
  TOKEN_BREAK        => "BREAK"
);

/**
 * Třída tokenu
 */
class Token
{
  public $type;
  public $attr;
  function __construct($type, $attr = NULL)
  {
    $this->$type = $type;
    $this->$attr = $attr;
  }
}

/**
 *  Třída mé vlastní vyjímky
 */
class my_Exception extends Exception
{
  public $err_message;

  public $err_code;

  function __construct(string $err_message,int $err_code)
  {
    $this->err_message = $err_message;
    $this->err_code = $err_code;
  }

  public function last_words(){
    fwrite(STDERR,"Chyba: " . $this->err_message . "\n");
    die($this->err_code);
  }
}

function get_input(){
  $in = fopen( 'php://stdin', 'r' );
  if ($in == FALSE) { // Nepovedlo se otevřít stdin
    var_dump($in);
    throw new my_Exception("Nelze otevřít vstupní soubor.", 11);
  } else{
      $input = file_get_content($in);
      if ($input == FALSE){
          var_dump($input);
          throw new my_Exception("Chyba při načítání vstupu.", 99);
      }
      else
        return $input;
  }
}

/**
 * Třída reprezentuje lexikální analyzátor. Načítá vstup z stdin, poté provede lex analýzu a generuje příslušné tokeny.
 */
class Scanner
{
  private $line_num = 1;
  public $input;
  private $len;
  private $index = 11;
  function __construct($input){
      $this->input = $input;
      $this->len = strlen($input);
  }
  // Funkce zkontroluje přítomnost hlavičky, poté zahájí lexikální analýzu
  public function scan(){
      if (substr($this->input,0,12) <> ".IPPcode20\n") {
        throw new my_Exception("Na řádku $this->line_num chybí hlavička", 21);
      }
      $Tokens = array(new Token(TOKEN_IPPcode20), new Token(TOKEN_EOL));
      do {
        $token = get_new_token();
        if ($token->type == NULL) {
          throw new my_Exception("Neznámý token", 23);
        }
        push_array($Tokens,$token);
      } while ($token->type != TOKEN_EOF);
      return $Tokens;
  }

  public function get_new_token(){
    return Final_State_Machine();
  }

  private function Final_State_Machine(){
    $buffer = null;
      while ($index < $len) {
        switch (variable) {
          case 'value':
            // code...
            break;

          default:
            // code...
            break;
        }
      }
      return new Token(TOKEN_EOF);
  }

}


?>
