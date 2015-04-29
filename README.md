# recho

Light CLI system to store MESSAGES.

## CLI

To install copy the `recho` file to `/usr/bin`.

The config is located on `~/.recho.conf`

### Use

Simply  `$ recho -m "Installing Apache2"`

## WWW

Check if the module `rewrite` of the Apache2 is enabled.

Create the folder `data`  on the root of the `www` directory.

* Assign the mode 0775 ( `$ sudo chmod -R 0775 data/` ).
* Change the owner to www-data  ( `$ sudo chown -R www-data.www-data data/` ).