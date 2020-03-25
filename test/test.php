<?php
/**************************************************************
 * @file parse.php                                            *
 * @author Vojtěch Ulej (xulejv00)                            *
 * @brief IPP projekt 1                                       *
 * Skript který provede test skriptů parse.php a interpret.py *
 **************************************************************/
declare(strict_types = 1);

$directory;
$parse_skript;
$int_skript;
$jexamxml_skript;
$recursive;
$parse_only;
$int_only;

function print_help(){
    echo "Program projde zadanou složku a spustí testy, které v ní najde. A na stdin vypíše html stránku s výsledky.\n";
    echo "\t--help - vypíše tuto nápovědu\n";
    echo "\t--directory=path - path je složka s testy (pokud tento parametr je prázdný, pak se prohledává .)\n";
    echo "\t--recursive - prohledává i všechny složky v zadaném složce\n";
    echo "\t--parse-script=file - file je cesta ke skriptu na testování parseru\n";
    echo "\t--int-script=file - file je cesta ke skriptu na testování interpretu\n";
    echo "\t--parse-only - testuje se pouze parser (nesmí být v kombinaci s --int-script=file a --int-only)\n";
    echo "\t--int-only - testuje se pouze interpret (nesmí být v kombinaci s --parse-script=file a --parse-only)\n";
    echo "\t--jexamxml=file - soubor s JAR balíčkem s nástrojem A7Soft JExamXML\n";
    exit(0);
}

function args_parse($argc, $argv){
    global $directory;
    global $parse_skript;
    global $int_skript;
    global $jexamxml_skript;
    global $parse_only;
    global $recursive;
    global $int_only;
    if ($argc == 1) {
      return ErrVal::ARG_MISS;
    }
    foreach ($argv as $arg) {

      if ($arg == basename(__FILE__))   # ignore $argv[0]
        continue;
      elseif ($arg == '--help')         # print help and stop
        if ($argc > 2)
          return ErrVal::ARG_MISS;
        else
          print_help();
      /**--recursive**/
      elseif ($arg == '--recursive') {
        if (isset($recursive))
          throw new my_Exception("Opakovaný výskyt parametru $arg", ErrVal::ARG_MISS);
        $recursive = true;
      }
      /**--int-only**/
      elseif ($arg == '--int-only') {
        if (isset($int_only))
          throw new my_Exception("Opakovaný výskyt parametru $arg", ErrVal::ARG_MISS);
        if (isset($parse_only) || isset($parse_skript))
          throw new my_Exception("Nepovolená kombinace parametru $arg a --parse-only, nebo --parse-script=", ErrVal::ARG_MISS);
        $int_only = true;
      }
      /**--parse_only**/
      elseif ($arg == '--parse-only') {
        if (isset($parse_only))
          throw new my_Exception("Opakovaný výskyt parametru $arg", ErrVal::ARG_MISS);
        if (isset($int_only) || isset($int_skript))
          throw new my_Exception("Nepovolená kombinace parametru $arg a --int-only, nebo --int-script=", ErrVal::ARG_MISS);
        $parse_only = true;
      }
      /*
       --directory=path
       --parse-script=file
       --int-script=file
       --jexamxml=file
      */
     /**--directory**/
     elseif (preg_match('/^--directory=/', $arg)) {
       if (isset($directory)){
        throw new my_Exception("Opakovaný výskyt parametru $arg", ErrVal::ARG_MISS);
      }
       $directory = preg_replace('/^--directory=/', "",$arg);
     }
     /**--parse-script**/
     elseif (preg_match('/^--parse-script=/', $arg)) {
       if (isset($int_only))
        throw new my_Exception("Neplatná kombinace parametrů $arg a --int-only", ErrVal::ARG_MISS);
       if (isset($parse_skript))
        throw new my_Exception("Opakovaný výskyt parametru $arg", ErrVal::ARG_MISS);
       $parse_skript = preg_replace('/^--parse-script=/',"", $arg);
     }
     /**--int-script**/
     elseif (preg_match('/^--int-script=/', $arg)) {
       if (isset($parse_only))
        throw new my_Exception("Neplatná kombinace parametrů $arg a --parse-only", ErrVal::ARG_MISS);
       if (isset($int_skript))
        throw new my_Exception("Opakovaný výskyt parametru $arg", ErrVal::ARG_MISS);
       $int_skript = preg_replace('/^--int-script=/', "",$arg);
     }
     /**--jexamxml**/
     elseif (preg_match('/^--jexamxml=/', $arg)) {
       if (isset($jexamxml_skript))
        throw new my_Exception("Opakovaný výskyt parametru $arg", ErrVal::ARG_MISS);
       $jexamxml_skript = preg_replace('/^--jexamxml=/',"", $arg);
     }
     else
         throw new my_Exception("Neznámý parametr $arg", ErrVal::ARG_MISS);
    }
    if (!isset($directory)) $directory = '.';
    if (!isset($int_skript)) $int_skript = './interpre.py';
    if (!isset($parse_skript)) $parse_skript = './parse.php';
    if (!isset($jexamxml_skript)) $jexamxml_skript = ' /pub/courses/ipp/jexamxml/jexamxml.jar';
}

