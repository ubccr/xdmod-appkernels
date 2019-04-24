---
title: Application Kernels Source Installation Guide
---

Install Source Package
----------------------

    $ tar zxvf xdmod-appkernels-x.y.z.tar.gz
    $ cd xdmod-appkernels-x.y.z
    # ./install -prefix=/opt/xdmod

**NOTE**: The installation prefix must be the same as your existing Open
XDMoD installation. These instructions assume you have already installed
Open XDMoD in `/opt/xdmod`.

### Copy Configuration Files

    # cp /opt/xdmod/etc/cron.d/xdmod-appkernels /etc/cron.d/xdmod-appkernels

The directory where this file is needed may differ depending on your
operating system.

Run Configuration Script
------------------------

    # /opt/xdmod/bin/xdmod-setup

There should be a new section titled "Application Kernels" in the list.
Select that option and provide the required information.  Specifically,
you will need to enter database credentials to access the databases
created by AKRR and the AKRR REST API credentials.

**NOTE**: It is also possible to manually modify
`/opt/xdmod/etc/portal_settings.d/appkernels.ini`.

Ingest Data From AKRR
---------------------

If one or more jobs have been submitted by AKRR and completed, you may
ingest that data:

    $ /opt/xdmod/bin/xdmod-akrr-ingestor -v -l load

See the [Application Kernels Ingestor Guide](ak-ingestor.html) for more
details.

Check Open XDMoD Portal
-----------------------

After successfully installing and configuring the application kernels
package you should check the Open XDMoD portal to make sure everything
is working correctly.  By default, the application kernel data is only
available to authorized users, so you must log into the portal.  After
logging in there should an additional tab visible named "App Kernels".
