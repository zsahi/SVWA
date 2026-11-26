<?php
require __DIR__.'/../includes/app.php';
start_page('Session Management', 'HTTP Basics');
?>
<div style="overflow-wrap:anywhere;">
<p>In Burp, open a request to <code>/profile.php</code> and look for <code>Cookie: SVWASESSID=...</code>. You did not type your password again, yet the page knows which profile to show. That cookie connects the request to session data held by the server.</p>
<p>Use the existing SVWA pages in Burp's browser. No new login system is needed for this lesson:</p>
<ul>
<li><a href="/login.php">Login</a> &mdash; <code>http://example.com:8888/login.php</code></li>
<li><a href="/profile.php">My profile</a> &mdash; <code>http://example.com:8888/profile.php</code></li>
<li><a href="/edit_profile.php">Edit my profile</a> &mdash; <code>http://example.com:8888/edit_profile.php</code></li>
</ul>
<h2>1. Start with a fresh browser session</h2>
<p>Open Login in a fresh Burp browser session with no SVWA cookie. Turn Intercept off, or forward the requests. In <strong>Proxy &gt; HTTP history</strong>, select <code>GET /login.php</code>. The response sets a session cookie even before you log in.</p>
<pre><code>HTTP/1.1 200 OK
Set-Cookie: SVWASESSID=ANONYMOUS_SESSION; path=/; HttpOnly; SameSite=Lax</code></pre>
<p>This is a shortened example, not captured traffic. The labels ANONYMOUS_SESSION and SIGNED_IN_SESSION below stand for actual opaque values. If your browser already has a session cookie, you may not see a new Set-Cookie header on this request.</p>
<p>A session ID is a lookup key, not your username or password. A cookie can exist before authentication. At that point, the server has not associated the session with a logged-in account.</p>
<h2>2. Log in and notice the changed ID</h2>
<p>Use an ordinary training account shown on the Login page, such as <code>user1</code> with <code>password1</code> if those defaults are unchanged. Click Login. Find <code>POST /login.php</code> in Burp history and inspect its request and response.</p>
<p>The form sends the username and password. Its Cookie header carries the anonymous session ID. Successful login returns a 303 redirect and a new session cookie:</p>
<pre><code>HTTP/1.1 303 See Other
Location: /profile.php
Set-Cookie: SVWASESSID=SIGNED_IN_SESSION; path=/; HttpOnly; SameSite=Lax</code></pre>
<p>Compare the old and new cookie values yourself. SVWA regenerates the session ID after login and invalidates the old ID. The browser stores the replacement and follows the redirect. On the server, the session now holds the account ID and a session-version value used to check whether the login is still valid.</p>
<h2>3. Follow the cookie to your profile</h2>
<p>The next request should look like this:</p>
<pre><code>GET /profile.php HTTP/1.1
Host: example.com:8888
Cookie: SVWASESSID=SIGNED_IN_SESSION

</code></pre>
<p><strong>Set-Cookie</strong> goes from server to browser. <strong>Cookie</strong> goes from browser to server. SVWA reads the session identified by this cookie, checks the account, and displays its profile. The password is not needed in this GET request.</p>
<p>Click Edit my own profile. Inspect <code>GET /edit_profile.php</code>: it should carry the same signed-in session ID. Open the form and click Save profile with the existing values to avoid changing the account. Inspect <code>POST /edit_profile.php</code>. It sends <code>name</code> and <code>email</code>, while the cookie still identifies whose profile to save. A successful save displays <strong>Profile saved.</strong></p>
<p>The existing account forms also include a hidden <code>csrf</code> field. Keep it when replaying a captured form. It is a separate form token, not the session ID; this lesson does not introduce any new token or authentication behavior.</p>
<h2>4. Compare a request without the session cookie</h2>
<p>Send your GET profile request to Repeater. Send it once with the signed-in cookie: expect 200 and your profile. Duplicate the request, remove only the <code>SVWASESSID</code> cookie, and send it again. Inspect the first response without following redirects:</p>
<pre><code>HTTP/1.1 303 See Other
Location: /login.php</code></pre>
<p>The server cannot find an authenticated session for the second request, so it sends you to Login. Removing the cookie in Repeater does not log out the browser or destroy its session; the browser still has its original cookie. An invented or old session ID does not make the request authenticated.</p>
<h2>5. Log out and check the old ID</h2>
<p>Keep a copy of the signed-in GET profile request in Repeater. In the browser, click Logout on the profile page. This submits <code>POST /logout.php</code>. SVWA clears the server-side session, expires the browser's session cookie, and redirects to Login.</p>
<p>Now resend the saved GET profile request with the old signed-in cookie. It should redirect to Login rather than return the profile. The old value no longer points to an authenticated session. A new anonymous cookie may be issued; that does not mean you are logged in again.</p>
<h2>6. Inspect the session file in Docker</h2>
<p>Because this lab runs on your machine, you can follow the cookie all the way to the server. In the current SVWA Docker setup, PHP uses file-based session storage in <code>/tmp</code> inside the <code>svwa-web-1</code> container. A session file is named <code>/tmp/sess_&lt;SVWASESSID value&gt;</code>.</p>
<p>The cookie value is the lookup key used in the filename. The file contains the session data; the browser does not receive that data in its cookie. These are files inside the container, not your Windows temporary folder.</p>
<p>Start a fresh login for this inspection. In Burp, select the authenticated request to <code>/profile.php</code> and copy only the value after <code>SVWASESSID=</code>, stopping before any semicolon. In PowerShell on your host machine, replace the placeholder and run:</p>
<pre><code>$sessionId = 'PASTE_YOUR_COOKIE_VALUE_HERE'

