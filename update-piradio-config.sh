#!/bin/bash

if [ "$EUID" -ne 0 ]; then
    echo "Please run as root"
    exit 1
fi

source /usr/local/bin/piradio.conf

aliasfile="/etc/apache2/mods-available/alias.conf"
configfile="/usr/local/bin/piradio.conf"

echo "###########################"
echo "#                         #"
echo "#  PiRadio Config update  #"
echo "#                         #"
echo "###########################"
echo
echo "current Audiopath: $path"
echo
echo
read -p "Insert a new Audio filepath or press enter to use the current filepath: " answer </dev/tty
echo
echo

if [ -z "$answer" ] ; then
  echo "The path remains unchanged"
  : # do nothing
else
  echo "The new Audiopath:  $answer  was set in the config file $configfile"
  pathstring="path=\"${path}\""
  newpathstring="path=\"${answer}\""
  sed -i "s:${pathstring}:${newpathstring}:" $configfile

  string1="Alias /audiofiles \"${path}\""
  string2="<Directory \"${path}\">"
  string1new="Alias /audiofiles \"${answer}\""
  string2new="<Directory \"${answer}\">"

  string1found=false
  string2found=false

  while read line; do
    if [ "$string1" == "$line" ] ; then
      string1found=true
    elif [ "$string2" == "$line" ] ; then
      string2found=true
    fi
  done < $aliasfile

  if [ "$string1found" == true ] && [ "$string2found" == true ] ; then
    echo "The new Audiopath:  $answer  was set in the config file $aliasfile"
    sed -i "s:${string1}:${string1new}:" $aliasfile
    sed -i "s:${string2}:${string2new}:" $aliasfile
  elif [ "$string1found" == false ] && [ "$string2found" == false ] ; then
    echo "No entry was found in Apache2 config file $aliasfile"
    echo "The new Audiopath:  $answer  was set in the config file $aliasfile"
    echo "" >> $aliasfile
    echo -e "\t$string1new" >> $aliasfile
    echo "" >> $aliasfile
    echo -e "\t$string2new" >> $aliasfile
    echo -e "\t\tRequire all granted" >> $aliasfile
    echo -e "\t</Directory>" >> $aliasfile
  else
    echo "The Apach2 config file seems invalid. Please check the config file $aliasfile"
    : # do nothing
  fi
fi
