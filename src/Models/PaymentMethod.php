<?php

namespace DeveloperItsMe\FiscalService\Models;

use DeveloperItsMe\FiscalService\Exceptions\ValidationException;
use DeveloperItsMe\FiscalService\Validation\ValidationHelper;

class PaymentMethod extends Model
{
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

    /** @var string|null */
    protected $advIIC;

    /** @var string|null */
    protected $compCard;

    /** @var string|null */
    protected $bankAcc;

    public function getType(): string
    {
        return $this->type;
    }

    public function __construct($amount = 0.00, $type = self::TYPE_BANKNOTE)
    {
        $this->amount = $amount;
        $this->type = $type;
    }

    public function setAdvIIC(string $advIIC): self
    {
        $this->advIIC = $advIIC;

        return $this;
    }

    public function setCompCard(string $compCard): self
    {
        $this->compCard = $compCard;

        return $this;
    }

    public function setBankAcc(string $bankAcc): self
    {
        $this->bankAcc = $bankAcc;

        return $this;
    }

    public function validate(): void
    {
        $errors = [];

        if (in_array($this->type, [self::TYPE_ADVANCE, self::TYPE_VOUCHER])) {
            ValidationHelper::requiredAndPattern($errors, $this->advIIC, ValidationHelper::HEX_32, 'advIIC', 'Advance IIC (AdvIIC)', 'HEX-32');
        } else {
            ValidationHelper::pattern($errors, $this->advIIC, ValidationHelper::HEX_32, 'advIIC', 'Advance IIC (AdvIIC)', 'HEX-32');
        }

        if ($this->type === self::TYPE_COMPANY) {
            ValidationHelper::required($errors, $this->compCard, 'compCard', 'Company card (CompCard)');
        }
        ValidationHelper::maxLength($errors, $this->compCard, 50, 'compCard', 'Company card (CompCard)');

        ValidationHelper::maxLength($errors, $this->bankAcc, 50, 'bankAcc', 'Bank account (BankAcc)');

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    public function toArray(): array
    {
        return [
            'type'      => $this->type,
            'amount'    => $this->amount,
            'adv_iic'   => $this->advIIC,
            'comp_card' => $this->compCard,
            'bank_acc'  => $this->bankAcc,
        ];
    }

    public function toXML(): string
    {
        $writer = $this->getXmlWriter();
        $writer->startElementNs(null, 'PayMethod', null);
        $writer->writeAttribute('Amt', $this->formatNumber($this->amount, 2));
        $writer->writeAttribute('Type', $this->type);
        if ($this->advIIC) {
            $writer->writeAttribute('AdvIIC', $this->advIIC);
        }
        if ($this->compCard) {
            $writer->writeAttribute('CompCard', $this->compCard);
        }
        if ($this->bankAcc) {
            $writer->writeAttribute('BankAcc', $this->bankAcc);
        }
        $writer->endElement();

        return $writer->outputMemory();
    }

    public function isAllowedForInvoiceType($invoiceType): bool
    {
        return in_array($this->type, $this->allowedInvoices[$invoiceType]);
    }
}
