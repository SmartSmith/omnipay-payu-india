<?php

namespace Omnipay\PayUIndia\Tests\Message;

use Omnipay\PayUIndia\Message\PurchaseRequest;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Tests\TestCase;

class PurchaseRequestTest extends TestCase
{
    private PurchaseRequest $request;

    public function setUp(): void
    {
        parent::setUp();
        $this->request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->initialize([
            'key' => 'gtKFFx',
            'salt' => 'eCwWELxi',
            'testMode' => true,
            'amount' => '100.00',
            'transactionId' => 'TXN_123',
            'productinfo' => 'iPhone',
            'firstname' => 'Ashish',
            'email' => 'abc@payu.in',
            'phone' => '9876543210',
            'surl' => 'https://example.com/success',
            'furl' => 'https://example.com/failure',
        ]);
    }

    public function testGetDataContainsAllMandatoryFields(): void
    {
        $data = $this->request->getData();

        $this->assertSame('gtKFFx', $data['key']);
        $this->assertSame('TXN_123', $data['txnid']);
        $this->assertSame('100.00', $data['amount']);
        $this->assertSame('iPhone', $data['productinfo']);
        $this->assertSame('Ashish', $data['firstname']);
        $this->assertSame('abc@payu.in', $data['email']);
        $this->assertSame('9876543210', $data['phone']);
        $this->assertSame('https://example.com/success', $data['surl']);
        $this->assertSame('https://example.com/failure', $data['furl']);
        $this->assertArrayHasKey('hash', $data);
    }

    public function testHashIsValid128CharHexString(): void
    {
        $data = $this->request->getData();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{128}$/', $data['hash']);
    }

    public function testHashIsCorrectlyComputed(): void
    {
        $data = $this->request->getData();

        // Manually compute expected hash
        $hashString = 'gtKFFx|TXN_123|100.00|iPhone|Ashish|abc@payu.in|||||||||||eCwWELxi';
        $expectedHash = strtolower(hash('sha512', $hashString));

        $this->assertSame($expectedHash, $data['hash']);
    }

    public function testTestModeEndpoint(): void
    {
        $this->request->setTestMode(true);
        $response = $this->request->send();

        $this->assertSame('https://test.payu.in/_payment', $response->getRedirectUrl());
    }

    public function testProductionModeEndpoint(): void
    {
        $this->request->setTestMode(false);
        $response = $this->request->send();

        $this->assertSame('https://secure.payu.in/_payment', $response->getRedirectUrl());
    }

    public function testValidationRequiresAmount(): void
    {
        $this->request->setAmount(null);
        $this->expectException(InvalidRequestException::class);
        $this->request->getData();
    }

    public function testValidationRequiresTransactionId(): void
    {
        $this->request->setTransactionId(null);
        $this->expectException(InvalidRequestException::class);
        $this->request->getData();
    }

    public function testValidationRequiresKey(): void
    {
        $request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $request->initialize([
            'salt' => 'eCwWELxi', 'amount' => '100.00', 'transactionId' => 'TXN_123',
            'productinfo' => 'iPhone', 'firstname' => 'Ashish', 'email' => 'abc@payu.in',
            'phone' => '9876543210', 'surl' => 'https://example.com/s', 'furl' => 'https://example.com/f',
        ]);
        $this->expectException(InvalidRequestException::class);
        $request->getData();
    }

    public function testValidationRequiresProductInfo(): void
    {
        $request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $request->initialize([
            'key' => 'gtKFFx', 'salt' => 'eCwWELxi', 'amount' => '100.00', 'transactionId' => 'TXN_123',
            'firstname' => 'Ashish', 'email' => 'abc@payu.in', 'phone' => '9876543210',
            'surl' => 'https://example.com/s', 'furl' => 'https://example.com/f',
        ]);
        $this->expectException(InvalidRequestException::class);
        $request->getData();
    }

    public function testValidationRequiresEmail(): void
    {
        $request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $request->initialize([
            'key' => 'gtKFFx', 'salt' => 'eCwWELxi', 'amount' => '100.00', 'transactionId' => 'TXN_123',
            'productinfo' => 'iPhone', 'firstname' => 'Ashish', 'phone' => '9876543210',
            'surl' => 'https://example.com/s', 'furl' => 'https://example.com/f',
        ]);
        $this->expectException(InvalidRequestException::class);
        $request->getData();
    }

    public function testValidationRequiresPhone(): void
    {
        $request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $request->initialize([
            'key' => 'gtKFFx', 'salt' => 'eCwWELxi', 'amount' => '100.00', 'transactionId' => 'TXN_123',
            'productinfo' => 'iPhone', 'firstname' => 'Ashish', 'email' => 'abc@payu.in',
            'surl' => 'https://example.com/s', 'furl' => 'https://example.com/f',
        ]);
        $this->expectException(InvalidRequestException::class);
        $request->getData();
    }

    public function testValidationRequiresSurl(): void
    {
        $request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $request->initialize([
            'key' => 'gtKFFx', 'salt' => 'eCwWELxi', 'amount' => '100.00', 'transactionId' => 'TXN_123',
            'productinfo' => 'iPhone', 'firstname' => 'Ashish', 'email' => 'abc@payu.in',
            'phone' => '9876543210', 'furl' => 'https://example.com/f',
        ]);
        $this->expectException(InvalidRequestException::class);
        $request->getData();
    }

    public function testValidationRequiresFurl(): void
    {
        $request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $request->initialize([
            'key' => 'gtKFFx', 'salt' => 'eCwWELxi', 'amount' => '100.00', 'transactionId' => 'TXN_123',
            'productinfo' => 'iPhone', 'firstname' => 'Ashish', 'email' => 'abc@payu.in',
            'phone' => '9876543210', 'surl' => 'https://example.com/s',
        ]);
        $this->expectException(InvalidRequestException::class);
        $request->getData();
    }

    public function testUdfFieldsDefaultToEmptyString(): void
    {
        $data = $this->request->getData();

        $this->assertSame('', $data['udf1']);
        $this->assertSame('', $data['udf2']);
        $this->assertSame('', $data['udf3']);
        $this->assertSame('', $data['udf4']);
        $this->assertSame('', $data['udf5']);
    }

    public function testUdfFieldsIncludedWhenSet(): void
    {
        $this->request->setUdf1('ref_123');
        $this->request->setUdf2('tag_456');
        $data = $this->request->getData();

        $this->assertSame('ref_123', $data['udf1']);
        $this->assertSame('tag_456', $data['udf2']);
    }

    public function testAmountFormatPreservedAsDecimalString(): void
    {
        $this->request->setAmount('1234.56');
        $data = $this->request->getData();

        $this->assertSame('1234.56', $data['amount']);
        $this->assertIsString($data['amount']);
    }

    public function testHashChangesWithDifferentInputs(): void
    {
        $data1 = $this->request->getData();

        $this->request->setAmount('200.00');
        $data2 = $this->request->getData();

        $this->assertNotSame($data1['hash'], $data2['hash']);
    }
}
