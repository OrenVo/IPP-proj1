from src.errors import ErrorCodes, FrameError


class Frame:
    def __init__(self):
        self.frame = {}

    def __str__(self):
        str = f'Frame:\n'
        for var in self.frame:
            str += f'\t{var} : {self.frame[var]}\n'
        return str

    def defvar(self, var):
        var = var.replace('GF@', '').replace('LF@', '').replace('TF@', '')
        if var in self.frame:
            raise FrameRedefineError
        self.frame[var] = None

    def move(self, var, value):
        var = var.replace('GF@', '').replace('LF@', '').replace('TF@', '')
        if var not in self.frame:
            raise FrameNotDefineError
        self.frame[var] = value

    def var(self, var):
        var = var.replace('GF@', '').replace('LF@', '').replace('TF@', '')
        if var not in self.frame:
            raise FrameNotDefineError
        return self.frame[var]


class FrameRedefineError(FrameError):
    pass


class FrameNotDefineError(FrameError):
    pass
