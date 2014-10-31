php-resque message dispatcher for ProophServiceBus
==================================================

[![Build Status](https://travis-ci.org/prooph/psb-php-resque-dispatcher.svg?branch=master)](https://travis-ci.org/prooph/psb-php-resque-dispatcher)

Use [php-resque](https://github.com/chrisboulton/php-resque) as message dispatcher for [ProophServiceBus](https://github.com/prooph/service-bus).

# Installation

You can install the dispatcher via composer by adding `"prooph/psb-php-resque-dispatcher": "~0.1"` as requirement to your composer.json.

Usage
-----

Check the [example](examples/resque/simple-resque-sample.php). Set up the dispatcher is a straightforward task. Most of
the required components are provided by PSB and php-resque. This package only provides the glue code needed to let both
systems work together.

# Support

- Ask questions on [prooph-users](https://groups.google.com/forum/?hl=de#!forum/prooph) google group.
- File issues at [https://github.com/prooph/psb-php-resque-dispatcher/issues](https://github.com/prooph/psb-php-resque-dispatcher/issues).

# Contribute

Please feel free to fork and extend existing or add new features and send a pull request with your changes!
To establish a consistent code quality, please provide unit tests for all your changes and may adapt the documentation.

License
-------

Released under the [New BSD License](https://github.com/prooph/psb-php-resque-dispatcher/blob/master/LICENSE).