function TestFiles(){

  global $directory;
  global $int_skript;
  global $parse_skript;
  global $jexamxml_skript;
  global $int_only;
  global $parse_only;
  if (!is_dir($directory))
    throw new my_Exception("Zadaná složka $directory není složka, nebo nemáte dostatečná práva.", ErrVal::SOURCE_FILE_ERR);

  if (!isset($parse_only)){
    if (!file_exists($int_skript)) throw new my_Exception("Soubor: $int_skript nelze otevřít.", ErrVal::SOURCE_FILE_ERR);
  }

  if (!isset($int_only)){
    if (!file_exists($parse_skript)) throw new my_Exception("Soubor:$parse_skript nelze otevřít.", ErrVal::SOURCE_FILE_ERR);
  }
  if (!file_exists($jexamxml_skript)) throw new my_Exception("Soubor: $jexamxml_skript nelze otevřít.", ErrVal::SOURCE_FILE_ERR);

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

class my_Exception extends Exception{
  public $err_message;
  public $err_code;
  public function __construct(string $err_message,int $err_code)
  {
    $this->err_message = $err_message;
    $this->err_code = $err_code;
  }
  public function last_words(){
    fwrite(STDERR,"Odchycena vyjímka v souboru: " . basename($this->getFile()) . ":" . $this->getLine() . ":\n\t Chyba: " . $this->err_message . "\n");
    die($this->err_code);
  }
}


class HTML{
  public $succ_tests = 0;
  public $failed_tests = 0;
  public $test_count = 0;
  public $HTML_out;
  public function __construct(){
    $this->HTML_out = "<!DOCTYPE HTML>\n<html>\n<head>\n<meta charset=\"utf-8\">\n<title>IPP projekt</title>\n</head>\n<body>\n<h3>autor: Vojtěch Ulej (xulejv00)<h3>\n";}
  public function output_HTML(){
    echo $this->HTML_out . "</body>\n</html>\n";
  }

  public function succ_test($name, $retval, $exp_retval){
    $this->succ_tests++;
    $this->HTML_out = $this->HTML_out . "<font color=\"green\"> $name TEST ČÍSLO:" . $this->test_count++ . " passed (návratová hodnota $retval očekávaná $exp_retval)</font> &#10004;<br>\n";
  }
  public function failed_test($name, $retval, $exp_retval){
    $this->failed_tests++;
    $this->HTML_out = $this->HTML_out . "<font color=\"red\"> $name TEST ČÍSLO:" . $this->test_count++ . " failed (návratová hodnota $retval očekávaná $exp_retval)</font> &#10060;<br>\n";
  }
  public function final_stats($test_count = 0, $succ_tests = 0, $failed = 0){
    if ($test_count != 0)
      $rate = round(($succ_tests/$test_count)*100, 2);
    else
      $rate = 100;
    $this->HTML_out = $this->HTML_out . "<hr>\nCelkový počet testů: $test_count<br>\nPočet úspěšných testů: $succ_tests<br>\nPočet neúspěšných testů: $failed<br>\nÚspěšnost: $rate%\n";
  }
  public function dir_name($name){
    $this->HTML_out = $this->HTML_out . "<h4><b>Složka: $name:</b></h4><br>\n";
  }
}

class Tester{
  private $html;
  function __construct(){
    $this->html = new HTML();
  }
  public function run_tests($dir){
    $files = scandir($dir);
    global $recursive;
    if (isset($recursive)) {
      foreach ($files as $file) {
        if ($file == '.' || $file == '..')
          continue;
        if (is_dir($dir . '/' . $file)) {
          $this->run_tests($dir . '/' . $file);
          continue;
        }
      }
    }
    $this->html->dir_name($dir);
    foreach ($files as $file) {
      if (is_dir($file))
        continue;
      if (!preg_match('/.*\.src$/',$file)) continue;
      /*test .rc*/
      if (!file_exists($dir . '/' . str_replace(".src", ".rc", $file))) {
        $file_rc = fopen($dir . '/' . str_replace(".src", ".rc", $file), 'w');
        fwrite($file_rc, '0');
        $rc = 0;
      }
      else {
        $file_rc = fopen($dir . '/' . str_replace(".src", ".rc", $file), 'r');
        $rc = fgets($file_rc);
        $rc = (int)$rc;
      }
      /*test .in*/
      if (!file_exists($dir . '/' . str_replace(".src", ".in", $file))) {
        $file_in = fopen($dir . '/' . str_replace(".src", ".in", $file), 'w');
        if ($file_in === false)
          throw new my_Exception("Nemohu otevřít soubor: " . $dir . '/' . str_replace(".src", ".in", $file), 11);

      }
      else {
        $file_in = fopen($dir . '/' . str_replace(".src", ".in", $file), 'r');
        if ($file_in === false)
          throw new my_Exception("Nemohu otevřít soubor: ". $dir . '/' . str_replace(".src", ".in", $file), 11);

      }
      /*test .out*/
      if (!file_exists($dir . '/' . str_replace(".src", ".out", $file))) {
        $file_out = fopen($dir . '/' . str_replace(".src", ".out", $file), 'w');
        if ($file_out === false)
                    throw new my_Exception("Nemohu otevřít soubor: ". $dir . '/' . str_replace(".src", ".out", $file), 11);

      }
      else {
        $file_out = fopen($dir . '/' . str_replace(".src", ".out", $file), 'r');
        if ($file_out === false)
                    throw new my_Exception("Nemohu otevřít soubor: ". $dir . '/' . str_replace(".src", ".out", $file), 11);
      }

      /*Parser test*/
      global $parse_skript;
      if (isset($parse_only) || (!isset($parse_only) && !isset($int_only))) {
        exec("timeout 3 php7.4 -f $parse_skript < $dir/$file 2> /dev/null > temp_pars.out ", $output, $retval);
        if ($retval === 124)
          $this->html->failed_test($file, $retval, $rc);
        elseif ($retval !== $rc)
          $this->html->failed_test($file, $retval, $rc);
        else
          if ($rc != 0) {
            $this->html->succ_test($file, $retval, $rc);
          }
          else{
            global $jexamxml_skript;
            exec("java -jar $jexamxml_skript " . $dir . '/' . str_replace(".src", ".out", $file) . " temp_pars.out", $out, $retval);

            if ($retval !== 0 ) {
              $this->html->failed_test($file, $retval, 0);
            }
            else
             $this->html->succ_test($file, $retval, 0);
          }
      }
      /*Int test*/
      global $int_skript;

      fclose($file_in);
      fclose($file_rc);
      fclose($file_out);
    }
    exec("rm -f temp_pars.out");
    exec("rm -f temp_int.out");
  }
  public function HTML_out(){
    $this->html->final_stats($this->html->test_count, $this->html->succ_tests, $this->html->failed_tests);
    $this->html->output_HTML();
  }
}
$tester = new Tester();
try {
  args_parse($argc,$argv);
  TestFiles();
  $tester->run_tests($directory);
  $tester->HTML_out();
} catch (my_Exception $my_exc) {
  $my_exc->last_words();
}
?>
