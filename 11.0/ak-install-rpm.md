---
title: Application Kernels RPM Installation Guide
---

Install RPM Package
-------------------

If your web server can reach GitHub via HTTPS, you can install the RPM package
directly:

    # dnf install https://github.com/ubccr/xdmod-appkernels/releases/download/v{{ page.rpm_version }}/xdmod-appkernels-{{ page.rpm_version }}.el8.noarch.rpm

Otherwise, you can download the RPM file from the [GitHub page for the
release](https://github.com/ubccr/xdmod-appkernels/releases/tag/v{{
page.rpm_version }}) and install it:

    # dnf install xdmod-appkernels-{{ page.rpm_version }}.el8.noarch.rpm

Run Configuration Script
------------------------

    # xdmod-setup

There should be a new section titled "Application Kernels" in the list.
Select that option and provide the required information.  Specifically,
you will need to enter database credentials to access the databases
created by AKRR and the AKRR REST API credentials.

**NOTE**: It is also possible to manually modify
`/etc/xdmod/portal_settings.d/appkernels.ini`.

Ingest Data From AKRR
---------------------

If one or more jobs have been submitted by AKRR and completed, you may
ingest that data:

    $ xdmod-akrr-ingestor -v -l load

See the [Application Kernels Ingestor Guide](ak-ingestor.html) for more
details.

Check Open XDMoD Portal
-----------------------

After successfully installing and configuring the application kernels
package you should check the Open XDMoD portal to make sure everything
is working correctly.  By default, the application kernel data is only
available to authorized users, so you must log into the portal.  After
logging in there should an additional tab visible named "App Kernels".