docker exec svwa-web-1 ls -l "/tmp/sess_$sessionId"
docker exec svwa-web-1 cat "/tmp/sess_$sessionId"</code></pre>
<p>The first command shows the file's metadata. In this setup, the owner and group are <code>www-data</code>, with permissions <code>600</code>. The second command prints the stored session data without changing it. Use your current signed-in ID: login replaces the earlier anonymous ID.</p>
<h3>Read the stored values</h3>
<p>PHP's session serialization is not JSON. This short example shows only the display preferences, not a complete session:</p>
<pre><code>submission|s:4:"post";show_query|b:0;show_errors|b:0;</code></pre>
<p>A field name is followed by <code>|</code> and its serialized value. <code>s:4:"post";</code> is a four-byte string, <code>b:0;</code> is false, and <code>i:2;</code> would be the integer 2.</p>
<ul>
<li><code>account_id</code> identifies the logged-in account. Profile details are read from the accounts database using this ID.</li>
<li><code>account_version</code> is checked against the account's session version to confirm the login is still valid.</li>
<li><code>submission</code>, <code>show_query</code>, and <code>show_errors</code> store the selected submission format and SQL display preferences.</li>
<li><code>display_settings_token</code> validates settings changes; <code>csrf</code> is the existing account-form token. Neither is the session ID.</li>
</ul>
<p>The password is not stored in this session file. Other features may add temporary fields, so your file can contain more than the fields listed here.</p>
<h3>Connect the file to login and logout</h3>
<ol>
<li>Before login, capture the anonymous cookie and inspect its file. It has no authenticated <code>account_id</code>.</li>
<li>Log in and capture the new cookie. Inspect the new file: it now contains <code>account_id</code> and <code>account_version</code>. SVWA removes the old anonymous session file when it rotates the ID.</li>
<li>Visit Profile and Edit Profile. Their requests use the same signed-in ID and therefore the same session file.</li>
<li>Log out, then run the same <code>cat</code> command with the old signed-in ID. It should report <code>No such file or directory</code>: logout destroyed that session file. A later anonymous request can create a different file.</li>
</ol>
<h2>Cookie details to recognize</h2>
<ul>
<li><code>Path=/</code> lets the browser send this cookie to paths such as Login, Profile, and Edit Profile on the same host.</li>
<li><code>HttpOnly</code> prevents page JavaScript from reading the cookie through <code>document.cookie</code>. Burp still shows it in HTTP traffic.</li>
<li><code>SameSite=Lax</code> limits sending the cookie in cross-site contexts. It does not replace the server's account checks.</li>
<li>On the supplied <code>http://</code> lab URL, SVWA does not set <code>Secure</code>. Its configuration adds that flag when PHP sees an HTTPS request. Secure cookies are sent over secure connections.</li>
</ul>
<h2>Observation task</h2>
<p>Follow one login through Profile, Edit Profile, and Logout. Record whether the ID changed at login, whether Profile and Edit Profile sent the same signed-in ID, which account_id appeared in its server-side file, and whether logout removed that file and prevented the saved ID from opening the profile. Use labels such as A and B in your notes instead of copying live session values.</p>
</div>
<?php end_page(); ?>
