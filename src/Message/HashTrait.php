<?php

namespace Omnipay\PayUIndia\Message;

trait HashTrait
{
    /**
     * Generate forward hash for purchase request.
     *
     * Formula: sha512(key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5||||||SALT)
     */
    public function generateForwardHash(
        string $key,
        string $txnid,
        string $amount,
        string $productinfo,
        string $firstname,
        string $email,
        string $salt,
        string $udf1 = '',
        string $udf2 = '',
        string $udf3 = '',
        string $udf4 = '',
        string $udf5 = ''
    ): string {
        $hashString = $key . '|' . $txnid . '|' . $amount . '|' . $productinfo . '|'
            . $firstname . '|' . $email . '|' . $udf1 . '|' . $udf2 . '|'
            . $udf3 . '|' . $udf4 . '|' . $udf5 . '||||||' . $salt;

        return strtolower(hash('sha512', $hashString));
    }

    /**
     * Generate reverse hash for response verification.
     *
     * Formula: sha512(SALT|status||||||udf5|udf4|udf3|udf2|udf1|email|firstname|productinfo|amount|txnid|key)
     */
    public function generateReverseHash(
        string $salt,
        string $status,
        string $email,
        string $firstname,
        string $productinfo,
        string $amount,
        string $txnid,
        string $key,
        string $udf1 = '',
        string $udf2 = '',
        string $udf3 = '',
        string $udf4 = '',
        string $udf5 = ''
    ): string {
        $hashString = $salt . '|' . $status . '||||||' . $udf5 . '|' . $udf4 . '|'
            . $udf3 . '|' . $udf2 . '|' . $udf1 . '|' . $email . '|'
            . $firstname . '|' . $productinfo . '|' . $amount . '|'
            . $txnid . '|' . $key;

        return strtolower(hash('sha512', $hashString));
    }

    /**
     * Verify a response hash from PayU India using reverse hashing.
     *
     * Uses hash_equals() for timing-safe comparison.
     */
    public function verifyResponseHash(array $responseData, string $key, string $salt): bool
    {
        $receivedHash = $responseData['hash'] ?? '';
        if (empty($receivedHash)) {
            return false;
        }

        $expectedHash = $this->generateReverseHash(
            $salt,
            $responseData['status'] ?? '',
            $responseData['email'] ?? '',
            $responseData['firstname'] ?? '',
            $responseData['productinfo'] ?? '',
            $responseData['amount'] ?? '',
            $responseData['txnid'] ?? '',
            $key,
            $responseData['udf1'] ?? '',
            $responseData['udf2'] ?? '',
            $responseData['udf3'] ?? '',
            $responseData['udf4'] ?? '',
            $responseData['udf5'] ?? ''
        );

        return hash_equals($expectedHash, strtolower($receivedHash));
    }
}
