<?php
/**
 * @author Vojtěch Ulej (xulejv00)
 * @file parser.php
 * @brief Soubor obsahující třídu syntaktického analyzátoru
 */
 declare(strict_types = 1);

 require_once __DIR__ . '/error_handler.php';
 require_once __DIR__ . '/scanner.php';
 require_once __DIR__ . '/XMLgen.php';

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


 class Parser
 {

   private const HEADER = '/^\s*\.IPPcode20((\s+)|$)#*.*$/';
   private const COMMENT_LINE = '/^\s*#.*$/';
   /* TYPY */
   private const T_TYPE = '/(bool)|(int)|(string)/';
   private const T_NIL = '/nil@nil/';
   private const T_INT = '/int@[0-9]+/';
   private const T_BOOL = '/bool@(true|false)/';
   private const T_STRING = '/string@([^\s#\\]|(\\[0-9]{3}))*/';
   /* Identifikátor */
   private const ID = '/(GF|LF|TF)@[_\-\$&%\*!\?a-zA-Z][_\-\$&%\*!\?a-zA-Z0-9]*/';
   private const LABEL = '/[_\-\$&%\*!\?a-zA-Z][_\-\$&%\*!\?a-zA-Z0-9]*/';
   public $line_num = 1;
   private $instruction_order = 1;
   public $XML;

   public function __construct(){
      $this->XML = new XML();
   }

   public function parse(){

     $line = fgets(STDIN);
     if ($line === FALSE || 1 <> preg_match(self::HEADER,$line)) {
       throw new my_Exception("Chybí hlavička na řádku $this->line_num.\n",ErrVal::HEADER_MISS);
     }
     $this->line_num++;
     while ( ($line = fgets(STDIN)) !== FALSE ) { $this->line_num++;
       if ($line == "\n")
         continue;
       elseif (preg_match(self::COMMENT_LINE,$line))
         continue;

       $line_arr = preg_split("/[\s]+/",$line,-1,PREG_SPLIT_NO_EMPTY); // Rozdělí řádek na slova.

       /** Odstranit po dokončení switche **/
       if (!self::isInstruction($line_arr[0]))
         throw new my_Exception("Neznámá instrukce: '$line_arr[0]'.", ErrVal::BAD_OPCODE);
       /************************************/

       switch ($line_arr[0]) {
         case "CREATEFRAME":
         case "PUSHFRAME":
         case "POPFRAME":
         case "RETURN":
         case "BREAK":
           if (count($line_arr) > 1) {
             if ($line_arr[1][0] !== "#") {
               throw new my_Exception("Instrukce: $line_arr[0] na řádku: $this->line_num neočekává žádný argument.", ErrVal::LEX_OR_SYN_ERR);

             }
           }
           $this->XML->add_ins($this->instruction_order++,$line_arr[0]);
           break;
         case "DEFVAR":
         case "POPS":
           // TODO: <var>
           break;
         case "ADD":
         case "SUB":
         case "MUL":
         case "IDIV":
         case "LG":
         case "GT":
         case "EQ":
         case "AND":
         case "OR":
         case "NOT":
         case "INT2CHAR":
         case "STRI2INT":
         case "CONCAT":
         case "GETCHAR":
         case "SETCHAR":
          // TODO: ⟨var⟩ ⟨symb1⟩ ⟨symb2 ⟩
          break;
         case "READ":
          // TODO: <var> <type>
          break;
         case "MOVE":
         case "STRLEN":
         case "TYPE":
          // TODO: ⟨var⟩ ⟨symb⟩
          break;
         case "CALL":
         case "LABEL":
         case "JUMP":
          // TODO: <label>
          break;
         case "JUMPIFEQ":
         case "JUMPIFNEQ":
          // TODO: ⟨label⟩ ⟨symb1⟩ ⟨symb2 ⟩
          break;
         case "PUSHS":
         case "WRITE":
         case "EXIT":
         case "DPRINT":
           // TODO: <symb>
           break;
         default:
          throw new my_Exception("Neznámá instrukce: '$line_arr[0]'.", ErrVal::BAD_OPCODE);
          break;
       }

     }
   }

   private function var(string $str, int $pos) : object {
     if (preg_match(self::ID,$str)) {
       return XML::gen_ARG("var",$str,$pos);
     }
     else throw new my_Exception("Chybný argument <var>: \'$str\' na řádku $line_num.", ErrVal::LEX_OR_SYN_ERR);

   }
   private function symb(string $str, int $pos) : object {
     if (preg_match(self::ID,$str)) {
       return XML::gen_ARG("var",$str,$pos);
     }
     elseif (preg_match(self::T_NIL,$str)) {
       return XML::gen_ARG("nil",$str,$pos);
     }
     elseif (preg_match(self::T_INT,$str)) {
       return XML::gen_ARG("int",$str,$pos);
     }
     elseif (preg_match(self::T_BOOL,$str)) {
       return XML::gen_ARG("bool",$str,$pos);
     }
     elseif (preg_match(self::T_STRING,$str)) {
       return XML::gen_ARG("string",$str,$pos);
     }
     else throw new my_Exception("Chybný argument <symb>: \'$str\' na řádku $line_num.", ErrVal::LEX_OR_SYN_ERR);
   }

   private function label(string $str, int $pos) : object {
     if (preg_match(self::LABEL,$str)) {
       return XML::gen_ARG("label",$str,$pos);
     }
     else throw new my_Exception("Chybný argument <label>: \'$str\' na řádku $line_num.", ErrVal::LEX_OR_SYN_ERR);
   }

   private function type(string $str, int $pos) : object {
     if (preg_match(self::T_TYPE,$str)) {
       return XML::gen_ARG("type",$str,$pos);
     }
     else throw new my_Exception("Chybný argument <type>: \'$str\' na řádku $line_num.", ErrVal::LEX_OR_SYN_ERR);
   }
   public function output_XML(){
     printf($this->XML->get_XML());
   }

   private function isInstruction( string $word ) : bool {
     switch ($word) {
       case 'MOVE':
       case 'CREATEFRAME':
       case 'PUSHFRAME':
       case 'POPFRAME':
       case 'DEFVAR':
       case 'CALL':
       case 'RETURN':
       case 'PUSHS':
       case 'POPS':
       case 'ADD':
       case 'SUB':
       case 'MUL':
       case 'IDIV':
       case 'LT':
       case 'GT':
       case 'EQ':
       case 'AND':
       case 'OR':
       case 'NOT':
       case 'INT2CHAR':
       case 'STRI2INT':
       case 'READ':
       case 'WRITE':
       case 'CONCAT':
       case 'STRLEN':
       case 'GETCHAR':
       case 'SETCHAR':
       case 'TYPE':
       case 'LABEL':
       case 'JUMP':
       case 'JUMPIFEQ':
       case 'JUMPIFNEQ':
       case 'EXIT':
       case 'DPRINT':
       case 'BREAK':
        return true;
        break;
       default:
         return false;
         break;
     }
   }
 }



?>
