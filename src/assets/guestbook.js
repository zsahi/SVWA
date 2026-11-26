{
  const root = document.getElementById('guestbook-manager');
  const endpoint = '/labs/guestbook-comments.php';
  const get = id => document.getElementById(id);
  let current = null;
  let page = Number(root.dataset.page);
  let keyword = root.dataset.keyword;
  let busy = false;
  function status(text) { get('comment-status').textContent = text; }
  function url(id) { return endpoint + '?id=' + encodeURIComponent(id); }
  async function request(method, path, fields) {
    const options = {method};
    if (fields) options.body = new URLSearchParams(fields);
    const response = await fetch(path, options);
    const text = await response.text();
    let data = null;
    try { data = text ? JSON.parse(text) : null; } catch (_) { throw new Error('Unexpected response. Check Burp and refresh the comments.'); }
    if (!response.ok) throw new Error(data?.error || 'Request failed.');
    return data;
  }
  async function run(work) {
    if (busy) return;
    busy = true;
    root.setAttribute('aria-busy', 'true');
    const buttons = [...root.querySelectorAll('button')];
    const wasDisabled = buttons.map(button => button.disabled);
    buttons.forEach(button => { button.disabled = true; });
    status('Working...');
    try { await work(); }
    catch (error) { status(error.message || 'Request failed. Refresh comments before retrying.'); }
    finally {
      buttons.forEach((button,index) => { button.disabled = wasDisabled[index]; });
      get('comments-previous').disabled = page <= 1;
      get('comments-next').disabled = page >= Number(root.dataset.pages || 1);
      root.removeAttribute('aria-busy');
      busy = false;
    }
  }
  function preview(comment) {
    const box = get('comment-preview');
    box.replaceChildren();
    const author = document.createElement('strong'); author.textContent = comment.author;
    const message = document.createElement('div'); message.className = 'message-body';
    // Intentional: retain the existing Stored XSS guestbook's raw message rendering.
    message.innerHTML = comment.message;
    box.append(author, message);
  }
  function select(comment, editing = false) {
    current = comment;
    get('comment-detail').hidden = false;
    get('comment-heading').textContent = 'Comment #' + comment.id;
    get('comment-resource').href = url(comment.id);
    get('comment-resource').textContent = 'example.com' + url(comment.id);
    get('edit-author').value = comment.author;
    get('edit-message').value = comment.message;
    get('comment-editor').hidden = !editing;
    preview(comment);
  }
  function renderList(data) {
    page = data.page; root.dataset.pages = data.pages;
    get('comment-total').textContent = data.total + (data.q ? ' matching' : ' total');
    get('comments-page').textContent = 'Page ' + data.page + ' of ' + data.pages;
    const list = get('comment-list'); list.replaceChildren();
    if (!data.comments.length) {
      const empty = document.createElement('p'); empty.className = 'panel muted'; empty.textContent = data.q ? 'No comments found.' : 'No comments yet.'; list.append(empty);
    }
    data.comments.forEach(comment => {
      const article = document.createElement('article'); article.className = 'panel'; article.dataset.commentId = comment.id;
      const meta = document.createElement('div'); meta.className = 'message-meta';
      const author = document.createElement('strong'); author.textContent = '#' + comment.id + ' - ' + comment.author;
      const time = document.createElement('time'); time.textContent = comment.created_at;
      meta.append(author, time);
      const message = document.createElement('div'); message.className = 'message-body';
      // Deliberate Stored XSS behavior, shared with the existing guestbook lab.
      message.innerHTML = comment.message;
      const actions = document.createElement('div'); actions.className = 'comment-actions';
      ['view','edit','delete'].forEach(action => {
        const button = document.createElement('button'); button.type = 'button'; button.dataset.action = action; button.dataset.id = comment.id;
        button.textContent = action[0].toUpperCase() + action.slice(1); actions.append(button);
      });
      article.append(meta, message, actions); list.append(article);
    });
  }
  async function refresh(targetPage = page, search = false) {
    if (search || keyword) {
      renderList(await request('POST', endpoint, {q: keyword, page: targetPage}));
    } else {
      renderList(await request('GET', endpoint + '?page=' + targetPage));
    }
  }
  async function load(id, editing = false) {
    try { select(await request('GET', url(id)), editing); status('Loaded comment #' + id + '.'); }
    catch (error) { current = null; get('comment-detail').hidden = true; throw error; }
  }
  async function remove(id) {
    if (!window.confirm('Delete guestbook comment #' + id + '?')) { status('Deletion cancelled.'); return; }
    await request('DELETE', url(id));
    if (current && Number(current.id) === Number(id)) { current = null; get('comment-detail').hidden = true; }
    status('Deleted comment #' + id + '. Repeat GET for this comment URL in Repeater to check the 404 response.');
    await refresh();
  }
  const create = get('comment-create');
  if (create.hasAttribute('data-comment-create')) create.addEventListener('submit', event => {
    event.preventDefault();
    const fields = Object.fromEntries(new FormData(create));
    run(async () => {
      const comment = await request('PUT', endpoint, fields);
      select(comment); status('Posted comment #' + comment.id + '.'); await refresh(1);
    });
  });
  async function filter(value) {
    keyword = value.trim();
    await refresh(1, true);
    current = null; get('comment-detail').hidden = true;
    status(keyword ? 'Showing comments matching "' + keyword + '".' : 'Showing all comments.');
  }
  get('comment-filter').addEventListener('submit', event => {
    event.preventDefault(); const value = get('comment-keyword').value; run(() => filter(value));
  });
  get('comments-clear').addEventListener('click', () => run(async () => {
    await filter(''); get('comment-keyword').value = '';
  }));
  get('comment-list').addEventListener('click', event => {
    const button = event.target.closest('button[data-action]'); if (!button) return;
    const {action,id} = button.dataset;
    run(() => action === 'delete' ? remove(id) : load(id, action === 'edit'));
  });
  get('comment-edit').addEventListener('click', () => {
    if (!current) return;
    get('comment-editor').hidden = false; get('edit-author').focus();
  });
  get('comment-cancel').addEventListener('click', () => { if (current) select(current); });
  get('comment-delete').addEventListener('click', () => { if (current) { const id = current.id; run(() => remove(id)); } });
  async function save() {
    if (!current || !get('comment-editor').reportValidity()) return;
    const fields = {};
    for (const name of ['author','message']) {
      const value = get('edit-' + name).value;
      if (value !== current[name]) fields[name] = value;
    }
    if (!Object.keys(fields).length) { status('No changes to save.'); return; }
    const id = current.id;
    const comment = await request('PATCH', url(id), fields);
    select(comment, true); status('Saved comment #' + id + '.'); await refresh();
  }
  get('comment-editor').addEventListener('submit', event => { event.preventDefault(); run(save); });
  get('comments-previous').addEventListener('click', () => run(async () => { await refresh(page - 1); status('Loaded page ' + page + '.'); }));
  get('comments-next').addEventListener('click', () => run(async () => { await refresh(page + 1); status('Loaded page ' + page + '.'); }));
  get('comments-refresh').addEventListener('click', () => run(async () => { await refresh(page); status('Comments refreshed.'); }));
}
