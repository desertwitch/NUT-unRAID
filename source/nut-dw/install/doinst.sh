#!/bin/bash
#
# Copyright Derek Macias (parts of code from NUT package)
# Copyright macester (parts of code from NUT package)
# Copyright gfjardim (parts of code from NUT package)
# Copyright SimonF (parts of code from NUT package)
# Copyright Lime Technology (any and all other parts of Unraid)
#
# Copyright desertwitch (as author and maintainer of this file)
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License 2
# as published by the Free Software Foundation.
#
# The above copyright notice and this permission notice shall be
# included in all copies or substantial portions of the Software.
#

BOOT="/boot/config/plugins/nut-dw"
DOCROOT="/usr/local/emhttp/plugins/nut-dw"

# Add nut user and group (for legacy packages)
if [ "$( grep -ic "218" /etc/group )" -eq 0 ]; then
    groupadd -g 218 nut >/dev/null 2>&1
fi

if [ "$( grep -ic "218" /etc/passwd )" -eq 0 ]; then
    useradd -u 218 -g nut -s /bin/false nut >/dev/null 2>&1
fi

# Update file permissions of scripts
chmod 755 $DOCROOT/scripts/* \
        /etc/rc.d/rc.nut \
        /etc/rc.d/rc.nutstats \
        /usr/sbin/nut-notify \
        /usr/sbin/nutstats

# copy the default
cp -n $DOCROOT/default.cfg $BOOT/nut-dw.cfg >/dev/null 2>&1

# fix variable issue (previous NUT versions used reserved SECONDS variable)
sed -i 's/^SECONDS=/RTVALUE=/' $BOOT/nut-dw.cfg >/dev/null 2>&1

# remove nut symlink (for legacy packages)
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
cp -nr $DOCROOT/nut-defaults/* $BOOT/ups/ >/dev/null 2>&1

# copy conf files from flash drive to local system, for our services to use
cp -rf $BOOT/ups/* /etc/nut/ >/dev/null 2>&1

# remove (legacy) plugin-specific polling tasks
rm -f /etc/cron.daily/nut-poller >/dev/null 2>&1

# set up permissions
if [ -d /etc/nut ]; then
    echo "Updating permissions for NUT..."
    chown root:nut /etc/nut
    chmod 750 /etc/nut
    chown root:nut /etc/nut/*
    chmod 640 /etc/nut/*
    chmod +x /etc/nut/*.sh
    chown root:nut /var/run/nut
    chmod 770 /var/run/nut
    chown root:nut /var/state/ups
    chmod 770 /var/state/ups
fi
