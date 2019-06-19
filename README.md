# Ziggy - commandline tools for Akeneo
*powered by Experius*

The ziggy cli tools provides some handy tools to work with Akeneo from the command line.

## Authors

 * Mr. Lewis - <https://github.com/lewisvoncken>

## Installation

There are three ways to install the tools:

### Download and Install Phar File

Download the latest stable Experius Ziggy phar-file from the file-server_:

    wget https://raw.githubusercontent.com/experius/ziggy/master/ziggy.phar

or if you prefer to use Curl:

    curl -O https://raw.githubusercontent.com/experius/ziggy/master/ziggy.phar

Now you can make the phar-file executable:

    chmod +x ./ziggy.phar

The base-installation is now complete and you can verify it:

    ./ziggy.phar --version

The command should execute successfully and show you the version number of Ziggy like:

    ziggy 1.0.0-beta1 by Experius

You now have successfully installed Ziggy! You can tailor the installation further like installing it system-wide and
enable autocomplete - read on for more information about these and other features.

If you want to use the command system wide you can copy it to `/usr/local/bin`.

    sudo cp ./ziggy.phar /usr/local/bin/

**Debian / suhosin:**

On some Debian systems with compiled in suhosin the phar extension must be added to a whitelist.

Add this to your php.ini file:

    suhosin.executor.include.whitelist="phar"

**You don't like the filename?**

Just rename it to whatever you want. Or better: create an alias so that the original command name still works. This can
be useful if you exchange scripts that are making use of ziggy with other users as the canonical name is
`ziggy.phar`, Some common aliases amongst the user-base are `ziggy` or just `zy` even.


## Usage / Commands

All commands try to detect the current Akeneo root directory.
If you have multiple Akeneo installations you must change your working directory to the preferred installation.

You can list all available commands by::

    $ ziggy.phar list


If you don't have the .phar file installed system wide you can call it with the PHP CLI interpreter::

   php ziggy.phar list


Global config parameters:

    --root-dir
      Force Akeneo root dir. No auto detection.
    --skip-config
      Do not load any custom config.
    --skip-root-check
      Do not check if ziggy runs as root.
      
### List PIM users

    $ ziggy.phar pim:user:list

### Delete PIM user

    $ ziggy.phar pim:user:delete [email|username]

ID can be e-mail or username. The command will attempt to find the user by username first and if it cannot be found it will attempt to find the user by e-mail. If ID is omitted you will be prompted for it. If the force parameter "-f" is omitted you will be prompted for confirmation.

### Change Password PIM user

    $ ziggy.phar pim:user:change-password [email|username] [password]

ID can be e-mail or username. The command will attempt to find the user by username first and if it cannot be found it will attempt to find the user by e-mail. If ID or password is omitted you will be prompted for it. If the force parameter "-f" is omitted you will be prompted for confirmation.

### Create PIM user

    $ ziggy.phar pim:user:create [options]

      Options:
            --username[=USERNAME]
            --password[=PASSWORD]
            --firstname[=FIRSTNAME]
            --lastname[=LASTNAME]
            --email[=EMAIL]
            --user-default-locale-code[=USER-DEFAULT-LOCALE-CODE]
            --catalog-default-locale-code[=CATALOG-DEFAULT-LOCALE-CODE]
            --catalog-default-scope-code[=CATALOG-DEFAULT-SCOPE-CODE]
            --default-tree-code[=DEFAULT-TREE-CODE]


## Thanks to

 * Everyone who is raising a Ziggy! https://ziggy.akeneo.com/
 * netz98 Team for providing magerun2 and giving us the idea to create ziggy
 * Symfony Team for the great console component.
