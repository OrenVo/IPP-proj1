from sys import stdin
from src.errors import InterpretException, ErrorCodes


class Input:
    def __init__(self, source):
        if source is not stdin:
            try:
                source = open(source, 'r')

            except OSError:
                raise InterpretException(f'Chyba při otvírání input souboru {source}', ErrorCodes.inErr)
        self.source = source

    def READ(self):     # tato funkce načte z stdin, nebo input="file.txt" řádek
        return self.source.readline().strip()

    def close(self):
        if self.source is not stdin:
            self.source.close()
