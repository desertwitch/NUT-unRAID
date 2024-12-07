#!/bin/bash
#
# Copyright Derek Macias (parts of code from NUT package)
# Copyright macester (parts of code from NUT package)
# Copyright gfjardim (parts of code from NUT package)
# Copyright SimonF (parts of code from NUT package)
# Copyright Lime Technology (any and all other parts of Unraid)
#
# Copyright V'yacheslav Stetskevych (as original script author)
# Copyright desertwitch (co-author and maintainer of this file)
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License 2
# as published by the Free Software Foundation.
#
# The above copyright notice and this permission notice shall be
# included in all copies or substantial portions of the Software.
#

DRIVERPATH=/usr/libexec/nut
export PATH=$PATH:$DRIVERPATH

NUT_QUIET_INIT_UPSNOTIFY=true
export NUT_QUIET_INIT_UPSNOTIFY

NUT_DEBUG_PID=true
export NUT_DEBUG_PID

PLGPATH="/boot/config/plugins/nut-dw"
CONFIG=$PLGPATH/nut-dw.cfg
DOCROOT="/usr/local/emhttp/plugins/nut-dw"

# read our configuration
[ -e "$CONFIG" ] && source $CONFIG

error_at_start() {
    if [ -d $DOCROOT/misc ]; then
        touch $DOCROOT/misc/start-failed
    fi
    exit 1
}

start_driver() {
    /usr/sbin/upsdrvctl -u root start || error_at_start
}

start_upsd() {
    if pgrep -x upsd >/dev/null 2>&1; then
        echo "NUT upsd is running..."
    else
        /usr/sbin/upsd -u root || error_at_start
    fi
}

start_upsmon() {
    if pgrep -x upsmon >/dev/null 2>&1; then
        echo "NUT upsmon is running..."
    else
        /usr/sbin/upsmon -p || error_at_start
    fi
}

start() {
    if [ "$ORUSBPOWER" == "enable" ]; then
        echo "WARNING: NUT was user-configured to disable power management for all USB devices."
        echo "WARNING: NUT is now forcing all USB devices to permanent [on] power state as requested..."

        OVRESULT="$(echo on | tee /sys/bus/usb/devices/*/power/control)"
        if [ "$OVRESULT" != "on" ]; then
            echo "ERROR: NUT has encountered an unexpected result disabling all USB power management: ${OVRESULT}"
        fi
    fi
    if [ "$MODE" != "slave" ]; then
        start_driver
        sleep 1
        start_upsd
    fi
    start_upsmon
}

stop() {
    echo "Stopping the NUT services... "
    if pgrep -x upsmon >/dev/null 2>&1; then
        /usr/sbin/upsmon -c stop

        TIMER=0
        while killall upsmon 2>/dev/null; do
            sleep 1
            killall upsmon
            TIMER=$((TIMER+1))
            if [ $TIMER -ge 30 ]; then
                killall -9 upsmon
                sleep 1
                break
            fi
        done
    fi

    if pgrep -x upsd >/dev/null 2>&1; then
        /usr/sbin/upsd -c stop

        TIMER=0
        while killall upsd 2>/dev/null; do
            sleep 1
            killall upsd
            TIMER=$((TIMER+1))
            if [ $TIMER -ge 30 ]; then
                killall -9 upsd
                sleep 1
                break
            fi
        done
    fi

    sleep 2

    # remove pid from old package
    if [ -f /var/run/upsmon.pid ]; then
        rm -f /var/run/upsmon.pid
    fi

    if [ -f /var/run/nut/upsmon.pid ]; then
        rm -f /var/run/nut/upsmon.pid
    fi

    /usr/sbin/upsdrvctl stop

}

