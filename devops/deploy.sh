#!/bin/bash

set -xe

#export REMOTE_HOST="root@123.206.175.60"
#export REMOTE_ROOT="/home/dnf/image"
#(cd ${WORKSPACE}/devops/;chmod +x *.sh;)
#(cd ${WORKSPACE}/devops/;chmod +x *.sh; ./test.sh)

SERVICE_NAME="image"
TAR_NAME="${SERVICE_NAME}-${BUILD_ID}-`date +%y%m%d`"
TAR_GZ="${TAR_NAME}.tar.gz"

type=$1;
REMOTE_PATH="${REMOTE_ROOT}/$type"
case $type in
    "dev") env_type="dev"; config_type="develop";;
    "test") env_type="test"; config_type="test";;
    "prod") env_type='prod'; config_type='release';;
    *) echo "unknown type: $type"; exit 1;;
esac

cd ${WORKSPACE}/devops
rm -rf *.tar.gz

cp  -f ../docker/.env-${env_type} ../docker/.env
cp -f ../src/params-${config_type}.php ../src/params.php

[ $type == prod ] && (cd ../src/public; cp -f crossdomain-prod.xml crossdomain.xml)
echo $BUILD_ID>"../build_id.txt"
echo $GIT_COMMIT>"../git_commit.txt"

#find ../docker -name '*.sh'|xargs chmod +x
find ../devops -name '*.sh'|xargs chmod +x
find ../docker/upload_fpm -name '*.sh'|xargs chmod +x
find ../docker/upload_nginx -name '*.sh'|xargs chmod +x
#find ../docker/upload_fpm -name '*.sh' -or -name "Dockerfile"|xargs dos2unix
#find ../docker/upload_nginx -name '*.sh' -or -name "Dockerfile"|xargs dos2unix

tar -czf ${TAR_GZ} -C .. . \
    --exclude=.git* --exclude=common/.git --exclude=vendor/.git\
    --exclude=src/public/crossdomain-*.xml\
    --exclude=docker/.env-*\
	--exclude=src/params-*.php\
    --exclude=devops/*.tar.gz

(
    ssh ${REMOTE_HOST} sudo mkdir -p ${REMOTE_PATH}
    scp ${WORKSPACE}/devops/${TAR_GZ} ${REMOTE_HOST}:/tmp/
    ssh ${REMOTE_HOST} sudo mv /tmp/${TAR_GZ} ${REMOTE_PATH}/
)
(ssh ${REMOTE_HOST} "cd ${REMOTE_PATH};
    sudo mkdir ${TAR_NAME};
    sudo tar xzf ${TAR_GZ}  -C ${TAR_NAME};
    if [ ! -d "$SERVICE_NAME" ]; then
        sudo ln -s ${TAR_NAME} ${SERVICE_NAME};
        (cd ${SERVICE_NAME}/docker; sudo docker-compose down; sudo docker-compose up -d --build)
    else
        (cd ${SERVICE_NAME}/docker; sudo docker-compose down;)
        sudo rm -rf ${SERVICE_NAME};
        sudo ln -s ${TAR_NAME} ${SERVICE_NAME};
        (cd ${SERVICE_NAME}/docker; sudo docker-compose up -d --build)
        (if [ $type != prod ];then for dir in ${SERVICE_NAME}-*; do if [ \$dir != $TAR_NAME ]; then rm -rf \$dir ; fi; done fi)
    fi
    ")