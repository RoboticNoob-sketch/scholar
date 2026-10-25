document.addEventListener('DOMContentLoaded', () => {
  const qrNodes = Array.from(document.querySelectorAll('[data-voucher-qr]'));
  const printButton = document.querySelector('[data-print-vouchers]');

  if (!qrNodes.length || !printButton) {
    return;
  }

  const renderFallbackQr = (node) => {
    const image = document.createElement('img');
    image.alt = 'Voucher QR code';
    image.src = `https://api.qrserver.com/v1/create-qr-code/?size=196x196&data=${encodeURIComponent(node.dataset.voucherQr || '')}`;
    node.replaceChildren(image);
  };

  qrNodes.forEach((node) => {
    const payload = node.dataset.voucherQr || '';
    if (window.QRCode) {
      new QRCode(node, {
        text: payload,
        width: 196,
        height: 196,
        colorDark: '#102017',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M,
      });
    } else {
      renderFallbackQr(node);
    }
  });

  printButton.disabled = false;
  const ticketCount = document.querySelectorAll('.print-voucher').length;
  const voucherLabel = ticketCount === 1 ? 'voucher' : 'vouchers';
  printButton.innerHTML = '<i data-lucide="printer"></i> Print ' + ticketCount + ' ' + voucherLabel;
  window.lucide?.createIcons();
  printButton.addEventListener('click', () => window.print());
});