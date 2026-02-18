<?php

namespace Omnipay\PayUIndia\Tests\Message;

use PHPUnit\Framework\TestCase;
use Omnipay\PayUIndia\Message\HashTrait;

class HashTraitTest extends TestCase
{
    private object $hasher;

    public function setUp(): void
    {
        parent::setUp();
        $this->hasher = new class {
            use HashTrait;
        };
    }

    public function testForwardHashProducesCorrectSha512(): void
    {
        $hash = $this->hasher->generateForwardHash(
            'gtKFFx',           // key
            'YPL938459435',     // txnid
            '100.00',           // amount
            'iPhone',           // productinfo
            'Ashish',           // firstname
            'abc@payu.in',      // email
            'eCwWELxi',         // salt
        );

        // Hash should be a 128-char lowercase hex string
        $this->assertMatchesRegularExpression('/^[a-f0-9]{128}$/', $hash);

        // Verify it's deterministic
        $hash2 = $this->hasher->generateForwardHash(
            'gtKFFx', 'YPL938459435', '100.00', 'iPhone', 'Ashish', 'abc@payu.in', 'eCwWELxi'
        );
        $this->assertSame($hash, $hash2);
    }

    public function testForwardHashWithEmptyUdfFields(): void
    {
        $hash = $this->hasher->generateForwardHash(
            'testKey', 'txn001', '50.00', 'Product', 'John', 'john@test.com', 'testSalt'
        );

        // Manually compute expected hash with empty UDF fields
        $hashString = 'testKey|txn001|50.00|Product|John|john@test.com|||||||||||testSalt';
        $expected = strtolower(hash('sha512', $hashString));

        $this->assertSame($expected, $hash);
    }

    public function testForwardHashWithPopulatedUdfFields(): void
    {
        $hash = $this->hasher->generateForwardHash(
            'testKey', 'txn001', '50.00', 'Product', 'John', 'john@test.com', 'testSalt',
            'udf1val', 'udf2val', 'udf3val', 'udf4val', 'udf5val'
        );

        $hashString = 'testKey|txn001|50.00|Product|John|john@test.com|udf1val|udf2val|udf3val|udf4val|udf5val||||||testSalt';
        $expected = strtolower(hash('sha512', $hashString));

        $this->assertSame($expected, $hash);
    }

    public function testReverseHashProducesCorrectSha512(): void
    {
        $hash = $this->hasher->generateReverseHash(
            'eCwWELxi',         // salt
            'success',          // status
            'abc@payu.in',      // email
            'Ashish',           // firstname
            'iPhone',           // productinfo
            '100.00',           // amount
            'YPL938459435',     // txnid
            'gtKFFx',           // key
        );

        // Manually compute expected
        $hashString = 'eCwWELxi|success|||||||||||abc@payu.in|Ashish|iPhone|100.00|YPL938459435|gtKFFx';
        $expected = strtolower(hash('sha512', $hashString));

        $this->assertSame($expected, $hash);
    }

    public function testVerifyResponseHashReturnsTrueForValidHash(): void
    {
        $key = 'testKey';
        $salt = 'testSalt';

        // Build a valid response
        $responseData = [
            'txnid' => 'txn001',
            'amount' => '100.00',
            'productinfo' => 'Test Product',
            'firstname' => 'John',
            'email' => 'john@test.com',
            'status' => 'success',
            'udf1' => '',
            'udf2' => '',
            'udf3' => '',
            'udf4' => '',
            'udf5' => '',
        ];

        // Generate the correct reverse hash
        $responseData['hash'] = $this->hasher->generateReverseHash(
            $salt, 'success', 'john@test.com', 'John', 'Test Product', '100.00', 'txn001', $key
        );

        $this->assertTrue($this->hasher->verifyResponseHash($responseData, $key, $salt));
    }

