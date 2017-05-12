Open XDMoD Application Kernels Change Log
=========================================

2017-05-11 v6.6.0
-----------------

- Miscellaneous
    - Updated for compatibility with Open XDMoD 6.6.0
      ([\#14](https://github.com/ubccr/xdmod-appkernels/pull/14),
       [\#19](https://github.com/ubccr/xdmod-appkernels/pull/19))
    - Cleaned up old and/or unused code
      ([\#12](https://github.com/ubccr/xdmod-appkernels/pull/12))
    - Improved quality assurance
      ([\#9](https://github.com/ubccr/xdmod-appkernels/pull/9),
       [\#11](https://github.com/ubccr/xdmod-appkernels/pull/11),
       [\#13](https://github.com/ubccr/xdmod-appkernels/pull/13),
       [\#15](https://github.com/ubccr/xdmod-appkernels/pull/15),
       [\#16](https://github.com/ubccr/xdmod-appkernels/pull/16))

2017-01-10 v6.5.0
-----------------

- Refactors and Miscellaneous
    - Spun this module out from the Open XDMoD repository.

2016-09-21 v6.0.0
-----------------

- Updated for compatibility with Open XDMoD 6.0.0.

2016-05-24 v5.6.0
-----------------

- Features
    - Added ability to select the 29th-31st of a month as a monthly report
      delivery day.
        - For months that don't have these days, reports scheduled for those
          days will be delivered on the last day of the month.
- Bug Fixes
    - Fixed Reports tab not appearing for some authorized users.
    - Reduced user authorization requirements for showing certain data.
    - Removed "." from tab labels.
    - Removed non-functional x-y swap from tabs.

2015-12-18 v5.5.0
-----------------

- Bug Fixes
    - Fixed handling of whitespace in XML parsing
    - Incorrect database credentials were being used in the app kernel
      performance map

2015-08-19 v5.0.0
-----------------

- Initial public release
