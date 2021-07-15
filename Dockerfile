FROM tools-ext-01.ccr.xdmod.org/xdmod-9.5.0:centos7.9-1.0

# install python3 for akrr build
RUN yum -y install --setopt=tsflags=nodocs python3
