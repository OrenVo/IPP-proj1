from sys import argv, stdin
from src.errors import eprint, ErrorCodes, ParserError
import re
import xml.etree.ElementTree as ET
import os.path
from os import path

"""
    Tento soubor obsahuje:
        Třídu arg parse pro jednodušší zpracování argumentů z příkazové řádky
"""


class ARGS:

    def __init__(self):
        self.src, self.input, self.stats = None, None, None
        self.STATI = []

    def ParseArgs(self):
        if len(argv) < 2:
            raise ParserError(f'Nebyl zadán žádný argument.\n Pro nápovědu použijte [--help]',
                                     ErrorCodes.parameter)
        for arg in argv[1:]:  # odstranění prvního prvku (název programu)
            # Základní argumenty
            if re.match(r'^--input=', arg):
                if self.input is not None:
                    raise ParserError(f'Argument --input nalezen 2x', ErrorCodes.parameter)
                self.input = arg.replace('--input=', '', 1)
            elif re.match(r'^--source=', arg):
                if self.src is not None:
                    raise ParserError(f'Argument --source nalezen 2x', ErrorCodes.parameter)
                self.src = arg.replace('--source=', '', 1)
            elif re.match(r'--help', arg):
                PrintHelp()
                exit(ErrorCodes.ok)
            # Argumenty pro STATI rozšíření
            elif re.match(r'^--stats=', arg):
                if self.stats is not None:
                    raise ParserError(f'Argument --stats nalezen 2x', ErrorCodes.parameter)
                self.stats = arg.replace('--stats=', '', 1)
            elif re.match(r'^--insts', arg):
                self.STATI.append('--insts')
            elif re.match(r'^--vars', arg):
                self.STATI.append('--vars')
            else:
                raise ParserError(f'Neznámý argument {arg}', ErrorCodes.parameter)
        if self.input is None and self.src is None:
            raise ParserError('Chybějící povinný argument --input, nebo --source', ErrorCodes.parameter)
        if self.STATI != [] and self.stats is None:
            raise ParserError('Chybějící argument --stats=', ErrorCodes.parameter)


def PrintHelp():
    print('Tento program slouží jako interpret XML reprezentace programu zapsaného v jazyce IPPCode19')
    print('Spuštění:')
    print(f'\tpython interpret.py argument [argumenty] [--stats=soubor,[STATI argumenty]')
    print(f'\tpython - interpret jazyka python ve verzi 3.8')
    print(f'\targument - povinný argument --input=soubor, nebo --source=soubor.')
    print(f'\t[argumenty]')
    print(f'\t--input=soubor - ze souboru bude použit vstup do souboru')
    print(f'\t--source=soubor - ze souboru bude načtena XML reprezentace programu, který bude interpretován')
    print(f'\t\tPokud bude jeden z argumentů --source=, nebo --input= chybět. Bude nahrazen vstupem z stdin.')
    print(f'\t--help - Vypíše tuto nápovědu.')
    print(f'\t--stats=soubor - soubor do kterého budou zapsány statistiky')
    print(f'\tSTATI argumenty - udávají které statistiky se budou vypisovat')
    print(f'\t\t --insts - zapíše počet vykonaných instrukcí')
    print(f'\t\t --vars - zapíše maximální počet inicializovaných proměnných')


