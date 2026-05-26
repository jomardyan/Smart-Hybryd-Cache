PLUGIN_SLUG := smart-hybrid-cache
PLUGIN_DIR := $(PLUGIN_SLUG)
BUILD_DIR := build
PLUGIN_FILE := $(PLUGIN_DIR)/smart-hybrid-cache.php
README_FILE := $(PLUGIN_DIR)/readme.txt
BUMP_SCRIPT := tools/bump-plugin-version.php
ZIP := $(BUILD_DIR)/$(PLUGIN_SLUG).zip
VERSION ?= $(shell sed -nE "s/^ \* Version:[[:space:]]*([^[:space:]]+).*$$/\1/p" $(PLUGIN_FILE) | head -n1)
VERSIONED_ZIP := $(BUILD_DIR)/$(PLUGIN_SLUG)-$(VERSION).zip
PHP_FILES := $(shell find $(PLUGIN_DIR) -name '*.php' -type f | sort)

.PHONY: all check-deps lint build build-versioned release clean tree version set-version help

all: build

help:
	@echo "Available targets:"
	@echo "  make lint                 Validate PHP syntax for plugin files"
	@echo "  make build                Build installable ZIP at $(ZIP)"
	@echo "  make build-versioned      Build versioned ZIP at $(VERSIONED_ZIP)"
	@echo "  make release              Build both standard and versioned ZIPs"
	@echo "  make version              Print detected plugin version"
	@echo "  make set-version VERSION=x.y.z  Update plugin metadata version"
	@echo "  make clean                Remove build artifacts"
	@echo "  make tree                 Print plugin file tree"
	@echo "  make help                 Show this help"

check-deps:
	@command -v php >/dev/null 2>&1 || { echo "Error: php is required."; exit 1; }
	@command -v zip >/dev/null 2>&1 || { echo "Error: zip is required."; exit 1; }
	@test -f $(PLUGIN_FILE) || { echo "Error: plugin file not found: $(PLUGIN_FILE)"; exit 1; }
	@test -f $(README_FILE) || { echo "Error: readme file not found: $(README_FILE)"; exit 1; }
	@test -n "$(VERSION)" || { echo "Error: could not detect plugin version from $(PLUGIN_FILE)"; exit 1; }

version:
	@echo $(VERSION)

lint: check-deps
	@echo "Linting PHP files..."
	@for file in $(PHP_FILES); do \
		php -l $$file || exit 1; \
	done
	@echo "PHP lint passed."

set-version:
	@test -n "$(VERSION)" || { echo "Usage: make set-version VERSION=x.y.z"; exit 1; }
	@php $(BUMP_SCRIPT) "$(VERSION)"
	@echo "Version updated to $(VERSION)"

build: check-deps lint
	@mkdir -p $(BUILD_DIR)
	@echo "Building $(ZIP)"
	@zip -qr $(ZIP) $(PLUGIN_DIR) -x '*/.DS_Store'
	@echo "Built $(ZIP)"

build-versioned: check-deps lint
	@mkdir -p $(BUILD_DIR)
	@echo "Building $(VERSIONED_ZIP)"
	@zip -qr $(VERSIONED_ZIP) $(PLUGIN_DIR) -x '*/.DS_Store'
	@echo "Built $(VERSIONED_ZIP)"

release: clean build build-versioned
	@echo "Release artifacts ready:"
	@echo " - $(ZIP)"
	@echo " - $(VERSIONED_ZIP)"

clean:
	@rm -rf $(BUILD_DIR)
	@echo "Cleaned $(BUILD_DIR)"

tree:
	@find $(PLUGIN_DIR) -maxdepth 3 -type f | sort