    public function testVerifyResponseHashReturnsFalseForTamperedAmount(): void
    {
        $key = 'testKey';
        $salt = 'testSalt';

        $responseData = [
            'txnid' => 'txn001',
            'amount' => '100.00',
            'productinfo' => 'Test Product',
            'firstname' => 'John',
            'email' => 'john@test.com',
            'status' => 'success',
        ];

        // Generate hash with original amount
        $responseData['hash'] = $this->hasher->generateReverseHash(
            $salt, 'success', 'john@test.com', 'John', 'Test Product', '100.00', 'txn001', $key
        );

        // Tamper with the amount
        $responseData['amount'] = '999.00';

        $this->assertFalse($this->hasher->verifyResponseHash($responseData, $key, $salt));
    }

    public function testVerifyResponseHashReturnsFalseForTamperedStatus(): void
    {
        $key = 'testKey';
        $salt = 'testSalt';

        $responseData = [
            'txnid' => 'txn001',
            'amount' => '100.00',
            'productinfo' => 'Test Product',
            'firstname' => 'John',
            'email' => 'john@test.com',
            'status' => 'failure',
        ];

        // Generate hash for 'failure' status
        $responseData['hash'] = $this->hasher->generateReverseHash(
            $salt, 'failure', 'john@test.com', 'John', 'Test Product', '100.00', 'txn001', $key
        );

        // Tamper: change status to 'success' but keep old hash
        $responseData['status'] = 'success';

        $this->assertFalse($this->hasher->verifyResponseHash($responseData, $key, $salt));
    }

    public function testVerifyResponseHashReturnsFalseForMissingHash(): void
    {
        $responseData = [
            'txnid' => 'txn001',
            'amount' => '100.00',
            'productinfo' => 'Test Product',
            'firstname' => 'John',
            'email' => 'john@test.com',
            'status' => 'success',
        ];

        $this->assertFalse($this->hasher->verifyResponseHash($responseData, 'testKey', 'testSalt'));
    }

    public function testVerifyResponseHashWithUdfFields(): void
    {
        $key = 'testKey';
        $salt = 'testSalt';

        $responseData = [
            'txnid' => 'txn001',
            'amount' => '200.00',
            'productinfo' => 'Premium',
            'firstname' => 'Jane',
            'email' => 'jane@test.com',
            'status' => 'success',
            'udf1' => 'ref123',
            'udf2' => 'tag456',
            'udf3' => '',
            'udf4' => '',
            'udf5' => '',
        ];

        $responseData['hash'] = $this->hasher->generateReverseHash(
            $salt, 'success', 'jane@test.com', 'Jane', 'Premium', '200.00', 'txn001', $key,
            'ref123', 'tag456', '', '', ''
        );

        $this->assertTrue($this->hasher->verifyResponseHash($responseData, $key, $salt));
    }

    public function testForwardAndReverseHashesAreDifferent(): void
    {
        $key = 'testKey';
        $salt = 'testSalt';

        $forwardHash = $this->hasher->generateForwardHash(
            $key, 'txn001', '100.00', 'Product', 'John', 'john@test.com', $salt
        );

        $reverseHash = $this->hasher->generateReverseHash(
            $salt, 'success', 'john@test.com', 'John', 'Product', '100.00', 'txn001', $key
        );

        $this->assertNotSame($forwardHash, $reverseHash);
    }

    public function testHashIsCaseInsensitive(): void
    {
        $key = 'testKey';
        $salt = 'testSalt';

        $responseData = [
            'txnid' => 'txn001',
            'amount' => '100.00',
            'productinfo' => 'Test Product',
            'firstname' => 'John',
            'email' => 'john@test.com',
            'status' => 'success',
        ];

        $correctHash = $this->hasher->generateReverseHash(
            $salt, 'success', 'john@test.com', 'John', 'Test Product', '100.00', 'txn001', $key
        );

        // PayU might return uppercase hash
        $responseData['hash'] = strtoupper($correctHash);

        $this->assertTrue($this->hasher->verifyResponseHash($responseData, $key, $salt));
    }
}
