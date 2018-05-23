Log Lemming - The web log viewer
================================


Log lemming is a small self-contained* PHP site to view your linux server logfiles from your browser.

![preview](README_FILES/main.png)

## Installation

### Step 1

First you need to have [simpleloglist](https://github.com/Mikescher/SimpleLogList/releases) available in your PATH (e.g. copy it to `/usr/local/sbin`).  
Also you need to configure sudo so that calling `sudo simpleloglist` doesn't require a password:

~~~
$> sudo visudo

# ...
ALL ALL=NOPASSWD: /usr/local/sbin/simpleloglist
# ...
~~~

Simpleloglist is needed as a wrapper to read and list the logfiles from your webserver process (which - hopefully - does not have root rights and cannot read all log files by default)

I know, executing some unknown program with sudo can make you uncomfortable. So I recommend to read the source of [simpleloglist](https://github.com/Mikescher/SimpleLogList) (it's around 300 LOC) and compile it for yourself.

### Step 2

Now simply drop the php/js/css files from this repository somewhere in your webspace and navigate to them in your browser

> **WARNING**
> *Obviously* there was a reason some log files are not world-readbable. They can, and will, contains sensitive information that you should not simply broadcast to every crawler in the world.  
> So please put the script behind some kind of password protection, even if its only a .htaccess file


## Features

 - List all log files in `/var/logs/*` 
 - Combine log rotation files into one entry (e.g. `auth.log`, `auth.log.1`, `auth.log.2`, ...)
 - read gzipped log files
 - read gzipped directories (non-recursive)
 - automatically refresh the currently display log file (aka `tail -f`)


## Final warning

This project uses three different languages and their corresponding standard libraries:

 - PHP, with which I have a bit of experience (but where I hacked most of the code together).
 - Rust, where I have **no experience** (prior to this project). I just literally changed code at random until the borrow-checker shut up.
 - And finally javascript, which I hate with a burning passion.


So, in summary: The code works but don't expect too much in the code quality category.