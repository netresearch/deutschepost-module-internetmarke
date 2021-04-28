Deutsche Post Internetmarke
===========================

Create shipping labels using Deutsche Post Internetmarke shipping products.

Description
-----------
The Deutsche Post DHL Group Internetmarke module is a companion to the
DHL Paket carrier module. It extends the list of available shipping
products to allow for smaller dimension shipping boxes (e.g. letters).

Requirements
------------
* PHP >= 7.1.3

Compatibility
-------------
* Magento >= 2.3.0+

Installation Instructions
-------------------------

Install sources:

    composer require deutschepost/module-internetmarke

Enable module:

    ./bin/magento module:enable DeutschePost_Internetmarke
    ./bin/magento setup:upgrade

Flush cache and compile:

    ./bin/magento cache:flush
    ./bin/magento setup:di:compile

Uninstallation
--------------

To unregister the module from the application, run the following command:

    ./bin/magento module:uninstall --remove-data DeutschePost_Internetmarke
    composer update

This will automatically remove source files, clean up the database, update package dependencies.

Support
-------
In case of questions or problems, please have a look at the
[Support Portal (FAQ)](http://dhl.support.netresearch.de/) first.

If the issue cannot be resolved, you can contact the support team via the
[Support Portal](http://dhl.support.netresearch.de/) or by sending an email
to <dhl.support@netresearch.de>.

License
-------
[OSL - Open Software Licence 3.0](http://opensource.org/licenses/osl-3.0.php)

Copyright
---------
(c) 2021 DHL Paket GmbH
