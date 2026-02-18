<?php

namespace Omnipay\PayUIndia\Message;

use Omnipay\Common\Message\ResponseInterface;

class PurchaseRequest extends AbstractRequest
{
    use HashTrait;

    public function getData(): array
    {
        $this->validate('amount', 'transactionId', 'key', 'salt', 'productinfo', 'firstname', 'email', 'phone', 'surl', 'furl');

        $data = [
            'key'         => $this->getKey(),
            'txnid'       => $this->getTransactionId(),
            'amount'      => $this->getAmount(),
            'productinfo' => $this->getProductInfo(),
            'firstname'   => $this->getFirstname(),
            'email'       => $this->getEmail(),
            'phone'       => $this->getPhone(),
            'surl'        => $this->getSurl(),
            'furl'        => $this->getFurl(),
            'udf1'        => $this->getUdf1(),
            'udf2'        => $this->getUdf2(),
            'udf3'        => $this->getUdf3(),
            'udf4'        => $this->getUdf4(),
            'udf5'        => $this->getUdf5(),
        ];

        $data['hash'] = $this->generateForwardHash(
            $data['key'],
            $data['txnid'],
            $data['amount'],
            $data['productinfo'],
            $data['firstname'],
            $data['email'],
            $this->getSalt(),
            $data['udf1'],
            $data['udf2'],
            $data['udf3'],
            $data['udf4'],
            $data['udf5']
        );

        return $data;
    }

    public function sendData($data): ResponseInterface
    {
        return $this->response = new PurchaseResponse($this, $data);
    }
}
