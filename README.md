This version of Pagenode is **NOT** supported. Its only here for archival purposes.

Please use https://github.com/phoboslab/pagenode instead.

Read about more about why this exists here: https://phoboslab.org/log/2018/12/pagenode


## Usage

If you want to try this version of Pagenode, clone this repository and start a PHP WebServer in this directory:

```
php -S localhost:8080
```

You should then be able to go to http://localhost:8080 and create a first user account.

Have a look at the `index.php` - it defines just one type "Article". You can easily define other types by extending the `Node` class. See `pagenode/lib/field.php` for all available field types. These will be automatically exposed in the administration interface.