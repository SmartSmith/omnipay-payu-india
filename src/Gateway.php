<?php

namespace Omnipay\PayUIndia;

use Omnipay\Common\AbstractGateway;
use Omnipay\PayUIndia\Message\CompletePurchaseRequest;
use Omnipay\PayUIndia\Message\PurchaseRequest;
use Omnipay\PayUIndia\Message\VerifyPaymentRequest;

class Gateway extends AbstractGateway
{
    public function getName(): string
    {
        return 'PayU India';
    }

    public function getDefaultParameters(): array
    {
        return [
            'key' => '',
            'salt' => '',
            'testMode' => true,
        ];
    }

    public function getKey(): ?string
    {
        return $this->getParameter('key');
    }

    public function setKey(string $value): self
    {
        return $this->setParameter('key', $value);
    }

    public function getSalt(): ?string
    {
        return $this->getParameter('salt');
    }

    public function setSalt(string $value): self
    {
        return $this->setParameter('salt', $value);
    }

    public function purchase(array $options = []): PurchaseRequest
    {
        return $this->createRequest(PurchaseRequest::class, $options);
    }

    public function completePurchase(array $options = []): CompletePurchaseRequest
    {
        return $this->createRequest(CompletePurchaseRequest::class, $options);
    }

    /**
     * Ask PayU how a transaction stands (its verify_payment command), by the txnid the purchase
     * sent as `transactionId`. It reads and changes nothing.
     */
    public function verifyPayment(array $options = []): VerifyPaymentRequest
    {
        return $this->createRequest(VerifyPaymentRequest::class, $options);
    }
}
