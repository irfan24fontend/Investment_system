@extends('layouts.app')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
  <div><a href="{{ route('admin.customer-orders') }}" class="text-sm text-indigo-700">← Customer orders</a><h1 class="text-xl font-bold mt-1">Receipt {{ $order->code }}</h1></div>
  <button id="download-memo" class="bg-indigo-600 text-white rounded-lg px-4 py-2 text-sm font-medium">Download receipt as JPG</button>
</div>
<div class="bg-white border rounded-xl p-3 overflow-x-auto">
  <canvas id="memo-canvas" class="max-w-full h-auto mx-auto border rounded"></canvas>
</div>
<script>
(() => {
  const memo = @json($memo);
  const canvas = document.getElementById('memo-canvas');
  const ctx = canvas.getContext('2d');
  const width = 1000;
  const left = 56, right = width - 56;
  const rowHeight = 58, tableTop = 302;
  const tableBottom = tableTop + 42 + Math.max(memo.items.length, 1) * rowHeight;
  const totalsTop = tableBottom + 40;
  const footerTop = totalsTop + 220;
  canvas.width = width;
  canvas.height = footerTop + 104;

  const black = '#080808', gray = '#5c5c5c', border = '#333333';
  const money = value => '৳' + Number(value).toLocaleString('en-BD', {minimumFractionDigits:2, maximumFractionDigits:2});
  const write = (text, x, y, font='16px Arial, sans-serif', color=black, align='left', maxWidth=Infinity) => {
    ctx.font = font; ctx.fillStyle = color; ctx.textAlign = align;
    ctx.fillText(String(text ?? ''), x, y, maxWidth);
  };
  const line = (x1, y1, x2, y2, color=border, lineWidth=1) => {
    ctx.strokeStyle = color; ctx.lineWidth = lineWidth; ctx.beginPath();
    ctx.moveTo(x1, y1); ctx.lineTo(x2, y2); ctx.stroke();
  };

  ctx.fillStyle = '#ffffff'; ctx.fillRect(0, 0, canvas.width, canvas.height);
  ctx.fillStyle = black; ctx.fillRect(0, 0, width, 10);
  ctx.fillRect(left, 34, 210, 58);
  write('RECEIPT', left + 105, 73, '700 34px Arial, sans-serif', '#ffffff', 'center');
  write(memo.company, 330, 55, '700 30px Arial, sans-serif');
  write('Phone: ' + memo.company_phone, 330, 82, '18px Arial, sans-serif');
  write('No.:', left, 132, '700 17px Arial, sans-serif');
  write(memo.code, left + 62, 132, '18px Arial, sans-serif', '#7f1d1d');
  line(left + 60, 140, left + 270, 140);
  write('Date:', right - 225, 132, '700 17px Arial, sans-serif');
  write(memo.date, right - 175, 132, '16px Arial, sans-serif', black, 'left', 175);
  line(right - 180, 140, right, 140);

  write('Client Information:', left, 184, '700 19px Arial, sans-serif');
  write('Name:', left + 20, 222, '700 16px Arial, sans-serif');
  write(memo.customer, left + 92, 222, '16px Arial, sans-serif', black, 'left', right - left - 110);
  line(left + 90, 228, right, 228);
  write('Address:', left + 20, 254, '700 16px Arial, sans-serif');
  write(memo.address || '—', left + 92, 254, '16px Arial, sans-serif', black, 'left', right - left - 110);
  line(left + 90, 260, right, 260);
  write('Contact:', left + 20, 286, '700 16px Arial, sans-serif');
  const contact = memo.whatsapp && memo.whatsapp !== memo.phone ? memo.phone + ' / WhatsApp: ' + memo.whatsapp : memo.phone;
  write(contact, left + 92, 286, '16px Arial, sans-serif', black, 'left', right - left - 110);
  line(left + 90, 292, right, 292);

  const cols = [left, left + 82, 630, 730, 830, right];
  ctx.fillStyle = black; ctx.fillRect(left, tableTop, right - left, 42);
  write('No.', (cols[0] + cols[1]) / 2, tableTop + 27, '700 15px Arial, sans-serif', '#ffffff', 'center');
  write('Item Description', cols[1] + 10, tableTop + 27, '700 15px Arial, sans-serif', '#ffffff');
  write('Units', (cols[2] + cols[3]) / 2, tableTop + 27, '700 15px Arial, sans-serif', '#ffffff', 'center');
  write('Price', (cols[3] + cols[4]) / 2, tableTop + 27, '700 15px Arial, sans-serif', '#ffffff', 'center');
  write('Amount', (cols[4] + cols[5]) / 2, tableTop + 27, '700 15px Arial, sans-serif', '#ffffff', 'center');
  const rows = Math.max(memo.items.length, 1);
  for (let i = 0; i <= rows; i++) line(left, tableTop + 42 + i * rowHeight, right, tableTop + 42 + i * rowHeight);
  cols.forEach(x => line(x, tableTop, x, tableBottom));
  memo.items.forEach((item, index) => {
    const y = tableTop + 42 + index * rowHeight + 35;
    write(index + 1, (cols[0] + cols[1]) / 2, y, '16px Arial, sans-serif', black, 'center');
    write(item.name, cols[1] + 10, y, '16px Arial, sans-serif', black, 'left', cols[2] - cols[1] - 20);
    write(item.quantity, (cols[2] + cols[3]) / 2, y, '16px Arial, sans-serif', black, 'center');
    write(money(item.unit_price), (cols[3] + cols[4]) / 2, y, '15px Arial, sans-serif', black, 'center');
    write(money(item.total), (cols[4] + cols[5]) / 2, y, '15px Arial, sans-serif', black, 'center');
  });
  write('E&OE', left + 6, tableBottom + 22, '700 13px Arial, sans-serif');

  const labelX = right - 430, amountX = right;
  const totals = [
    ['Sum total:', memo.subtotal, false],
    ['Delivery charge:', memo.delivery_charge, false],
    ['Total:', memo.total, true],
    ['Paid:', memo.paid, false],
    ['Due:', memo.due, true],
  ];
  totals.forEach(([label, amount, strong], index) => {
    const y = totalsTop + index * 38 + 22;
    const font = strong ? '700 19px Arial, sans-serif' : '16px Arial, sans-serif';
    write(label, labelX, y, font, black, 'right');
    write(money(amount), amountX, y, font, black, 'right');
    line(labelX + 14, y + 8, amountX, y + 8, strong ? black : '#777777', strong ? 1.5 : 1);
  });
  ctx.fillStyle = black; ctx.fillRect(0, footerTop, width, 42);
  write('THANK YOU SO MUCH FOR YOUR BUSINESS.', width / 2, footerTop + 27, 'italic 700 17px Arial, sans-serif', '#ffffff', 'center');
  write('Goods & payment once received cannot be returned!', width / 2, footerTop + 72, 'italic 700 15px Arial, sans-serif', black, 'center');

  document.getElementById('download-memo').addEventListener('click', () => {
    canvas.toBlob(blob => {
      if (!blob) return;
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = memo.code + '-receipt.jpg';
      link.click();
      URL.revokeObjectURL(link.href);
    }, 'image/jpeg', 0.94);
  });
})();
</script>
@endsection
