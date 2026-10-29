<?php
namespace App\Services\Gateways;
use App\Contracts\BkashGateway;
use Illuminate\Support\Facades\Http;

/**
 * bKash Tokenized Checkout (merchant). Needs merchant credentials from bKash.
 * Set in .env: BKASH_BASE_URL, BKASH_APP_KEY, BKASH_APP_SECRET, BKASH_USERNAME, BKASH_PASSWORD
 * VERIFY endpoint paths/fields against the current official bKash developer docs before going live.
 */
class BkashTokenizedGateway implements BkashGateway {
    private function token(): string {
        $c = config('services.bkash');
        $r = Http::withHeaders(['username' => $c['username'], 'password' => $c['password']])
            ->post($c['base_url'].'/tokenized/checkout/token/grant', ['app_key' => $c['app_key'], 'app_secret' => $c['app_secret']])->throw()->json();
        return $r['id_token'] ?? throw new \RuntimeException('bKash token grant failed');
    }
    private function http() {
        return Http::withHeaders(['Authorization' => $this->token(), 'X-APP-Key' => config('services.bkash.app_key')]);
    }
    public function createPayment(string $amount, string $invoice, string $callbackUrl): array {
        $r = $this->http()->post(config('services.bkash.base_url').'/tokenized/checkout/create', [
            'mode' => '0011', 'payerReference' => $invoice, 'callbackURL' => $callbackUrl,
            'amount' => $amount, 'currency' => 'BDT', 'intent' => 'sale', 'merchantInvoiceNumber' => $invoice,
        ])->throw()->json();
        return ['paymentId' => $r['paymentID'], 'url' => $r['bkashURL']];
    }
    public function verify(string $paymentId): array {
        $base = config('services.bkash.base_url');
        $r = $this->http()->post("$base/tokenized/checkout/execute", ['paymentID' => $paymentId])->json();
        if (($r['statusCode'] ?? '') !== '0000') {   // already executed? ask status endpoint
            $r = $this->http()->post("$base/tokenized/checkout/payment/status", ['paymentID' => $paymentId])->json();
        }
        return ['status' => $r['transactionStatus'] ?? 'Failed', 'trxId' => $r['trxID'] ?? null, 'amount' => number_format((float) ($r['amount'] ?? 0), 2, '.', '')];
    }
}
