#!/bin/bash
sleep 1

port=$(virsh vncdisplay "$2"); 
port=${port:1};
port1=$((port + 6080));

if [ "$1" == "start" ] # If started, spawn the noVNC daemon
then
	port2=$((port + 5900));
	/var/www/html/includes/websockify/run -D --web "/var/www/html/" "$port1" "localhost:$port2"
fi 

if [ "$1" == "stop" ]
then
	pid=$(netstat -tpnl | grep ":$port1")
	pidarray=($pid)
	pid=$(echo ${pidarray[6]} | sed 's|[^0-9]||g')
	kill -15 $pid
fi
