<?php

declare(strict_types=1);

namespace Omnipay\PayUIndia\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;

/**
 * PayU's answer to `verify_payment`. PayU answers HTTP 200 whether or not it accepted the request:
 * `{"status":0,"msg":"Invalid Hash."}` when the key or salt is wrong, otherwise the transaction's
 * details under its txnid, whose status is success, failure, pending, in progress, or "Not Found"
 * when PayU holds no attempt for the txnid (the buyer never paid).
 */
class VerifyPaymentResponse extends AbstractResponse
{
    /** @param array<array-key, mixed> $data */
    public function __construct(RequestInterface $request, $data, private readonly int $httpStatus, private readonly string $txnid)
    {
        parent::__construct($request, $data);
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    /** PayU answered with the transaction's details. */
    public function wasAnswered(): bool
    {
        return $this->httpStatus === 200 && $this->details() !== null;
    }

    /** PayU refused the request's hash: the merchant key or salt is wrong. */
    public function isHashRefused(): bool
    {
        return $this->httpStatus === 200 && isset($this->data['msg']) && $this->data['msg'] === 'Invalid Hash.';
    }

    /** @return array<array-key, mixed>|null */
    private function details(): ?array
    {
        if (! isset($this->data['transaction_details'][$this->txnid]) || ! is_array($this->data['transaction_details'][$this->txnid])) {
            return null;
        }

        return $this->data['transaction_details'][$this->txnid];
    }

    private function detail(string $field): ?string
    {
        $details = $this->details();
        if ($details === null || ! isset($details[$field])) {
            return null;
        }

        return (string) $details[$field];
    }

    /** The transaction status PayU reports, lower case: success, failure, pending, in progress or not found. */
    public function getPayUStatus(): ?string
    {
        $status = $this->detail('status');
        if ($status === null) {
            return null;
        }

        return strtolower($status);
    }

    public function isSuccessful(): bool
    {
        return $this->wasAnswered() && $this->getPayUStatus() === 'success';
    }

    public function isPending(): bool
    {
        return $this->wasAnswered() && in_array($this->getPayUStatus(), ['pending', 'in progress'], true);
    }

    public function isFailed(): bool
    {
        return $this->wasAnswered() && $this->getPayUStatus() === 'failure';
    }

    /** PayU holds no payment attempt for the txnid. */
    public function isNotFound(): bool
    {
        return $this->wasAnswered() && $this->getPayUStatus() === 'not found';
    }

    /** PayU's payment id, once the buyer attempted a payment. */
    public function getTransactionReference(): ?string
    {
        if ($this->isNotFound()) {
            return null;
        }

        return $this->detail('mihpayid');
    }

    public function getTransactionId(): string
    {
        return $this->txnid;
    }

    /** PayU's finer status, such as captured, bounced, dropped or userCancelled. */
    public function getUnmappedStatus(): ?string
    {
        return $this->detail('unmappedstatus');
    }

    /** The amount PayU holds for the transaction, as PayU writes it (rupees with two decimals). */
    public function getAmount(): ?string
    {
        return $this->detail('amt');
    }

    public function getCode(): ?string
    {
        return $this->detail('error_code');
    }

    public function getMessage(): ?string
    {
        $message = $this->detail('error_Message');
        if ($message !== null) {
            return $message;
        }
        if (! isset($this->data['msg'])) {
            return null;
        }

        return (string) $this->data['msg'];
    }
}
