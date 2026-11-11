# Low End Forum
"Low End Forum" as it is called is designed to be extremely lightweight forum software that is easy-ish to use, but above all else works with very old client software. Being a vintage computer hobbyist, this is something the modern web could really use a little bit more of.

I have no delusions of grandeur; **this is not intended to be on the same level as other commercially available forum software suites**! It is more of a pet project than anything else, and after the recent PHP vulnerability debacle I realized that there's nothing truly *good* out there in terms of forum software. Of course, what does one do when the tools presented to them suck? They make their own tools.

There is **no warranty**, express or implied, with this software. You use it at your own risk. It may delete data, break your server, or even insult your mother. I am not making any functionality guarantees. Likewise, spam and content management are your responsibility.

## System Requirements
Software requirements are very basic. While client-side you can run pretty much anything, the server *should* be reasonably up to date.

"LEF" requires the following:
* Linux operating system. Which one doesn't matter, but it is written with UNIX in mind and Windows probably can't handle any of it. Software is tested on Ubuntu Server.
* Some kind of web server software. What it is probably doesn't matter. Software testing is done with Apache2.
* PHP, preferably something that is up to date. Hard requirement is somewhere around PHP 7, but you may experiment if you wish (and understand that running unsupported software is *your* hill to die on!)
* SQLite, version 2 or newer. All you should need is the "php-sqlite" package (or similar), but having the standalone SQLite executable might also be nice.

If you are using a hosting service: you **must** have access to directories below the web root!

## Installation
How to install the Low End Forum software:
1. Set up your operating environment.
2. Download and unzip/untar the LEF source code package for a given release, or the code package from the master branch.
3. Place all of the extracted files into your web root under the desired folder. Example: "/var/www/html/low-end-forum"
4. Create the following directory on your server: "/var/www/lef-db", and give the web server user (www-data on Ubuntu Apache2) full rwx access to this folder.
5. Move the "database.sqlite" file into this new folder.
6. Create two sub-directories under this new folder: "avatars" and "themes". Ensure both are writable by the web server user.
7. Create "symlinks" to these directories under the LEF root folder:
   1. "ln -s /var/www/lef-db/themes &lt;your-lef-root&gt;/themes"
   2. "ln -s /var/www/lef-db/avatars &lt;your-lef-root&gt;/avatars"
8. Move all the CSS themes into your created themes directory.
9. Navigate to your web browser: "http://&lt;your-server&gt;/&lt;your-lef-root&gt;/init.php" and follow the script

## Upgrading
1. **TAKE A BACKUP!** The importance of backing up is often not clear until you *don't* and disaster strikes. So, just back up before doing anything.
2. Download the LEF source code package for a given release, or the code package from the master branch. Download this somewhere *other* than the LEF root folder, e.g. your home folder.
3. Unzip/untar the package, and go into the sub-directory it creates (something along the lines of "low-end-forum-<something>")
4. Copy (or move) all of the PHP code files to the LEF root.
5. **IF A DATABASE UPGRADE SCRIPT EXISTS:** navigate to your web browser: "http://&lt;your-server&gt;/&lt;your-lef-root&gt;/upgrade_database.php" and follow the script.
6. Read the release notes and perform any special instructions if needed.
