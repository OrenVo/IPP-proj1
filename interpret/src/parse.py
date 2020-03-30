from sys import argv, stdin
from src.errors import eprint, ErrorCodes, InterpretException
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
            raise InterpretException(f'Nebyl zadán žádný argument.\n Pro nápovědu použijte [--help]',
                                     ErrorCodes.parameter)
        for arg in argv[1:]:  # odstranění prvního prvku (název programu)
            # Základní argumenty
            if re.match(r'^--input=', arg):
                if self.input is not None:
                    raise InterpretException(f'Argument --input nalezen 2x', ErrorCodes.parameter)
                self.input = arg.replace('--input=', '', 1)
            elif re.match(r'^--source=', arg):
                if self.src is not None:
                    raise InterpretException(f'Argument --source nalezen 2x', ErrorCodes.parameter)
                self.src = arg.replace('--source=', '', 1)
            elif re.match(r'--help', arg):
                PrintHelp()
                exit(ErrorCodes.ok)
            # Argumenty pro STATI rozšíření
            elif re.match(r'^--stats=', arg):
                if self.stats is not None:
                    raise InterpretException(f'Argument --stats nalezen 2x', ErrorCodes.parameter)
                self.stats = arg.replace('--stats=', '', 1)
            elif re.match(r'^--insts', arg):
                self.STATI.append('--insts')
            elif re.match(r'^--vars', arg):
                self.STATI.append('--vars')
            else:
                raise InterpretException(f'Neznámý argument {arg}', ErrorCodes.parameter)
        if self.input is None and self.src is None:
            raise InterpretException('Chybějící povinný argument --input, nebo --source', ErrorCodes.parameter)
        if self.STATI != [] and self.stats is None:
            raise InterpretException('Chybějící argument --stats=', ErrorCodes.parameter)


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
        inp = ''
        if self.source == 'stdin':
            inp = stdin.read()
        else:
            if os.path.isfile(self.source):
                try:
                    with open(self.source, 'r') as file:
                        inp = file.read()
                except OSError:
                    raise InterpretException(f'Soubor: {self.source} nelze otevřít', ErrorCodes.inErr)
            else:
                raise InterpretException(f'Soubor: {self.source} neexistuje.', ErrorCodes.inErr)
        try:
            element = ET.fromstring(inp)
        except ET.ParseError:
            raise InterpretException('Chyba při parsování XML.', ErrorCodes.badxml)
        if element.tag != 'program':
            raise InterpretException('Chybí kořenový element program.', ErrorCodes.badxml)
        if element.attrib['language'] is not None and element.attrib['language'].lower() != 'ippcode20':
            raise InterpretException('Chybí, nebo špatný atribut kořenového elementu.', ErrorCodes.badxml)
        self.XML = element
        try:
            self.LoadXML()
        except IndexError:
            raise InterpretException('Chybějící instrukce', ErrorCodes.syntaxErr)
        except KeyError:
            raise InterpretException('Špatné atributy XML', ErrorCodes.badxml)

    def LoadXML(self):
        for ins in self.XML:
            if ins.tag != 'instruction':
                raise InterpretException(f'Neznámý element {ins.tag} v xml', ErrorCodes.badxml)
            if re.match(r'^label$', ins.attrib['opcode'], re.IGNORECASE):
                if len(ins) == 0:
                    raise InterpretException(f'Chybí argument u instrukce LABEL', ErrorCodes.syntaxErr)
                if ins[0].tag != 'arg1':
                    raise InterpretException(f'Neznámý element {ins[0].tag} v xml', ErrorCodes.badxml)
                if ins[0].attrib['type'] != 'label':
                    raise InterpretException(f'Špatný typ instrukce argumentu u instrukce LABEL', ErrorCodes.syntaxErr)
                if ins[0].text not in self.labels.keys():
                    self.labels[ins[0].text] = ins.attrib['order']
                else:
                    raise InterpretException(f'Návěští: {ins[0].text} bylo použito více než jednou',
                                             ErrorCodes.semanticErr)
            else:
                self.instructions.append(ins)
        self.instructions.sort(key=lambda el: int(el.attrib['order']))
        self.CheckIns()

    def CheckIns(self):
        for ins in self.instructions:
            instruction = ins.attrib['opcode']
            i = self.InsArgs(instruction)
            if i is None:
                raise InterpretException(f'Neznámá instrukce: {instruction}', ErrorCodes.syntaxErr)
            print(f'{instruction}:')
            del instruction

            for index, arg in enumerate(i):
                idx = index + 1
                print(ins[index].attrib['type'])
                if arg == '<var>':
                    if ins[index].tag != f'arg{idx}':
                        raise InterpretException(f'Špatný název elementu {ins[index].tag}', ErrorCodes.badxml)
                    if ins[index].attrib['type'] != 'var':
                        raise InterpretException(f'{ins[index].tag} musí být typu var', ErrorCodes.syntaxErr)
                elif arg == '<symb>':
                    print('\t<symb>')
                elif arg == '<label>':
                    if ins[index].tag != f'arg{idx}':
                        raise InterpretException(f'Špatný název elementu {ins[index].tag}', ErrorCodes.badxml)
                    if ins[index].attrib['type'] != 'label':
                        raise InterpretException(f'{ins[index].tag} musí být typu var', ErrorCodes.syntaxErr)
                elif arg == '<type>':
                    if ins[index].tag != f'arg{idx}':
                        raise InterpretException(f'Špatný název elementu {ins[index].tag}', ErrorCodes.badxml)
                    if ins[index].attrib['type'] != 'type':
                        raise InterpretException(f'{ins[index].tag} musí být typu var', ErrorCodes.syntaxErr)
                else:
                    raise InterpretException(f'Unknown argument {arg}')

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
            "DPRINT": ['<symb>']
        }
        return ins_dict.get(ins, None)
