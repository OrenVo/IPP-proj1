####################################
# Makefile pro IPP interpret       #
# Autor: Vojtěch Ulej (xulejv00)   #
####################################

ARCH = xulejv00.tbz
PACKFILES = makefile interpret.py src test.php readme2.md rozsireni
SRC = interpret.py src/*.py
PYTHON = python3.8

.PHONY: run
run: $(SRC)
	$(PYTHON) interpret.py $(ARGS)
PHONY: pack
pack: clean $(ARCH)

.PHONY: clean
clean:
	rm -rf xulejv00.tbz2 ./__pycache__ src/__pycache__

$(ARCH): $(PACKFILES)
	tar -acvf $@ $^ && rm -rf test.php
test.php: ../test/test.php
	cp ../test/test.php .
.PHONY: test
test: ../test
