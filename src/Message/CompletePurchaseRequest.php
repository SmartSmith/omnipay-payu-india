<?php

namespace Omnipay\PayUIndia\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Message\ResponseInterface;

class CompletePurchaseRequest extends AbstractRequest
{
    use HashTrait;

    public function getCallbackData(): ?array
    {
        return $this->getParameter('callbackData');
    }

    public function setCallbackData(array $value): self
    {
        return $this->setParameter('callbackData', $value);
    }

    /**
     * @throws InvalidRequestException
     */
    public function getData(): array
    {
        $this->validate('key', 'salt');

        // Explicit callback data takes priority — supports server-to-server verification
        // where the original HTTP request is not available (e.g., forwarded from another domain).
        $callbackData = $this->getCallbackData();
        if (!empty($callbackData) && is_array($callbackData)) {
            return $callbackData;
        }

        // Fallback: read from the current HTTP request (traditional redirect flow)
        $data = $this->httpRequest->request->all() ?: $this->httpRequest->query->all();

        if (empty($data)) {
            throw new InvalidRequestException(
                'No callback data available. Provide callbackData parameter or ensure HTTP request contains PayU response.'
            );
        }

        return $data;
    }

    public function sendData($data): ResponseInterface
    {
        $isValid = $this->verifyResponseHash($data, $this->getKey(), $this->getSalt());

        return $this->response = new CompletePurchaseResponse($this, $data, $isValid);
    }
}
