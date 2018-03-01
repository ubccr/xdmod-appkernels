---
title: Application Kernel Remote Runner (AKRR)
---

AKRR executes application kernels on HPC resources using the same
mechanism as a regular user, for example it uses ssh to access the
system and submits job scripts through the system scheduler. This allows
for not only monitoring the performance of the application kernels
themselves but also testing the whole workflow that regular users employ
in order to carry out their work.

AKRR was designed to execute a large number of jobs on a number of HPC
resources in 24/7 mode. To achieve high reliability, a multi-process
design was chosen where the master process dispatches a small
self-contained subtask to the children processes. This allows the master
process code to be relatively simple and moves the more complicated code
to the children processes. This way a severe error on one of the child
processes does not cause the whole system to collapse.

Additional Documentation
------------------------

Additional documentation for AKRR is included as a PDF in the AKRR
package.
