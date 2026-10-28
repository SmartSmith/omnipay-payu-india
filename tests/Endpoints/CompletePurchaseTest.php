<?php

declare(strict_types=1);

namespace Omnipay\PayUIndia\Tests\Endpoints;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\PayUIndia\Message\CompletePurchaseResponse;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

/**
 * `Gateway::completePurchase`: PayU's callback, handed over as `callbackData` (SmartPay forwards it
 * server to server) or read from the browser's POST back to the shop, verified by its reverse hash.
 */
final class CompletePurchaseTest extends EndpointTestCase
{
    #[Test]
    #[Calls('Gateway::completePurchase')]
    public function a_signed_success_callback_completes_the_payment(): void
    {
        $response = $this->gateway()->completePurchase(['callbackData' => $this->signedCallback('success')])->send();

        $this->assertInstanceOf(CompletePurchaseResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isPending());
        $this->assertTrue($response->isHashValid());
        $this->assertSame('403993715531077182', $response->getTransactionReference());
        $this->assertSame('TXN_123', $response->getTransactionId());
        $this->assertSame('UPI', $response->getPaymentMode());
        $this->assertSame('896193988312194700', $response->getBankReference());
        $this->assertSame('captured', $response->getCode());
        $this->assertNoHttpRequestSent();
    }

    #[Test]
    public function the_browsers_post_back_to_the_shop_is_read_when_no_callback_data_is_given(): void
    {
        $response = $this->gateway(new HttpRequest([], $this->signedCallback('success')))->completePurchase()->send();

        $this->assertInstanceOf(CompletePurchaseResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertSame('TXN_123', $response->getTransactionId());
        $this->assertNoHttpRequestSent();
    }

    #[Test]
    public function a_tampered_callback_is_refused_as_tampering(): void
    {
        $callback = $this->signedCallback('success');
        $callback['amount'] = '1.00';

        $response = $this->gateway()->completePurchase(['callbackData' => $callback])->send();

        $this->assertInstanceOf(CompletePurchaseResponse::class, $response);
        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isHashValid());
        $this->assertSame('Invalid response hash - possible tampering detected', $response->getMessage());
    }

    #[Test]
    public function a_signed_failure_callback_fails_the_payment_with_payus_reason(): void
    {
        $callback = $this->signedCallback('failure');
        $callback['error_Message'] = 'Bank was unable to authenticate.';

        $response = $this->gateway()->completePurchase(['callbackData' => $callback])->send();

        $this->assertInstanceOf(CompletePurchaseResponse::class, $response);
        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isPending());
        $this->assertTrue($response->isHashValid());
        $this->assertSame('failure', $response->getPayUStatus());
        $this->assertSame('Bank was unable to authenticate.', $response->getMessage());
    }

    #[Test]
    public function a_signed_pending_callback_leaves_the_payment_pending(): void
    {
        $response = $this->gateway()->completePurchase(['callbackData' => $this->signedCallback('pending')])->send();

        $this->assertInstanceOf(CompletePurchaseResponse::class, $response);
        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isPending());
        $this->assertTrue($response->isHashValid());
    }

    #[Test]
    public function a_completion_with_no_callback_at_all_is_refused(): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('No callback data available');

        $this->gateway()->completePurchase()->send();
    }
}
