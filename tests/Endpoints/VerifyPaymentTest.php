<?php

declare(strict_types=1);

namespace Omnipay\PayUIndia\Tests\Endpoints;

use Omnipay\Common\Exception\InvalidRequestException;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\RequestInterface;

/**
 * `Gateway::verifyPayment`: PayU's verify_payment command, one signed form POST that reads how a
 * transaction stands and changes nothing.
 */
final class VerifyPaymentTest extends EndpointTestCase
{
    private const string TXNID = 'TXN_123';

    private const string VERIFY_URL = 'https://test.payu.in/merchant/postservice.php?form=2';

    #[Test]
    #[Calls('Gateway::verifyPayment')]
    public function verify_payment_reads_a_paid_transaction_with_one_signed_post(): void
    {
        $gateway = $this->gateway();
        $this->setMockHttpResponse('VerifySuccess.txt');

        $response = $gateway->verifyPayment(['transactionId' => self::TXNID])->send();

        $request = $this->onlyRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(self::VERIFY_URL, (string) $request->getUri());
        $this->assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
        parse_str((string) $request->getBody(), $form);
        $this->assertSame([
            'key' => self::KEY,
            'command' => 'verify_payment',
            'var1' => self::TXNID,
            'hash' => hash('sha512', self::KEY.'|verify_payment|'.self::TXNID.'|'.self::SALT),
        ], $form);
        $this->assertTrue($response->wasAnswered());
        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isPending());
        $this->assertSame('success', $response->getPayUStatus());
        $this->assertSame('captured', $response->getUnmappedStatus());
        $this->assertSame('403993715531077182', $response->getTransactionReference());
        $this->assertSame(self::TXNID, $response->getTransactionId());
        $this->assertSame('100.00', $response->getAmount());
    }

    #[Test]
    public function verify_payment_says_when_payu_holds_no_attempt_for_the_txnid(): void
    {
        $gateway = $this->gateway();
        $this->setMockHttpResponse('VerifyNotFound.txt');

        $response = $gateway->verifyPayment(['transactionId' => self::TXNID])->send();

        $this->onlyRequest();
        $this->assertTrue($response->wasAnswered());
        $this->assertTrue($response->isNotFound());
        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isFailed());
        $this->assertNull($response->getTransactionReference());
    }

    #[Test]
    public function verify_payment_says_when_payu_refuses_the_key(): void
    {
        $gateway = $this->gateway();
        $this->setMockHttpResponse('VerifyInvalidHash.txt');

        $response = $gateway->verifyPayment(['transactionId' => self::TXNID])->send();

        $this->onlyRequest();
        $this->assertFalse($response->wasAnswered());
        $this->assertTrue($response->isHashRefused());
        $this->assertSame(200, $response->getHttpStatus());
        $this->assertSame('Invalid Hash.', $response->getMessage());
        $this->assertNull($response->getPayUStatus());
    }

    #[Test]
    public function verify_payment_without_a_txnid_is_refused_before_sending(): void
    {
        try {
            $this->gateway()->verifyPayment([])->send();
            $this->fail('A verify_payment with no txnid was sent.');
        } catch (InvalidRequestException $refused) {
            $this->assertSame('The transactionId parameter is required', $refused->getMessage());
        }
        $this->assertNoHttpRequestSent();
    }

    private function onlyRequest(): RequestInterface
    {
        $requests = $this->getMockedRequests();
        $this->assertCount(1, $requests, 'verify_payment sent '.count($requests).' requests, expected one.');

        return $requests[0];
    }
}
