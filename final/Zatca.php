<?php
require_once 'const.php';

class Zatca {   
    /**
     * Genrates Public & Private Keys for the first time use
     * @param void
     * @return array the private key `$privateKey` and the public key `$publicKey` inside an array, in addition to the private key path `$privateKeyPath`
     */
    public function generateKeys(): array
    {    
        // Generate Private Key
        $privateKeySSL = openssl_pkey_new(PRIVATE_KEY_OPTIONS); // PRIVATE_KEY_OPTIONS is in const.php
        openssl_pkey_export($privateKeySSL, $privateKeyStr);
                
        // Generate Public Key
        $details = openssl_pkey_get_details($privateKeySSL);
        $publicKey = $details['key'];

        // Store Private Key in a .pem File
        $privateKeyFile = fopen('PrivateKey.pem', 'w');
        fwrite($privateKeyFile, str_replace("PRIVATE", "EC PRIVATE", $privateKeyStr));
        fclose($privateKeyFile);


        return [
        'privateKey' => $privateKeyStr,
        'privateKeyPath' => __DIR__.SEP."PrivateKey.pem",
        'publicKey' => $publicKey,
        'privateKeySSL' => $privateKeySSL
        ];
    }

    /**
     * Checks The input array to `createConfigCnf()` function is valid
     * @param array $data The config.cnf data to be filled as an array
     * @return string A statement of success or failure, indicating the 
     */
    public function validateDataArray(array $data): string
    {
        foreach (CONFIG_CNF_DATA as $key) {
            if (array_search($key, $data) === false) {
                return "ERROR $key was not found";
            }
            return "Array is Valid";
        }
    }

    /**
     * Creates a `config.cnf` file based on a data array
     * @param array $data The data included in array with keys `$emailAddress`, `$commonName`, `$country`, `$organizationalUnitName`, `$organizationName`, `$serialNumber`, `$vatNumber`, `$invoiceType`, `registeredAddress` & `businessCategory`
     * @param string $output the output file name (without extension)
     * @return array [
     * 
     * `data` => the file content as a string
     * 
     * `$path` => the `config.cnf` file path
     * 
     * ]
     */
    public function createConfigCnf(array $data): array
    {
        $template = CONFIG_CNF_FILE_TEMPLATE;
        foreach($data as $key => $value)
        {
            $configCnf = str_replace("__$key", $value, $template);
            $template = $configCnf;
        }
        
        $configCnfFile = fopen('config.cnf', 'w');
        fwrite($configCnfFile, $configCnf);
        fclose($configCnfFile);
        
        return ['data' => $configCnf, 'path' => __DIR__.SEP."config.cnf"];
    }

    /**
     * Creates a Certificate Signing Request based on a private key and a configuration file
     * @param string $privateKeyPath The path to the stored private key
     * @param string $configCnfPath The path to `.cnf` file
     * @param string $outputFileName The desired name for the output csr file, without extension. Default set to `taxpayer`
     * @return array [
     * 
     * `utf8` => The csr string decoded in utf8
     * 
     * `base64` => The csr string encoded to base64
     * 
     * ]
     */
    public function createCsr(string $privateKeyPath, string $configCnfPath, ?string $outputFileName = 'taxpayer'): array
    {
        shell_exec("openssl req -new -sha256 -key \"$privateKeyPath\" -extensions v3_req -config \"$configCnfPath\" -out $outputFileName.csr");
        $csrFile = fopen("$outputFileName.csr", 'r');
        $csr = fread($csrFile, filesize("$outputFileName.csr"));
        $csrEncoded = base64_encode($csr);
        fclose($csrFile);
        return ['utf8' => $csr, 'base64' => $csrEncoded];
    }


