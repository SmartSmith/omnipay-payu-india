<?php

namespace Omnipay\PayUIndia\Message;

use Omnipay\Common\Message\AbstractRequest as OmnipayAbstractRequest;

abstract class AbstractRequest extends OmnipayAbstractRequest
{
    protected const TEST_ENDPOINT = 'https://test.payu.in/_payment';
    protected const PRODUCTION_ENDPOINT = 'https://secure.payu.in/_payment';

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

    public function getProductInfo(): ?string
    {
        return $this->getParameter('productinfo');
    }

    public function setProductInfo(string $value): self
    {
        return $this->setParameter('productinfo', $value);
    }

    public function getFirstname(): ?string
    {
        return $this->getParameter('firstname');
    }

    public function setFirstname(string $value): self
    {
        return $this->setParameter('firstname', $value);
    }

    public function getEmail(): ?string
    {
        return $this->getParameter('email');
    }

    public function setEmail(string $value): self
    {
        return $this->setParameter('email', $value);
    }

    public function getPhone(): ?string
    {
        return $this->getParameter('phone');
    }

    public function setPhone(string $value): self
    {
        return $this->setParameter('phone', $value);
    }

    public function getSurl(): ?string
    {
        return $this->getParameter('surl');
    }

    public function setSurl(string $value): self
    {
        return $this->setParameter('surl', $value);
    }

    public function getFurl(): ?string
    {
        return $this->getParameter('furl');
    }

    public function setFurl(string $value): self
    {
        return $this->setParameter('furl', $value);
    }

    public function getUdf1(): string
    {
        return $this->getParameter('udf1') ?? '';
    }

    public function setUdf1(string $value): self
    {
        return $this->setParameter('udf1', $value);
    }

    public function getUdf2(): string
    {
        return $this->getParameter('udf2') ?? '';
    }

    public function setUdf2(string $value): self
    {
        return $this->setParameter('udf2', $value);
    }

    public function getUdf3(): string
    {
        return $this->getParameter('udf3') ?? '';
    }

    public function setUdf3(string $value): self
    {
        return $this->setParameter('udf3', $value);
    }

    public function getUdf4(): string
    {
        return $this->getParameter('udf4') ?? '';
    }

    public function setUdf4(string $value): self
    {
        return $this->setParameter('udf4', $value);
    }

    public function getUdf5(): string
    {
        return $this->getParameter('udf5') ?? '';
    }

    public function setUdf5(string $value): self
    {
        return $this->setParameter('udf5', $value);
    }

    public function getEndpoint(): string
    {
        return $this->getTestMode() ? self::TEST_ENDPOINT : self::PRODUCTION_ENDPOINT;
    }
}
