php-resque message dispatcher for ProophServiceBus
==================================================

php-resque message dispatcher for PSB

[![Build Status](https://travis-ci.org/prooph/psb-php-resque-dispatcher.svg?branch=master)](https://travis-ci.org/prooph/psb-php-resque-dispatcher)

Use [php-resque](https://github.com/chrisboulton/php-resque) as message dispatcher for [ProophServiceBus](https://github.com/prooph/service-bus).

Usage
-----

Check the [example](examples/resque/simple-resque-sample.php). Set up the dispatcher is a straightforward task. Most of
the required components are provided by PSB and php-resque. This package only provides the glue code needed to let both
systems work together.

License
-------

Released under the [New BSD License](https://github.com/prooph/psb-php-resque-dispatcher/blob/master/LICENSE).
