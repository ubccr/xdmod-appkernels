# Running Tests of XDMoD-Appkernels with Docker

Build image

```shell script
docker build -t pseudo_repo/xdmod-appkernels-tests:latest -f Tests.Dockerfile .
```

Run Tests with specific XDMoD branch

```shell script
docker run -it --rm -p 8010:8080 -e XDMOD_BRANCH=xdmod9.0 pseudo_repo/xdmod-appkernels-tests:latest
```

Docker container will install XDMoD, AKRR, xdmod-appkernels, perform tests and get to bash session.

Browse to http://localhost:8010 to check installation.


Same without rebuilding image by attaching host directory

```shell script
# in xdmod-appkernels directory
docker run -it --rm -p 8010:8080 \
    -e XDMOD_BRANCH=xdmod9.0 \
    -v `pwd`:/root/src/ubccr/xdmod-appkernels \
    pseudo_repo/xdmod-appkernels-tests:latest
```
