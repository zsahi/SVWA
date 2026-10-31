document.querySelectorAll('form[data-submission="json"]').forEach(form => {
  form.addEventListener('submit', async event => {
    event.preventDefault();
    const button = form.querySelector('button');
    button.disabled = true;
    try {
      const response = await fetch(location.pathname, {
        method: 'POST', headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(Object.fromEntries(new FormData(form)))
      });
      const page = await response.text();
      document.open(); document.write(page); document.close();
    } catch (error) {
      button.disabled = false;
      let status = form.querySelector('[role="alert"]');
      if (!status) { status = document.createElement('p'); status.setAttribute('role','alert'); form.append(status); }
      status.textContent = 'Request failed. Please try again.';
    }
  });
});
