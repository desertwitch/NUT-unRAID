#!/bin/bash
#
# Copyright V'yacheslav Stetskevych
# Copyright Derek Macias
# Copyright macester
# Copyright gfjardim
# Copyright SimonF
# Copyright desertwitch
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
    /usr/sbin/upsdrvctl -u root start 2>&1 || error_at_start
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
    sleep 1
    write_config
    sleep 1
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
                var10="pollfreq = ${POLL}"
                var11="community = ${COMMUNITY}"
                var12='snmp_version = v2c'
            else
                var10=''
                var11=''
                var12=''
            fi

            # UPS Driver Debug Level
            if [ "$DEBLEVEL" == "default" ]; then
                var13=''
            elif [ -n "$DEBLEVEL" ]; then
                var13="debug_min = ${DEBLEVEL}"
            else
                var13=''
            fi

            sed -i "4 s/.*/$var10/" /etc/nut/ups.conf
            sed -i "5 s/.*/$var11/" /etc/nut/ups.conf
            sed -i "6 s/.*/$var12/" /etc/nut/ups.conf
            sed -i "7 s/.*/$var13/" /etc/nut/ups.conf
        fi

        # add mode standalone/netserver
        sed -i "1 s/.*/MODE=${MODE}/" /etc/nut/nut.conf

        # Set monitor ip address, user, password and mode
        if [ "$MODE" == "slave" ]; then
            MONITOR="slave"
        else
            MONITOR="master"
        fi

        # check for old USERNAME in the config then convert it
        if [ -v USERNAME ]; then
            if [ ! -v MONUSER ]; then
                MONUSER=$USERNAME
                sed -i "/USERNAME/c\MONUSER=\"${MONUSER}\"" $CONFIG
            else
                sed -i "/USERNAME/d" $CONFIG
            fi
        fi

        # check for old PASSWORD in the config then convert it
        if [ -v PASSWORD ]; then
            if [ ! -v MONPASS ]; then
                MONPASS="$(echo "$PASSWORD" | base64)"
                sed -i "/PASSWORD/c\MONPASS=\"${MONPASS}\"" $CONFIG
            else
                sed -i "/PASSWORD/d" $CONFIG
            fi
        fi

        # decode monitor passwords
        MONPASS="$(echo "$MONPASS" | base64 --decode)"
        SLAVEPASS="$(echo "$SLAVEPASS" | base64 --decode)"

        var1="MONITOR ${NAME}@${IPADDR} 1 ${MONUSER} ${MONPASS} ${MONITOR}"
        sed -i "1 s,.*,$var1," /etc/nut/upsmon.conf

        # Set which shutdown script NUT should use
        sed -i "2 s,.*,SHUTDOWNCMD \"/sbin/poweroff\"," /etc/nut/upsmon.conf

        # Set which notification script NUT should use
        sed -i "6 s,.*,NOTIFYCMD \"/usr/sbin/nut-notify\"," /etc/nut/upsmon.conf

        # Set if the ups should be turned off
        if [ "$UPSKILL" == "enable" ]; then
            var8='POWERDOWNFLAG /etc/nut/killpower'
            sed -i "3 s,.*,$var8," /etc/nut/upsmon.conf
        else
            var9='POWERDOWNFLAG /etc/nut/no_killpower'
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

        # Set upsd users
        var13="[admin]"
        var14="password=adminpass"
        var15="actions=set"
        var16="actions=fsd"
        var17="instcmds=all"
        var18="[${MONUSER}]"
        var19="password=${MONPASS}"
        var20="upsmon master"
        var21="[${SLAVEUSER}]"
        var22="password=${SLAVEPASS}"
        var23="upsmon slave"
        sed -i "1 s,.*,$var13," /etc/nut/upsd.users
        sed -i "2 s,.*,$var14," /etc/nut/upsd.users
        sed -i "3 s,.*,$var15," /etc/nut/upsd.users
        sed -i "4 s,.*,$var16," /etc/nut/upsd.users
        sed -i "5 s,.*,$var17," /etc/nut/upsd.users
        sed -i "6 s,.*,$var18," /etc/nut/upsd.users
        sed -i "7 s,.*,$var19," /etc/nut/upsd.users
        sed -i "8 s,.*,$var20," /etc/nut/upsd.users
        sed -i "9 s,.*,$var21," /etc/nut/upsd.users
        sed -i "10 s,.*,$var22," /etc/nut/upsd.users
        sed -i "11 s,.*,$var23," /etc/nut/upsd.users
    fi
    
    # save conf files to flash drive regardless of mode
    # also here in case someone directly modified files in /etc/nut
    # flash directory will be created if missing (shouldn't happen)
	
    if [ ! -d $PLGPATH/ups ]; then
        mkdir $PLGPATH/ups
    fi
	
    cp -rf /etc/nut/* $PLGPATH/ups >/dev/null 2>&1

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
        chown root:nut /var/run/nut
        chmod 0770 /var/run/nut
        chown root:nut /var/state/ups
        chmod 0770 /var/state/ups
    fi

    # Link shutdown scripts for poweroff in rc.6
    if [ "$( grep -ic "/etc/rc.d/rc.nut restart_udev" /etc/rc.d/rc.6 )" -eq 0 ]; then
        echo "Adding UDEV lines to rc.6 for NUT"
        sed -i '/\/bin\/mount -v -n -o remount,ro \//a [ -x /etc/rc.d/rc.nut ] && /etc/rc.d/rc.nut restart_udev' /etc/rc.d/rc.6
    fi

    if [ "$( grep -ic "/etc/rc.d/rc.nut shutdown" /etc/rc.d/rc.6 )" -eq 0 ]; then
        echo "Adding UPS shutdown lines to rc.6 for NUT"
         sed -i -e '/# Now halt /a [ -x /etc/rc.d/rc.nut ] && /etc/rc.d/rc.nut shutdown' -e //N /etc/rc.d/rc.6
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
        start
        ;;
    start_upsmon) # starts upsmon only (for a ups client box)
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
    *)
    echo "Usage: $0 {start|start_upsmon|stop|shutdown|reload|restart|write_config}"
esac
