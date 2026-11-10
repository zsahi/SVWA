const stockButton = document.getElementById('check-stock');
const stockResult = document.getElementById('stock-result');

stockButton.addEventListener('click', async () => {
  stockButton.disabled = true;
  stockResult.textContent = 'Checking warehouse availability...';
  try {
    const response = await fetch('/labs/ssrf.php', {
      method: 'POST',
      body: new URLSearchParams({
        stockApi: 'http://127.0.0.1/internal/stock.php?item=1'
      })
    });
    stockResult.textContent = await response.text();
  } catch (error) {
    stockResult.textContent = 'Unable to check stock. Please try again.';
  } finally {
    stockButton.disabled = false;
  }
});
