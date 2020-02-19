<?php
/**
 * @author Vojtěch Ulej (xulejv00)
 * @file scanner.php
 * @brief Soubor obsahující třídu lexikálního analyzátoru
 */
 require_once __DIR__ . '/error_handler.php';


 class T_Type
 {
 /** Definice konstant tokenů **/
 // INSTRUKCE
   public const  T_MOVE = 0;
   public const  T_CREATEFRAME = 1;
   public const  T_PUSHFRAME = 2;
   public const  T_POPFRAME = 3;
   public const  T_DEFVAR = 4;
   public const  T_CALL = 5;
   public const  T_RETURN = 6;
   public const  T_PUSHS = 7;
   public const  T_POPS = 8;
   public const  T_ADD = 9;
   public const  T_SUB = 10;
   public const  T_MUL = 11;
   public const  T_IDIV = 12;
   public const  T_LT = 13;
   public const  T_GT = 14;
   public const  T_EQ = 15;
   public const  T_AND = 16;
   public const  T_OR = 17;
   public const  T_NOT = 18;
   public const  T_INT2CHAR = 19;
   public const  T_STRI2INT = 20;
   public const  T_READ = 21;
   public const  T_WRITE = 22;
   public const  T_CONCAT = 23;
   public const  T_STRLEN = 24;
   public const  T_GETCHAR = 25;
   public const  T_SETCHAR = 26;
   public const  T_TYPE = 27;
   public const  T_LABEL = 28;
   public const  T_JUMP = 29;
   public const  T_JUMPIFEQ = 30;
   public const  T_JUMPIFNEQ = 31;
   public const  T_EXIT = 32;
   public const  T_DPRINT = 33;
   public const  T_BREAK = 34;
 // Ostatní klíčová slova + znaky
   public const  T_EOL = 35;
   public const  T_EOF = 36;
   public const  T_IPPcode20 = 37;    // hlavička .IPPcode20
   public const  T_AT = 38;           // @
 // Paměťové rámce
   public const  T_GF = 39;
   public const  T_TF = 40;
   public const  T_LF = 41;
 // Datové typy
   public const T_bool = 42;
   public const T_nil = 43;
   public const T_string = 44;
   public const T_int = 45;
 }
 $KeyWords = array(
   T_Type::T_MOVE         => "MOVE",
   T_Type::T_CREATEFRAME  => "CREATEFRAME",
   T_Type::T_PUSHFRAME    => "PUSHFRAME",
   T_Type::T_POPFRAME     => "POPFRAME",
   T_Type::T_DEFVAR       => "DEFVAR",
   T_Type::T_CALL         => "CALL",
   T_Type::T_RETURN       => "RETURN",
   T_Type::T_PUSHS        => "PUSHS",
   T_Type::T_POPS         => "POPS",
   T_Type::T_ADD          => "ADD",
   T_Type::T_SUB          => "SUB",
   T_Type::T_MUL          => "MUL",
   T_Type::T_IDIV         => "IDIV",
   T_Type::T_LT           => "LT",
   T_Type::T_GT           => "GT",
   T_Type::T_EQ           => "EQ",
   T_Type::T_AND          => "AND",
   T_Type::T_OR           => "OR",
   T_Type::T_NOT          => "NOT",
   T_Type::T_INT2CHAR     => "INT2CHAR",
   T_Type::T_STRI2INT     => "STRI2INT",
   T_Type::T_READ         => "READ",
   T_Type::T_WRITE        => "WRITE",
   T_Type::T_CONCAT       => "CONCAT",
   T_Type::T_STRLEN       => "STRLEN",
   T_Type::T_GETCHAR      => "GETCHAR",
   T_Type::T_SETCHAR      => "SETCHAR",
   T_Type::T_TYPE         => "TYPE",
   T_Type::T_LABEL        => "LABEL",
   T_Type::T_JUMP         => "JUMP",
   T_Type::T_JUMPIFEQ     => "JUMPIFEQ",
   T_Type::T_JUMPIFNEQ    => "JUMPIFNEQ",
   T_Type::T_EXIT         => "EXIT",
   T_Type::T_DPRINT       => "DPRINT",
   T_Type::T_BREAK        => "BREAK"
 );

 /**
  * Třída tokenu
  */
 class Token
 {
   public $type;
   public $attr;
   public function __construct($t_type, $t_attr = NULL)
   {
     $this->type = $t_type;
     $this->attr = $t_attr;
   }
 }

 class Scanner
 {
   private $line_num = 1;
   public $input;
   private $len;
   private $index = 11;
   function __construct(){
       $this->input = self::get_input();
       $this->len = strlen($this->input);
   }
   // Funkce zkontroluje přítomnost hlavičky, poté zahájí lexikální analýzu
   public function scan(){
       if (substr($this->input,0,12) <> ".IPPcode20\n") {
         throw new my_Exception("Na řádku $this->line_num chybí hlavička", 21);
       }
       $Tokens = array(new Token(TOKEN_IPPcode20), new Token(TOKEN_EOL));
       do {
         $token = $this->get_new_token();
         if ($token->type == NULL) {
           throw new my_Exception("Neznámý token $token->type", 23);
         }
         array_push($Tokens,$token);
       } while ($token->type != TOKEN_EOF);
       return $Tokens;
   }

   private function get_new_token(){
     $tok= new Token(TOKEN_EOF);
     $tok->type = TOKEN_EOF;
     echo "$tok->type";
     return $tok;
       while ($this->index < $this->len) {
         switch ($this->input[$this->index]) {
           case '\n':
             return new Token(TOKEN_EOL);
             break;

           default:
             return new Token(TOKEN_EOF);
             break;
         }
       }
   }

   function get_input(){
     $in = fopen( 'php://stdin', 'r' );
     if ($in === FALSE) { // Nepovedlo se otevřít stdin
       var_dump($in);
       throw new my_Exception("Nelze otevřít vstupní soubor.", 11);
     } else{
         $input = file_get_contents('php://stdin');
         if ($input === FALSE){
             var_dump($input);
             throw new my_Exception("Chyba při načítání vstupu.", 99);
         }
         else
           return $input;
     }
   }

 }

?>
