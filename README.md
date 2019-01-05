# Smtp-Client

[![Latest Stable Version](https://poser.pugx.org/harmonyio/smtp-client/v/stable)](https://packagist.org/packages/harmonyio/smtp-client)
[![Build Status](https://travis-ci.org/HarmonyIO/Smtp-Client.svg?branch=master)](https://travis-ci.org/HarmonyIO/Smtp-Client)
[![Build status](https://ci.appveyor.com/api/projects/status/r8sfpm0257an6o47/branch/master?svg=true)](https://ci.appveyor.com/project/PeeHaa/smtp-client/branch/master)
[![Coverage Status](https://coveralls.io/repos/github/HarmonyIO/Smtp-Client/badge.svg?branch=master)](https://coveralls.io/github/HarmonyIO/Smtp-Client?branch=master)
[![License](https://poser.pugx.org/harmonyio/smtp-client/license)](https://packagist.org/packages/harmonyio/smtp-client)

Async SMTP client

Requirements

- PHP 7.3
  - ext-hash
  - ext-json

In addition for non-blocking contexts one of the following event libraries should be installed:

- [ev](https://pecl.php.net/package/ev)
- [event](https://pecl.php.net/package/event)
- [php-uv](https://github.com/bwoebi/php-uv)

## Installation

```
composer require harmonyio/smtp-client
```

## Implementation

### Authentication

The following authentication methods are currently implemented:

- `PLAIN`
- `LOGIN`
- `CRAM-MD5`

### SMTP Extensions

The following SMTP extensions are currently implemented:

- Authentication (`AUTH`)
- MessageSizeDeclaration (`SIZE`)
