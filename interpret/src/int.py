from src.errors import InterpretException, ErrorCodes, eprint
from src.stack import Stack
from src.memory_frames import Frame
from src.input import Input
import re


class Interpret:
    def __init__(self, instructions, labels, inp):
        self.instructions = instructions    # List tuplů s instrukcemi
        self.labels = labels                # Dictionary návěstí s přiřazeným indexem
        self.ins_index = 0
        self.input = Input(inp)
        self.stack = Stack()
        self.frame_stack = Stack()
        self.call_stack = Stack()
        self.GF = Frame()
        self.LF = None
        self.TF = None

    def Interpret(self):
        while True:
            if self.ins_index >= len(self.instructions):
                exit(ErrorCodes.ok)
            if self.instructions[self.ins_index][1] == 'EXIT':
                try:
                    retval = int(self.instructions[self.ins_index][2])
                    if not 0 <= retval <= 49:
                        raise InterpretException(f'Špatný rozsah hodnot u instrukce EXIT <symb>: {retval}',
                                                 ErrorCodes.runtimeMissingValue)
                except ValueError:
                    raise InterpretException('U instrukce EXIT nenalezena celočíselná hodnota',
                                             ErrorCodes.runtimeMissingValue)
                else:
                    exit(retval)
            self.DoInstruction(self.instructions[self.ins_index])

    def DoInstruction(self, instruction):
        self.ins_index += 1
        if instruction[1] == 'DEFVAR':
            if instruction[2] is None:
                ...  # Chyba syntax
            elif re.match(r'^TF@', instruction[2], re.IGNORECASE):
                if self.TF is not None:
                    ...
                else:
                    raise InterpretException(f'Neexistuje rámec TF', ErrorCodes.runtimeMissingFrame)
            elif re.match(r'^GF@', instruction[2], re.IGNORECASE):
                ...
            elif re.match(r'^LF@', instruction[2], re.IGNORECASE):
                if self.TF is not None:
                    ...
                else:
                    raise InterpretException(f'Neexistuje rámec TF', ErrorCodes.runtimeMissingFrame)

    def Jump(self, label):
        ...
