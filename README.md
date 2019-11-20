# htaccess CLI

A CLI tool to test how .htaccess files behave

### Installation

To start performing analysis on your code, require htaccess CLI in Composer:

```bash
composer require --dev madewithlove/htaccess-cli
```

Composer will install htaccess-cli's executable in its bin-dir which defaults to vendor/bin.


### Usage

If your .htaccess file lives in your root directory, you can run the cli tool using

```bash
htaccess http://localhost/foo
```

Where the url is the request url you want to test your .htaccess file with.

### Note

The tool simulates only one pass through the server, while Apache will do multiple if you get back
on the same domain. This is a feature we might still add in the future, but it's a limitation for now.
