FROM tools-ext-01.ccr.xdmod.org/xdmod-10.5.0-x86_64:rockylinux8.5-0.3

# install python3 for akrr build
RUN dnf -y install --setopt=tsflags=nodocs python3
