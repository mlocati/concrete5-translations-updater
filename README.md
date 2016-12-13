# concrete5 Translations Updater

Based on the great [Core Translation Updater](https://github.com/hissy/addon_core_translation) package by @hissy, this concrete5 package allows you to update both the concrete5 translations, as well as the translations of the packages listed [here](http://concrete5.github.io/package-translations/).

## Installation

Simply [download this file](https://github.com/mlocati/concrete5-translations-updater/archive/master.zip) and extract its contents in the `packages` folder of your concrete5 installation (renaming the extracted folder to `translations_updater`).

Otherwise you can also use git:

```sh
git clone https://github.com/mlocati/concrete5-translations-updater.git packages/translations_updater
```

Once you have your local copy of Translation Updater, simply install it in the usual ways:
1. via the concrete5 Dashboard page `Extend concrete5` > `Add Functionality`
2. or via the [c5:package-install](http://documentation.concrete5.org/developers/appendix/cli-commands#c5-package-install) CLI command:  
  ```sh
  concrete/bin/concrete5 c5:package-install translations_updater  
  ```

## Usage

Once you have installed Translations Updater, go to the Dashboard of your concrete5 installation, and browse to `System & Settings` > `Multilingual` > `Update Translations`.

In that page you'll be able to:

- update on the fly the language files
- download the compiled translation files (in gettext `.mo` format)
- download the source translation files (in gettext `.po` format)
