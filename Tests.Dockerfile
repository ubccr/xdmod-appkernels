# Perform shippable-like tests locally

FROM tas-tools-ext-01.ccr.xdmod.org/centos7_6-open8.5.1:latest

RUN yum -y install --setopt=tsflags=nodocs python3 && \
    yum clean all

COPY . /root/src/ubccr/xdmod-appkernels
WORKDIR /root/src/ubccr/xdmod-appkernels

COPY ./tests/utils/cmd_setup ./tests/utils/cmd_start ./tests/utils/cmd_stop /usr/local/sbin/

ENV COMPOSER_ALLOW_SUPERUSER=1

ENTRYPOINT ["/usr/local/sbin/cmd_start"]
CMD ["-set-no-exit-on-fail", "/root/src/ubccr/xdmod-appkernels/tests/runtests.sh", "bash"]
