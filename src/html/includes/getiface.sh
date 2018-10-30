#!/bin/bash

arp -na |

awk -v mac="$1" '$0 ~ " at " mac {gsub("[()]", "", $2); print $2}'

