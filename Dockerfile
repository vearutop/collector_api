FROM centos:centos6
MAINTAINER Mickael HOAREAU mhoareau84@gmail.com

ADD ./docker/yum/nginx.repo /etc/yum.repos.d/nginx.repo

RUN yum -y update && yum clean all

# Ensure UTF-8
RUN yum -y reinstall glibc-common
#RUN locale-gen en_US.UTF-8
ENV LANG       en_US.UTF-8
ENV LC_ALL     en_US.UTF-8

# Set timezone
RUN echo "Asia/Ho_Chi_Minh" > /etc/timezone
RUN cp -f /usr/share/zoneinfo/Asia/Ho_Chi_Minh /etc/localtime

RUN rpm -Uvh http://download.fedoraproject.org/pub/epel/6/i386/epel-release-6-8.noarch.rpm
RUN rpm -Uvh http://dl.iuscommunity.org/pub/ius/stable/CentOS/6/x86_64/ius-release-1.0-14.ius.centos6.noarch.rpm

# Git
RUN yum -y install git tar

# nginx
RUN yum -y install nginx
# tell Nginx to stay foregrounded
RUN echo "daemon off;" >> /etc/nginx/nginx.conf

# PHP
RUN yum -y install memcached-devel
RUN yum -y install php55u-fpm \
    php55u-cli \
    php55u-pecl-memcached \
    php55u-xml \
    php55u-mcrypt \
    php55u-pecl-xdebug \
    php55u-pdo \
    php55u-mbstring \
    php55u-mysql


# Composer
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer
RUN /usr/local/bin/composer self-update

# SSH
RUN yum -y install openssh-server openssh-clients
RUN echo 'root:hackathon' | chpasswd
RUN sed -i 's/PermitRootLogin without-password/PermitRootLogin yes/' /etc/ssh/sshd_config

# SSH login fix. Otherwise user is kicked off after login
RUN sed 's@session\s*required\s*pam_loginuid.so@session optional pam_loginuid.so@g' -i /etc/pam.d/sshd

RUN mkdir /var/lib/php/session
RUN chown -R nginx /var/lib/php/session

EXPOSE 80 81 22 3306

RUN mkdir -p /var/www && chown -R nginx /var/www
ADD . /var/www
RUN cd /var/www && composer install

ADD docker/laravel.env /var/www/.env
ADD ./docker/nginx/*.conf /etc/nginx/conf.d/
ADD ./docker/php-fpm/www.conf /etc/php-fpm.d/www.conf
ADD ./docker/php-fpm/php.ini /etc/php.ini
ADD ./docker/bin/* /usr/local/bin/

CMD ["startup.sh"]