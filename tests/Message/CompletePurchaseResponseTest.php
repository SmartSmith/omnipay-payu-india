<?php

namespace Omnipay\PayUIndia\Tests\Message;

use Omnipay\PayUIndia\Message\CompletePurchaseResponse;
use PHPUnit\Framework\TestCase;
use Mockery;

class CompletePurchaseResponseTest extends TestCase
{
    private function createResponse(array $data, bool $hashValid): CompletePurchaseResponse
    {
        $request = Mockery::mock(\Omnipay\Common\Message\RequestInterface::class);
        return new CompletePurchaseResponse($request, $data, $hashValid);
    }

    public function testSuccessStatusWithValidHash(): void
    {
        $response = $this->createResponse([
            'mihpayid' => '403993715531077182',
            'txnid' => 'TXN_123',
            'amount' => '100.00',
            'status' => 'success',
            'unmappedstatus' => 'captured',
            'mode' => 'CC',
            'bank_ref_num' => '896193988312',
        ], true);

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($response->isHashValid());
        $this->assertSame('success', $response->getPayUStatus());
    }

    public function testFailureStatusWithValidHash(): void
    {
        $response = $this->createResponse([
            'mihpayid' => '403993715531077182',
            'txnid' => 'TXN_123',
            'status' => 'failure',
            'unmappedstatus' => 'failed',
        ], true);

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isHashValid());
        $this->assertSame('failure', $response->getPayUStatus());
    }

    public function testSuccessStatusWithInvalidHash(): void
    {
        $response = $this->createResponse([
            'txnid' => 'TXN_123',
            'status' => 'success',
        ], false);

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isHashValid());
    }

    public function testGetTransactionReference(): void
    {
        $response = $this->createResponse([
            'mihpayid' => '403993715531077182',
            'txnid' => 'TXN_123',
            'status' => 'success',
        ], true);

        $this->assertSame('403993715531077182', $response->getTransactionReference());
    }

    public function testGetTransactionId(): void
    {
        $response = $this->createResponse([
            'mihpayid' => '403993715531077182',
            'txnid' => 'TXN_123',
            'status' => 'success',
        ], true);

        $this->assertSame('TXN_123', $response->getTransactionId());
    }

    public function testGetMessageWhenHashInvalid(): void
    {
        $response = $this->createResponse([
            'txnid' => 'TXN_123',
            'status' => 'success',
        ], false);

        $this->assertSame('Invalid response hash - possible tampering detected', $response->getMessage());
    }

    public function testGetMessageFromField9(): void
    {
        $response = $this->createResponse([
            'txnid' => 'TXN_123',
            'status' => 'success',
            'field9' => 'Transaction is Successful',
        ], true);

        $this->assertSame('Transaction is Successful', $response->getMessage());
    }

    public function testGetMessageFromErrorMessage(): void
    {
        $response = $this->createResponse([
            'txnid' => 'TXN_123',
            'status' => 'failure',
            'error_Message' => 'Card declined by bank',
        ], true);

        $this->assertSame('Card declined by bank', $response->getMessage());
    }

    public function testGetPaymentMode(): void
    {
        $response = $this->createResponse([
            'status' => 'success',
            'mode' => 'CC',
        ], true);

        $this->assertSame('CC', $response->getPaymentMode());
    }

    public function testGetPaymentModeUPI(): void
    {
        $response = $this->createResponse([
            'status' => 'success',
            'mode' => 'UPI',
        ], true);

        $this->assertSame('UPI', $response->getPaymentMode());
    }

    public function testGetPaymentModeNetBanking(): void
    {
        $response = $this->createResponse([
            'status' => 'success',
            'mode' => 'NB',
        ], true);

        $this->assertSame('NB', $response->getPaymentMode());
    }

    public function testGetBankReference(): void
    {
        $response = $this->createResponse([
            'status' => 'success',
            'bank_ref_num' => '896193988312194700',
        ], true);

        $this->assertSame('896193988312194700', $response->getBankReference());
    }

    public function testGetCode(): void
    {
        $response = $this->createResponse([
            'status' => 'success',
            'unmappedstatus' => 'captured',
        ], true);

        $this->assertSame('captured', $response->getCode());
    }

    public function testGetCodeFallsBackToStatus(): void
    {
        $response = $this->createResponse([
            'status' => 'failure',
        ], true);

        $this->assertSame('failure', $response->getCode());
    }

    public function testMissingMihpayidReturnsNull(): void
    {
        $response = $this->createResponse([
            'txnid' => 'TXN_123',
            'status' => 'success',
        ], true);

        $this->assertNull($response->getTransactionReference());
    }

    public function testMissingTxnidReturnsNull(): void
    {
        $response = $this->createResponse([
            'status' => 'success',
        ], true);

        $this->assertNull($response->getTransactionId());
    }

    public function testEmptyResponseData(): void
    {
        $response = $this->createResponse([], false);

        $this->assertFalse($response->isSuccessful());
        $this->assertNull($response->getPayUStatus());
        $this->assertNull($response->getTransactionReference());
        $this->assertNull($response->getTransactionId());
        $this->assertNull($response->getPaymentMode());
        $this->assertNull($response->getBankReference());
    }

    public function testStatusIsCaseInsensitive(): void
    {
        $response = $this->createResponse([
            'status' => 'SUCCESS',
        ], true);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('success', $response->getPayUStatus());
    }
}
