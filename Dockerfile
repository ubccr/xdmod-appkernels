FROM tas-tools-ext-01.ccr.xdmod.org/centos7_6-open8.5.1:latest

RUN yum update && \
    yum install -y httpd httpd-tools && \
    yum clean all
