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

![Screenshot 2019-11-21 at 12 28 53](https://user-images.githubusercontent.com/1398405/69334214-8cf9ea00-0c5a-11ea-8ee8-06f397719289.png)

### Note

The tool simulates only one pass through the server, while Apache will do multiple if you get back
on the same domain. This is a feature we might still add in the future, but it's a limitation for now.