    /** Get Compliance CSID
     * @param string $encodedCsr The generated CSR ecnoded to Base64
     * @return array JSON response as a key-value array
     */
    public function getCompCsid(string $encodedCsr): array
    {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, ZATCA_ORIGIN."/compliance");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'accept: application/json',
          'OTP: 123345',
          'Accept-Version: V2',
          'Content-Type: application/json',
      ]);
      curl_setopt($ch, CURLOPT_POSTFIELDS, "{\n  \"csr\": \"$encodedCsr\"\n}");

      $response = curl_exec($ch);

      curl_close($ch);
      return json_decode($response, true);
    }

    /**
     * Get a Production CSID based on an authorized CSID & a compliance request id
     * @param array $authorization The authorization credentials in an array. The `getCompCsid` return
     * @return array The JSON response as a key-value array
     */
    public function getProdCsid(array $authorization): array
    {
        $complianceRequestId = $authorization['requestID'];
        $username = $authorization['binarySecurityToken'];
        $password = $authorization['secret'];

        $basicToken = base64_encode("$username:$password");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, ZATCA_ORIGIN.'/production/csids');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'accept: application/json',
          'Accept-Version: V2',
          'Authorization: Basic '.$basicToken,
          'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{\n  \"compliance_request_id\": \"$complianceRequestId\"\n}");

        $response = curl_exec($ch);

        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Gets a new production CSID based on a submitted CSR
     * @param string $encodedCsr The generated CSR ecnoded to Base64
     * @return array JSON response as a key-value array
     */
    public function renewProdCsid(string $encodedCsr): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, ZATCA_ORIGIN."/production/csids");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json',
            'OTP: 123456',
            'accept-language: en',
            'Accept-Version: V2',
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{\n  \"csr\": \"$encodedCsr\"\n}");

        $response = curl_exec($ch);

        curl_close($ch);
        // Return empty array, investigate later
        echo $response;
        return json_decode($response, true);
    }

    public function createXmlInvoice()
    {
        //
    }

    /**
     * Signs an XML E-invoice and return the signed invoice (both encoded and decoded) in addition to the signed invoice hash
     * @param string $xmlInvoicePath The path to the xml invoice document
     * @param string $privateKeyPath The path to the private key
     * @return array [
     * `utf8Invoice` => The signed xml document as a string,
     * 
     * `invoice` => The signed xml document encoded to base64,
     * 
     * `invoiceHash` => The signed invoice hash
     * ]
     */
    public function signXmlInvoice(string $xmlInvoicePath, string $privateKeyPath = 'PrivateKey.pem'): array
    {
        $cmd = shell_exec("fatoora -sign -qr -invoice \"$xmlInvoicePath\" -privateKey \"$privateKeyPath\"");
        $invoiceHash = explode("INVOICE HASH = ", $cmd)[1];
        $invoiceHash = str_replace("\n", "", $invoiceHash);
        $invoicePathSegments = explode(SEP,$xmlInvoicePath);
        $invoiceFileName = $invoicePathSegments[count($invoicePathSegments) - 1];
        $signedInvoiceFileName = str_replace(".xml", "_signed.xml", $invoiceFileName);
        $signedInvoiceFile = fopen($signedInvoiceFileName, 'r');
        $signedInvoice = fread($signedInvoiceFile, filesize($signedInvoiceFileName));
        $encodedSignedInvoice = base64_encode($signedInvoice);
        fclose($signedInvoiceFile);

        return [
            "utf8Invoice" => $signedInvoice, 
            "invoice" => $encodedSignedInvoice,
            "invoiceHash" => $invoiceHash
        ];
    }

    /**
     * Validates an invoice using compliance credentials
     * @param array $invoiceData an array following this schema
     * 
     * [
     *  `invoice` => The signed xml document encoded to base64
     * 
     * `invoiceHash` => The signed invoice hash
     * ]
     * @param string $uuid UUID V4 for the device
     * @param array $authorization The authorization credentials in an array. `getCompCsid` return can be passed, or an array following the same schema
     * 
     * [
     * `$username` => `binarySecurityToken`,
     * `$password` => `secret`
     * ]
     * @return array The JSON response as a key-value array
     */
    public function validateCompXmlInvoice(array $invoiceData, string $uuid, array $authorization): array
    {
        // uuid should be same as the one in the invoice, we will handle that in the `createXmlInvoice` function
        $username = $authorization['binarySecurityToken'];
        $password = $authorization['secret'];

        $basicToken = base64_encode("$username:$password");

        $invoice = $invoiceData['invoice'];
        $invoiceHash = $invoiceData['invoiceHash'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, ZATCA_ORIGIN.'/compliance/invoices');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json',
            'Accept-Language: en',
            'Accept-Version: V2',
            'Authorization: Basic '. $basicToken,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{\n  \"invoiceHash\": \"$invoiceHash\",\n  \"uuid\": \"$uuid\",\n  \"invoice\": \"$invoice\"\n}");

        $response = curl_exec($ch);

        curl_close($ch);

        echo $response;
        return json_decode($response, true);
    }

    /**
     * Clears (Stamps) a Standard Invoice using invoice data, UUID V4 & authorization credentials, valid ONLY when the clearance is enabled `CLEARANCE_STATUS = 1` and the invoice type is standard (B2B)
     * @param array $invoiceData an array following this schema
     * 
     * [
     * `invoice` => The signed xml document encoded to base64,
     * `invoiceHash` => The signed invoice hash
     * ]
     * @param string $uuid UUID V4 for the device
     * @param array $authorization The authorization credentials in an array. `getCompCsid` return can be passed, or an array following the same schema
     * 
     * [
     * `$username` => `binarySecurityToken`,
     * `$password` => `secret`
     * ]
     * @return array The JSON response as a key-value array
     */
    public function clearXmlInvoice(array $invoiceData, string $uuid, array $authorization): array
    {
        // uuid should be same as the one in the invoice, we will handle that in the `createXmlInvoice` function
        $username = $authorization['binarySecurityToken'];
        $password = $authorization['secret'];

        $basicToken = base64_encode("$username:$password");

        $invoice = $invoiceData['invoice'];
        $invoiceHash = $invoiceData['invoiceHash'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, ZATCA_ORIGIN.'/invoices/clearance/single');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json',
            'Accept-Language: en',
            'Accept-Version: V2',
            'Authorization: Basic '. $basicToken,
            'Clearance-Status: '. CLEARANCE_STATUS,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{\n  \"invoiceHash\": \"$invoiceHash\",\n  \"uuid\": \"$uuid\",\n  \"invoice\": \"$invoice\"\n}");

        $response = curl_exec($ch);

        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Reports a simplified Invoice using invoice data, UUID V4 & authorization credentials, valid all the times for simplified invoices (B2C). It can be used for reporting standard invoices (B2B) ONLY when the clearance is disabled `CLEARANCE_STATUS = 0`
     * @param array $invoiceData the `getProdCsid()` response or an array following this schema
     * 
     * [
     * `invoice` => The signed xml document encoded to base64,
     * `invoiceHash` => The signed invoice hash
     * ]
     * @param string $uuid UUID V4 for the device
     * @param array $authorization The authorization credentials in an array. `getCompCsid` return can be passed, or an array following the same schema
     * 
     * [
     * `$username` => `binarySecurityToken`,
     * `$password` => `secret`
     * ]
     * @return array The JSON response as a key-value array
     */
    public function reportXmlInvoice(array $invoiceData, string $uuid, array $authorization): array
    {
        // uuid should be same as the one in the invoice, we will handle that in the `createXmlInvoice` function
        $username = $authorization['binarySecurityToken'];
        $password = $authorization['secret'];

        $basicToken = base64_encode("$username:$password");

        $invoice = $invoiceData['invoice'];
        $invoiceHash = $invoiceData['invoiceHash'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, ZATCA_ORIGIN.'/invoices/reporting/single');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json',
            'Accept-Language: en',
            'Accept-Version: V2',
            'Authorization: Basic '. $basicToken,
            'Clearance-Status: '. CLEARANCE_STATUS,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{\n  \"invoiceHash\": \"$invoiceHash\",\n  \"uuid\": \"$uuid\",\n  \"invoice\": \"$invoice\"\n}");

        $response = curl_exec($ch);

        curl_close($ch);
        echo $response;

        return json_decode($response, true);
    }
}