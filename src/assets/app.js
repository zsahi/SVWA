{
const displaySettings = document.querySelector('.display-settings');
if (displaySettings) {
  const controls = [...displaySettings.querySelectorAll('input[type="checkbox"]')];
  const status = displaySettings.querySelector('[role="status"]');
  let saved = Object.fromEntries(controls.map(control => [control.name, control.checked]));
  displaySettings.addEventListener('change', async () => {
    controls.forEach(control => { control.disabled = true; });
    status.textContent = 'Saving…';
    try {
      const body = new URLSearchParams({token: displaySettings.dataset.token});
      controls.forEach(control => body.set(control.name, control.checked ? '1' : '0'));
      const response = await fetch('/display-settings.php', {method: 'POST', body});
      if (!response.ok) throw new Error('Save failed');
      saved = await response.json();
      controls.forEach(control => { control.checked = saved[control.name]; });
      status.textContent = '';
    } catch (error) {
      controls.forEach(control => { control.checked = saved[control.name]; });
      status.textContent = 'Could not save. Please try again.';
    } finally {
      controls.forEach(control => { control.disabled = false; });
    }
  });
}
}

{
  const settings = document.querySelector('.submission-settings');
  if (settings) {
    const select = settings.querySelector('select');
    const status = settings.querySelector('[role="status"]');
    let saved = select.value;
    select.addEventListener('change', async () => {
      select.disabled = true;
      status.textContent = '';
      const buttons = [...document.querySelectorAll('form[data-submission] button')];
      const disabled = buttons.map(button => button.disabled);
      buttons.forEach(button => { button.disabled = true; });
      try {
        const response = await fetch('/submission-settings.php', {
          method: 'POST',
          body: new URLSearchParams({token: settings.dataset.token, submission: select.value})
        });
        if (!response.ok) throw new Error('Save failed');
        saved = (await response.json()).submission;
        select.value = saved;
        document.querySelectorAll('form[data-submission]').forEach(form => {
          form.dataset.submission = saved;
          form.method = saved === 'get' ? 'get' : 'post';
          form.enctype = saved === 'multipart' ? 'multipart/form-data' : 'application/x-www-form-urlencoded';
        });
      } catch (error) {
        select.value = saved;
        status.textContent = 'Could not save. Please try again.';
      } finally {
        select.disabled = false;
        buttons.forEach((button, index) => { button.disabled = disabled[index]; });
      }
    });
  }
}

document.querySelectorAll('form[data-submission]').forEach(form => {
  form.addEventListener('submit', async event => {
    if (form.dataset.submission !== 'json') return;
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
