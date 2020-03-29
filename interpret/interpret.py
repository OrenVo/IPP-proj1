#! /usr/bin/python3.8
#############################################
# Projekt pro předmět IPP 2019/2020         #
# @author: Vojtěch Ulej (xulejv00)          #
# @file interpret.py                        #
# @brief Tento skript spouští interpretaci  #
#        zdrojového kódu (v XML)            #
#############################################
import src.parse as parse
from src.errors import InterpretException
import sys
"""for arg in sys.argv[1:]:
    print(arg)"""
if __name__ == '__main__':
    args = parse.ARGS()
    try:
        args.ParseArgs()
        XML = parse.XML('stdin' if args.src is None else args.src)
        XML.ReadNCheck()
    except InterpretException as ex:
        ex.LastWords()
