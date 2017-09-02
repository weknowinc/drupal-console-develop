# Drupal Console Develop

Drupal Console Develop, this project contains Drupal Console commands to maintain Drupal Console translations and Documentation

### Commands:

### Contribute commands
```
* develop:create:symlinks                Create symlinks between Drupal site and cloned repositories.
```

### Example commands
```
* develop:example
* develop:example:container:aware
```


#### Documentation commands
```
* develop:doc:cheatsheet (gdc)           commands.develop.doc.cheatsheet.description
* develop:doc:dash (gdd)                 Generate the DrupalConsole.docset package for Dash
* develop:doc:data (gdda)                Generate documentations for Commands.
* develop:doc:gitbook (gdg)              Generate documentations for Commands
```
#### Translation commands
```
* develop:translation:cleanup (tc)       Clean up translation files
* develop:translation:pending (tp)       Determine pending translation strings in a language or a specific file in a language
* develop:translation:stats (ts)         Calcuate translation stats
* develop:translation:sync (tsy)         Sync translation files
```

### Install on a site:
```
cd /path/to/drupal/

composer require drupal/console-develop
```

### Install globally:
```
cd ~/.console/extend/

composer require drupal/console-develop

```
* For more information about adding commands globally [Drupal Console Extend](https://github.com/hechoendrupal/drupal-console-extend#drupal-console-extend)
