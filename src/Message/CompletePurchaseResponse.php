<?php

namespace Omnipay\PayUIndia\Message;

use Omnipay\Common\Message\AbstractResponse;

class CompletePurchaseResponse extends AbstractResponse
{
    protected bool $hashValid;

    public function __construct($request, $data, bool $hashValid)
    {
        parent::__construct($request, $data);
        $this->hashValid = $hashValid;
    }

    public function isSuccessful(): bool
    {
        return $this->hashValid && $this->getPayUStatus() === 'success';
    }

    public function isPending(): bool
    {
        $status = $this->getPayUStatus();

        return in_array($status, ['pending', 'in progress'], true);
    }

    public function isHashValid(): bool
    {
        return $this->hashValid;
    }

    public function getPayUStatus(): ?string
    {
        return isset($this->data['status']) ? strtolower($this->data['status']) : null;
    }

    public function getTransactionReference(): ?string
    {
        return $this->data['mihpayid'] ?? null;
    }

    public function getTransactionId(): ?string
    {
        return $this->data['txnid'] ?? null;
    }

    public function getMessage(): ?string
    {
        if (!$this->hashValid) {
            return 'Invalid response hash - possible tampering detected';
        }

        return $this->data['error_Message'] ?? $this->data['field9'] ?? $this->data['status'] ?? null;
    }

    public function getCode(): ?string
    {
        return $this->data['unmappedstatus'] ?? $this->data['status'] ?? null;
    }

    public function getPaymentMode(): ?string
    {
        return $this->data['mode'] ?? null;
    }

    public function getBankReference(): ?string
    {
        return $this->data['bank_ref_num'] ?? null;
    }
}
