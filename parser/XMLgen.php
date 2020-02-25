<?php
/**
 * @author Vojtěch Ulej (xulejv00)
 * @file parser.php
 * @brief Soubor obsahující třídu pro generování XML
 */
 declare(strict_types = 1);
 class XML
 {
   public $output;

   public function __construct(){
      $this->output = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<program language=\"IPPcode20\">\n";
   }

   public function add_ins(int $order, string $opcode, object $arg1 = null, object $arg2 = null, object $arg3 = null) : void {
     $args = null;
     // Načtení argumentů do pole pro budoucí zpracování pomocí foreach cyklu
     if ($arg1 !== NULL)
       $args[] = $arg1;
       if ($arg2 !== NULL)
         $args[] = $arg2;
         if ($arg3 !== NULL)
           $args[] = $arg3;

     $this->output .= "\t<instruction order=\"$order\" opcode=\"$opcode\"";
     // Pokud instrukce nemá žádné argumenty pak se vypíše krátký zápis
     if ($args === null) {
       $this->output .= " />\n";
     }
     else {
       $this->output .= ">\n";
       foreach ($args as $arg) {
         $this->output .= "\t\t<arg$arg->pos type=\"$arg->type\">$arg->attr</arg$arg->pos>\n";
       }
       $this->output .= "\t</instruction>\n";
     }
   }

   public function get_XML() : string {
     return $this->output . "</program>\n";
   }

   public function gen_ARG(string $type, string $attr, int $position) : object {
     // Kontrola
     if ($position < 1 || $position > 3) {
       throw new my_Exception("Chybná pozice argumentu (může být jen 1, 2, 3)", ErrVal::INTERN_ERR);
     }
     if ($type <> "int" || $type <> "bool" || $type <> "string" || $type <> "nil" || $type <> "label" || $type <> "type" || $type <> "var") {
       throw new my_Exception("Chybný typ argumentu.", ErrVal::LEX_OR_SYN_ERR);
     }
     if ($type === "string") {
       $attr = str_replace("&","&amp;",$attr);
       $attr = str_replace("<","&lt;",$attr);
       $attr = str_replace(">","&gt;",$attr);
     }
     return new ARG($type, $attr, $position);
   }

 }

 class ARG
 {
   public $type;        // Typ argumentu
   public $attr;        // Atribut argumentu
   public $pos;         // Pozice argumentu (může být jen 1, 2, 3)
   public function __construct(string $arg_type, string $arg_attr, int $arg_pos){
     $this->type = $arg_type;
     $this->attr = $arg_attr;
     $this->pos  = $arg_pos;
   }
 }

?>
