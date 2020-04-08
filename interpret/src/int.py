from src.errors import InterpretException, ErrorCodes, eprint
from src.stack import Stack


class Interpret:
    def __init__(self, instructions, labels):
        self.instructions = instructions
        self.labels = labels
        self.index = 0
        self.stack = []
        self.frame_stack = []

    def Interpret(self):
        while True:
            if self.index == len(self.instructions):
                exit(ErrorCodes.ok)
            if self.instructions[self.index][1] == 'EXIT':
                try:
                    retval = int(self.instructions[self.index][2])
                    if not 0 <= retval <= 49:
                        raise InterpretException(f'Špatný rozsah hodnot u instrukce EXIT <symb>: {retval}',
                                                 ErrorCodes.runtimeMissingValue)
                except ValueError:
                    raise InterpretException('U instrukce EXIT nenalezena celočíselná hodnota',
                                                 ErrorCodes.runtimeMissingValue)
                else:
                    exit(retval)
            self.DoInstruction(self.instructions[self.index])

    def DoInstruction(self, instruction):
        self.index += 1

    def Jump(self, label):
        ...
