# htaccess CLI

[![Build status](https://github.com/madewithlove/htaccess-cli/workflows/Continious%20Integration/badge.svg)](https://github.com/madewithlove/htaccess-cli/actions?query=branch%3Amaster)
[![Latest Stable Version](https://poser.pugx.org/madewithlove/htaccess-cli/version)](https://packagist.org/packages/madewithlove/htaccess-cli)
[![License](https://poser.pugx.org/madewithlove/htaccess-cli/license)](https://packagist.org/packages/madewithlove/htaccess-cli)
[![codecov](https://codecov.io/gh/madewithlove/htaccess-cli/branch/master/graph/badge.svg)](https://codecov.io/gh/madewithlove/htaccess-cli)

A CLI tool to test how .htaccess files behave.

## Installation

To start performing analysis on your code, require htaccess CLI in Composer:

```bash
composer require --dev madewithlove/htaccess-cli
```

Composer will install htaccess-cli's executable in its bin-dir which defaults to vendor/bin.

### Global installation

```bash
composer global require madewithlove/htaccess-cli
```

Then make sure you have the global Composer binaries directory in your ``PATH``. This directory is platform-dependent, see `Composer documentation <https://getcomposer.org/doc/03-cli.md#composer-home>`_ for details.
This allows you to use the tool as `htaccess` from every location in your system.

## Usage

Run the .htaccess CLI tester from a directory containing a .htaccess file.

```bash
# using global installation
htaccess http://localhost/foo

# using project-specific installation
vendor/bin/htaccess http://localhost/foo
```

Where the url is the request url you want to test your .htaccess file with.

![Output of the htaccess tester](https://user-images.githubusercontent.com/1398405/70325684-d8072600-1832-11ea-99f2-3182c0ac3906.png)

### Usage through Docker

```bash
# install the container
docker pull madewithlove/htaccess-cli

# run the htaccess tester in the current folder
docker run --rm -v $PWD:/app madewithlove/htaccess-cli [url] <options>
```

### Usage as a GitHub Action

Check https://github.com/madewithlove/htaccess-cli-github-action if you want verify how .htaccess files behave in a GitHub Action.

## CLI Options

The following options are available:

```
-r, --referrer[=REFERRER]          The referrer header, used as HTTP_REFERER in apache
-s, --server-name[=SERVER-NAME]    The configured server name, used as SERVER_NAME in apache
-e, --expected-url[=EXPECTED-URL]  When configured, errors when the output url does not equal this url
    --share                        When passed, you'll receive a share url for your test run
-l, --url-list[=URL-LIST]          Location of the yaml file containing your url list
-h, --help                         Display a help message
```

## Usages with multiple url's

To test one htaccess file with multiple url's, you can use a yaml file that contains them.

```yaml
- http://localhost/foo
- http://localhost/bar
```

If you want to pass an expected url for every url you're testing, you can use this yaml structure, where each url maps to an expected url.

```yaml
http://localhost/foo: http://localhost/test
http://localhost/bar: http://localhost/test
```

You can then run the command using

```bash
htaccess --url-list ./url-list.yaml
```

![Output with multiple urls](https://user-images.githubusercontent.com/1398405/72885990-45024c80-3d09-11ea-8c32-da6e490fd51d.png)

### Note

The tool simulates only one pass through the server, while Apache will do multiple if you get back
on the same domain. This is a feature we might still add in the future, but it's a limitation for now.
