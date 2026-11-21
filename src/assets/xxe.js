const xmlForm = document.getElementById('xml-order-form');
const xmlResult = document.getElementById('xml-result');
const xmlResponseStatus = document.getElementById('xml-response-status');
xmlForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  const button = xmlForm.querySelector('button');
  button.disabled = true;
  xmlResult.textContent = 'Parsing order...';
  xmlResponseStatus.textContent = '';
  try {
    const response = await fetch('/labs/xxe.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/xml'},
      body: document.getElementById('xml-order').value
    });
    xmlResult.textContent = await response.text();
    xmlResponseStatus.textContent = response.ok
      ? `HTTP ${response.status}: Order parsed. Compare the item below with your objective.`
      : `HTTP ${response.status}: Request rejected. Check the response below and revise your XML.`;
  } catch (error) {
    xmlResult.textContent = 'Unable to preview the order. Please try again.';
    xmlResponseStatus.textContent = 'Network error: check that the Docker lab is running.';
  } finally {
    button.disabled = false;
  }
});
