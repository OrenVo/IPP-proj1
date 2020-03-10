# Implementační dokumentace k 1. úloze do IPP 2019/2020
# Jméno a příjmení: Vojtěch Ulej
# Login: xulejv00

## Struktura projektu
  Projekt je rozdělen do 4 souborů (**parse.php**, **parser.php**, **error_handler.php**, **XMLgen.php**).
  V projektu je implementováno rozšíření STATP, pro výpis

### parse.php
  Hlavní skript, který spouští celé zpracování zdrojového kódu.
  Nejdříve dojde k zpracování argumentů, ty jsou pak nahrané do pole _$stats_, který je pak   	předán konstrukturu třídy **_Parser_** (viz. **parser.php**). Po úspěšné syntaktické a 	 lexikální analýze
  dojde k vytvoření souboru _stats_ (pokud byl zadaný argumenty). Po úspěšném zápisu statistik dojde
  k výpisu výstupního XML. Při vyskytnutí chyby je vytvořena výjimka _my_Exception_, která je definovaná
  v souboru **error_handler.php**. Výjimka je v tomto souboru odchycena, je vypsán obsah její zprávy a
  program je ukončen s chybovým kódem, který je také obsažen ve výjimce.

### error_handler.php
  Skript obsahuje 2 třídy (_ErrVal_ a _my_Exception_). Třída _ErrVal_ je pouze výčtem návratových kódů.

  Třída _my_Exception_ je podtřída třídy _Exception_. Konstrutoru výjimky je potřeba předat chybovou hlášku
  a kód. _my_Exception_ obsahuje metodu _last_words()_, která vypíše chybovou hlášku s informacemi, ve
  kterém souboru na kterém řádku byla výjimka vyvolána a ukončí program s chybovým kódem.

### XMLgen.php
  Tento soubor obsahuje 2 třídy (_ARG_ a _XML_). Třída _XML_ obsahuje výstupní XML formát a metody pro jeho
  tvoření (_add_ins()_, _gen_ARG()_). Metoda _add_ins()_ vygeneruje část XML, která odpovídá instrukci.
  Požaduje pořadové číslo instrukce, operační kód a poté 0 až 3 objekty _ARG_. Metoda _gen_ARG()_ slouží k
  vytvoření objektu ARG argumentu, požaduje typ, atribut a posici argumentu. A metodu _get_XML()_, která
  vrací (jako _řetězec_) kompletní XML formát.

  Třída _ARG_ slouží k tvorbě objektu argumentu instrukce, který udržuje informace o argumentu (typ,
  atribut a posici).

### parser.php
  Tento soubor obsahuje funkci _args_parse()_ pro zpracování argumentů skriptu. Argumenty jsou načteny do
  pole _$stats_. Funkce _print_help()_ vypíše na stdin nápovědu skriptu a ukončí skript s návratovou
  hodnotou 0.

  Třída _Parser_ obsahuje metody pro zpracování vstupu, dále si vytvoří objekt _XML_ (viz. XMLgen), do
  kterého generuje výchozí XML formát. Třída definuje konstanty obsahující regulární výrazy, které jsou
  využity při kontrole správnosti vstupního kódu.  

  Metoda _parse()_ načítá řádek po řádku ze standardního vstupu. Řádek je dále rozdělen na jednotlivá slova   (oddělena bílými znaky). Podle instrukčního kódu jsou dále kontrolovány argumenty instrukce, jestli  odpovídají formátu v zadání. Kontrola se provádí regulárnímy výrazy.  Při výskytu chyby (syntaktické, lexikální, či vnitřní), dochází k vyvolání výjimky _my_Exception_.

  Metoda _output_stats()_ zapíše statistiky do souboru, zadaného argumentem skriptu. Čítače statistik jsou inkrementovány v metodě _parse()_ při výskytu daného elementu.
