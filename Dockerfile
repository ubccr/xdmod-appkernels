FROM tas-tools-ext-01.ccr.xdmod.org/centos7_6-open8.5.1:latest

# install python3 for akrr build
RUN yum -y update && \
    yum -y install --setopt=tsflags=nodocs python3 && \
    yum -y clean all
