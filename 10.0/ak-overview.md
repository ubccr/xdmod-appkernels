---
title: Application Kernels Overview
redirect_from:
    - "/10.0/"
    - ""
---



Application Kernel Performance Monitoring Module of XDMoD tool is
designed to measure quality of service as well as preemptively identify
underperforming hardware and software by deploying customized,
computationally lightweight "application kernels" that are run
frequently (daily to several times per week) to continuously monitor HPC
system performance and reliability from the application users' point of
view. The term "computationally-lightweight" is used to indicate that
the application kernel requires relatively modest resources for a given
run frequency. Accordingly, through XDMoD, system managers have the
ability to proactively monitor system performance as opposed to having
to rely on users to report failures or underperforming hardware and
software.

The application kernel module of XDMoD consists of two parts. 1) the
application kernel remote runner (AKRR) executes the scheduled jobs,
monitors their execution, processes the output, extracts performance
metrics and exports the results to the database, 2) the application
kernel performance analytics and visualization.

[AKRR](https://akrr.xdmod.org) should be installed first prior to this module (xdmod-appkernels).
