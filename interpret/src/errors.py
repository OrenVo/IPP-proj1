from sys import stderr

"""
    Soubor obsahuje:
        funkce eprint - funguje jako print, text je však směrován na stderr
        třída ErrorCodes - obsahuje návratové kódy 
        třída InterpretException - výjimka, kterou vyvolá Interpret v případě chyby. 
"""


def eprint(*args, **kwargs):
    """
    Funkce se chová stejně jako print.
    Výpis je směrován na stderr
    """
    print(*args, file=stderr, **kwargs)


class ErrorCodes:
    ok = 0
    parameter = 10
    inErr = 11
    outErr = 12
    badxml = 31
    syntaxErr = 32
    semanticErr = 52
    runtimeBadType = 53
    runtimeMissingVar = 54
    runtimeMissingFrame = 55
    runtimeMissingValue = 56
    runtimeBadValue = 57
    runtimeBadStringOperation = 58
    internalError = 99


class InterpretException(Exception):
    def __init__(self, msg='', exitcode=99):
        self.msg = msg
        self.ec = exitcode

    def LastWords(self):
        if self.msg != '':
            eprint(self.msg)
        exit(self.ec)


class ParserError(InterpretException):
    pass


class StackError(InterpretException):
    pass


class FrameError(InterpretException):
    pass
