from src.errors import StackError


class Stack:
    def __init__(self):
        self.data = []

    def __str__(self):
        return str(reversed(self.data))

    def push(self, value):
        self.data.append(value)

    def pop(self):
        if len(self.data) == 0:
            raise StackError
        ret = self.data[-1]
        del self.data[-1]
        return ret

    def top(self):
        if len(self.data) == 0:
            raise StackError
        return self.data[-1]
