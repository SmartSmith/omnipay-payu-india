<?php

declare(strict_types=1);

namespace Omnipay\PayUIndia\Tests\Endpoints;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\PayUIndia\Message\HashTrait;
use Omnipay\PayUIndia\Message\PurchaseResponse;
use PHPUnit\Framework\Attributes\Test;

/** `Gateway::purchase`: the signed form a consuming app posts the buyer's browser to PayU with. */
final class PurchaseTest extends EndpointTestCase
{
    /** @var array<string, string> */
    private const array ORDER = [
        'amount' => '100.00',
        'transactionId' => 'TXN_123',
        'productinfo' => 'Test Product',
        'firstname' => 'John',
        'email' => 'john@example.com',
        'phone' => '9876543210',
        'surl' => 'https://shop.example.test/payu/success',
        'furl' => 'https://shop.example.test/payu/failure',
        'udf1' => 'order-42',
    ];

    #[Test]
    #[Calls('Gateway::purchase')]
    public function a_purchase_redirects_the_buyer_to_payu_with_a_signed_form(): void
    {
        $response = $this->gateway()->purchase(self::ORDER)->send();

        $this->assertInstanceOf(PurchaseResponse::class, $response);
        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());
        $this->assertSame('POST', $response->getRedirectMethod());
        $this->assertSame('https://test.payu.in/_payment', $response->getRedirectUrl());
        $this->assertSame('TXN_123', $response->getTransactionId());
        $form = $response->getRedirectData();
        $this->assertSame([
            'key' => self::KEY,
            'txnid' => 'TXN_123',
            'amount' => '100.00',
            'productinfo' => 'Test Product',
            'firstname' => 'John',
            'email' => 'john@example.com',
            'phone' => '9876543210',
            'surl' => 'https://shop.example.test/payu/success',
            'furl' => 'https://shop.example.test/payu/failure',
            'udf1' => 'order-42',
            'udf2' => '',
            'udf3' => '',
            'udf4' => '',
            'udf5' => '',
        ], array_diff_key($form, ['hash' => true]));
        $signer = new class
        {
            use HashTrait;
        };
        $this->assertSame(
            $signer->generateForwardHash(self::KEY, 'TXN_123', '100.00', 'Test Product', 'John', 'john@example.com', self::SALT, 'order-42'),
            $form['hash'],
        );
        $this->assertNoHttpRequestSent();
    }

    #[Test]
    public function a_live_gateway_posts_to_payus_live_endpoint(): void
    {
        $gateway = $this->gateway();
        $gateway->setTestMode(false);

        $response = $gateway->purchase(self::ORDER)->send();

        $this->assertInstanceOf(PurchaseResponse::class, $response);
        $this->assertSame('https://secure.payu.in/_payment', $response->getRedirectUrl());
    }

    #[Test]
    public function a_purchase_missing_a_required_field_is_refused_before_any_form_is_built(): void
    {
        $order = self::ORDER;
        unset($order['email']);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('The email parameter is required');

        try {
            $this->gateway()->purchase($order)->send();
        } finally {
            $this->assertNoHttpRequestSent();
        }
    }
}
