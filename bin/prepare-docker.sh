#!/usr/bin/env bash
DEV_ENVIRONMENT=$(ipconfig getifaddr en0) ; export DEV_ENVIRONMENT
PROJECT_NAME='e20r-member-directory-for-pmpro' ; export PROJECT_NAME
PLUGIN_DIR=../docker-env
PLUGIN_LIST="paid-memberships-pro ${PROJECT_NAME}"
CURRENT_DIR=$(pwd)
if [[ ${DEV_ENVIRONMENT} = "192.168.1.87" || ${DEV_ENVIRONMENT} == "192.168.1.50" ]];
then
    echo "At home so using the docker env on docker.local"
    ssh docker.local "cd ./www/docker-images/docker4wordpress/ ; mkdir -p ./mariadb-init" # mkdir -p ./traefik
    cp  ${PLUGIN_DIR}/hosts.home ~/PhpStormProjects/docker-images/docker4wordpress/hosts.docker
    scp ${PLUGIN_DIR}/docker-compose.yml docker.local:./www/docker/docker4wordpress/docker-compose.yml
    scp ${PLUGIN_DIR}/docker-compose.override.yml-home docker.local:./www/docker/docker4wordpress/docker-compose.override.yml
    scp ${PLUGIN_DIR}/${PROJECT_NAME}.sql docker.local:./www/docker/docker4wordpress/mariadb-init/
    scp ${PLUGIN_DIR}/env docker.local:./www/docker/docker4wordpress/.env
    scp ${PLUGIN_DIR}/import-db.sh docker.local:./www/docker/docker4wordpress/import-db.sh
    # scp -r ${PLUGIN_DIR}/traefik docker.local:./www/docker/docker4wordpress/traefik
    ssh docker.local "cd ./www/docker-images/docker4wordpress/ ; make down ; make up"
    ssh docker.local "cd ./www/docker-images/docker4wordpress/ ; chmod +x ./import-db.sh ; nohup ./import-db.sh"
    ssh docker.local "cd ./www/docker-images/docker4wordpress/ ; make wp plugin activate ${PLUGIN_LIST}"
    DOCKER_CONTAINER=$(ssh docker.local docker ps | grep ${PROJECT_NAME}_php | awk '{print $1}')
    ssh docker.local "cd ./www/docker-images/docker4wordpress/ ; docker exec ${DOCKER_CONTAINER} wp config set MPMS_TESTING_ENABLED true --add --raw --type=constant"
else
    echo "Not at home (using the local laptop docker env)"
    mkdir -p /Volumes/Development/www/docker-images/docker4wordpress/mariadb-init
    cp ${PLUGIN_DIR}/hosts.local ~/PhpStormProjects/docker-images/docker4wordpress/hosts.docker
    cp ${PLUGIN_DIR}/docker-compose.yml ~/PhpStormProjects/docker-images/docker4wordpress/docker-compose.yml
    cp ${PLUGIN_DIR}/docker-compose.override.yml-local ~/PhpStormProjects/docker-images/docker4wordpress/docker-compose.override.yml
    cp ${PLUGIN_DIR}/${PROJECT_NAME}.sql ~/PhpStormProjects/docker-images/docker4wordpress/mariadb-init/
    cp ${PLUGIN_DIR}/import-db.sh ~/PhpStormProjects/docker-images/docker4wordpress/import-db.sh
    cp ${PLUGIN_DIR}/env ~/PhpStormProjects/docker-images/docker4wordpress/.env
    cd /Users/sjolshag/PhpStormProjects/docker-images/docker4wordpress/
    make down
    make up
    chmod +x ./import-db.sh
    nohup ./import-db.sh
    make wp plugin activate ${PROJECT_NAME}
    DOCKER_CONTAINER=$(docker ps | grep ${PROJECT_NAME}_php | awk '{print $1}')
    docker exec ${DOCKER_CONTAINER} wp config set MPMS_TESTING_ENABLED true --add --raw --type=constant
    # ./import-db.sh
    cd ${CURRENT_DIR}
fi
