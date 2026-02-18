<?php

namespace Omnipay\PayUIndia\Message;

use Omnipay\Common\Message\ResponseInterface;

class CompletePurchaseRequest extends AbstractRequest
{
    use HashTrait;

    public function getData(): array
    {
        $this->validate('key', 'salt');

        return $this->httpRequest->request->all() ?: $this->httpRequest->query->all();
    }

    public function sendData($data): ResponseInterface
    {
        $isValid = $this->verifyResponseHash($data, $this->getKey(), $this->getSalt());

        return $this->response = new CompletePurchaseResponse($this, $data, $isValid);
    }
}
