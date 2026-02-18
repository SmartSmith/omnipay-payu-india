<?php

namespace Omnipay\PayUIndia\Tests\Message;

use Omnipay\PayUIndia\Message\PurchaseResponse;
use Omnipay\PayUIndia\Message\PurchaseRequest;
use Omnipay\Tests\TestCase;
use Mockery;

class PurchaseResponseTest extends TestCase
{
    private function createResponse(array $data = [], bool $testMode = true): PurchaseResponse
    {
        $request = Mockery::mock(PurchaseRequest::class);
        $request->shouldReceive('getEndpoint')->andReturn(
            $testMode ? 'https://test.payu.in/_payment' : 'https://secure.payu.in/_payment'
        );

        return new PurchaseResponse($request, $data);
    }

    public function testIsNotSuccessful(): void
    {
        $response = $this->createResponse([
            'key' => 'testKey',
            'txnid' => 'TXN_123',
            'amount' => '100.00',
            'hash' => 'somehash',
        ]);

        $this->assertFalse($response->isSuccessful());
    }

    public function testIsRedirect(): void
    {
        $response = $this->createResponse();

        $this->assertTrue($response->isRedirect());
    }

    public function testRedirectMethodIsPost(): void
    {
        $response = $this->createResponse();

        $this->assertSame('POST', $response->getRedirectMethod());
    }

    public function testRedirectUrlTestMode(): void
    {
        $response = $this->createResponse([], true);

        $this->assertSame('https://test.payu.in/_payment', $response->getRedirectUrl());
    }

    public function testRedirectUrlProductionMode(): void
    {
        $response = $this->createResponse([], false);

        $this->assertSame('https://secure.payu.in/_payment', $response->getRedirectUrl());
    }

    public function testRedirectDataContainsAllFormFields(): void
    {
        $formData = [
            'key' => 'testKey',
            'txnid' => 'TXN_123',
            'amount' => '100.00',
            'productinfo' => 'iPhone',
            'firstname' => 'John',
            'email' => 'john@test.com',
            'phone' => '9876543210',
            'surl' => 'https://example.com/success',
            'furl' => 'https://example.com/failure',
            'hash' => 'abc123def456',
            'udf1' => '',
            'udf2' => '',
            'udf3' => '',
            'udf4' => '',
            'udf5' => '',
        ];

        $response = $this->createResponse($formData);
        $redirectData = $response->getRedirectData();

        $this->assertSame($formData, $redirectData);
        $this->assertSame('testKey', $redirectData['key']);
        $this->assertSame('TXN_123', $redirectData['txnid']);
        $this->assertSame('abc123def456', $redirectData['hash']);
    }

    public function testGetTransactionId(): void
    {
        $response = $this->createResponse(['txnid' => 'TXN_456']);

        $this->assertSame('TXN_456', $response->getTransactionId());
    }

    public function testGetTransactionReferenceIsNull(): void
    {
        $response = $this->createResponse(['txnid' => 'TXN_456']);

        $this->assertNull($response->getTransactionReference());
    }
}
