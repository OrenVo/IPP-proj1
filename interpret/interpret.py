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
from src.int import Interpret
import traceback

if __name__ == '__main__':
    args = parse.ARGS()
    try:
        args.ParseArgs()
        XML = parse.XML(stdin if args.src is None else args.src)
        XML.ReadNCheck()
        XML.LoadIns()
        interpret = Interpret(XML.instructions, XML.labels, stdin if args.input is None else args.input)
        interpret.Interpret()
    except InterpretException as ex:
        exc_type, exc_obj, exc_tb = exc_info()
        fname = os.path.split(exc_tb.tb_frame.f_code.co_filename)[1]
        eprint(f'Exception raised in file {exc_info} on line: {exc_tb.tb_lineno}')
        ex.LastWords()
    """except Exception as ex:
        exc_type, exc_obj, exc_tb = exc_info()
        traceback.print_tb(exc_tb, 1)
        exit(ErrorCodes.internalError)"""
