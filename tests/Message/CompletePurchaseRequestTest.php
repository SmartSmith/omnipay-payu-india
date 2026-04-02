<?php

namespace Omnipay\PayUIndia\Tests\Message;

use Omnipay\PayUIndia\Message\CompletePurchaseRequest;
use Omnipay\Tests\TestCase;
use Omnipay\PayUIndia\Message\HashTrait;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

class CompletePurchaseRequestTest extends TestCase
{
    private string $key = 'testKey';
    private string $salt = 'testSalt';

    private function createRequestWithPostData(array $postData): CompletePurchaseRequest
    {
        $httpRequest = new HttpRequest([], $postData);
        $request = new CompletePurchaseRequest($this->getHttpClient(), $httpRequest);
        $request->initialize([
            'key' => $this->key,
            'salt' => $this->salt,
        ]);

        return $request;
    }

    private function buildValidResponseData(string $status = 'success'): array
    {
        $hasher = new class { use HashTrait; };

        $data = [
            'txnid' => 'TXN_123',
            'amount' => '100.00',
            'productinfo' => 'Test Product',
            'firstname' => 'John',
            'email' => 'john@test.com',
            'status' => $status,
            'mihpayid' => '403993715531077182',
            'mode' => 'CC',
            'unmappedstatus' => $status === 'success' ? 'captured' : 'failed',
            'bank_ref_num' => '896193988312194700',
            'udf1' => '',
            'udf2' => '',
            'udf3' => '',
            'udf4' => '',
            'udf5' => '',
        ];

        $data['hash'] = $hasher->generateReverseHash(
            $this->salt,
            $status,
            $data['email'],
            $data['firstname'],
            $data['productinfo'],
            $data['amount'],
            $data['txnid'],
            $this->key,
            $data['udf1'],
            $data['udf2'],
            $data['udf3'],
            $data['udf4'],
            $data['udf5']
        );

        return $data;
    }

    public function testSuccessfulResponseWithValidHash(): void
    {
        $postData = $this->buildValidResponseData('success');
        $request = $this->createRequestWithPostData($postData);
        $response = $request->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($response->isHashValid());
        $this->assertSame('403993715531077182', $response->getTransactionReference());
        $this->assertSame('TXN_123', $response->getTransactionId());
    }

