<?php
/**
 * @author Vojtěch Ulej (xulejv00)
 * @file parser.php
 * @brief Soubor obsahující třídu syntaktického analyzátoru
 */
 declare(strict_types = 1);

 require_once __DIR__ . '/error_handler.php';
 require_once __DIR__ . '/XMLgen.php';

 function print_help(){
  printf("php parse.php [--help]\n --help - Zobrazí tuto nápovědu.\n Skript parse.php načítá vstup ze standardního vstupu a výstup vypisuje na standardní výstup.\n Na vstupu očekává zdrojový kód napsaný v jazyce IPPcode20.\n Poté provede lexikální a syntaktickou analýzu. Pokud vše proběhne bez chyby. Na výstup vypíše reprezentaci programu ve formátu XML.\n Chybové a ladící hlášky sktript vypisuje na standardní chybový výstup.\n");
  exit(ErrVal::ALL_OK);
 }

 function args_parse(int $argc, $argv, string $filename = __FILE__)
 {
   if ($argc == 1) {
     return array();
   }
   $stats = array();
   foreach ($argv as $arg) {
     if ($arg == $filename) { // Odstraní $argv[0] (název skriptu)
       continue;
     }
     if ($arg == "--help")
       if ($argc > 2){
         throw new my_Exception("Neplatný počet argumentů, nebo kombinace argumentů.\n Spusťte skript pouze s --help parametrem.", ErrVal::ARG_MISS);
       }else{
         print_help();
       }
     if (preg_match('/^\s*--stats=.+\s*$/',$arg)) {
       $stats[] = substr_replace($arg, '', 0, strlen("--stats="));
     }

   }
   if (count($stats) == 0) {
     throw new my_Exception("Chybí argument --stats=\"název_souboru\". ", ErrVal::ARG_MISS);
   }
   /** Odstranění uvozovek na "", nebo '' na začátku a konci souboru **/
   if (($stats[0][0] == "\"" && $stats[0][strlen($stats[0])-1] == "\"" ) || ($stats[0][0] == "\'" && $stats[0][strlen($stats[0])-1] == "\'" )) {
     $stats[0] = ltrim($stats[0],"\"");
     $stats[0] = ltrim($stats[0],"\'");
     $stats[0] = rtrim($stats[0],"\"");
     $stats[0] = rtrim($stats[0],"\'");
   }

   if ($stats[0] == '')
     throw new my_Exception("Chybí název souboru v argumentu --stats=", ErrVal::ARG_MISS);
   foreach ($argv as $arg) {
     if ($arg == $filename) {
       continue;
     }
     if (preg_match('/^\s*--stats=.+\s*$/i',$arg))
       continue;
     switch ($arg) {
       case '--loc':
       case '--comments':
       case '--jumps':
       case '--labels':
         $stats[] = $arg;
         break;
       default:
         throw new my_Exception("Neznámí argument $arg", ErrVal::ARG_MISS);
         break;
     }
   }
   return $stats;
 }


 class Parser
 {

   private const HEADER = '/^\s*(\.IPPcode20)(((\s+)#.*$)|(\s*$))/i';
   private const COMMENT_LINE = '/^\s*#.*$/';
   /* TYPY */
   private const T_TYPE = '/^((bool)|(int)|(string))$/';
   private const T_NIL = '/^(nil@nil)$/';
   private const T_INT = '/^(int@([+\-]|[0-9])[0-9]*)$/';
   private const T_BOOL = '/^(bool@(true|false))$/';
   private const T_STRING = '/^(string@([^\s#\\\\]|\\\\[0-9]{3})*)$/u';
   /* Identifikátor */
   private const ID = '/^(GF|LF|TF)@[_\-\$&%\*!\?a-zA-Z][_\-\$&%\*!\?a-zA-Z0-9]*$/';
   private const LABEL = '/^[_\-\$&%\*!\?a-zA-Z][_\-\$&%\*!\?a-zA-Z0-9]*$/';
   public $line_num = 1;
   private $instruction_order = 1;
   public $XML;
   public $stats;
   private $loc = 0,
           $comments = 0,
           $jumps = 0,
           $labels = array();

   public function __construct($stats){
      $this->XML = new XML();
      $this->stats = $stats;
   }

   public function parse(){

     while ( ($line = fgets(STDIN)) !== FALSE ) { $this->line_num++;
       if (preg_match('/^\s*$/',$line) )
         continue;
        elseif ( preg_match('/^\s*#.*$/',$line)) { $this->comments++;
          continue;
        }
       else break;
     }
     if ($line === FALSE) {
       exit(0);
     }
     $line = preg_split('/#/',$line);
     $line = $line[0];
     if (!preg_match(self::HEADER,$line)) {
       throw new my_Exception("Chybí hlavička na řádku $this->line_num.\n",ErrVal::HEADER_MISS);
     }
     if (preg_match('/^\s+#.*$/',$line)) {
       $this->comments++;
     }
     while ( ($line = fgets(STDIN)) !== FALSE ) { $this->line_num++;

       if (preg_match('/#.*/',$line)) $this->comments++;
       $line = preg_split('/#/',$line);
       $line = $line[0];
       if (preg_match('/^\s*$/',$line))
         continue;
       elseif (preg_match(self::COMMENT_LINE,$line)){
         $this->comments++;
         continue;
       }

       $line_arr = preg_split("/[\s]+/",$line,-1,PREG_SPLIT_NO_EMPTY); // Rozdělí řádek na slova.
       for ($i=0; $i < count($line_arr); $i++) {
         $line_arr[$i] = trim($line_arr[$i]);
       }
       $line_arr[0] = strtoupper($line_arr[0]);
       self::isInstruction($line_arr[0]); # Připočte čítače pro stats soubor
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
             $this->comments++;
           }
           $this->XML->add_ins($this->instruction_order++,$line_arr[0]);
           break;
         /**    <var>    **/
         case "DEFVAR":
         case "POPS":
           if (count($line_arr) > 2) {
             if ($line_arr[2][0] !== "#") {
               throw new my_Exception("Instrukce: $line_arr[0] na řádku: $this->line_num neočekává žádný argument.", ErrVal::LEX_OR_SYN_ERR);
             }
             $this->comments++;
           }
           elseif (count($line_arr) < 2) {
             throw new my_Exception("Instrukce: $line_arr[0] na řádku: $this->line_num očekává 1 argument.", ErrVal::LEX_OR_SYN_ERR);
           }
           $this->XML->add_ins($this->instruction_order++,$line_arr[0],self::var($line_arr[1],1));
           break;
         /**   <var> <symb> <symb>   **/
         case "ADD":
         case "SUB":
         case "MUL":
         case "IDIV":
         case "LT":
         case "GT":
         case "EQ":
         case "AND":
         case "OR":
         case "STRI2INT":
         case "CONCAT":
         case "GETCHAR":
         case "SETCHAR":
          if (count($line_arr) > 4) {
            if ($line_arr[4][0] !== "#") {
              throw new my_Exception("Instrukce: $line_arr[0] na řádku: $this->line_num neočekává žádný argument.", ErrVal::LEX_OR_SYN_ERR);
            }
            $this->comments++;
          }
          elseif (count($line_arr) < 4) {
            throw new my_Exception("Instrukce: $line_arr[0] na řádku: $this->line_num očekává 3 argumenty.", ErrVal::LEX_OR_SYN_ERR);
          }
          $this->XML->add_ins($this->instruction_order++,$line_arr[0],self::var($line_arr[1],1),self::symb($line_arr[2],2),self::symb($line_arr[3],3));
          break;
         /**   <var> <type>   **/
         case "READ":
          if (count($line_arr) > 3) {
            if ($line_arr[3][0] !== "#") {
              throw new my_Exception("Instrukce: $line_arr[0] na řádku: $this->line_num neočekává žádný argument.", ErrVal::LEX_OR_SYN_ERR);
            }
            $this->comments++;
          }
          elseif (count($line_arr) < 3) {
            throw new my_Exception("Instrukce: $line_arr[0] na řádku: $this->line_num očekává 2 argumenty.", ErrVal::LEX_OR_SYN_ERR);
          }
          $this->XML->add_ins($this->instruction_order++,$line_arr[0],self::var($line_arr[1],1),self::type($line_arr[2],2));
          break;
         /**   <var> <symb>   **/
         case "MOVE":
         case "INT2CHAR":
         case "NOT":
         case "STRLEN":
         case "TYPE":
          if (count($line_arr) > 3) {
            if ($line_arr[3][0] !== "#") {
              throw new my_Exception("Instrukce: $line_arr[0] na řádku: $this->line_num neočekává žádný argument.", ErrVal::LEX_OR_SYN_ERR);
            }
            $this->comments++;
          }
          elseif (count($line_arr) < 3) {
            throw new my_Exception("Instrukce: $line_arr[0] na řádku: $this->line_num očekává 2 argumenty.", ErrVal::LEX_OR_SYN_ERR);
          }
          $this->XML->add_ins($this->instruction_order++,$line_arr[0],self::var($line_arr[1],1),self::symb($line_arr[2],2));
          break;
         /**   <label>   **/
         case "LABEL":
         if (count($line_arr) > 1) {
           $this->labels[] = $line_arr[1];
         }
         case "CALL":
         case "JUMP":
          if (count($line_arr) > 2) {
            if ($line_arr[3][0] !== "#") {
              throw new my_Exception("Instrukce: $line_arr[0] na řádku: $this->line_num neočekává žádný argument.", ErrVal::LEX_OR_SYN_ERR);
            }
            $this->comments++;
          }
          elseif (count($line_arr) < 2) {
            throw new my_Exception("Instrukce: $line_arr[0] na řádku: $this->line_num očekává právě 1 argument.", ErrVal::LEX_OR_SYN_ERR);
          }
          $this->XML->add_ins($this->instruction_order++,$line_arr[0],self::label($line_arr[1],1));
          break;
         /**   <label> <symb> <symb>   **/
         case "JUMPIFEQ":
         case "JUMPIFNEQ":
          if (count($line_arr) > 4) {
            if ($line_arr[4][0] !== "#") {
              throw new my_Exception("Instrukce: $line_arr[0] na řádku: $this->line_num neočekává žádný argument.", ErrVal::LEX_OR_SYN_ERR);
            }
            $this->comments++;
          }
          elseif (count($line_arr) < 4) {
            throw new my_Exception("Instrukce: $line_arr[0] na řádku: $this->line_num očekává 3 argumenty.", ErrVal::LEX_OR_SYN_ERR);
          }
          $this->XML->add_ins($this->instruction_order++,$line_arr[0],self::label($line_arr[1],1),self::symb($line_arr[2],2),self::symb($line_arr[3],3));

          break;
         /**   <symb>   **/
         case "PUSHS":
         case "WRITE":
         case "EXIT":
         case "DPRINT":
           if (count($line_arr) > 2) {
             if ($line_arr[2][0] !== "#") {
               throw new my_Exception("Instrukce: $line_arr[0] na řádku: $this->line_num neočekává jeden argument.", ErrVal::LEX_OR_SYN_ERR);
             }
             $this->comments++;
           }
           elseif (count($line_arr) < 2)
             throw new my_Exception("Instrukce: $line_arr[0] na řádku: $this->line_num očekává 1 argumenty.", ErrVal::LEX_OR_SYN_ERR);

           $this->XML->add_ins($this->instruction_order++,$line_arr[0],self::symb($line_arr[1],1));
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
     else throw new my_Exception("Chybný argument <var>: '$str' na řádku $this->line_num.", ErrVal::LEX_OR_SYN_ERR);

   }
   private function symb(string $str, int $pos) : object {
     if (preg_match(self::T_NIL,$str)) {
       return XML::gen_ARG("nil",str_replace("nil@","",$str),$pos);
     }
     elseif (preg_match(self::T_INT,$str)) {
       return XML::gen_ARG("int",str_replace("int@","",$str),$pos);
     }
     elseif (preg_match(self::T_BOOL,$str)) {
       return XML::gen_ARG("bool",str_replace("bool@","",$str),$pos);
     }
     elseif (preg_match(self::T_STRING,$str)) {
       return XML::gen_ARG("string",str_replace("string@","",$str),$pos);
     }
     elseif (preg_match(self::ID,$str)) {
       return XML::gen_ARG("var",$str,$pos);
     }
     else throw new my_Exception("Chybný argument <symb>: '$str' na řádku $this->line_num.", ErrVal::LEX_OR_SYN_ERR);
   }

   private function label(string $str, int $pos) : object {
     if (preg_match(self::LABEL,$str)) {
       return XML::gen_ARG("label",$str,$pos);
     }
     else throw new my_Exception("Chybný argument <label>: '$str' na řádku $this->line_num.", ErrVal::LEX_OR_SYN_ERR);
   }

   private function type(string $str, int $pos) : object {
     if (preg_match(self::T_TYPE,$str)) {
       return XML::gen_ARG("type",$str,$pos);
     }
     else throw new my_Exception("Chybný argument <type>: '$str' na řádku $this->line_num.", ErrVal::LEX_OR_SYN_ERR);
   }
   public function output_XML(){
     echo $this->XML->get_XML();
   }
   public function output_stats(){
     $file = fopen($this->stats[0],'w');
     if ($file === FALSE)
       throw new my_Exception("Nemohu otevřít (nebo vytvořit) soubor $stats[0].", ErrVal::DEST_FILE_ERR);

     foreach ($this->stats as $stat) {
       switch ($stat) {
         case '--loc':
           fwrite($file, (string)$this->loc . "\n");
           break;
         case '--comments':
           fwrite($file, (string)$this->comments . "\n");
           break;
         case '--labels':
           fwrite($file, (string)count(array_unique($this->labels)) . "\n");
           break;
         case '--jumps':
           fwrite($file, (string)$this->jumps . "\n");
           break;
         case $this->stats[0]:
           continue 2;
           break;
         default:
           fclose($file);
           throw new my_Exception("Neznámý argument $stat", ErrVal::INTERN_ERR);
           break;
       }
     }
     fclose($file);
   }

   private function isInstruction( string $word ) : bool {
     switch ($word) {
       case 'JUMP':
       case 'JUMPIFEQ':
       case 'JUMPIFNEQ':
       case 'CALL':
       case 'RETURN':
        $this->jumps++;
       case 'MOVE':
       case 'CREATEFRAME':
       case 'PUSHFRAME':
       case 'POPFRAME':
       case 'DEFVAR':
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
       case 'EXIT':
       case 'DPRINT':
       case 'BREAK':
        $this->loc++;
        return true;
        break;
       default:
         return false;
         break;
     }
   }
 }



?>
