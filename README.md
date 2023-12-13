# Custom Questions

A plugin to add custom questions to submission wizard

## Compatibility

The latest release of this plugin is compatible with the following PKP applications:

* OPS 3.4.0

## Installation

1. Enter the administration area of ​​your application and navigate to Settings > Website > Plugins > Upload a new plugin.
2. Under Upload file select the file customQuestions.tar.gz.
3. Click Save and the plugin will be installed on your website.

## Running Tests

### Unit Tests

To execute the unit tests, run the following command from root of the PKP Appplication directory:
```bash
lib/pkp/lib/vendor/phpunit/phpunit/phpunit -c lib/pkp/tests/phpunit.xml plugins/generic/customQuestions/tests
```

### Integration Tests

To execute Cypress integration tests, run the following command from root of the PKP Appplication directory:
```bash
$(npm bin)/cypress run --config '{"specPattern":["plugins/generic/customQuestions/cypress/tests/functional/**/*.cy.js"]}'
```

# License
__This plugin is licensed under the GNU General Public License v3.0__

__Copyright (c) 2023 Lepidus Tecnologia__

__Copyright (c) 2023 SciELO__
