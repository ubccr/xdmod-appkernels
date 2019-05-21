Open XDMoD Application Kernels Change Log
=========================================

## 2019-04-23 v8.1.0

- Bug Fixes
    - Fix App Kernels tab in internal dashboard ([\#48](https://github.com/ubccr/xdmod-appkernels/pull/48))

## 2018-11-07 v8.0.0

- Miscellaneous
    - Updated for compatibility with Open XDMoD 8.0.0 ([\#49](https://github.com/ubccr/xdmod-appkernels/pull/49)), ([\#50](https://github.com/ubccr/xdmod-appkernels/pull/50)), ([\#51](https://github.com/ubccr/xdmod-appkernels/pull/51))

## 2018-03-01 v7.5.0

- Features
    - Added PDF export support
- Bug Fixes
    - Fixed report generator and ingestion cron scripts
    - Fixed loading mask when no data is present
    - Added missing files

## 2017-09-21 v7.0.0

- Bug Fixes
    - Fixed various compatibility issues with PHP 7 ([\#18](https://github.com/ubccr/xdmod-appkernels/pull/18))
    - Fixed issue that allowed incompatible versions of XDMoD and this module to be installed when installing via RPM ([\#35](https://github.com/ubccr/xdmod-appkernels/pull/35))
- Miscellaneous
    - Updated for compatibility with Open XDMoD 7.0.0 ([\#20](https://github.com/ubccr/xdmod-appkernels/pull/20), [\#28](https://github.com/ubccr/xdmod-appkernels/pull/28), [\#29](https://github.com/ubccr/xdmod-appkernels/pull/29))
    - Improved development workflow ([\#21](https://github.com/ubccr/xdmod-appkernels/pull/21))
    - Improved quality assurance ([\#22](https://github.com/ubccr/xdmod-appkernels/pull/22), [\#27](https://github.com/ubccr/xdmod-appkernels/pull/27), [\#30](https://github.com/ubccr/xdmod-appkernels/pull/30), [\#37](https://github.com/ubccr/xdmod-appkernels/pull/37))

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
