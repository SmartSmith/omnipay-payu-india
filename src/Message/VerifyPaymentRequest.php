<?php

declare(strict_types=1);

namespace Omnipay\PayUIndia\Message;

/**
 * Ask PayU how a transaction stands with its `verify_payment` command: one form POST to PayU's
 * postservice, signed with sha512(key|command|txnid|salt). It reads the transaction and changes
 * nothing.
 *
 * @see https://docs.payu.in/reference/verify_payment_api
 */
class VerifyPaymentRequest extends AbstractRequest
{
    protected const TEST_VERIFY_ENDPOINT = 'https://test.payu.in/merchant/postservice.php?form=2';

    protected const PRODUCTION_VERIFY_ENDPOINT = 'https://info.payu.in/merchant/postservice.php?form=2';

    private const COMMAND = 'verify_payment';

    /** @return array{key: string, command: string, var1: string, hash: string} */
    public function getData(): array
    {
        $this->validate('key', 'salt', 'transactionId');

        $key = $this->getKey();
        $txnid = $this->getTransactionId();

        return [
            'key' => $key,
            'command' => self::COMMAND,
            'var1' => $txnid,
            'hash' => strtolower(hash('sha512', $key.'|'.self::COMMAND.'|'.$txnid.'|'.$this->getSalt())),
        ];
    }

    /** @param array{key: string, command: string, var1: string, hash: string} $data */
    public function sendData($data): VerifyPaymentResponse
    {
        $endpoint = $this->getTestMode() ? self::TEST_VERIFY_ENDPOINT : self::PRODUCTION_VERIFY_ENDPOINT;

        $httpResponse = $this->httpClient->request(
            'POST',
            $endpoint,
            ['Content-Type' => 'application/x-www-form-urlencoded', 'Accept' => 'application/json'],
            http_build_query($data, '', '&'),
        );

        $body = $httpResponse->getBody()->getContents();
        $decoded = json_decode($body, true);
        if (! is_array($decoded)) {
            $decoded = [];
        }

        return $this->response = new VerifyPaymentResponse($this, $decoded, $httpResponse->getStatusCode(), $data['var1']);
    }
}
