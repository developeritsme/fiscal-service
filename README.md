# Montenegro Fiscal Service

PHP library for integrating with Montenegro's fiscal service (Poreska Uprava). Handles invoice registration, cash deposits, and terminal code (TCR) management.

## Requirements

- PHP 8.0 or higher
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
use DeveloperItsMe\FiscalService\Certificate;

// From file (production)
$fiscal = Fiscal::fromFile('/path/to/certificate.pfx', 'certificate-password');

// From file (test environment)
$fiscal = Fiscal::fromFile('/path/to/certificate.pfx', 'certificate-password', true);

// From raw PKCS12 content
$fiscal = Fiscal::fromContent($pkcs12Content, 'certificate-password', true);

// From a pre-built Certificate instance
$certificate = Certificate::fromFile('/path/to/certificate.pfx', 'certificate-password');
$fiscal = Fiscal::fromCertificate($certificate, true);

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

// Create invoice (parameter: decimal precision for items — 2, 3, or 4; default 2)
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
// Cash vs Non-cash (TypeOfInv)
$invoice->setMethod(Invoice::TYPE_CASH);     // Default
$invoice->setMethod(Invoice::TYPE_NONCASH);

// Invoice types (InvType)
$invoice->setInvoiceType(Invoice::TYPE_INVOICE);      // Regular invoice
$invoice->setInvoiceType(Invoice::TYPE_CORRECTIVE);   // Corrective invoice
$invoice->setInvoiceType(Invoice::TYPE_ADVANCE);      // Advance payment
$invoice->setInvoiceType(Invoice::TYPE_CREDIT_NOTE);  // Credit note
```

### Payment Methods

Payment methods are restricted by invoice method. Using a disallowed type throws `InvalidArgumentException`.

**Cash invoices** (`TYPE_CASH`):

```php
PaymentMethod::TYPE_BANKNOTE    // Cash
PaymentMethod::TYPE_CARD        // Card payment
PaymentMethod::TYPE_ORDER       // Order/Check
PaymentMethod::TYPE_OTHER_CASH  // Other cash
```

**Non-cash invoices** (`TYPE_NONCASH`):

```php
PaymentMethod::TYPE_BUSINESS_CARD // Business card
PaymentMethod::TYPE_VOUCHER       // Voucher
PaymentMethod::TYPE_COMPANY       // Company card
PaymentMethod::TYPE_ORDER         // Order/Check
PaymentMethod::TYPE_ADVANCE       // Advance payment
PaymentMethod::TYPE_ACCOUNT       // Bank transfer
PaymentMethod::TYPE_FACTORING     // Factoring
PaymentMethod::TYPE_OTHER         // Other
```

`TYPE_ADVANCE` and `TYPE_VOUCHER` require `setAdvIIC()`, and `TYPE_COMPANY` requires `setCompCard()`:

```php
$pm = (new PaymentMethod(100, PaymentMethod::TYPE_ADVANCE))
    ->setAdvIIC('aabb0011ccdd2233eeff44556677aabb');

$pm = (new PaymentMethod(100, PaymentMethod::TYPE_COMPANY))
    ->setCompCard('COMP-CARD-123');
```

### Item Rebates and VAT Exemptions

```php
// Rebate (discount percentage, reduces base price by default)
$item = (new Item('Product', 21))
    ->setUnitPrice(100)
    ->setRebate(10);        // 10% discount, reduces base price

// Or: rebate that does not reduce base price
$item->setRebate(10, false);

// VAT exemption (only valid when VAT rate is 0)
$item = (new Item('Exempt Product', 0))
    ->setUnitPrice(50)
    ->setExemptFromVAT(Item::EXEMPT_CL17); // VAT_CL17 through VAT_CL44
```

### Additional Invoice Fields

```php
// Payment deadline (format: YYYY-MM-DD)
$invoice->setPayDeadline('2025-12-31');

// Bank account number (max 50 characters)
$invoice->setBankAccNum('550-12332-44');

// Note (max 200 characters)
$invoice->setNote('Please pay within 30 days');
```

### Subsequent Delivery

For invoices sent after the fact (e.g. no internet at time of sale):

```php
$invoice->setSubsequentDeliveryType('NOINTERNET');
// Also: BOUNDBOOK, SERVICE, TECHNICALERROR, BUSINESSNEEDS

// Works on cash deposits too
$cashDeposit->setSubsequentDeliveryType('TECHNICALERROR');
```

### IIC References (Advance Invoices)

Reference previous advance invoices when issuing the final invoice:

```php
use DeveloperItsMe\FiscalService\Models\IICRef;

$invoice->addIICRef(new IICRef(
    'aabb0011ccdd2233eeff44556677aabb', // IIC of the advance invoice
    '2025-01-15T10:00:00+01:00',        // Issue date/time
    100.00                               // Amount (optional)
));
```

### Error Handling

All exceptions extend `FiscalException`, so you can catch broadly or specifically.

```php
use DeveloperItsMe\FiscalService\Exceptions\FiscalException;
use DeveloperItsMe\FiscalService\Exceptions\CertificateException;
use DeveloperItsMe\FiscalService\Exceptions\InvalidArgumentException;
use DeveloperItsMe\FiscalService\Exceptions\ValidationException;

// InvalidArgumentException — thrown immediately by setters on invalid input
try {
    $invoice->setNumber(0);          // must be > 0
    $invoice->setMethod('INVALID');  // must be CASH or NONCASH
} catch (InvalidArgumentException $e) {
    // $e->getMessage()
}

// ValidationException — thrown by validate() or toArray() for missing/invalid fields
try {
    $invoice->validate();
} catch (ValidationException $e) {
    $e->getErrors();    // ['field' => ['message', ...], ...]
    $e->getMessages();  // ['field: message', ...]
}

// CertificateException — invalid certificate or wrong password
try {
    $fiscal = Fiscal::fromFile('/path/to/cert.pfx', 'password');
} catch (CertificateException $e) {
    $errors = $e->getOpensslErrors();
}

// Response errors — fiscal service rejection or connection failure
$response = $fiscal->request($request)->send();

if ($response->failed()) {
    $error = $response->error();   // Error message (includes connection errors like timeouts)
    $errors = $response->errors(); // Detailed errors array
}
```

### Array Serialization

All models support `toArray()` for inspection, logging, or JSON encoding. Validation runs automatically before serialization.

```php
$arr = $invoice->toArray();
// ['uuid' => '...', 'number' => 'bu001/1/2025/en001', 'seller' => [...], 'items' => [...], ...]

json_encode($invoice->toArray());
```

### Request Timeouts

```php
$request = new RegisterInvoice($invoice);
$request->timeout(60);         // Total timeout in seconds (default: 30)
$request->connect_timeout(15); // Connection timeout in seconds (default: 10)
```

## License

MIT
