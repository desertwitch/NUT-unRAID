#!/bin/sh
# Slackware startup script for Network UPS Tools
# Copyright 2010 V'yacheslav Stetskevych
# Edited for unRAID by macester macecapri@gmail.com
# Revised by Derek Macias 2017.05.01
#

PROG="nut"
PLGPATH="/boot/config/plugins/$PROG"
CONFIG=$PLGPATH/$PROG.cfg
DPATH=/usr/bin
export PATH=$DPATH:$PATH

# read our configuration
[ -e "$CONFIG" ] && source $CONFIG

start_driver() {
    /usr/sbin/upsdrvctl -u root start 2>&1 || exit 1
}

start_upsd() {
    if pgrep -x upsd 2>&1 >/dev/null; then
        echo "$PROG upsd is running..."
    else
        /usr/sbin/upsd -u root || exit 1
    fi
}

start_upsmon() {
    if pgrep upsmon 2>&1 >/dev/null; then
        echo "$PROG upsmon is running..."
    else
        /usr/sbin/upsmon -u root || exit 1
    fi
}

stop() {
    echo "Stopping the UPS services... "
    if pgrep -x upsd 2>&1 >/dev/null; then
        /usr/sbin/upsd -c stop

        TIMER=0
        while `killall upsd 2>/dev/null`; do
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

    if pgrep upsmon 2>&1 >/dev/null; then
        /usr/sbin/upsmon -c stop

        TIMER=0
        while `killall upsmon 2>/dev/null`; do
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

    sleep 2

    # remove pid from old package
    if [ -f /var/run/upsmon.pid ]; then
        rm /var/run/upsmon.pid
    fi

    if [ -f /var/run/nut/upsmon.pid ]; then
        rm /var/run/nut/upsmon.pid
    fi

    /usr/sbin/upsdrvctl stop

}

write_config() {
    echo "Writing $PROG config"

    if [ $MANUAL == "disable" ]; then

        # add the name
        sed -i "1 s~.*~[${NAME}]~" /etc/nut/ups.conf

        # Add the driver config
        if [ $DRIVER == "custom" ]; then
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

        # add mode standalone/netserver
        sed -i "1 s/.*/MODE=${MODE}/" /etc/nut/nut.conf

        # Add SNMP-specific config
        if [ $DRIVER == "snmp-ups" ]; then
            var10="pollfreq = ${POLL}"
            var11="community = ${COMMUNITY}"
            var12='snmp_version = v2c'
        else
            var10=''
            var11=''
            var12=''
        fi

        sed -i "4 s/.*/$var10/" /etc/nut/ups.conf
        sed -i "5 s/.*/$var11/" /etc/nut/ups.conf
        sed -i "6 s/.*/$var12/" /etc/nut/ups.conf

        # Set monitor ip address, user, password and mode
        if [ $MODE == "slave" ]; then
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
                MONPASS=$(echo $PASSWORD | base64)
                sed -i "/PASSWORD/c\MONPASS=\"${MONPASS}\"" $CONFIG
            else
                sed -i "/PASSWORD/d" $CONFIG
            fi
        fi

        # decode monitor passwords
        MONPASS=$(echo $MONPASS | base64 --decode)
        SLAVEPASS=$(echo $SLAVEPASS | base64 --decode)

        var1="MONITOR ${NAME}@${IPADDR} 1 ${MONUSER} ${MONPASS} ${MONITOR}"
        sed -i "1 s,.*,$var1," /etc/nut/upsmon.conf

        # Set which shutdown script NUT should use
        sed -i "2 s,.*,SHUTDOWNCMD \"/sbin/poweroff\"," /etc/nut/upsmon.conf

        # Set which notification script NUT should use
        sed -i "6 s,.*,NOTIFYCMD \"/usr/sbin/nut-notify\"," /etc/nut/upsmon.conf

        # Set if the ups should be turned off
        if [ $UPSKILL == "enable" ]; then
            var8='POWERDOWNFLAG /etc/nut/killpower'
            sed -i "3 s,.*,$var8," /etc/nut/upsmon.conf
        else
            var9='POWERDOWNFLAG /etc/nut/no_killpower'
            sed -i "3 s,.*,$var9," /etc/nut/upsmon.conf
        fi

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
	
    cp -f /etc/nut/* $PLGPATH/ups >/dev/null 2>&1
 
    # update permissions
    if [ -d /etc/nut ]; then
        echo "Updating permissions..."
        chown root:nut /etc/nut/*
        chmod 640 /etc/nut/*
        chown root:nut /var/run/nut
        chmod 0770 /var/run/nut
        chown root:nut /var/state/ups
        chmod 0770 /var/state/ups
        #chown -R 218:218 /etc/nut
        #chmod -R 0644 /etc/nut
    fi

    # Link shutdown scripts for poweroff in rc.6
    if [ $( grep -ic "/etc/rc.d/rc.nut restart_udev" /etc/rc.d/rc.6 ) -eq 0 ]; then
        echo "Adding UDEV lines to rc.6"
        sed -i '/\/bin\/mount -v -n -o remount,ro \//a [ -x /etc/rc.d/rc.nut ] && /etc/rc.d/rc.nut restart_udev' /etc/rc.d/rc.6
    fi

    if [ $( grep -ic "/etc/rc.d/rc.nut shutdown" /etc/rc.d/rc.6 ) -eq 0 ]; then
        echo "Adding UPS shutdown lines to rc.6"
         sed -i -e '/# Now halt /a [ -x /etc/rc.d/rc.nut ] && /etc/rc.d/rc.nut shutdown' -e //N /etc/rc.d/rc.6
    fi

}

case "$1" in
    shutdown) # shuts down the UPS driver
        if [ -f /etc/nut/killpower ]; then
            echo "Shutting down UPS driver..."
            /usr/sbin/upsdrvctl shutdown
        fi
        ;;
    start)  # starts everything (for a ups server box)
        sleep 1
        write_config
        sleep 1
        if [ "$MODE" != "slave" ]; then
            start_driver
            sleep 1
            start_upsd
        fi
        start_upsmon
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
    restart_udev)
        if [ -f /etc/nut/killpower ]; then
            echo "Restarting udev to be able to shut the UPS inverter off..."
            /etc/rc.d/rc.udev start
            sleep 10
        fi
        ;;
    write_config)
        write_config
        ;;
    *)
    echo "Usage: $0 {start|start_upsmon|stop|shutdown|reload|write_config}"
esac