backup_logs() {
    if [ "$SYSLOGBACKUP" == "enable" ]; then
        if [ "$SYSLOGMETHOD" == "file" ] || [ "$SYSLOGMETHOD" == "both" ]; then
            if [ -f /var/log/nut.log ]; then
                echo "Backing up last 200KB of NUT service logs to USB..."
                if ! tail -c 200k /var/log/nut.log > /tmp/nut.log; then
                    echo "Failed to back up last 200KB of NUT service logs to USB - tail operation failed."
                    rm -f /tmp/nut.log; rm -f /boot/logs/nut.log
                elif ! mv -f /tmp/nut.log /boot/logs/nut.log; then
                    echo "Failed to back up last 200KB of NUT service logs to USB - move operation failed."
                    rm -f /tmp/nut.log; rm -f /boot/logs/nut.log
                fi
            fi
        fi
        if [ "$SYSLOGFILTER" == "enable" ]; then
            if [ -f /var/log/nut-spam ]; then
                echo "Backing up last 200KB of NUT spam logs to USB..."
                if ! tail -c 200k /var/log/nut-spam > /tmp/nut-spam; then
                    echo "Failed to back up last 200KB of NUT spam logs to USB - tail operation failed."
                    rm -f /tmp/nut-spam; rm -f /boot/logs/nut-spam
                elif ! mv -f /tmp/nut-spam /boot/logs/nut-spam; then
                    echo "Failed to back up last 200KB of NUT spam logs to USB - move operation failed."
                    rm -f /tmp/nut-spam; rm -f /boot/logs/nut-spam
                fi
            fi
        fi
    fi
}

