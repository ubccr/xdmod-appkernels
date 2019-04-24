---
title: Application Kernels Ingestor Guide
---

This guide will attempt to outline the use of the Open XDMoD application
kernel ingestor command line utility.  This ingestor is responsible for
loading data that has been collected from AKRR into the application
kernel database.  It also can calculate the application kernel control
regions after ingestion is complete.

General Usage
-------------

The application kernel ingestor has many options, but typically a
command similar to this will be appropriate:

    $ xdmod-akrr-ingestor -l load -r -c

This will ingest all application kernel data since the previous
ingestion, replacing any duplicates and then calculate the control
regions.

Help
----

To display the ingestor help text from the command line:

    $ xdmod-akrr-ingestor -h

Verbose Output
--------------

By default the Open XDMoD ingestor only outputs what it considers to be
warnings, errors or notices. If you would like to see informational
output about what is being performed, use the verbose option:

    $ xdmod-akrr-ingestor -v

Debugging output is also available:

    $ xdmod-akrr-ingestor --debug

Timeframes
----------

There are multiple ways to specify the timeframe of the data that is
being ingested.  The simplest is to use the `--since-last`/`-l` option
with the `load` timeframe.

    $ xdmod-akrr-ingestor -l load ...

You can also use the `--since-last`/`-l` with `hour`, `day`, `week` or
`month` to limit the timeframe.

    $ xdmod-akrr-ingestor -l week ...

If you know the exact dates that you want to ingest, that is also
possible to specify using the `--start`/`-s` and `--end`/`-e` options.
These will accept UNIX timestamps as values.

    $ xdmod-akrr-ingestor -s 1420088400 -e 1422680400 ...

Calculating Control Regions
---------------------------

In addition to ingesting data it is also necessary to calculate control
regions that will be used to determine if an application kernel is
performing as expected.  This is specified using the
`--calculate-controls`/`-c` option.

    $ xdmod-akrr-ingestor -c ...

For historical reasons there is also an option to re-calculate controls.
This should not be necessary unless a bug was found in the control
calculation implementation.  Use with caution!

    $ xdmod-akrr-ingestor -y ...

Replacing and Removing Data
---------------------------

By default, the ingestor ignores duplicate data.  This allows you to
specify a timeframe that overlaps with data that has already been
ingested.  Using the `--replace`/`-r` it is possible to replace any
existing data with the newly ingested data.  This option is currently
recommended for normal use.

    $ xdmod-akrr-ingestor -r ...

It is also possible to remove data that has been ingested before
reingesting data from the same time period using the `--remove`/`-m`
option.  This was originally implemented for testing purposes and should
not be necessary for normal use.

    $ xdmod-akrr-ingestor -m ...

Restricting Ingestion
---------------------

If data for only specific application kernels or resources should be
ingested, this is also possible.  The application kernel can be
restricted with `--kernel`/`-k` and the resource can be restricted with
`--resource`/`-R`.

    $ xdmod-akrr-ingestor -k my.app.kernel ...
    $ xdmod-akrr-ingestor -R my-resource ...
