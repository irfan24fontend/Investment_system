@extends('layouts.app')
@section('content')
@php $cb = config('services.company_bank'); @endphp
<div class="grid sm:grid-cols-3 gap-3 mb-6">
  <div class="sm:col-span-2 bg-indigo-600 text-white rounded-xl p-5 shadow"><div class="text-xs opacity-80">Available balance</div><div class="text-3xl font-bold">৳{{ number_format($balance,2) }}</div></div>
  <div class="bg-white border rounded-xl p-5"><div class="text-xs text-slate-500">Reserved for withdrawals</div><div class="text-xl font-bold">৳{{ number_format($reserved,2) }}</div></div>
</div>

<div class="bg-white border rounded-xl p-5 mb-8" x-data="{tab: '{{ old('method') || old('account_number') ? 'withdraw' : (old('trx_id') ? 'bkash' : 'bank') }}', wm: '{{ old('method','bank') }}'}">
  <div class="flex flex-wrap gap-2 mb-4 text-sm">
    @foreach(['bank'=>'Deposit: Islami Bank','bkash'=>'Deposit: bKash','withdraw'=>'Withdraw'] as $k=>$l)
    <button type="button" @click="tab='{{ $k }}'" :class="tab==='{{ $k }}' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700'" class="px-3 py-1.5 rounded-lg">{{ $l }}</button>@endforeach
  </div>

  {{-- Islami Bank deposit --}}
  <form x-show="tab==='bank'" method="POST" action="{{ route('deposit.bank') }}" enctype="multipart/form-data" class="space-y-3 max-w-md">@csrf
    <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-sm space-y-1">
      <div><b>Step 1.</b> Transfer / deposit to our <b>Islami Bank</b> account:</div>
      <div class="bg-white rounded p-2 border text-slate-800">
        {{ $cb['bank_name'] }}<br>Account name: <b>{{ $cb['account_name'] ?? 'not set' }}</b><br>Account no: <b class="text-emerald-700 text-base">{{ $cb['account_number'] ?? 'not set' }}</b>@if($cb['branch'])<br>Branch: {{ $cb['branch'] }}@endif
      </div>
      <div><b>Step 2.</b> Fill the form with the transaction/slip number and upload the slip or screenshot.</div>
      <div><b>Step 3.</b> Staff verify the money in our account, then your wallet is credited.</div>
    </div>
    <input name="amount" type="number" step="0.01" min="100" value="{{ old('amount') }}" placeholder="Amount (৳)" required class="border rounded-lg px-3 py-2 w-full">
    <input name="reference" value="{{ old('reference') }}" placeholder="Transaction / slip number" required class="border rounded-lg px-3 py-2 w-full">
    <input name="sender_account" value="{{ old('sender_account') }}" placeholder="Your account number (optional)" class="border rounded-lg px-3 py-2 w-full">
    <label class="block text-sm text-slate-600">Slip / screenshot (JPG, PNG, PDF, max 3MB)<input type="file" name="receipt" required class="block mt-1 text-sm"></label>
    <button class="bg-emerald-600 text-white px-4 py-2 rounded-lg">Submit for verification</button>
  </form>

  {{-- bKash deposit --}}
  <form x-show="tab==='bkash'" x-cloak method="POST" action="{{ route('deposit.bkash') }}" class="space-y-3 max-w-md">@csrf
    <div class="p-3 bg-pink-50 border border-pink-200 rounded-lg text-sm">
      <b>Step 1.</b> Send money by bKash to <b class="text-pink-700 text-base">{{ config('services.bkash.receive_number') ?? 'number not set' }}</b><br>
      <b>Step 2.</b> Enter the amount, your bKash number and the TrxID.<br><b>Step 3.</b> Staff verify it; then your wallet is credited.</div>
    <input name="amount" type="number" step="0.01" min="10" value="{{ old('amount') }}" placeholder="Amount (৳)" required class="border rounded-lg px-3 py-2 w-full">
    <input name="sender_number" value="{{ old('sender_number') }}" placeholder="Your bKash number (01XXXXXXXXX)" required class="border rounded-lg px-3 py-2 w-full">
    <input name="trx_id" value="{{ old('trx_id') }}" placeholder="TrxID (e.g. 9AB3C4D5EF)" required class="border rounded-lg px-3 py-2 w-full uppercase">
    <button class="bg-pink-600 text-white px-4 py-2 rounded-lg">Submit for verification</button>
  </form>

  {{-- Withdraw: any bank --}}
  <form x-show="tab==='withdraw'" x-cloak method="POST" action="{{ route('withdraw') }}" data-confirm="Request this withdrawal? Please double-check the account number." class="space-y-3 max-w-md">@csrf
    <input type="hidden" name="token" value="{{ \Illuminate\Support\Str::uuid() }}">
    <div class="flex gap-4 text-sm">
      <label><input type="radio" name="method" value="bank" x-model="wm"> Bank account (any bank)</label>
      <label><input type="radio" name="method" value="bkash" x-model="wm"> bKash</label>
    </div>
    <input x-show="wm==='bank'" name="bank_name" value="{{ old('bank_name') }}" placeholder="Bank name (e.g. Dutch-Bangla Bank)" class="border rounded-lg px-3 py-2 w-full">
    <input name="account_name" value="{{ old('account_name', auth()->user()->name) }}" placeholder="Account holder name" required class="border rounded-lg px-3 py-2 w-full">
    <input name="account_number" value="{{ old('account_number') }}" :placeholder="wm==='bank' ? 'Bank account number' : 'bKash number'" required class="border rounded-lg px-3 py-2 w-full">
    <input name="amount" type="number" step="0.01" min="100" value="{{ old('amount') }}" placeholder="Amount (৳, min 100)" required class="border rounded-lg px-3 py-2 w-full">
    <input name="password" type="password" placeholder="Your login password (for security)" required class="border rounded-lg px-3 py-2 w-full">
    <p class="text-xs text-slate-500">The amount is reserved immediately. Staff review the request and send the money to the account above. Use an account in your own name.</p>
    <button class="bg-slate-800 text-white px-4 py-2 rounded-lg">Request withdrawal</button>
  </form>
