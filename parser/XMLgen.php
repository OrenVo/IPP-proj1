<?php
/**
 * @author Vojtěch Ulej (xulejv00)
 * @file parser.php
 * @brief Soubor obsahující třídu pro generování XML
 */


 /**
  * @brief Pro snadnější generování výsledného XML.
  */
 class XML
 {
   public $output = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<program language=\"IPPcode20\">\n";

   public function add_ins(int $order, string $opcode, $arg1 = null, $arg2 = null, $arg3 = null){
     $output .= "\t" . "<instruction order=\"$order\" opcode=\"$opcode\"";
     // Pokud instrukce nemá žádné argumenty pak
     if ($arg1 === null) {
       $output .= " />\n";
     }
   }


 }


?>
