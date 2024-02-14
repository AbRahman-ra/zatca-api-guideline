<?php

// Constants used across the application in the ZATCA part

/**
 * Private Key Options, Called when generating a new private key
 */
define('PRIVATE_KEY_OPTIONS', [
    'private_key_type' => OPENSSL_KEYTYPE_EC,
    'curve_name' => 'secp256k1',
]);

/**
 * Data keys that should be filled, called when validating a data array
 */
define('CONFIG_CNF_DATA', [
    'emailAddress',
    'commonName',
    'country',
    'organizationalUnitName',
    'organizationName',
    'serialNumber',
    'vatNumber',
    'invoiceType',
    'registeredAddress',
    'businessCategory'
]);

/**
 * `Config.cnf` File Template, called when generating new `config.cnf` file. The last 3 lines of this template can be uncommented by removing the '#' sign. I found no difference and it works fine for both statuses.
 */
define('CONFIG_CNF_FILE_TEMPLATE', "oid_section=OIDS
[ OIDS ]
certificateTemplateName= 1.3.6.1.4.1.311.20.2
[req]
default_bits=2048
emailAddress=__emailAddress
req_extensions=v3_req
x509_extensions=v3_Ca
prompt=no
default_md=sha256
req_extensions=req_ext
distinguished_name=dn
[dn]
CN=__commonName
C=__country
OU=__organizationalUnitName
O=__organizationName
[v3_req]
basicConstraints = CA:FALSE
keyUsage = nonRepudiation, digitalSignature, keyEncipherment
[req_ext]
certificateTemplateName = ASN1:PRINTABLESTRING:PREZATCA-code-Signing
subjectAltName = dirName:alt_names
[alt_names]
SN=__serialNumber
UID=__vatNumber
title=__invoiceType
registeredAddress=__registeredAddress
businessCategory=__businessCategory
# [v3_Ca]
# subjectKeyIdentifier=hash
# authorityKeyIdentifier=keyid:always,issue");

/**
 * ZATCA Origin, the URL origin we are hitting, called when sending requests 
 */
define('ZATCA_ORIGIN', 'https://gw-fatoora.zatca.gov.sa/e-invoicing/developer-portal');

/**
 * OS Seperator, backslash for windows, forward slash otherwise, called when dealing with file paths in general
 */
define('SEP', str_starts_with(PHP_OS, 'WIN') ? "\\" : "/");

/**
 * Clearance Status, called when using clearance or reporting APIs. Default is 1 (enabled), if it's disabled for some reason, change it to 0
 */
define('CLEARANCE_STATUS', 0);