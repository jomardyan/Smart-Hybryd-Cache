PLUGIN_SLUG=smart-hybrid-cache
BUILD_DIR=build
ZIP=$(BUILD_DIR)/$(PLUGIN_SLUG).zip
PHP_FILES=$(shell find $(PLUGIN_SLUG) -name '*.php')

.PHONY: all lint build clean tree

all: lint build

lint:
@for file in $(PHP_FILES); do php -l $$file; done

build: clean lint
@mkdir -p $(BUILD_DIR)
@zip -qr $(ZIP) $(PLUGIN_SLUG) -x '*/.DS_Store'
@echo "Built $(ZIP)"

clean:
@rm -rf $(BUILD_DIR)

tree:
@find $(PLUGIN_SLUG) -maxdepth 3 -type f | sort