write_config() {
    echo "Writing NUT configuration..."

    if [ "$MANUAL" == "disable" ] || [ "$MANUAL" == "onlyups" ]; then
        if [ "$MANUAL" == "disable" ]; then
            # add the name
            sed -i "1 s~.*~[${NAME}]~" /etc/nut/ups.conf

            # Add the driver config
            if [ "$DRIVER" == "custom" ]; then
                    sed -i "2 s/.*/driver = ${SERIAL}/" /etc/nut/ups.conf
            else
                    sed -i "2 s/.*/driver = ${DRIVER}/" /etc/nut/ups.conf
            fi

            # add the port
            if [ -n "$PORT" ]; then
                sed -i "3 s~.*~port = ${PORT}~" /etc/nut/ups.conf
            else
                sed -i "3 s~.*~port = auto~" /etc/nut/ups.conf
            fi

            # Add SNMP-specific config
            if [ "$DRIVER" == "snmp-ups" ]; then
                [ -z "$SNMPVER" ] && SNMPVER="v2c"
                [ -z "$SNMPMIB" ] && SNMPMIB="auto"

                var10="pollfreq = ${POLL}"
                var11="community = ${COMMUNITY}"
                var12="snmp_version = ${SNMPVER}"

                if [ "$SNMPMIB" == "auto" ]; then
                    var13=''
                else
                    var13="mibs = \"${SNMPMIB}\""
                fi
            else
                var10=''
                var11=''
                var12=''
                var13=''
            fi

            # UPS Driver Debug Level
            if [ "$DEBLEVEL" == "default" ]; then
                var14=''
            elif [ -n "$DEBLEVEL" ]; then
                var14="debug_min = ${DEBLEVEL}"
            else
                var14=''
            fi

            sed -i "4 s/.*/$var10/" /etc/nut/ups.conf
            sed -i "5 s/.*/$var11/" /etc/nut/ups.conf
            sed -i "6 s/.*/$var12/" /etc/nut/ups.conf
            sed -i "7 s/.*/$var13/" /etc/nut/ups.conf
            sed -i "8 s/.*/$var14/" /etc/nut/ups.conf
        fi

        # add mode standalone/netserver
        sed -i "1 s/.*/MODE = ${MODE}/" /etc/nut/nut.conf

        # Set monitor ip address, user, password and mode
        if [ "$MODE" == "slave" ]; then
            MONITOR="slave"
        else
            MONITOR="master"
        fi

        # decode monitor passwords
        MONPASS="$(echo "$MONPASS" | base64 --decode)"
        SLAVEPASS="$(echo "$SLAVEPASS" | base64 --decode)"

        var1="MONITOR ${NAME}@${IPADDR} 1 ${MONUSER} ${MONPASS} ${MONITOR}"
        sed -i "1 s,.*,$var1," /etc/nut/upsmon.conf

        # Set if the ups should be turned off
        if [ "$UPSKILL" == "enable" ]; then
            var8='POWERDOWNFLAG "/etc/nut/killpower"'
            sed -i "3 s,.*,$var8," /etc/nut/upsmon.conf
        else
            var9='POWERDOWNFLAG "/etc/nut/no_killpower"'
            sed -i "3 s,.*,$var9," /etc/nut/upsmon.conf
        fi

        # NUT Monitor Debug Level
        if [ "$DEBLEVELMON" == "default" ]; then
            var24=''
        elif [ -n "$DEBLEVELMON" ]; then
            var24="DEBUG_MIN ${DEBLEVELMON}"
        else
            var24=''
        fi

        sed -i "8 s/.*/$var24/" /etc/nut/upsmon.conf

        if [ "$MODE" != "slave" ]; then
            # Set upsd users
            var18="[${MONUSER}]"
            var19="password = ${MONPASS}"
            var21="[${SLAVEUSER}]"
            var22="password = ${SLAVEPASS}"
            sed -i "6 s,.*,$var18," /etc/nut/upsd.users
            sed -i "7 s,.*,$var19," /etc/nut/upsd.users
            sed -i "9 s,.*,$var21," /etc/nut/upsd.users
            sed -i "10 s,.*,$var22," /etc/nut/upsd.users
        fi
    fi

    # save conf files to flash drive regardless of mode
    # also here in case someone directly modified files in /etc/nut
    # flash directory will be created if missing (shouldn't happen)

    if [ ! -d $PLGPATH/ups ]; then
        mkdir $PLGPATH/ups
    fi

    cp -rf /etc/nut/* $PLGPATH/ups/ >/dev/null 2>&1

    # re-create state directories if missing
    [ ! -d /var/state/ups ] && mkdir -p /var/state/ups
    [ ! -d /var/run/nut ] && mkdir -p /var/run/nut

    # update permissions
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

    # Link shutdown scripts for poweroff in rc.6
    if [ "$( grep -ic "/etc/rc.d/rc.nut restart_udev" /etc/rc.d/rc.6 )" -eq 0 ]; then
        echo "Adding UDEV lines to rc.6 for NUT..."
        sed -i '/\/bin\/mount -v -n -o remount,ro \//a [ -x /etc/rc.d/rc.nut ] && /etc/rc.d/rc.nut restart_udev' /etc/rc.d/rc.6
    fi

    if [ "$( grep -ic "/etc/rc.d/rc.nut shutdown" /etc/rc.d/rc.6 )" -eq 0 ]; then
        echo "Adding UPS shutdown lines to rc.6 for NUT..."
         sed -i '/# Now halt /a [ -x /etc/rc.d/rc.nut ] && /etc/rc.d/rc.nut shutdown' /etc/rc.d/rc.6
    fi

    RESTARTSYSLOG="NO"

    # Handle backing up and restoring logs to and from the USB drive
    if [ "$SYSLOGBACKUP" == "enable" ]; then
        if [ "$( grep -ic "/etc/rc.d/rc.nut backup_logs" /etc/rc.d/rc.local_shutdown )" -eq 0 ]; then
            echo "Adding log backup line to rc.local_shutdown for NUT..."
            sed -i '/# Get time-out setting/i [ -x /etc/rc.d/rc.nut ] && /etc/rc.d/rc.nut backup_logs | logger -t "rc.nut"' /etc/rc.d/rc.local_shutdown
        fi

        # If none exist - restore any previous logs from the USB drive
        if [ "$SYSLOGMETHOD" == "file" ] || [ "$SYSLOGMETHOD" == "both" ]; then
            if [ ! -f /var/log/nut.log ] && [ -f /boot/logs/nut.log ]; then
                echo "Restoring previous NUT service logs from USB..."
                if ! mv -f /boot/logs/nut.log /var/log/nut.log; then
                    echo "Failed to restore previous NUT service logs from USB... move operation failed."
                    rm -f /boot/logs/nut.log; rm -f /var/log/nut.log
                else
                    chown root:root /var/log/nut.log
                    chmod 644 /var/log/nut.log
                fi
                RESTARTSYSLOG="YES"
            fi
        else
            [ -f /boot/logs/nut.log ] && rm -f /boot/logs/nut.log
        fi

        if [ "$SYSLOGFILTER" == "enable" ]; then
            if [ ! -f /var/log/nut-spam ] && [ -f /boot/logs/nut-spam ]; then
                echo "Restoring previous NUT spam logs from USB..."
                if ! mv -f /boot/logs/nut-spam /var/log/nut-spam; then
                    echo "Failed to restore previous NUT spam logs from USB... move operation failed."
                    rm -f /boot/logs/nut-spam; rm -f /var/log/nut-spam
                else
                    chown root:root /var/log/nut-spam
                    chmod 644 /var/log/nut-spam
                fi
                RESTARTSYSLOG="YES"
            fi
        else
            [ -f /boot/logs/nut-spam ] && rm -f /boot/logs/nut-spam
        fi

    else
        [ -f /boot/logs/nut.log ] && rm -f /boot/logs/nut.log
        [ -f /boot/logs/nut-spam ] && rm -f /boot/logs/nut-spam
    fi

    # NUT Rule-Based Repetitive Message Filtering (SYSLOG Anti-Spam Module)
    if [ "$SYSLOGFILTER" == "enable" ]; then
        if [ -f /etc/nut/xnut-nospam.conf ]; then
            if [ -f /etc/rsyslog.d/xnut-nospam.conf ] && [ ! -f /etc/rsyslog.d/98-nut-nospam.conf ]; then
                mv -f /etc/rsyslog.d/xnut-nospam.conf /etc/rsyslog.d/98-nut-nospam.conf
                chmod 644 /etc/rsyslog.d/98-nut-nospam.conf
                RESTARTSYSLOG="YES"
            fi
            if [ ! -f /etc/rsyslog.d/98-nut-nospam.conf ] || ! cmp -s /etc/nut/xnut-nospam.conf /etc/rsyslog.d/98-nut-nospam.conf; then
                echo "Adding NUT rule-based message filters to SYSLOG configuration..."
                cp -f /etc/nut/xnut-nospam.conf /etc/rsyslog.d/98-nut-nospam.conf
                chmod 644 /etc/rsyslog.d/98-nut-nospam.conf
                RESTARTSYSLOG="YES"
            fi
            if [ ! -f /etc/logrotate.d/nut-spam ] || ! cmp -s $DOCROOT/misc/nut-spam /etc/logrotate.d/nut-spam; then
                cp -f $DOCROOT/misc/nut-spam /etc/logrotate.d/nut-spam
                chmod 644 /etc/logrotate.d/nut-spam
                RESTARTSYSLOG="YES"
            fi
        fi
    else
        if [ -f /etc/rsyslog.d/98-nut-nospam.conf ]; then
            echo "Removing NUT rule-based message filters from SYSLOG configuration..."
            rm -f /etc/rsyslog.d/98-nut-nospam.conf
            RESTARTSYSLOG="YES"
        fi
        if [ -f /etc/rsyslog.d/xnut-nospam.conf ]; then
            echo "Removing NUT rule-based message filters from SYSLOG configuration..."
            rm -f /etc/rsyslog.d/xnut-nospam.conf
            RESTARTSYSLOG="YES"
        fi
        if [ -f /etc/logrotate.d/nut-spam ]; then
            rm -f /etc/logrotate.d/nut-spam
            RESTARTSYSLOG="YES"
        fi
    fi

    # Redirection for NUT Service Logs
    if [ "$SYSLOGMETHOD" == "file" ]; then
        if [ -f /etc/rsyslog.d/99-nut-to-both.conf ]; then
            rm -f /etc/rsyslog.d/99-nut-to-both.conf
            RESTARTSYSLOG="YES"
        fi
        if [ ! -f /etc/logrotate.d/nut ] || ! cmp -s $DOCROOT/misc/nut /etc/logrotate.d/nut; then
            cp -f $DOCROOT/misc/nut /etc/logrotate.d/nut
            chmod 644 /etc/logrotate.d/nut
            RESTARTSYSLOG="YES"
        fi
        if [ ! -f /etc/rsyslog.d/99-nut-to-file.conf ] || ! cmp -s $DOCROOT/misc/99-nut-to-file.conf /etc/rsyslog.d/99-nut-to-file.conf; then
            cp -f $DOCROOT/misc/99-nut-to-file.conf /etc/rsyslog.d/99-nut-to-file.conf
            chmod 644 /etc/rsyslog.d/99-nut-to-file.conf
            RESTARTSYSLOG="YES"
        fi
    elif [ "$SYSLOGMETHOD" == "both" ]; then
        if [ -f /etc/rsyslog.d/99-nut-to-file.conf ]; then
            rm -f /etc/rsyslog.d/99-nut-to-file.conf
            RESTARTSYSLOG="YES"
        fi
        if [ ! -f /etc/logrotate.d/nut ] || ! cmp -s $DOCROOT/misc/nut /etc/logrotate.d/nut; then
            cp -f $DOCROOT/misc/nut /etc/logrotate.d/nut
            chmod 644 /etc/logrotate.d/nut
            RESTARTSYSLOG="YES"
        fi
        if [ ! -f /etc/rsyslog.d/99-nut-to-both.conf ] || ! cmp -s $DOCROOT/misc/99-nut-to-both.conf /etc/rsyslog.d/99-nut-to-both.conf; then
            cp -f $DOCROOT/misc/99-nut-to-both.conf /etc/rsyslog.d/99-nut-to-both.conf
            chmod 644 /etc/rsyslog.d/99-nut-to-both.conf
            RESTARTSYSLOG="YES"
        fi
    else
        if [ -f /etc/logrotate.d/nut ]; then
            rm -f /etc/logrotate.d/nut
            RESTARTSYSLOG="YES"
        fi
        if [ -f /etc/rsyslog.d/99-nut-to-both.conf ]; then
            rm -f /etc/rsyslog.d/99-nut-to-both.conf
            RESTARTSYSLOG="YES"
        fi
        if [ -f /etc/rsyslog.d/99-nut-to-file.conf ]; then
            rm -f /etc/rsyslog.d/99-nut-to-file.conf
            RESTARTSYSLOG="YES"
        fi
    fi

    if [ "$RESTARTSYSLOG" == "YES" ]; then
        echo "Restarting SYSLOG daemon due to configurational changes for NUT..."
        sleep 1
        /etc/rc.d/rc.rsyslogd restart
        sleep 1
    fi

    # NUT Runtime Statistics Module
    /etc/rc.d/rc.nutstats check
}

case "$1" in
    shutdown) # shuts down the UPS driver
        if [ -f /etc/nut/killpower ]; then
            echo "NUT is shutting down the UPS inverter..."
            /usr/sbin/upsdrvctl -u root shutdown
        fi
        ;;
    start)  # starts everything (for a ups server box)
        sleep 1
        write_config
        sleep 1
        start
        ;;
    start_upsmon) # starts upsmon only (for a ups client box)
        sleep 1
        write_config
        sleep 1
        start_upsmon
        ;;
    stop) # stops all UPS-related daemons
        sleep 1
        write_config
        sleep 1
        stop
        ;;
    reload)
        sleep 1
        write_config
        sleep 1
        if [ "$MODE" != "slave" ]; then
            start_driver
            sleep 1
            /usr/sbin/upsd -c reload
        fi
        /usr/sbin/upsmon -c reload
        ;;
    restart)
        sleep 1
        write_config
        sleep 1
        stop
        sleep 3
        start
        ;;
    restart_udev)
        if [ -f /etc/nut/killpower ]; then
            echo "Restarting udev for NUT to be able to shut the UPS inverter off..."
            /etc/rc.d/rc.udev start
            sleep 10
        fi
        ;;
    write_config)
        write_config
        ;;
    backup_logs)
        backup_logs
        ;;
    *)
    echo "Usage: $0 {start|start_upsmon|stop|shutdown|reload|restart|write_config}"
esac
