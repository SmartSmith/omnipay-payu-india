<?php

namespace Omnipay\PayUIndia\Tests;

use Omnipay\PayUIndia\Gateway;
use Omnipay\PayUIndia\Message\PurchaseRequest;
use Omnipay\PayUIndia\Message\CompletePurchaseRequest;
use Omnipay\Tests\GatewayTestCase;

class GatewayTest extends GatewayTestCase
{

    public function setUp(): void
    {
        parent::setUp();
        $this->gateway = new Gateway($this->getHttpClient(), $this->getHttpRequest());
        $this->gateway->initialize([
            'key' => 'test_merchant_key',
            'salt' => 'test_merchant_salt',
            'testMode' => true,
        ]);
    }

    public function testGatewayName(): void
    {
        $this->assertSame('PayU India', $this->gateway->getName());
    }

    public function testDefaultParameters(): void
    {
        $defaults = $this->gateway->getDefaultParameters();

        $this->assertArrayHasKey('key', $defaults);
        $this->assertArrayHasKey('salt', $defaults);
        $this->assertArrayHasKey('testMode', $defaults);
        $this->assertTrue($defaults['testMode']);
    }

    public function testGetSetKey(): void
    {
        $this->gateway->setKey('my_merchant_key');
        $this->assertSame('my_merchant_key', $this->gateway->getKey());
    }

    public function testGetSetSalt(): void
    {
        $this->gateway->setSalt('my_merchant_salt');
        $this->assertSame('my_merchant_salt', $this->gateway->getSalt());
    }

    public function testPurchaseReturnsCorrectRequestClass(): void
    {
        $request = $this->gateway->purchase([
            'amount' => '100.00',
            'transactionId' => 'TXN_123',
            'productinfo' => 'Test Product',
            'firstname' => 'John',
            'email' => 'john@example.com',
            'phone' => '9876543210',
            'surl' => 'https://example.com/success',
            'furl' => 'https://example.com/failure',
        ]);

        $this->assertInstanceOf(PurchaseRequest::class, $request);
    }

    public function testCompletePurchaseReturnsCorrectRequestClass(): void
    {
        $request = $this->gateway->completePurchase([]);

        $this->assertInstanceOf(CompletePurchaseRequest::class, $request);
    }

    public function testPurchaseInheritsGatewayCredentials(): void
    {
        $request = $this->gateway->purchase([
            'amount' => '100.00',
            'transactionId' => 'TXN_123',
            'productinfo' => 'Test Product',
            'firstname' => 'John',
            'email' => 'john@example.com',
            'phone' => '9876543210',
            'surl' => 'https://example.com/success',
            'furl' => 'https://example.com/failure',
        ]);

        $this->assertSame('test_merchant_key', $request->getKey());
        $this->assertSame('test_merchant_salt', $request->getSalt());
        $this->assertTrue($request->getTestMode());
    }
}
