@php
  $kind = $kind ?? '';
  $colors = ['confirmed'=>'bg-blue-100 text-blue-800','settled'=>'bg-green-100 text-green-800','loss_settled'=>'bg-orange-100 text-orange-800','completed'=>'bg-green-100 text-green-800',
    'pending'=>'bg-amber-100 text-amber-800','rejected'=>'bg-red-100 text-red-800','failed'=>'bg-red-100 text-red-800','cancelled'=>'bg-slate-200 text-slate-700'];
  $labels = $kind==='investment' ? ['confirmed'=>'Active','settled'=>'Settled','loss_settled'=>'Settled (loss)'] : ['confirmed'=>'Confirmed','pending'=>'Pending review'];
@endphp
<span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$s] ?? 'bg-slate-100 text-slate-700' }}">{{ $labels[$s] ?? ucfirst(str_replace('_',' ',$s)) }}</span>
