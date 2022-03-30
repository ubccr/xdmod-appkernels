FROM tools-ext-01.ccr.xdmod.org/xdmod-10.0.0:centos7.9-0.6

# install python3 for akrr build
RUN yum -y install --setopt=tsflags=nodocs python3
