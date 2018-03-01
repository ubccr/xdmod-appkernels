---
title: Application Kernels Configuration Guide
---

Setup Script
------------

After installing the Open XDMoD Application Kernels package, another
item will be added to the setup script.  You can run the setup script
and select this options ("Application Kernels") to configure everything
that is needed by Open XDMoD.

    # xdmod-setup

### Application Kernels

The application kernels settings include:

- Database hostname
- Database port number
- Database username
- Database password
- AKRR REST API username
- AKRR REST API password
- AKRR REST API hostname
- AKRR REST API port number
- AKRR REST API end point

These settings are stored in `portal_settings.d/appkernels.ini`.

Cron Configuration
------------------

A cron config file (`cron.d/xdmod-appkernels`) is included that runs the
script that emails scheduled reports.

### portal_settings.d/appkernels.ini

This is the primary configuration file for the Open XDMoD application
kernels package.  It stores the credentials for the `mod_akrr` and
`mod_appkernel` databases and the AKRR REST API.

### roles.d/appkernels.json

This file is applied to the primary roles configuration (`roles.json`)
and allows authorized users access to the "Application Kernels" and
"Application Kernel Explorer" tabs in the Open XDMoD portal.

**NOTE**: The application kernel roles configuration file
`roles.d/appkernels.json` uses a different format than the primary roles
configuration file, `roles.json`.  This file extends the configuration
contained in the primary file.  A key with the prefix `+` indicates that
the value should be merged into the the value for the corresponding key
in the primary file.  This process is then applied recursively.  In the
default configuration, the application kernels module is added to the
default role with three submodules.

    {
        "+roles": {
            "+default": {
                "+permitted_modules": [
                    {
                        "name": "app_kernels",
                        "title": "App Kernels",
                        "position": 400,
                        "permitted_modules": [
                            "app_kernel_viewer",
                            "app_kernel_explorer",
                            "app_kernel_notification"
                        ]
                    }
                ]
            }
        }
    }