class XML:
    """Načte, zkontroluje a zpracuje XML"""

    def __init__(self, source):
        self.source = source  # Jméno souboru, nebo stdin
        self.XML = None
        self.instructions = []
        self.labels = {}

    def ReadNCheck(self):
        if self.source == stdin:
            inp = stdin.read()
        else:
            if os.path.isfile(self.source):
                try:
                    with open(self.source, 'r') as file:
                        inp = file.read()
                except OSError:
                    raise ParserError(f'Soubor: {self.source} nelze otevřít', ErrorCodes.inErr)
            else:
                raise ParserError(f'Soubor: {self.source} neexistuje.', ErrorCodes.inErr)
        try:
            element = ET.fromstring(inp)
        except ET.ParseError:
            raise ParserError('Chyba při parsování XML.', ErrorCodes.badxml)
        if element.tag != 'program':
            raise ParserError('Chybí kořenový element program.', ErrorCodes.syntaxErr)
        if element.attrib['language'] is not None and element.attrib['language'].lower() != 'ippcode20':
            raise ParserError('Chybí, nebo špatný atribut kořenového elementu.', ErrorCodes.syntaxErr)
        self.XML = element
        try:
            self.LoadXML()
        except IndexError:
            raise ParserError('Chybějící instrukce', ErrorCodes.syntaxErr)
        except KeyError:
            raise ParserError('Špatné atributy XML', ErrorCodes.syntaxErr)

    def LoadXML(self):
        orders = []
        for ins in self.XML:
            if 'order' not in ins.attrib:
                raise ParserError(f'Chybí order', ErrorCodes.syntaxErr)
            try:
                if int(ins.attrib['order']) <= 0:
                    raise ParserError(f'Hodnota order je záporná', ErrorCodes.syntaxErr)
            except ValueError:
                raise ParserError(f'Hodnota order není číslo', ErrorCodes.syntaxErr)
            orders.append(ins.attrib['order'])
            if ins.tag != 'instruction':
                raise ParserError(f'Neznámý element {ins.tag} v xml', ErrorCodes.syntaxErr)
            if re.match(r'^label$', ins.attrib['opcode'], re.IGNORECASE):
                if len(ins) == 0:
                    raise ParserError(f'Chybí argument u instrukce LABEL', ErrorCodes.syntaxErr)
                if ins[0].tag != 'arg1':
                    raise ParserError(f'Neznámý element {ins[0].tag} v xml', ErrorCodes.syntaxErr)
                if ins[0].attrib['type'] != 'label':
                    raise ParserError(f'Špatný typ instrukce argumentu u instrukce LABEL', ErrorCodes.syntaxErr)
                if ins[0].text not in self.labels.keys():
                    self.labels[ins[0].text] = ins.attrib['order']
                else:
                    raise ParserError(f'Návěští: {ins[0].text} bylo použito více než jednou',
                                             ErrorCodes.semanticErr)
            else:
                self.instructions.append(ins)
        if len(orders) != len(set(orders)):
            raise ParserError('Duplicitní hodnoty order', ErrorCodes.syntaxErr)
        self.instructions.sort(key=lambda el: int(el.attrib['order']))
        self.CheckIns()

    def CheckIns(self):
        for ins in self.instructions:
            instruction = ins.attrib['opcode']
            i = self.InsArgs(instruction)
            if i is None:
                raise ParserError(f'Neznámá instrukce: {instruction}', ErrorCodes.syntaxErr)
            del instruction
            arguments = []
            for args in ins:
                arguments.append(args)
            arguments.sort(key=lambda x: int(x.tag[-1]))
            ins = arguments
            if len(arguments) != len(i):
                raise ParserError('Chybí, nebo přebývá argument', ErrorCodes.syntaxErr)
            for index, arg in enumerate(i):
                if arg == '<var>':
                    if ins[index].tag != f'arg1' and ins[index].tag != f'arg2' and ins[index].tag != f'arg3':
                        raise ParserError(f'Špatný název elementu {ins[index].tag}', ErrorCodes.syntaxErr)
                    if ins[index].attrib['type'] != 'var':
                        raise ParserError(f'{ins[index].tag} musí být typu var', ErrorCodes.syntaxErr)
                elif arg == '<symb>':
                    if ins[index].tag != f'arg1' and ins[index].tag != f'arg2' and ins[index].tag != f'arg3':
                        raise ParserError(f'Špatný název elementu {ins[index].tag}', ErrorCodes.syntaxErr)
                    tmp = ins[index].attrib['type']
                    if tmp != 'var' and tmp != 'int' and tmp != 'string' and tmp != 'bool' and tmp != 'float' and tmp != 'nil':
                        raise ParserError(f'{ins[index].tag} musí být typu var, int, string, bool, float, nebo nil, '
                                          f'ale je typu {tmp}', ErrorCodes.syntaxErr)
                    try:
                        if tmp == 'int':
                            int(ins[index].text)
                        elif tmp == 'bool':
                            if not (ins[index].text == 'true' or ins[index].text == 'false'):
                                raise ValueError
                        elif tmp == 'float':
                            float.fromhex(ins[index].text)
                        elif tmp == 'nil':
                            if not ins[index].text == 'nil':
                                raise ValueError
                        elif tmp == 'var':
                            if not re.match(r'^[TLG]F@',ins[index].text):
                                raise ValueError
                    except ValueError:
                        raise ParserError('Špatný typ argumentů', ErrorCodes.syntaxErr)
                    del tmp
                elif arg == '<label>':
                    if ins[index].tag != f'arg1' and ins[index].tag != f'arg2' and ins[index].tag != f'arg3':
                        raise ParserError(f'Špatný název elementu {ins[index].tag}', ErrorCodes.syntaxErr)
                    if ins[index].attrib['type'] != 'label':
                        raise ParserError(f'{ins[index].tag} musí být typu var', ErrorCodes.syntaxErr)
                elif arg == '<type>':
                    if ins[index].tag != f'arg1' and ins[index].tag != f'arg2' and ins[index].tag != f'arg3':
                        raise ParserError(f'Špatný název elementu {ins[index].tag}', ErrorCodes.syntaxErr)
                    if ins[index].attrib['type'] != 'type':
                        raise ParserError(f'{ins[index].tag} musí být typu var', ErrorCodes.syntaxErr)
                else:
                    raise ParserError(f'Unknown argument {arg}')

    def LoadIns(self):
        instructions = self.instructions
        self.instructions = []
        for instruction in instructions:
            self.instructions.append(self.LoadIn(instruction))
        self.MapLabelToInstruction()

    @staticmethod
    def LoadIn(instruction):
        """ Tato metoda zpracuje xml formát instrukce a vytvoří z něj tuple.
            Tuple bude vypadat následovně: (order, opcode, arg1|None, arg2|None, arg3|None)"""

        order = instruction.attrib['order']
        opcode = instruction.attrib['opcode']
        args = [None, None, None]
        types = [None, None, None]
        for index, arg in enumerate(instruction):
            if arg.tag == 'arg1':
                index = 0
            elif arg.tag == 'arg2':
                index = 1
            elif arg.tag == 'arg3':
                index = 2
            if arg.attrib['type'] == 'string':
                args[index] = string_escape(arg.text)
            else:
                args[index] = arg.text
            types[index] = arg.attrib['type']
        if args[0] is None and (args[1] is not None or args[2] is not None):
            raise ParserError(f'Chybějící argument', ErrorCodes.syntaxErr)
        elif args[1] is None and args[2] is not None:
            raise ParserError(f'Chybějící argument', ErrorCodes.syntaxErr)
        return int(order), opcode.upper(), args[0], args[1], args[2], types

    def MapLabelToInstruction(self):
        """Metoda vrátí pozměněný dictionary self.labels. Pozmění se jen hodnoty indexu na instrukce"""
        keys = list(self.labels)
        values = []
        newdict = {}
        for order in self.labels.values():
            try:
                order = int(order)
            except ValueError:
                raise ParserError(f'Špatný order: {order}', ErrorCodes.syntaxErr)
            if order < 0:
                raise ParserError(f'Záporný element order', ErrorCodes.syntaxErr)
            for idx, val in enumerate(self.instructions):
                if val[0] < 0:
                    raise ParserError(f'Záporný element order', ErrorCodes.syntaxErr)
                if val[0] > order:
                    values.append(idx)
                    break
                elif idx == (len(self.instructions) - 1):
                    values.append(idx+1)
                    break
        for idx, key in enumerate(keys):
            newdict[key] = values[idx]
        self.labels = newdict

    @staticmethod
    def InsArgs(instruction):
        ins = instruction.upper()
        ins_dict = {
            "CREATEFRAME": [],
            "PUSHFRAME": [],
            "POPFRAME": [],
            "RETURN": [],
            "BREAK": [],
            "DEFVAR": ['<var>'],
            "POPS": ['<var>'],
            "ADD": ['<var>', '<symb>', '<symb>'],
            "SUB": ['<var>', '<symb>', '<symb>'],
            "MUL": ['<var>', '<symb>', '<symb>'],
            "IDIV": ['<var>', '<symb>', '<symb>'],
            "DIV": ['<var>', '<symb>', '<symb>'],
            "LT": ['<var>', '<symb>', '<symb>'],
            "GT": ['<var>', '<symb>', '<symb>'],
            "EQ": ['<var>', '<symb>', '<symb>'],
            "AND": ['<var>', '<symb>', '<symb>'],
            "OR": ['<var>', '<symb>', '<symb>'],
            "STRI2INT": ['<var>', '<symb>', '<symb>'],
            "CONCAT": ['<var>', '<symb>', '<symb>'],
            "GETCHAR": ['<var>', '<symb>', '<symb>'],
            "SETCHAR": ['<var>', '<symb>', '<symb>'],
            "READ": ['<var>', '<type>'],
            "MOVE": ['<var>', '<symb>'],
            "INT2CHAR": ['<var>', '<symb>'],
            "INT2FLOAT": ['<var>', '<symb>'],  # Podpora rozšíření FLOAT
            "FLOAT2INT": ['<var>', '<symb>'],
            "NOT": ['<var>', '<symb>'],
            "STRLEN": ['<var>', '<symb>'],
            "TYPE": ['<var>', '<symb>'],
            "CALL": ['<label>'],
            "JUMP": ['<label>'],
            "JUMPIFEQ": ['<label>', '<symb>', '<symb>'],
            "JUMPIFNEQ": ['<label>', '<symb>', '<symb>'],
            "PUSHS": ['<symb>'],
            "WRITE": ['<symb>'],
            "EXIT": ['<symb>'],
            "DPRINT": ['<symb>'],
            "JUMPIFEQS": ['<label>'],
            "JUMPIFNEQS": ['<label>'],
            "ADDS": [],
            "SUBS": [],
            "MULS": [],
            "DIVS": [],
            "IDIVS": [],
            "LTS": [],
            "GTS": [],
            "EQS": [],
            "ANDS": [],
            "ORS": [],
            "NOTS": [],
            "INT2FLOATS": [],
            "FLOAT2INTS": [],
            "INT2CHARS": [],
            "STRI2INTS": [],
            "CLEARS": []
        }
        return ins_dict.get(ins, None)


def string_escape(string):
    return re.sub(r'\\[0-9]{3}', lambda x: chr(int(x.group(0)[1:])), string)
