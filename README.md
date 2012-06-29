# PHP OAuth Authorization Server

This project is a stand-alone OAuth 2 Authorization Server. 

# Features
* PDO storage backend for OAuth tokens
* OAuth 2 (authorization code and implicit grant) support
* SAML authentication support ([simpleSAMLphp](http://www.simplesamlphp.org)) 
* [BrowserID](http://browserid.org) authentication support

# Requirements
The installation requirements on Fedora/CentOS can be installed like this:

    $ su -c 'yum install git php-pdo php httpd unzip wget'

On Debian/Ubuntu:

    $ sudo apt-get install git sqlite3 php5 php5-sqlite wget unzip

# Installation
The project includes install scripts that downloads the required dependencies
and sets the permissions for the directories to write to and fixes SELinux 
permissions. *NOTE*: in the `chown` line you need to use your own user account 
name!

    $ cd /var/www/html
    $ su -c 'mkdir php-oauth'
    $ su -c 'chown fkooman.fkooman php-oauth'
    $ git clone git://github.com/fkooman/php-oauth.git
    $ cd php-oauth
    $ docs/install_dependencies.sh

Now you can create the default configuration files, the paths will be 
automatically set, permissions set and a sample Apache configuration file will 
be generated and shown on the screen (see later for Apache configuration).

    $ docs/configure.sh

Next make sure to configure the database settings, and possibly other settings. 
If you want to keep using SQlite you are good to go without fiddling with the
database settings. Now to initialize the database:

    $ php docs/initOAuthDatabase.php https://www.example.org

Make sure to replace the URI with the base URI to the management client. So if 
the `html-manage-oauth` client is installed at 
`https://www.example.org/app/html-manage-oauth/index.html` use 
`https://www.example.org/app` here. See below for more details on the 
management client.

On Ubuntu (Debian) you would typically install in `/var/www/php-oauth` and not in
`/var/www/html/php-oauth` and you use `sudo` instead of `su -c`.

# SELinux
The install script already takes care of setting the file permissions of the
`data/` directory to allow Apache to write to the directory. If you want to use
the BrowserID authentication plugin you also need to give Apache permission to 
access the network. These permissions can be given by using `setsebool` as root:

    $ sudo setsebool -P httpd_can_network_connect=on

This is only for Red Hat based Linux distributions like RHEL, CentOS and 
Fedora.

# Apache
There is an example configuration file in `docs/apache.conf`. 

On Red Hat based distributions the file can be placed in 
`/etc/httpd/conf.d/php-oauth.conf`. On Debian based distributions the file can
be placed in `/etc/apache2/conf.d/php-oauth`. Be sure to modify it to suit your 
environment and do not forget to restart Apache. 

The install script from the previous section outputs a config for your system
which replaces the `/PATH/TO/APP` with the actual directory.

# Configuration
In the configuration file `config/oauth.ini` various aspects can be configured. 
To configure the SAML integration, make sure the following settings are correct:

    authenticationMechanism = "SspResourceOwner"

    ; simpleSAMLphp configuration
    [SspResourceOwner]
    sspPath = "/var/simplesamlphp/lib"
    authSource = "default-sp"

    resourceOwnerIdAttributeName = "uid"
    resourceOwnerDisplayNameAttributeName = "cn"
    ;resourceOwnerIdAttributeName = "urn:mace:dir:attribute-def:uid"
    ;resourceOwnerDisplayNameAttributeName = "urn:mace:dir:attribute-def:displayName"
