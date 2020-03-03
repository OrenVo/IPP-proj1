<?php
/**
 * @author Vojtěch Ulej (xulejv00)
 * @file error_handler.php
 * @brief Soubor obsahující třídu vyjímky (my_Exception) a definici návratových hodnot
 */
 declare(strict_types = 1);

 /**
 *  Třída mé vlastní vyjímky
 */
class my_Exception extends Exception
{
  public $err_message;

  public $err_code;

  public function __construct(string $err_message,int $err_code)
  {
    $this->err_message = $err_message;
    $this->err_code = $err_code;
  }

  public function last_words(){
    fwrite(STDERR,"Odchycena vyjímka na řádku:" . $this->getLine() . ", v souboru: " . $this->getFile() . ":\n\t Chyba: " . $this->err_message . "\n");
    die($this->err_code);
  }
}

/**
 * Definice návratových hodnot
 */
class ErrVal
{
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
