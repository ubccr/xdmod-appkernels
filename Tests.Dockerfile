# Perform shippable-like tests locally
# with xdebug on

FROM tas-tools-ext-01.ccr.xdmod.org/centos7_6-open8.5.1:latest

RUN yum -y install --setopt=tsflags=nodocs python3 openssh-server && \
    yum clean all

COPY . /root/src/ubccr/xdmod-appkernels
WORKDIR /root/src/ubccr/xdmod-appkernels

COPY ./tests/utils/cmd_setup ./tests/utils/cmd_start ./tests/utils/cmd_stop /usr/local/sbin/


COPY ./tests/artifacts/sshd/ssh_host_ecdsa_key \
     ./tests/artifacts/sshd/ssh_host_ecdsa_key.pub \
     ./tests/artifacts/sshd/ssh_host_ed25519_key \
     ./tests/artifacts/sshd/ssh_host_ed25519_key.pub \
     ./tests/artifacts/sshd/ssh_host_rsa_key \
     ./tests/artifacts/sshd/ssh_host_rsa_key.pub \
     /etc/ssh/


RUN /usr/local/sbin/cmd_setup xdebug sshd && echo 'root:root' |chpasswd && \
    wget -O /usr/local/bin/phpunit https://phar.phpunit.de/phpunit-4.phar && \
    chmod 755 /usr/local/bin/phpunit && \
    cp /usr/local/bin/phpunit /usr/local/bin/phpunit.phar

EXPOSE 22

ENV COMPOSER_ALLOW_SUPERUSER=1

ENTRYPOINT ["/usr/local/sbin/cmd_start"]
CMD ["-set-no-exit-on-fail", "sshd", "/root/src/ubccr/xdmod-appkernels/tests/runtests.sh", "bash"]