    public function testFailedResponseWithValidHash(): void
    {
        $postData = $this->buildValidResponseData('failure');
        $request = $this->createRequestWithPostData($postData);
        $response = $request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isHashValid());
        $this->assertSame('failure', $response->getPayUStatus());
    }

    public function testResponseWithInvalidHash(): void
    {
        $postData = $this->buildValidResponseData('success');
        $postData['hash'] = 'tampered_invalid_hash_value';
        $request = $this->createRequestWithPostData($postData);
        $response = $request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isHashValid());
        $this->assertSame('Invalid response hash - possible tampering detected', $response->getMessage());
    }

    public function testResponseWithTamperedAmount(): void
    {
        $postData = $this->buildValidResponseData('success');
        $postData['amount'] = '999.00'; // Tamper with amount
        $request = $this->createRequestWithPostData($postData);
        $response = $request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isHashValid());
    }

    public function testResponseWithMissingHash(): void
    {
        $postData = $this->buildValidResponseData('success');
        unset($postData['hash']);
        $request = $this->createRequestWithPostData($postData);
        $response = $request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isHashValid());
    }

    public function testResponseWithUdfFields(): void
    {
        $hasher = new class { use HashTrait; };

        $data = [
            'txnid' => 'TXN_UDF',
            'amount' => '200.00',
            'productinfo' => 'Premium',
            'firstname' => 'Jane',
            'email' => 'jane@test.com',
            'status' => 'success',
            'mihpayid' => '12345678',
            'mode' => 'NB',
            'udf1' => 'custom_ref',
            'udf2' => 'tag_abc',
            'udf3' => '',
            'udf4' => '',
            'udf5' => '',
        ];

        $data['hash'] = $hasher->generateReverseHash(
            $this->salt, 'success', 'jane@test.com', 'Jane', 'Premium', '200.00', 'TXN_UDF', $this->key,
            'custom_ref', 'tag_abc', '', '', ''
        );

        $request = $this->createRequestWithPostData($data);
        $response = $request->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($response->isHashValid());
    }

    public function testGetDataReadsFromHttpPostRequest(): void
    {
        $postData = ['txnid' => 'TXN_123', 'status' => 'success'];
        $request = $this->createRequestWithPostData($postData);
        $data = $request->getData();

        $this->assertSame('TXN_123', $data['txnid']);
        $this->assertSame('success', $data['status']);
    }

    // --- callbackData parameter tests (server-to-server / forwarded flow) ---

    public function testCallbackDataParameterTakesPriorityOverHttpRequest(): void
    {
        // HTTP request has different data — callbackData should win
        $httpPostData = ['txnid' => 'HTTP_TXN', 'status' => 'failure'];
        $callbackData = $this->buildValidResponseData('success');

        $httpRequest = new HttpRequest([], $httpPostData);
        $request = new CompletePurchaseRequest($this->getHttpClient(), $httpRequest);
        $request->initialize([
            'key' => $this->key,
            'salt' => $this->salt,
            'callbackData' => $callbackData,
        ]);

        $response = $request->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($response->isHashValid());
        $this->assertSame('TXN_123', $response->getTransactionId());
    }

    public function testCallbackDataParameterWithValidHash(): void
    {
        $callbackData = $this->buildValidResponseData('success');

        // Empty HTTP request — only callbackData available
        $httpRequest = new HttpRequest();
        $request = new CompletePurchaseRequest($this->getHttpClient(), $httpRequest);
        $request->initialize([
            'key' => $this->key,
            'salt' => $this->salt,
            'callbackData' => $callbackData,
        ]);

        $response = $request->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($response->isHashValid());
        $this->assertSame('403993715531077182', $response->getTransactionReference());
        $this->assertSame('TXN_123', $response->getTransactionId());
        $this->assertSame('CC', $response->getPaymentMode());
        $this->assertSame('896193988312194700', $response->getBankReference());
    }

    public function testCallbackDataParameterWithInvalidHash(): void
    {
        $callbackData = $this->buildValidResponseData('success');
        $callbackData['hash'] = 'forged_hash_value';

        $httpRequest = new HttpRequest();
        $request = new CompletePurchaseRequest($this->getHttpClient(), $httpRequest);
        $request->initialize([
            'key' => $this->key,
            'salt' => $this->salt,
            'callbackData' => $callbackData,
        ]);

        $response = $request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isHashValid());
        $this->assertSame('Invalid response hash - possible tampering detected', $response->getMessage());
    }

    public function testCallbackDataParameterWithFailedStatus(): void
    {
        $callbackData = $this->buildValidResponseData('failure');

        $httpRequest = new HttpRequest();
        $request = new CompletePurchaseRequest($this->getHttpClient(), $httpRequest);
        $request->initialize([
            'key' => $this->key,
            'salt' => $this->salt,
            'callbackData' => $callbackData,
        ]);

        $response = $request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isHashValid());
        $this->assertSame('failure', $response->getPayUStatus());
    }

    public function testCallbackDataParameterWithTamperedAmount(): void
    {
        $callbackData = $this->buildValidResponseData('success');
        $callbackData['amount'] = '999.00'; // Tampered

        $httpRequest = new HttpRequest();
        $request = new CompletePurchaseRequest($this->getHttpClient(), $httpRequest);
        $request->initialize([
            'key' => $this->key,
            'salt' => $this->salt,
            'callbackData' => $callbackData,
        ]);

        $response = $request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isHashValid());
    }

    public function testEmptyCallbackDataFallsBackToHttpRequest(): void
    {
        $postData = ['txnid' => 'HTTP_FALLBACK', 'status' => 'success'];

        $httpRequest = new HttpRequest([], $postData);
        $request = new CompletePurchaseRequest($this->getHttpClient(), $httpRequest);
        $request->initialize([
            'key' => $this->key,
            'salt' => $this->salt,
            // callbackData not set
        ]);

        $data = $request->getData();

        $this->assertSame('HTTP_FALLBACK', $data['txnid']);
        $this->assertSame('success', $data['status']);
    }

    public function testNoDataAvailableThrowsException(): void
    {
        $httpRequest = new HttpRequest(); // Empty request
        $request = new CompletePurchaseRequest($this->getHttpClient(), $httpRequest);
        $request->initialize([
            'key' => $this->key,
            'salt' => $this->salt,
            // No callbackData, empty HTTP request
        ]);

        $this->expectException(\Omnipay\Common\Exception\InvalidRequestException::class);
        $this->expectExceptionMessage('No callback data available');
        $request->getData();
    }

    public function testCallbackDataWithUdfFields(): void
    {
        $hasher = new class { use HashTrait; };

        $callbackData = [
            'txnid' => 'TXN_CB_UDF',
            'amount' => '500.00',
            'productinfo' => 'Premium Plan',
            'firstname' => 'Alice',
            'email' => 'alice@test.com',
            'status' => 'success',
            'mihpayid' => '99887766',
            'mode' => 'UPI',
            'udf1' => 'session_abc',
            'udf2' => '',
            'udf3' => '',
            'udf4' => '',
            'udf5' => '',
        ];

        $callbackData['hash'] = $hasher->generateReverseHash(
            $this->salt, 'success', 'alice@test.com', 'Alice', 'Premium Plan', '500.00', 'TXN_CB_UDF', $this->key,
            'session_abc', '', '', '', ''
        );

        $httpRequest = new HttpRequest();
        $request = new CompletePurchaseRequest($this->getHttpClient(), $httpRequest);
        $request->initialize([
            'key' => $this->key,
            'salt' => $this->salt,
            'callbackData' => $callbackData,
        ]);

        $response = $request->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($response->isHashValid());
        $this->assertSame('99887766', $response->getTransactionReference());
        $this->assertSame('UPI', $response->getPaymentMode());
    }

    // --- isPending() tests ---

    public function testIsPendingWithPendingStatus(): void
    {
        $postData = $this->buildValidResponseData('pending');
        $request = $this->createRequestWithPostData($postData);
        $response = $request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isPending());
    }

    public function testIsPendingWithInProgressStatus(): void
    {
        $postData = $this->buildValidResponseData('in progress');
        $request = $this->createRequestWithPostData($postData);
        $response = $request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isPending());
    }

    public function testIsNotPendingWithSuccessStatus(): void
    {
        $postData = $this->buildValidResponseData('success');
        $request = $this->createRequestWithPostData($postData);
        $response = $request->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isPending());
    }

    public function testIsNotPendingWithFailureStatus(): void
    {
        $postData = $this->buildValidResponseData('failure');
        $request = $this->createRequestWithPostData($postData);
        $response = $request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isPending());
    }
}
