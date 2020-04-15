from src.errors import InterpretException, ErrorCodes, eprint
from src.stack import Stack, StackError
from src.memory_frames import Frame, FrameEmptyVariable
from src.input import Input
import re


class Interpret:
    def __init__(self, instructions, labels, inp):
        self.instructions = tuple(instructions)  # Tuple tuplů s instrukcemi
        self.labels = labels  # Dictionary návěstí s přiřazeným indexem
        self.ins_index = 0
        self.ins_done = 0  # Počet vykonaných instrukcí
        self.vars = 0
        self.input = inp
        self.stack = Stack()
        self.frame_stack = Stack()
        self.call_stack = Stack()
        self.GF = Frame()
        self.LF = None
        self.TF = None

    def __del__(self):
            self.input.close()

    def __str__(self):
        string = f'Pozice v kódu: order, instrukce, arg1, arg2, arg3\n'
        string += f'               {self.instructions[self.index - 1][0]}, {self.instructions[self.index - 1][1]}, {self.instructions[self.index - 1][2]}, {self.instructions[self.index - 1][3]}, {self.instructions[self.index - 1][4]}\n'
        string += f'GF:\n'
        string += self.GF.__str__()
        if self.LF is not None:
            string += f'LF:\n'
            string += self.LF.__str__()
        else:
            string += f'LF nebyl vytvořen\n'
        if self.TF is not None:
            string += f'TF:\n'
            string += self.TF.__str__()
        else:
            string += f'TF nebyl vytvořen\n'
        string += f'Zásobník paměťových rámců:\n'
        for frame in reversed(self.frame_stack.data):
            string += frame.__str__()
        return string

    def Interpret(self):
        while True:
            if self.ins_index >= len(self.instructions):
                return 0
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
                    return retval
            self.DoInstruction(self.instructions[self.ins_index])

    def DoInstruction(self, instruction):
        self.ins_done += 1
        self.ins_index += 1
        order, ins, arg1, arg2, arg3, types = instruction
        if ins == 'DEFVAR':
            self.vars += 1
            if arg1 is None:
                raise InterpretException(f'Chybí argument u instrukce {ins} č. {order}')
            self.var(arg1).defvar(arg1)
        elif ins == "CREATEFRAME":
            self.TF = Frame()
        elif ins == "PUSHFRAME":
            if self.TF is None:
                raise InterpretException(f'Nebyl vytvořen TF, aby mohl být vložen do zásobníku, instrukce č.: {order}',
                                         ErrorCodes.runtimeMissingFrame)
            else:
                self.frame_stack.push(self.TF)
                self.LF = self.TF
                self.TF = None
        elif ins == "POPFRAME":
            try:
                self.TF = self.frame_stack.pop()
                self.LF = self.frame_stack.top()
            except StackError:
                raise InterpretException(f'Prázdný zásobník paměťových rámců instrukce č. {order}',
                                         ErrorCodes.runtimeMissingFrame)
        elif ins == "POPS":
            try:
                self.var(arg1).move(arg1, self.stack.pop())
            except StackError:
                raise InterpretException(f'Chybí hodnota na vrcholu zásobníku instrukce {ins}č. {order}',
                                         ErrorCodes.runtimeMissingValue)
        elif ins == "ADD":
            value = self.do_op('+', self.symb(arg2, types[1]), self.symb(arg3, types[2]))
            self.var(arg1).move(arg1, value)
        elif ins == "SUB":
            value = self.do_op('-', self.symb(arg2, types[1]), self.symb(arg3, types[2]))
            self.var(arg1).move(arg1, value)
        elif ins == "MUL":
            value = self.do_op('*', self.symb(arg2, types[1]), self.symb(arg3, types[2]))
            self.var(arg1).move(arg1, value)
        elif ins == "IDIV":
            value = self.do_op('//', self.symb(arg2, types[1]), self.symb(arg3, types[2]))
            self.var(arg1).move(arg1, value)
        elif ins == "DIV":
            value = self.do_op('/', self.symb(arg2, types[1]), self.symb(arg3, types[2]))
            self.var(arg1).move(arg1, value)
        elif ins == "LT":
            value = self.do_op('<', self.symb(arg2, types[1]), self.symb(arg3, types[2]))
            self.var(arg1).move(arg1, value)
        elif ins == "GT":
            value = self.do_op('>', self.symb(arg2, types[1]), self.symb(arg3, types[2]))
            self.var(arg1).move(arg1, value)
        elif ins == "EQ":
            value = self.do_op('==', self.symb(arg2, types[1]), self.symb(arg3, types[2]))
            self.var(arg1).move(arg1, value)
        elif ins == "AND":
            value = self.do_op('&', self.symb(arg2, types[1]), self.symb(arg3, types[2]))
            self.var(arg1).move(arg1, value)
        elif ins == "OR":
            value = self.do_op('|', self.symb(arg2, types[1]), self.symb(arg3, types[2]))
            self.var(arg1).move(arg1, value)
        elif ins == "STRI2INT":
            string = self.symb(arg2, types[1])
            integer = self.symb(arg3, types[1])
            if not isinstance(string, str) or not isinstance(integer, int):
                raise InterpretException(f'Špatný typ', ErrorCodes.runtimeBadType)
            try:
                self.var(arg1).move(arg1, ord(string[integer]))
            except IndexError:
                raise InterpretException('Index mimo velikost pole', ErrorCodes.runtimeBadStringOperation)
        elif ins == "CONCAT":
            str1, str2 = self.symb(arg2, types[1]), self.symb(arg3, types[2])
            if not isinstance(str1, str) and type(str1) is not type(str2):
                raise InterpretException(f'Špatný typ operandů', ErrorCodes.runtimeBadType)
            self.var(arg1).move(arg1, str1 + str2)
        elif ins == "GETCHAR":
            string, index = self.symb(arg2, types[1]), self.symb(arg3, types[2])
            if not isinstance(string, str) and not isinstance(index, int):
                raise InterpretException('Špatný typ operandů')
            try:
                self.var(arg1).move(arg1, string[index])
            except IndexError:
                raise InterpretException(f'Index mimo rozsah řetězce', ErrorCodes.runtimeBadStringOperation)
        elif ins == "SETCHAR":
            str1, index, char = self.var(arg1).var(arg1), self.symb(arg2, types[1]), self.symb(arg3, types[2])
            if not isinstance(str1, str) or not isinstance(index, int) or not isinstance(char, str):
                raise InterpretException(f'Špatný typ operandů', ErrorCodes.runtimeBadType)
            elif char == '' or index > len(str1) - 1:
                raise InterpretException('Index mimo rozsah řetězce', ErrorCodes.runtimeBadStringOperation)
            str1 = list(str1)
            str1[index] = char
            self.var(arg1).move(arg1, ''.join(str1))
        elif ins == "READ":
            value = self.input.READ()
            try:
                if arg2 == 'string':
                    value = str(value)
                elif arg2 == 'int':
                    value = int(value)
                elif arg2 == 'bool':
                    value = True if value == 'true' else False
                elif arg2 == 'float':
                    value = float.fromhex(value)
                else:
                    value = Nill()
            except BaseException:
                value = Nill()
            self.var(arg1).move(arg1, value)
        elif ins == "MOVE":
            frame = self.var(arg1)
            try:
                value = self.symb(arg2, types[1])
            except (ValueError, TypeError):
                raise InterpretException(f'Špatný formát konstanty {arg2}, instrukce {ins} č. {order}',
                                         ErrorCodes.runtimeBadType)
            frame.move(var=arg1, value=value)
        elif ins == "INT2CHAR":
            val = self.symb(arg2, types[1])
            if not isinstance(val, int):
                raise InterpretException(f'Špatný datový typ', ErrorCodes.runtimeBadValue)
            try:
                self.var(arg1).move(arg1, chr(val))
            except ValueError:
                raise InterpretException(f'Chyba převodu int2char', ErrorCodes.runtimeBadStringOperation)
        elif ins == "INT2FLOAT":
            integer = self.symb(arg2, types[1])
            if not isinstance(integer, int) or types[1] == 'bool':
                raise InterpretException(f'Špatný typ operandů', ErrorCodes.runtimeBadType)
            self.var(arg1).move(arg1, float(integer))
        elif ins == "FLOAT2INT":
            flt = self.symb(arg2, types[1])
            if not isinstance(flt, float):
                raise InterpretException(f'Špatný typ operandů', ErrorCodes.runtimeBadType)
            self.var(arg1).move(arg1, int(flt))
        elif ins == "NOT":
            value = self.do_op('!', self.symb(arg2, types[1]), self.symb(arg3, types[2]))
            self.var(arg1).move(arg1, value)
        elif ins == "STRLEN":
            string = self.symb(arg2, types[1])
            if not isinstance(string, str):
                raise InterpretException(f'Špatný typ operandů', ErrorCodes.runtimeBadType)
            self.var(arg1).move(arg1, len(string))
        elif ins == "TYPE":
            if types[1] != 'var':
                self.var(arg1).move(arg1, types[1])
            else:
                try:
                    val = self.var(arg1).var(arg1)
                except FrameEmptyVariable:
                    self.var(arg1).move(arg1, '')
                else:
                    if isinstance(val, str):
                        self.var(arg1).move(arg1, 'string')
                    elif isinstance(val, int):
                        self.var(arg1).move(arg1, 'int')
                    elif isinstance(val, float):
                        self.var(arg1).move(arg1, 'float')
                    elif isinstance(val, Nill):
                        self.var(arg1).move(arg1, 'nill')
                    elif isinstance(val, bool):
                        self.var(arg1).move(arg1, 'bool')
        elif ins == "CALL":
            self.call_stack.push(self.ins_index)
            self.Jump(arg1)
        elif ins == "JUMP":
            self.Jump(arg1)
        elif ins == "JUMPIFEQ":
            val1, val2 = self.symb(arg2, types[1]), self.symb(arg3, types[2])
            if self.do_op('==', val1, val2):
                self.Jump(arg1)
        elif ins == "JUMPIFNEQ":
            val1, val2 = self.symb(arg2, types[1]), self.symb(arg3, types[2])
            if not self.do_op('==', val1, val2):
                self.Jump(arg1)
        elif ins == "PUSHS":
            if arg1 is None:
                raise InterpretException(f'Chybí argument 1 u instrukce {ins} č. {order}',
                                         ErrorCodes.runtimeBadType)
            else:
                try:
                    value = self.symb(arg1, types[0])
                except (ValueError, TypeError):
                    raise InterpretException(f'Špatný formát konstanty {arg1}, instrukce {ins} č. {order}',
                                             ErrorCodes.runtimeBadType)
            self.stack.push(value)
        elif ins == "WRITE":
            value = self.symb(arg1, types[0])
            if value is True:
                value = 'true'
            elif value is False:
                value = 'false'
            elif isinstance(value, float):
                value = float.hex(value)
            print(value, end='')
        elif ins == "DPRINT":
            eprint(self.symb(arg1, types[0]))
        elif ins == "RETURN":
            self.ins_index = self.call_stack.pop()
        elif ins == "BREAK":
            eprint(self)
        elif ins == "ADDS":  # Zásobníkové instrukce ADDS/SUBS/MULS/DIVS/IDIVS
            val1, val2 = self.stack.pop(), self.stack.pop()
            self.stack.push(self.do_op('+', val1, val2))
        elif ins == "SUBS":
            val2, val1 = self.stack.pop(), self.stack.pop()
            self.stack.push(self.do_op('-', val1, val2))
        elif ins == "MULS":
            val1, val2 = self.stack.pop(), self.stack.pop()
            self.stack.push(self.do_op('*', val1, val2))
        elif ins == "DIVS":
            val2, val1 = self.stack.pop(), self.stack.pop()
            self.stack.push(self.do_op('/', val1, val2))
        elif ins == "IDIVS":
            val2, val1 = self.stack.pop(), self.stack.pop()
            self.stack.push(self.do_op('//', val1, val2))
        elif ins == "LTS":  # LTS/GTS/EQS
            val2, val1 = self.stack.pop(), self.stack.pop()
            self.stack.push(self.do_op('>', val2, val1))
        elif ins == "GTS":
            val2, val1 = self.stack.pop(), self.stack.pop()
            self.stack.push(self.do_op('<', val2, val1))
        elif ins == "EQS":
            val1, val2 = self.stack.pop(), self.stack.pop()
            self.stack.push(self.do_op('==', val1, val2))
        elif ins == "ANDS":  # ANDS/ORS/NOTS
            val1, val2 = self.stack.pop(), self.stack.pop()
            self.stack.push(self.do_op('&', val1, val2))
        elif ins == "ORS":
            val1, val2 = self.stack.pop(), self.stack.pop()
            self.stack.push(self.do_op('|', val1, val2))
        elif ins == "NOTS":
            val1 = self.stack.pop()
            self.stack.push(self.do_op('!', val1))
        elif ins == "INT2FLOATS":  # INT2FLOATS/FLOAT2INTS/INT2CHARS/STRI2INTS
            integer = self.stack.pop()
            if not isinstance(integer, int):
                raise InterpretException(f'Špatný typ operandů', ErrorCodes.runtimeBadType)
            self.stack.push(float(integer))
        elif ins == "FLOAT2INTS":
            flt = self.stack.pop()
            if not isinstance(flt, float):
                raise InterpretException(f'Špatný typ operandů', ErrorCodes.runtimeBadType)
            self.stack.push(flt)
        elif ins == "INT2CHARS":
            val = self.stack.pop()
            if not isinstance(val, int):
                raise InterpretException(f'Špatný datový typ', ErrorCodes.runtimeBadType)
            try:
                self.stack.push(chr(val))
            except ValueError:
                raise InterpretException(f'Chyba převodu int2char', ErrorCodes.runtimeBadStringOperation)
        elif ins == "STRI2INTS":
            index = self.stack.pop()
            string = self.stack.pop()
            if not isinstance(string, str) or not isinstance(index, int):
                raise InterpretException(f'Špatný typ', ErrorCodes.runtimeBadType)
            try:
                self.stack.push(ord(string[index]))
            except IndexError:
                raise InterpretException('Index mimo velikost pole', ErrorCodes.runtimeBadStringOperation)
        elif ins == "JUMPIFEQS":  # JUMPIFEQS/JUMPIFNEQS <label>
            val1, val2 = self.stack.pop(), self.stack.pop()
            if self.do_op('==', val1, val2):
                self.Jump(arg1)
        elif ins == "JUMPIFNEQS":
            val1, val2 = self.stack.pop(), self.stack.pop()
            if not self.do_op('==', val1, val2):
                self.Jump(arg1)
        elif ins == "CLEARS":
            del self.stack
            self.stack = Stack()

    def Jump(self, label):
        if label not in self.labels:
            raise InterpretException('Nedefinované návěstí', ErrorCodes.semanticErr)
        self.ins_index = self.labels[label]

    def var(self, var):
        order, instruction, _, _, _, _ = self.instructions[self.ins_index - 1]
        if re.match(r'^TF@', var, re.IGNORECASE):
            if self.TF is not None:
                return self.TF
            else:
                raise InterpretException(f'Neexistuje rámec TF \n Instrukce: {instruction} č. {order}',
                                         ErrorCodes.runtimeMissingFrame)
        elif re.match(r'^GF@', var, re.IGNORECASE):
            return self.GF
        elif re.match(r'^LF@', var, re.IGNORECASE):
            if self.LF is not None:
                return self.LF
            else:
                raise InterpretException(f'Neexistuje rámec LF\n Instrukce: {instruction} č. {order}',
                                         ErrorCodes.runtimeMissingFrame)

    def symb(self, string, typ):
        if typ == 'int':
            return int(string)
        elif typ == 'float':
            return float.fromhex(string)
        elif typ == 'string':
            return string
        elif string == 'nil':
            return Nill()
        elif re.match(r'(true)|(false)', string):
            return True if string == 'true' else False
        elif typ == 'var':
            return self.var(string).var(string)

    @staticmethod
    def do_op(op, val1, val2=None):
        if op == '+':
            if not isinstance(val1, int) and not isinstance(val1, float):
                raise InterpretException(f'Špatný typ operandu {val1}', ErrorCodes.runtimeBadType)
            if not isinstance(val2, int) and not isinstance(val2, float):
                raise InterpretException(f'Špatný typ operandu {val1}', ErrorCodes.runtimeBadType)
            if not type(val1) == type(val2):
                raise InterpretException(f'Špatný typ operandu {val1}', ErrorCodes.runtimeBadType)
            return val1 + val2
        elif op == '-':
            if not isinstance(val1, int) and not isinstance(val1, float):
                raise InterpretException(f'Špatný typ operandu {val1}', ErrorCodes.runtimeBadType)
            if not isinstance(val2, int) and not isinstance(val2, float):
                raise InterpretException(f'Špatný typ operandu {val1}', ErrorCodes.runtimeBadType)
            if not type(val1) == type(val2):
                raise InterpretException(f'Špatný typ operandu {val1}', ErrorCodes.runtimeBadType)
            return val1 - val2
        elif op == '*':
            if not isinstance(val1, int) and not isinstance(val1, float):
                raise InterpretException(f'Špatný typ operandu {val1}', ErrorCodes.runtimeBadType)
            if not isinstance(val2, int) and not isinstance(val2, float):
                raise InterpretException(f'Špatný typ operandu {val1}', ErrorCodes.runtimeBadType)
            if not type(val1) == type(val2):
                raise InterpretException(f'Špatný typ operandu {val1}', ErrorCodes.runtimeBadType)
            return val1 * val2
        elif op == '/':
            if not isinstance(val1, float):
                raise InterpretException(f'Špatný typ operandu {val1}', ErrorCodes.runtimeBadType)
            if not isinstance(val2, float):
                raise InterpretException(f'Špatný typ operandu {val1}', ErrorCodes.runtimeBadType)
            return val1 / val2
        elif op == '//':
            if not isinstance(val1, int):
                raise InterpretException(f'Špatný typ operandu {val1}', ErrorCodes.runtimeBadType)
            if not isinstance(val2, int):
                raise InterpretException(f'Špatný typ operandu {val1}', ErrorCodes.runtimeBadType)
            return val1 // val2
        elif op == '<':
            if not type(val1) == type(val2):
                raise InterpretException(f'Špatný typ operandu {val1}', ErrorCodes.runtimeBadType)
            else:
                return val1 < val2
        elif op == '>':
            if not type(val1) == type(val2):
                raise InterpretException(f'Špatný typ operandu {val1}', ErrorCodes.runtimeBadType)
            else:
                return val1 > val2
        elif op == '==':
            if isinstance(val1, Nill) or isinstance(val2, Nill):
                return isinstance(val1, Nill) and isinstance(val2, Nill)
            if type(val1) != type(val2):
                raise InterpretException(f'Špatný typ operandu {val1}', ErrorCodes.runtimeBadType)
            else:
                return val1 == val2
        elif op == '&':
            if type(val1) != type(val2) or not isinstance(val1, bool):
                raise InterpretException(f'Špatný typ operandu {val1}', ErrorCodes.runtimeBadType)
            else:
                return val1 and val2
        elif op == '|':
            if type(val1) != type(val2) or not isinstance(val1, bool):
                raise InterpretException(f'Špatný typ operandu {val1}', ErrorCodes.runtimeBadType)
            else:
                return val1 or val2
        elif op == '!':
            if not isinstance(val1, bool):
                raise InterpretException(f'Špatný typ operandu {val1}', ErrorCodes.runtimeBadType)
            else:
                return not val1
        else:
            raise InterpretException


class Nill:
    def __init__(self):
        pass

    def __str__(self):
        return ''
