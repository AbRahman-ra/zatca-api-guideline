#!/bin/bash

:'
This is the installation script for the Fatoora SDK provided by 
ZATCA, this script works on bash & zsh, feel free to modify 
according to your system if you are using a different shell by
modifying the $config_file in line 24 (if any)
'

# Set the SDK Directory
export FATOORA_HOME=$(dirname "$(realpath $0)")
export PATH=$PATH:$FATOORA_HOME/Apps/

# Find the user shell
user_shell=$(basename "$SHELL")

# Touch the corresponding configuration file based on the shell
if [ "$user_shell" = "zsh" ]; then
    config_file=~/.zshrc
elif [ "$user_shell" = "bash" ]; then
    config_file=~/.bashrc
else
    # CHANGE THE LINE BELOW ONLY FOR SHELLS THAT AREN'T ZSH/BASH
    config_file=~/.bash-profile
fi

touch $config_file

SDK_CONFIG="${FATOORA_HOME}/Configuration/config.json"

echo "export PATH=$PATH:$FATOORA_HOME/Apps/" >> $config_file
echo "export FATOORA_HOME=$FATOORA_HOME/Apps" >> $config_file
echo "export SDK_CONFIG=$SDK_CONFIG" >> $config_file

source "$config_file"

cd "${FATOORA_HOME}/Configuration"

xsdPath=$(jq -r '.xsdPath' defaults.json)
xsdPathFileName="$(basename $xsdPath)"

enSchematron=$(jq -r '.enSchematron' defaults.json)
enSchematronFileName="$(basename $enSchematron)"

zatcaSchematron=$(jq -r '.zatcaSchematron' defaults.json)
zatcaSchematronFileName="$(basename $zatcaSchematron)"

certPath=$(jq -r '.certPath' defaults.json)
certPathFileName="$(basename $certPath)"

pkPath=$(jq -r '.privateKeyPath' defaults.json)
pkPathFileName="$(basename $pkPath)"

pihPath=$(jq -r '.pihPath' defaults.json)
pihPathFileName="$(basename $pihPath)"

usagePath=$(jq -r '.usagePathFile' defaults.json)
usagePathFileName="$(basename $usagePath)"


jq -n '{"xsdPath":$one, "enSchematron":$two, "zatcaSchematron":$thr,"certPath":$fou, "privateKeyPath":$fiv  ,"pihPath":$six ,"inputPath":$sev,"usagePathFile":$eight}' \
  --arg one "${FATOORA_HOME}/Data/Schemas/xsds/UBL2.1/xsd/maindoc/$xsdPathFileName" \
  --arg two "${FATOORA_HOME}/Data/Rules/schematrons/$enSchematronFileName"   \
  --arg thr "${FATOORA_HOME}/Data/Rules/schematrons/$zatcaSchematronFileName" \
  --arg fou "${FATOORA_HOME}/Data/Certificates/$certPathFileName" \
  --arg fiv "${FATOORA_HOME}/Data/Certificates/$pkPathFileName" \
  --arg six "${FATOORA_HOME}/Data/PIH/$pihPathFileName"  \
  --arg sev "${FATOORA_HOME}/Data/Input"  \
  --arg eight "${FATOORA_HOME}/Configuration/$usagePathFileName"  > ${FATOORA_HOME}/Configuration/config.json

cd ../
