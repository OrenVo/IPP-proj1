#! /usr/bin/python3.8
#############################################
# Projekt pro předmět IPP 2019/2020         #
# @author: Vojtěch Ulej (xulejv00)          #
# @file interpret.py                        #
# @brief Tento skript spouští interpretaci  #
#        zdrojového kódu (v XML)            #
#############################################
import src.parse as parse
from src.errors import InterpretException, eprint, ErrorCodes
from sys import stdin, exc_info
from src.int import Interpret, Nill
from src.input import Input

def stati(file, args, interpret):
    try:
        with open(file, 'w') as STATI:
            for stat in args:
                s = ''
                if stat == '--insts':
                    s = str(interpret.ins_done) + '\n'
                if stat == '--vars':
                    s = str(interpret.vars) + '\n'
                STATI.write(s)
    except OSError:
        eprint(f'Nelze otevřít výstupní soubor pro statistiky {file}', ErrorCodes.outErr)

args = parse.ARGS()
try:
    args.ParseArgs()
    XML = parse.XML(stdin if args.src is None else args.src)
    XML.ReadNCheck()
    XML.LoadIns()
    input = Input(stdin if args.input is None else args.input)
    interpret = Interpret(XML.instructions, XML.labels, input)
    retval = interpret.Interpret()

except InterpretException as ex:
    try:
        if interpret:
            eprint(f'Chyba při provádění instrukce (order, opcode, arg1, arg2 , arg3, type_arg1, type_arg2, '
                   f'type_arg3):\n\t\t {interpret.instructions[interpret.ins_index - 1]}')
    except BaseException:
        pass
    ex.LastWords()
    """except Exception as ex:
    exc_type, exc_obj, exc_tb = exc_info()
    traceback.print_tb(exc_tb, 1)
    exit(ErrorCodes.internalError)"""
except ZeroDivisionError:
    eprint('Dělení nulou')
    exit(ErrorCodes.runtimeBadValue)
else:
    if args.stats is not None:
        stati(args.stats, args.STATI, interpret)
    exit(retval)
