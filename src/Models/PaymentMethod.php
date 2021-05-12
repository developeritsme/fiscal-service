<?php

namespace DeveloperItsMe\FiscalService\Models;

use DeveloperItsMe\FiscalService\Traits\HasXmlWriter;

class PaymentMethod extends Model
{
    use HasXmlWriter;

    public const TYPE_BANKNOTE = 'BANKNOTE';
    public const TYPE_CARD = 'CARD';
    public const TYPE_BUSINESS_CARD = 'BUSINESSCARD';
    public const TYPE_VOUCHER = 'SVOUCHER';
    public const TYPE_COMPANY = 'COMPANY';
    public const TYPE_ORDER = 'ORDER';
    public const TYPE_ADVANCE = 'ADVANCE';
    public const TYPE_ACCOUNT = 'ACCOUNT';
    public const TYPE_FACTORING = 'FACTORING';
    public const TYPE_OTHER = 'OTHER';
    public const TYPE_OTHER_CASH = 'OTHER-CASH';

    protected $allowedInvoices = [
        Invoice::TYPE_CASH    => [
            self::TYPE_BANKNOTE,
            self::TYPE_CARD,
            self::TYPE_ORDER,
            self::TYPE_OTHER_CASH,
        ],
        Invoice::TYPE_NONCASH => [
            self::TYPE_BUSINESS_CARD,
            self::TYPE_VOUCHER,
            self::TYPE_COMPANY,
            self::TYPE_ORDER,
            self::TYPE_ADVANCE,
            self::TYPE_ACCOUNT,
            self::TYPE_FACTORING,
            self::TYPE_OTHER,
        ],
    ];

    /** @var float|mixed */
    protected $amount;

    /**
     * @var string
     */
    protected $type;

    public function __construct($amount = 0.00, $type = self::TYPE_BANKNOTE)
    {
        $this->amount = $amount;
        $this->type = $type;
    }

    public function toXML(): string
    {
        $writer = $this->getXmlWriter();
        $writer->startElementNs(null, 'PayMethod', null);
        $writer->writeAttribute('Amt', number_format($this->amount, 2));
        $writer->writeAttribute('Type', $this->type);
        $writer->endElement();

        return $writer->outputMemory();
    }

    public function isAllowedForInvoiceType($invoiceType): bool
    {
        return in_array($this->type, $this->allowedInvoices[$invoiceType]);
    }
}
