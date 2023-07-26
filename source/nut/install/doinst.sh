#!/bin/sh
BOOT="/boot/config/plugins/nut"
DOCROOT="/usr/local/emhttp/plugins/nut"

# Add nut user and group for udev at shutdown
if [ $( grep -ic "218" /etc/group ) -eq 0 ]; then
    groupadd -g 218 nut
fi

if [ $( grep -ic "218" /etc/passwd ) -eq 0 ]; then
    useradd -u 218 -g nut -s /bin/false nut
fi

# Update file permissions of scripts
chmod +0755 $DOCROOT/scripts/* \
        /etc/rc.d/rc.nut \
        /usr/sbin/nut-notify

# copy the default
cp -nr $DOCROOT/default.cfg $BOOT/nut.cfg >/dev/null 2>&1

# remove nut symlink
if [ -L /etc/nut ]; then
    rm -f /etc/nut
    mkdir /etc/nut
fi

# create nut directory
if [ ! -d /etc/nut ]; then
    mkdir /etc/nut
fi

# prepare conf backup directory on flash drive, if it does not already exist
if [ ! -d $BOOT/ups ]; then
    mkdir $BOOT/ups
fi

# copy default conf files to flash drive, if no backups exist there
cp -nr $DOCROOT/nut/* $BOOT/ups >/dev/null 2>&1

# copy conf files from flash drive to local system, for our services to use
cp -f $BOOT/ups/* /etc/nut >/dev/null 2>&1

# update permissions
if [ -d /etc/nut ]; then
    chown -R 218:218 /etc/nut
    chmod -R -r /etc/nut
fi
