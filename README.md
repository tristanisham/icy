# Icy (I see)

Icy is a PHP static analyzer designed to prioritize developer experience.
It is very pre-alpha software, so integrate at your own risk. That being said,
the developer is very open to contributions and will respond quickly.

- https://github.com/tristanisham/icy

# Install
```sh
composer global require --dev tristan/icy
```

# Commands

## Usage:

command [options] [arguments]

## Options:

-h, --help Display help for the given command. When no command is given display help for the list command
-q, --quiet Do not output any message
-V, --version Display this application version
--ansi|--no-ansi Force (or disable --no-ansi) ANSI output
-n, --no-interaction Do not ask any interactive question
-v|vv|vvv, --verbose Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

## Available commands:

completion Dump the shell completion script
help Display help for a command
list List commands

### sa

sa:gen-import-map  [sa:im] generates an import map for all PHP files in a directory.
