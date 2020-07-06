FROM tas-tools-ext-01.ccr.xdmod.org/centos7_6-open8.5.1:latest

RUN yum update &&
    yum -y install --setopt=tsflags=nodocs python3 && \
    yum clean all