</div>

<div class="grid lg:grid-cols-2 gap-6">
  <div><h2 class="font-semibold mb-2">Deposits</h2>
    <div class="bg-white border rounded-xl divide-y text-sm">@forelse($deposits as $d)
      <div class="p-3 flex justify-between gap-2"><div><div class="font-medium">৳{{ number_format($d->amount,2) }} <span class="text-slate-400">· {{ strtoupper($d->method) }}</span></div><div class="text-xs text-slate-500">{{ $d->created_at->timezone('Asia/Dhaka')->format('d M Y, h:i A') }} · {{ $d->reference }}</div>@if($d->rejection_reason)<div class="text-xs text-red-600">{{ $d->rejection_reason }}</div>@endif</div>@include('partials.badge',['s'=>$d->status])</div>
    @empty<div class="p-4 text-slate-500">No deposits yet.</div>@endforelse</div></div>
  <div><h2 class="font-semibold mb-2">Withdrawals</h2>
    <div class="bg-white border rounded-xl divide-y text-sm">@forelse($withdrawals as $w)
      <div class="p-3 flex justify-between gap-2"><div><div class="font-medium">৳{{ number_format($w->amount,2) }} <span class="text-slate-400">· {{ $w->bank_name ?? strtoupper($w->method) }} {{ $w->destination }}</span></div><div class="text-xs text-slate-500">{{ $w->created_at->timezone('Asia/Dhaka')->format('d M Y, h:i A') }} {{ $w->payment_reference ? '· Ref '.$w->payment_reference : '' }}</div>@if($w->admin_note)<div class="text-xs text-red-600">{{ $w->admin_note }}</div>@endif</div>@include('partials.badge',['s'=>$w->status])</div>
    @empty<div class="p-4 text-slate-500">No withdrawals yet.</div>@endforelse</div></div>
</div>

<h2 class="font-semibold mt-8 mb-2">Transaction history</h2>
<div class="bg-white border rounded-xl divide-y text-sm">
@forelse($tx as $t)<div class="p-3 flex justify-between"><div><div class="font-medium">{{ ucwords(str_replace('_',' ',$t->type)) }}</div><div class="text-xs text-slate-500">{{ \Illuminate\Support\Carbon::parse($t->created_at)->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}</div></div>
<div class="font-semibold {{ $t->direction==='credit'?'text-green-700':'text-red-700' }}">{{ $t->direction==='credit'?'+':'−' }}৳{{ number_format($t->amount,2) }}</div></div>
@empty<div class="p-4 text-slate-500">No transactions yet.</div>@endforelse</div>
<div class="mt-3">{{ $tx->links() }}</div>
@endsection
