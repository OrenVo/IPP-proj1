from src.errors import ErrorCodes, FrameError


class Frame:
    def __init__(self):
        self.frame = {}

    def __str__(self):
        string = f'Frame:\n'
        for var in self.frame:
            string += f'\t{var} : {self.frame[var]}\n'
        return string

    def defvar(self, var):
        var = var.replace('GF@', '').replace('LF@', '').replace('TF@', '')
        if var in self.frame:
            raise FrameRedefineError(f'Redefinice proměnné{var}', ErrorCodes.semanticErr)
        self.frame[var] = None

    def move(self, var, value):
        var = var.replace('GF@', '').replace('LF@', '').replace('TF@', '')
        if var not in self.frame:
            raise FrameNotDefineError(f'Proměnná {var} nebyla definována', ErrorCodes.runtimeMissingVar)
        self.frame[var] = value

    def var(self, var):
        var = var.replace('GF@', '').replace('LF@', '').replace('TF@', '')
        if var not in self.frame:
            raise FrameNotDefineError(f'Proměnná {var} nebyla definována', ErrorCodes.runtimeMissingVar)
        elif self.frame[var] is None:
            raise FrameEmptyVariable(f'Čtení prázdné proměnné {var}', ErrorCodes.runtimeMissingValue)
        return self.frame[var]


class FrameRedefineError(FrameError):
    pass


class FrameNotDefineError(FrameError):
    pass


class FrameEmptyVariable(FrameError):
    pass