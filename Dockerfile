FROM tas-tools-ext-01.ccr.xdmod.org/xdmod-9.0.0:centos7.8-0.1

# install python3 for akrr build
RUN yum -y install --setopt=tsflags=nodocs python3
