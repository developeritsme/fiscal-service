# Montenegro Fiscal Service

PHP library for integrating with Montenegro's fiscal service (Poreska Uprava). Handles invoice registration, cash deposits, and terminal code (TCR) management.

## Requirements

- PHP 7.3 or higher
- Extensions: `xmlwriter`, `dom`, `openssl`, `curl`
- PKCS12 certificate from Montenegro Tax Authority

## Installation

```shell
composer require developeritsme/fiscal-service
```

## Usage

### Initialize the Fiscal Service

```php
use DeveloperItsMe\FiscalService\Fiscal;

// Production
$fiscal = new Fiscal('/path/to/certificate.pfx', 'certificate-password');

// Test environment
$fiscal = new Fiscal('/path/to/certificate.pfx', 'certificate-password', true);

// Check certificate expiration date
$expiresAt = $fiscal->certificate()->expiresAt(); // Returns DateTimeImmutable
```

### Register an Invoice

```php
use DeveloperItsMe\FiscalService\Models\Invoice;
use DeveloperItsMe\FiscalService\Models\Item;
use DeveloperItsMe\FiscalService\Models\Seller;
use DeveloperItsMe\FiscalService\Models\Buyer;
use DeveloperItsMe\FiscalService\Models\PaymentMethod;
use DeveloperItsMe\FiscalService\Requests\RegisterInvoice;

// Create seller
$seller = new Seller('Company Name', '12345678', true); // name, TIN, isVAT
$seller->setAddress('Street Address')
    ->setTown('Podgorica');

// Create invoice (parameter: decimal precision for items, default 2)
$invoice = (new Invoice(4))
    ->setNumber(1)
    ->setEnu('ab123cd456')           // TCR code (format: xx000xx000)
    ->setBusinessUnitCode('bu123bu123') // format: xx000xx000
    ->setSoftwareCode('sw123sw456')     // format: xx000xx000
    ->setOperatorCode('op123op789')     // format: xx000xx000
    ->setSeller($seller);

// Add buyer (optional, required for non-cash invoices)
$buyer = new Buyer('Buyer Name', '87654321');
$buyer->setAddress('Buyer Street')
    ->setTown('Budva')
    ->setCountry('MNE');
$invoice->setBuyer($buyer);

// Add items
$item = (new Item())
    ->setName('Product Name')
    ->setCode('1234567890123') // barcode/SKU (optional)
    ->setUnit('pcs')
    ->setQuantity(2)
    ->setUnitPrice(10.00)
    ->setVatRate(21); // 0, 7, 15, or 21

$invoice->addItem($item);

// Add payment method
$total = 20.00;
$invoice->addPaymentMethod(new PaymentMethod($total, PaymentMethod::TYPE_BANKNOTE));

// Send to fiscal service
$request = new RegisterInvoice($invoice);
$response = $fiscal->request($request)->send();

if ($response->ok()) {
    $data = $response->data();
    // $data['ikof']   - Invoice identification code (IIC)
    // $data['jikr']   - Fiscal identification code (FIC)
    // $data['url']    - QR code verification URL
    // $data['number'] - Invoice number
}

// Access raw request/response XML
$xmlRequest = $response->request();
$xmlResponse = $response->body();
```

### Register a TCR (Terminal/Cash Register)

Register a TCR once through your application. Once registered, you receive an ENU code that can be used for all subsequent invoice requests.

```php
use DeveloperItsMe\FiscalService\Models\BusinessUnit;
use DeveloperItsMe\FiscalService\Requests\RegisterTCR;

$businessUnit = (new BusinessUnit())
    ->setIdNumber('12345678')
    ->setUnitCode('bu123bu123')       // format: xx000xx000
    ->setSoftwareCode('sw123sw456')   // format: xx000xx000
    ->setMaintainerCode('mt123mt456') // format: xx000xx000
    ->setInternalId('internal-1');

$request = new RegisterTCR($businessUnit);
$response = $fiscal->request($request)->send();

if ($response->ok()) {
    $data = $response->data();
    // $data['code'] - Registered TCR code (ENU)
}
```

### Register a Cash Deposit

Must be registered once per day before sending invoices.

```php
use DeveloperItsMe\FiscalService\Models\CashDeposit;
use DeveloperItsMe\FiscalService\Requests\RegisterCashDeposit;
use Carbon\Carbon;

$cashDeposit = (new CashDeposit())
    ->setDate(Carbon::now())
    ->setIdNumber('12345678')
    ->setAmount(0) // initial amount in register
    ->setEnu('TCR-CODE')
    ->setOperation(CashDeposit::OPERATION_INITIAL);

$request = new RegisterCashDeposit($cashDeposit);
$response = $fiscal->request($request)->send();

if ($response->ok()) {
    $data = $response->data();
    // $data['fcdc'] - Fiscal cash deposit code
}
```

### Corrective Invoice

To correct a previously sent invoice:

```php
use DeveloperItsMe\FiscalService\Models\CorrectiveInvoice;

// Reference the original invoice
$corrective = new CorrectiveInvoice(
    $originalIkof,           // IKOF of original invoice
    $originalDateTime        // DateTime of original invoice
);

$invoice = (new Invoice())
    // ... set other invoice properties
    ->setCorrectiveInvoice($corrective);

// Add items with negative quantities to reverse
$item = (new Item())
    ->setName('Product Name')
    ->setQuantity(-1) // negative to reverse
    ->setUnitPrice(10.00)
    ->setVatRate(21);

$invoice->addItem($item);
```

### Supply and Tax Periods

For invoices covering a specific period:

```php
// Supply period (for services/goods delivered over time)
$invoice->setSupplyPeriod('2025-01-01', '2025-01-31');

// Or single date
$invoice->setSupplyPeriod('2025-01-15');

// Tax period (format: MM/YYYY)
$invoice->setTaxPeriod('01/2025');
```

### Invoice Types

```php
// Cash vs Non-cash
$invoice->setType(Invoice::TYPE_CASH);     // Default
$invoice->setType(Invoice::TYPE_NONCASH);

// Invoice types
$invoice->setType(Invoice::TYPE_INVOICE);      // Regular invoice
$invoice->setType(Invoice::TYPE_CORRECTIVE);   // Corrective invoice
$invoice->setType(Invoice::TYPE_ADVANCE);      // Advance payment
$invoice->setType(Invoice::TYPE_CREDIT_NOTE);  // Credit note
```

### Payment Methods

```php
PaymentMethod::TYPE_BANKNOTE  // Cash
PaymentMethod::TYPE_CARD      // Card payment
PaymentMethod::TYPE_ACCOUNT   // Bank transfer
PaymentMethod::TYPE_ORDER     // Order/Check
PaymentMethod::TYPE_OTHER     // Other
```

### Error Handling

```php
use DeveloperItsMe\FiscalService\Exceptions\CertificateException;

try {
    $fiscal = new Fiscal('/path/to/cert.pfx', 'password');
} catch (CertificateException $e) {
    // Invalid certificate or wrong password
    $errors = $e->getOpensslErrors();
}

$response = $fiscal->request($request)->send();

if ($response->failed()) {
    $error = $response->error();   // Error message (includes connection errors like timeouts)
    $errors = $response->errors(); // Detailed errors array
}
```

### Request Timeouts

```php
$request = new RegisterInvoice($invoice);
$request->timeout(60);         // Total timeout in seconds (default: 30)
$request->connect_timeout(15); // Connection timeout in seconds (default: 10)
```

## License

MIT
