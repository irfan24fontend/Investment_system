@extends('layouts.app')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
  <div><a href="{{ route('admin.customer-orders') }}" class="text-sm text-indigo-700">← Customer orders</a><h1 class="text-xl font-bold mt-1">Memo {{ $order->code }}</h1></div>
  <button id="download-memo" class="bg-indigo-600 text-white rounded-lg px-4 py-2 text-sm font-medium">Download memo as JPG</button>
</div>
<div class="bg-white border rounded-xl p-3 overflow-x-auto">
  <canvas id="memo-canvas" class="max-w-full h-auto mx-auto border rounded"></canvas>
</div>
<script>
(() => {
  const memo = @json($memo);
  const canvas = document.getElementById('memo-canvas');
  const ctx = canvas.getContext('2d');
  const width = 900;
  const height = 590 + memo.items.length * 58 + (memo.address ? 42 : 0);
  canvas.width = width; canvas.height = height;
  const ink = '#172033', muted = '#64748b', line = '#dbe2ea', accent = '#4338ca';
  const money = value => '৳' + Number(value).toLocaleString('en-BD', {minimumFractionDigits:2, maximumFractionDigits:2});
  const write = (text, x, y, font, color=ink, align='left') => { ctx.font = font; ctx.fillStyle = color; ctx.textAlign = align; ctx.fillText(String(text ?? ''), x, y); };
  ctx.fillStyle = '#ffffff'; ctx.fillRect(0,0,width,height);
  ctx.fillStyle = accent; ctx.fillRect(0,0,width,12);
  write(memo.company, 56, 72, '700 34px "Hind Siliguri", sans-serif', accent);
  write('CUSTOMER MEMO', width-56, 68, '700 23px Arial, sans-serif', ink, 'right');
  write('Order: ' + memo.code, 56, 116, '600 20px Arial, sans-serif');
  write('Date: ' + memo.date, width-56, 116, '16px Arial, sans-serif', muted, 'right');
  ctx.strokeStyle = line; ctx.beginPath(); ctx.moveTo(56,140); ctx.lineTo(width-56,140); ctx.stroke();
  write('CUSTOMER', 56, 177, '700 14px Arial, sans-serif', muted);
  write(memo.customer, 56, 207, '600 22px "Hind Siliguri", Arial, sans-serif');
  write('Phone: ' + memo.phone + (memo.whatsapp ? '   WhatsApp: ' + memo.whatsapp : ''), 56, 237, '16px Arial, sans-serif', muted);
  let y = 268;
  if (memo.address) { write('Delivery address: ' + memo.address, 56, y, '16px "Hind Siliguri", Arial, sans-serif', muted); y += 38; }
  const tableTop = y + 8;
  ctx.fillStyle = '#f1f5f9'; ctx.fillRect(56, tableTop, width-112, 42);
  write('PRODUCT', 72, tableTop+27, '700 14px Arial, sans-serif', muted);
  write('QTY', 600, tableTop+27, '700 14px Arial, sans-serif', muted, 'right');
  write('UNIT PRICE', 730, tableTop+27, '700 14px Arial, sans-serif', muted, 'right');
  write('TOTAL', width-72, tableTop+27, '700 14px Arial, sans-serif', muted, 'right');
  y = tableTop+42;
  memo.items.forEach(item => {
    y += 48;
    write(item.name, 72, y-16, '17px "Hind Siliguri", Arial, sans-serif');
    write(item.quantity, 600, y-16, '17px Arial, sans-serif', ink, 'right');
    write(money(item.unit_price), 730, y-16, '17px Arial, sans-serif', ink, 'right');
    write(money(item.total), width-72, y-16, '17px Arial, sans-serif', ink, 'right');
    ctx.strokeStyle = line; ctx.beginPath(); ctx.moveTo(56,y); ctx.lineTo(width-56,y); ctx.stroke();
  });
  y += 34;
  const labelX = width-340, amountX = width-72;
  write('Subtotal', labelX, y, '16px Arial, sans-serif', muted); write(money(memo.subtotal), amountX, y, '16px Arial, sans-serif', ink, 'right'); y+=32;
  write('Delivery charge', labelX, y, '16px Arial, sans-serif', muted); write(money(memo.delivery_charge), amountX, y, '16px Arial, sans-serif', ink, 'right'); y+=40;
  ctx.strokeStyle = line; ctx.beginPath(); ctx.moveTo(labelX,y-15); ctx.lineTo(amountX,y-15); ctx.stroke();
  write('TOTAL', labelX, y+12, '700 20px Arial, sans-serif', accent); write(money(memo.total), amountX, y+12, '700 22px Arial, sans-serif', accent, 'right'); y+=55;
  write('Paid: ' + money(memo.paid), labelX, y, '16px Arial, sans-serif', muted);
  write('Due: ' + money(memo.due), amountX, y, '700 17px Arial, sans-serif', Number(memo.due)>0 ? '#b91c1c' : '#15803d', 'right');
  y+=58; ctx.strokeStyle = line; ctx.beginPath(); ctx.moveTo(56,y); ctx.lineTo(width-56,y); ctx.stroke();
  write('Thank you for your order!', width/2, y+42, '600 18px "Hind Siliguri", Arial, sans-serif', accent, 'center');

  document.getElementById('download-memo').addEventListener('click', () => {
    canvas.toBlob(blob => {
      if (!blob) return;
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = memo.code + '-memo.jpg';
      link.click();
      URL.revokeObjectURL(link.href);
    }, 'image/jpeg', 0.94);
  });
})();
</script>
@endsection
