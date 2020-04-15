# Implementační dokumentace k 2. úloze do IPP 2019/2020
# Jméno a příjmení: Vojtěch Ulej
# Login: xulejv00

## Skript test.php
Skript je složen z modulů:
* **args_parse**
* **TestFiles**
* **HTML**
* **Tester**
### args_parse
Tato funkce slouží k zpracování argumentů z příkazové řádky, argumenty jsou nastaveny do globálních proměnných, se kterými poté pracují další moduly. Při chybě vyvolá vyjímku *my_Exception*.

### TestFiles
Funkce *TestFiles* kontroluje, zda-li scripty na zadané v argumentech lze číst. A tudíž mohou být použity, pokud je některý ze souborů nečitelný, nebo neexistuje, funkce vyvolá výjimku *my_Exception*.

### HTML
Třída **HTML** slouží pro generování výstupního HTML s výsledky testů. Metody *succ_test* a *failed_test* slouží k zapsání výsledku testu, jako argumenty dostávají, název testu, návratovou hodnotu a očekávanou návratovou hodnotu. Metoda *final_stats* spočítá a vypíše úspěšnost testů, celkový počet testů a počet úspěšných i neúspěšných testů.

### Tester
Třída **Tester** spouští jednotlivé testy a vyhodnocuje jejich výsledky. Metoda *run_tests* nejdříve zkontroluje jestli jsou soubory potřebné pro testování ve složce, pokud nejsou, funkce je vytvoří (soubory s příponou .out, .in, .rc). Pokud byl zadán argument **--recursive** začnou se jednotlivé testy spouštět rekuzivně ve všech složkách. V průběhu testů jsou vytvářeny dočasné pomocné soubory, které jsou po skončení testů vymazány.

## Skript interpret.py
Tento skript spouští interpret, který je implementován ve více souborech ve složce *src*.
Soubory:
* interpret.py
* src
    * errors.py
    * input.py
    * int.py
    * memory_frames.py
    * parse.py
    * stack.py
### interpret.py
Tento skript spouští celý proces interpretace a úspěšném konci vypisuje statistiky (pokud byl na příkazovém řádku předán argument --stats)
### errors.py
V tomto skriptu je definována funkce eprint, která se chová stejně jako funkce print, pouze je výstup směrován na stderr. Dále jsou zde definovány výjimky InterpretException, ParserError, StackError, FrameError.
### input.py
V tomto souboru je definována třída *Input* která slouží k jednodušímu čtení vstupu. Tato třída potřebuje při konstrukci znát vstupní soubor. Metoda **READ** načte ze vstupního souboru jeden řádek a vrátí jej jako string. Pokud byl vstupním souborem soubor a ne stdin, je třeba jej po konci čtení zavřít metodou **close**
### memory_frames.py
Tento soubor obsahuje třídu *Frame*, která reprezentuje jednotlivé paměťové rámce. Pro rámce je zde využit slovník, kde ke každému názvu proměné je přiřazena její hodnota. Metoda **defvar** slouží k definici nové proměnné, při chybě vrací výjimku *FrameRedefineError*. Metoda **move** slouží k přiřazení hodnoty proměnné. Metoda **var** slouží k čtení hodnoty proměnné.
### stack.py
Třída *Stack* slouží k práci se zásobníkem. Zásobník je implementován jako datový typ *list*. Pro práci se zásobníkem jsou definovány metody **push**, **pop**, **top**.
### parse.py
Tento soubor obsahuje třídu ARGS, která slouží na jednodušší zpracování argumentů z příkazové řádky. Třídu XML, která slouží k načtení instrukcí ze vstupu. A funkce escape_string, která přavádí escape sekvence v řetězcích na znaky.
XML je nejdříve zpracováno modulem ElementTree. Poté je jeho obsah zkontrolován, zda-li obsahuje vše potřebné, k tomu slouží metoda **CheckIns**. Instrukce jsou poté metodou **LoadIns** načteny do *listu* *tuplů* ve formátu (order, opcode, arg1, arg2, arg3, types), kde types je *list* obsahující typy argumentů. Návěští jsou načtena do slovníku, kde ke každému návěští je přiřazen index do *listu* instrukcí, kde se návěští vyskytuje.
### int.py
Tento skript obsahuje třítu Interpret, která pro konstrukci vyžaduje *list* instrukcí ve formátu viz. **parse.py**, slovník návěští, a třídu objekt Input, který slouží ke čtení ze vstupu.
Metoda **Interpret** obsahuje smyčku, která spouští zpracování jednotlivých instrukcí metodou **DoInstruction**. Tato metoda provádí jednotlivé instrukce. Této metodě pomáhají ještě metody **Jump** (pro nastavení indexu podle návěští), **var** (pro výběr paměťového rámce), **symb** (pro získání hodnoty z argumentu) a **do_op** (pro vykonání aritmetických a logických operací). V interpretu jsou implementovány instrukce z rozšíření pro práci s čísli s plavoucí desetinou čárkou, nebo pro práci s zásobníkem.
Interpret obsahuje 3 zásobníky. Zásobník pro aritmetické, logické a jiné operace, dále zásobník paměťových rámců a nakonec zásobník volání.
