#!/bin/bash
#
# DO NOT TOUCH THIS FILE IF YOU DO NOT KNOW WHAT YOU ARE DOING
# ESPECIALLY DO NOT TOUCH THIS FILE IF YOU HAVE NEVER USED A LINUX TERMINAL
#
# This file defines custom commands to be run when certain UPS statuses are
# encountered. The commands run NON-BLOCKING, meaning NUT does NOT WAIT for
# them to complete before proceeding with its internal (shutdown) routines,
# to ensure safe shutdowns regardless of any errors induced by this script.
#
# DO NOT PUT ANY COMMANDS OR STATEMENTS OUTSIDE OF THE ALREADY DEFINED FUNCTIONS
# BEWARE THAT FUNCTIONS CANNOT BE EMPTY SO KEEP THE ECHO STATEMENT IN EACH FUNCTION
#

ONLINE () {
    echo "ONLINE commands are executing..."
    # Put commands to run when the UPS is first seen ON LINE in this function:
    # Note that these may also get executed during short flickers or returning to ONLINE from other statuses.


}

ONBATT () {
    echo "ONBATT commands are executing..."
    # Put commands to run when the UPS is first seen ON BATTERY in this function:
    # Note that these may also get executed during short flickers or returning to ONBATT from other statuses.


}

ONBATT_SHUTDOWN () {
    echo "ONBATT_SHUTDOWN commands are executing..."
    # Put commands to run when the UPS is about to SHUTDOWN from an ON BATTERY event in this function:
    # Note that these do not get executed during an emergency shutdown e.g. due to premature battery failure.


}

REPLBATT () {
    echo "REPLBATT commands are executing..."
    # Put commands to run when the UPS reports that BATTERIES NEED REPLACEMENT in this function:
    # Note that these may also get executed every RBWARNTIME and whenever the UPS firmware emits this status.


}

#
# You can define additional functions here, if you configured for additional statuses to be notified of.
# The name of the function needs to be the respective UPS event, these can be found listed e.g. in the manual:
# https://networkupstools.org/docs/man/upsmon.html -> refer to the section 'NOTIFY EVENTS' for various events.
#
