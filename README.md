# PHP OAuth Authorization Server

This project aims at providing a stand-alone OAuth v2 Authorization Server that
is easy to integrate with your existing REST services, written in any language, 
without requiring extensive changes.

# Features
* PDO (database abstraction layer for various databases) storage backend for
  OAuth tokens
* OAuth v2 (authorization code and implicit grant) support
* SAML authentication support ([simpleSAMLphp](http://www.simplesamlphp.org)) 
* [BrowserID](http://browserid.org) authentication support using 
([php-browserid](https://github.com/fkooman/php-browserid/))

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

Now you can create the default configuration files, the paths will be 
automatically set, permissions set and a sample Apache configuration file will 
be generated and shown on the screen (see below for more information on
Apache configuration).

    $ docs/configure.sh

Next make sure to configure the database settings in `config/oauth.ini`, and 
possibly other settings. If you want to keep using SQlite you are good to go 
without fiddling with the database settings. Now to initialize the database:

    $ php docs/initOAuthDatabase.php https://www.example.org/html-manage-oauth/index.html

Make sure to replace the URI with the full redirect URI of the management 
client. If you do not provide a URI the default redirect URI 
`http://localhost/html-manage-oauth/index.html` is used. 

*NOTE*: On Ubuntu (Debian) you would typically install in `/var/www/php-oauth` and not 
in `/var/www/html/php-oauth` and you use `sudo` instead of `su -c`.

# Management Client
A reference management client can be found 
[here](https://github.com/fkooman/html-manage-oauth/). This client is written
in HTML, CSS and JavaScript only and can be hosted on any (static) web server.
See the accompanying README file for more information.

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
    sspPath = "/var/simplesamlphp"
    authSource = "default-sp"

    ; by default we use the (persistent) NameID value received from the SAML 
    ; assertion as the user identifier (RECOMMENDED)
    useNameID = TRUE

    ; if you want to use an attribute for the uid set the above to FALSE and specify
    ; the attribute here
    ;resourceOwnerIdAttributeName = "uid"
    ;resourceOwnerIdAttributeName = "urn:mace:dir:attribute-def:uid"

    ; displayName
    resourceOwnerDisplayNameAttributeName = "cn"
    ;resourceOwnerDisplayNameAttributeName = "urn:mace:dir:attribute-def:displayName"

