# ZATCA Phase 2 Integration

## A simplified guideline for integrating your API with ZATCA (ZAkat, Tax & Customs Authorization) e-invoicing API Written in PHP (Laravel Friendly)

---

## API Documentation

- [Official Documentation](https://sandbox.zatca.gov.sa/IntegrationSandbox)
- [My Documentation (Easier, Shorter & Simpler)](https://documenter.getpostman.com/view/28563220/2sA2r3Z69q)

---

## Requirements

- [Downloading ZATCA SDK, Requires Java 11](https://sandbox.zatca.gov.sa/downloadSDK)
  - The SDK installation scripts provided by ZATCA have errors, replace `install.bat` & `install.sh` files with the ones uploaded in this repo. Then follow their steps on the link for installation as normal
  - Samples for XMLs are inside the SDK extracted zip file (`Data/Samples/` folder)
  - Also, Copy the `Data/` folder after extraction and paste in inside the `Configuration/` folder
- PHP installed (preferably v8.1+)

---

## Directories Structure

- `scratches/`: Trying here and there, it's worling but a bit messy. You can just ignore it
- `final/`: Everything is organized and accessible

---

## Files Structure (inside `final/`)

- `const.php`: All constants, templates & static data are defined here
- `Zatca.php`: The `ZATCA` class file, all methods and logic is inside
- `requests.php`: The testing file, we run this file to test the methods inside the `Zatca` class
