# Amazon EC2 plugin for CakePHP #

Version 1.0

This plugin will allow you to interface with [Amazon EC2](http://aws.amazon.com) and perform a subset of the functionality that the EC2 environment offers.

## Requirements ##

[CakePHP](http://cakephp.org) 2.0+ is required for this plugin.

The AmazonServer Model uses [MongoCake plugin](https://github.com/lorenzo/MongoCake) for storage.

## Installation ##

Clone the MongoCake plugin, or add as a submodule to your git repository. Alternatively you can download the archive via Github, and place into your Plugin directory:

	$ git clone git://github.com/lorenzo/MongoCake.git Plugin/MongoCake
	or
	$ git submodule add git://github.com/lorenzo/MongoCake.git Plugin/MongoCake

Clone this Ec2 repository into your Plugin directory, or setup as a submodule, or extract an archive from Github:

	$ git clone git://github.com/CakeDC/Ec2.git Plugin/Ec2
	or
	$ git submodule add git://github.com/CakeDC/Ec2.git Plugin/Ec2

## Configuration ##

An example configuration file is in the Ec2 directory `Config/Amazon.php.default`.

Take a copy of this file and place into your application `Config` directory: `Config/Amazon.php`.

The settings in the `Amazon.php` file are taken from your Amazon account settings.

## CLI Usage ##

The command line lets you view information about your amazon ec2 instances, as well as interact with them.

Available commands:

* create
* run
* describe
* start
* stop
* reboot
* terminate
* status

### Describe ###

Show all information about current instances registered with Amazon:

	$ cake Ec2.amazon_server describe

### Status ###

View information about a specific instance id:

	$ cake Ec2.amazon_server status 4e9e405eb67099021c000000

### Reboot ###

Issue instructions to reboot the instance specified:

	$ cake Ec2.amazon_server reboot 4e9e405eb67099021c000000

### Stop ###

Stop an instance. Unlike terminate, this instance will be available for running again later:

	$ cake Ec2.amazon_server stop 4e9e405eb67099021c000000

### Start ###

Start an instance that has been stopped

	$ cake Ec2.amazon_server start 4e9e405eb67099021c000000

### Terminate ###

Terminate a specific EC2 instance:

	$ cake Ec2.amazon_server terminate 4e9e405eb67099021c000000

Terminate all instances:

	$ cake Ec2.amazon_server terminate all

## Support ##

For support and feature requests, please use [Github issues](https://github.com/CakeDC/Ec2/issues).

For more information about our Professional CakePHP Services please visit the [Cake Development Corporation website](http://cakedc.com).

## License ##

Copyright 2011, [Cake Development Corporation](http://cakedc.com)

Licensed under [The MIT License](http://www.opensource.org/licenses/mit-license.php)<br/>
Redistributions of files must retain the above copyright notice.

## Copyright ###

Copyright 2011<br/>
[Cake Development Corporation](http://cakedc.com)<br/>
1785 E. Sahara Avenue, Suite 490-423<br/>
Las Vegas, Nevada 89104<br/>
http://cakedc.com<br/>