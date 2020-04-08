from src.errors import ErrorCodes, FrameError

class Frame:
    def __init__(self):
        self.frame = {}

    def defvar(self, var):
        var = var.replace('GF@', '').replace('LF@', '').replace('TF@', '')
        if var in self.frame:
            ... # raise frame error redefinition of variable
        self.frame[var] = None

    def move(self, var, value):
        var = var.replace('GF@', '').replace('LF@', '').replace('TF@', '')
        self.frame[var] = value

