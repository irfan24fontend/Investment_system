<?php
namespace App\Contracts;
interface BkashGateway {
    /** @return array{paymentId:string,url:string} */
    public function createPayment(string $amount, string $invoice, string $callbackUrl): array;
    /** Server-to-server verification. @return array{status:string,trxId:?string,amount:string} */
    public function verify(string $paymentId): array;
}
