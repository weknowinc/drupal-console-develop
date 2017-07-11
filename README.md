# Drupal Console Develop

Drupal Console Develop, this project contains Drupal Console commands to maintain Drupal Console translations and Documentation

### Commands:

#### Documentation commands
```
* generate:doc:cheatsheet (gdc)  Generate a printable cheatsheet for Commands
* generate:doc:dash (gdd)        Generate the DrupalConsole.docset package for Dash
* generate:doc:data (gdda)             Generate documentations for Commands.
* generate:doc:gitbook (gdg)     Generate documentations for Commands
```
#### Translation commands
```
* translation:cleanup (tc)       Clean up translation files
* translation:pending (tp)       Determine pending translation string in a language or a specific file in a language
* translation:stats (ts)         Generate translate stats
* translation:sync (tsy)           Sync translation files
```

### Install on a site:
```
cd /path/to/drupal/

composer require drupal/console-develop
```

### Install globally:
```
cd cd ~/.console/extend/

composer require drupal/console-develop

```
* For more information about adding commands globally [Drupal Console Extend](https://github.com/hechoendrupal/drupal-console-extend#drupal-console-extend)