# VHost Finder

Many websites have virtual hosts that are hidden. This small script aims to help running an automated list of potential hosts.

## Installation 

`$ git clone https://github.com/Shaked/vhost-finder`

`$ cd vhost-finder && comopser update`

## Usage 

Choose `host`, `ip` and `wordlist` file:

`$ php run.php vhost:finder --host=domain.com --ip=1.2.3.4 --wordlist=./wordlist.txt`

You can add custom headers by using a json file: `--headers=./headers.json`.

SSL is activated by default. Use `--ssl=0` to deactivate it. 

The script supports a proxy list new line delimited: `--proxies=./proxies.txt`.

In order to keep things calm, it is possible to sleep between requests (in milliseconds): `--sleepBetweenRequests=1000` 

More @  `$ php run.php vhost:finder --help `

## TODO

- Finish Dockerfile