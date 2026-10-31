<?php

declare(strict_types=1);

namespace Omnipay\PayUIndia\Tests\Endpoints;

use Omnipay\Omnipay;
use Omnipay\PayUIndia\Gateway;
use Omnipay\PayUIndia\Message\HashTrait;
use Omnipay\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

/**
 * The gateway as a consuming app builds it: SmartPay's GatewayFactory calls
 * `Omnipay::create('PayUIndia', $client)` and initializes it with the merchant's key and salt. The
 * HTTP client is omnipay/tests' mock client, so any request the gateway sent would be recorded
 * there. Only verifyPayment calls a PayU API; purchase is a browser POST to PayU, and the callback
 * is verified by its hash.
 */
abstract class EndpointTestCase extends TestCase
{
    protected const string KEY = 'test_merchant_key';

    protected const string SALT = 'test_merchant_salt';

    protected function gateway(?HttpRequest $httpRequest = null): Gateway
    {
        $gateway = Omnipay::create('PayUIndia', $this->getHttpClient(), $httpRequest ?? $this->getHttpRequest());
        $this->assertInstanceOf(Gateway::class, $gateway);
        $gateway->initialize(['key' => self::KEY, 'salt' => self::SALT, 'testMode' => true]);

        return $gateway;
    }

    /**
     * PayU's callback for a payment, signed with the merchant's salt as PayU signs it.
     *
     * @return array<string, string>
     */
    protected function signedCallback(string $status): array
    {
        $data = [
            'mihpayid' => '403993715531077182',
            'txnid' => 'TXN_123',
            'amount' => '100.00',
            'productinfo' => 'Test Product',
            'firstname' => 'John',
            'email' => 'john@example.com',
            'status' => $status,
            'mode' => 'UPI',
            'unmappedstatus' => $status === 'success' ? 'captured' : $status,
            'bank_ref_num' => '896193988312194700',
            'udf1' => 'order-42',
            'udf2' => '',
            'udf3' => '',
            'udf4' => '',
            'udf5' => '',
        ];
        $signer = new class
        {
            use HashTrait;
        };
        $data['hash'] = $signer->generateReverseHash(
            self::SALT,
            $status,
            $data['email'],
            $data['firstname'],
            $data['productinfo'],
            $data['amount'],
            $data['txnid'],
            self::KEY,
            $data['udf1'],
        );

        return $data;
    }

    protected function assertNoHttpRequestSent(): void
    {
        $this->assertSame([], $this->getMockedRequests(), 'This operation calls no PayU API.');
    }
}
