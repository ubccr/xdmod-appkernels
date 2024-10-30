# Running Tests of XDMoD-Appkernels with Docker

Build image

```shell script
docker build -t pseudo_repo/xdmod-appkernels-tests:latest -f Tests.Dockerfile .
```

Run Tests with specific XDMoD branch

```shell script
docker run -it --rm -p 20080:8080 \
    -e XDMOD_BRANCH=main \
    pseudo_repo/xdmod-appkernels-tests:latest
```

Docker container will install XDMoD, AKRR, xdmod-appkernels, perform tests and get to bash session.

Browse to http://localhost:20080 to check installation.


Same without rebuilding image by attaching host directory

```shell script
# in xdmod-appkernels directory
docker run -it --rm -p 20080:8080 \
    -e XDMOD_BRANCH=main \
    -v `pwd`:/root/src/ubccr/xdmod-appkernels \
    pseudo_repo/xdmod-appkernels-tests:latest
```

Using local XDMoD and adding remote debugging capabilities with port forwarding for mysql, httpd and ssh
```shell script
# in xdmod-appkernels directory
docker run -it --rm \
    -p 20443:443 -p 23306:3306 -p 20022:22 \
    -e XDMOD_BRANCH=main \
    -v `pwd`/../xdmod:/root/src/ubccr/xdmod \
    -v `pwd`/../xdmod-qa:/root/src/ubccr/xdmod-qa \
    -v `pwd`:/root/src/ubccr/xdmod-appkernels \
    pseudo_repo/xdmod-appkernels-tests:latest
```

For xdebug create a tunnel:
```shell script
ssh -R 9000:localhost:9000 root@localhost -p 20022
```
