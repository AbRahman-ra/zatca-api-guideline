<?php

require "const.php";
require "Zatca.php";

$zatca = new Zatca();

$keys = $zatca->generateKeys();

$configDetails = $zatca->createConfigCnf([
    'emailAddress' => 'abdurrahman.tantawi@gmail.com',
    'commonName' => 'mydomain.com',
    'country' => 'SA',
    'organizationalUnitName' => 'Dammam Branch',
    'organizationName' => 'Test Company',
    'serialNumber' => '1-Model|2-3492842|3-49182743421',
    'vatNumber' => '317460736806263',
    'invoiceType' => '1100',
    'registeredAddress' => 'Dammam',
    'businessCategory' => 'Software Development'
]);

$csr = $zatca->createCsr(
  $keys['privateKeyPath'],
  $configDetails['path']
);
$encodedCsr = $csr['base64'];

$compCsidResponse = $zatca->getCompCsid($encodedCsr);

// Get Production CSID
$prodCsidResponse = $zatca->getProdCsid($compCsidResponse);

// Getting Error (investigating)
// $zatca->renewProdCsid($compCsidResponse['binarySecurityToken']);

$invoice = $zatca->signXmlInvoice(
  __DIR__.SEP.'Samples'.SEP.'Standard'.SEP.'Invoice'.SEP.'Standard_Invoice_Original.xml', 
  __DIR__.SEP.'PrivateKey.pem'
);

// UUID should be same as the one in invoice
$validateInvoice = $zatca->reportXmlInvoice($invoice, '8d487816-70b8-4ade-a618-9d620b73814a', $prodCsidResponse);