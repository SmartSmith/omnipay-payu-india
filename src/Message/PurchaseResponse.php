<?php

namespace Omnipay\PayUIndia\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

class PurchaseResponse extends AbstractResponse implements RedirectResponseInterface
{
    public function isSuccessful(): bool
    {
        return false;
    }

    public function isRedirect(): bool
    {
        return true;
    }

    public function getRedirectUrl(): string
    {
        return $this->getRequest()->getEndpoint();
    }

    public function getRedirectMethod(): string
    {
        return 'POST';
    }

    public function getRedirectData(): array
    {
        return $this->data;
    }

    public function getTransactionId(): ?string
    {
        return $this->data['txnid'] ?? null;
    }

    public function getTransactionReference(): ?string
    {
        return null;
    }

    public function getMessage(): ?string
    {
        return null;
    }
}
