FROM ubuntu:16.04

RUN apt-get update
RUN apt-get install -y software-properties-common python-software-properties
RUN LC_ALL=C.UTF-8 add-apt-repository -y ppa:ondrej/php
RUN apt-get update
RUN apt-get install -y php7.2 \
	php-mcrypt \
	php7.2-cli php7.2-common \
	php-pear php7.2-curl php7.2-dev php7.2-mbstring php7.2-zip php7.2-mysql php7.2-xml \
	php7.2-fpm \
	vim \
	wget \
	curl \
	composer
RUN apt-get install -y apt-transport-https ca-certificates
RUN curl -fsSL https://download.docker.com/linux/ubuntu/gpg | apt-key add -
# Install unbuffer
RUN apt-get install -y expect
WORKDIR /var/code