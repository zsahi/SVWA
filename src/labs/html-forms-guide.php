<?php
require __DIR__.'/../includes/app.php';
start_page('Student Guide: HTML Forms and PHP', 'Web Development Basics');
?>
<div style="overflow-wrap:anywhere;">
<p><a href="/labs/html-forms.php">Open the lab</a> &middot; <a href="/labs/html-forms.php?source=1">Read the complete single-page source</a></p>
<p>This lesson follows one form from browser controls to PHP arrays. Open <code>example.com:8888/labs/html-forms.php</code>. The fields already contain practice values, so you can submit immediately.</p>
<h2>What you will learn</h2>
<ul><li>Recognize common HTML form tags and input types.</li><li>Connect a control's name and value to the HTTP request.</li><li>Compare GET, URL-encoded POST, and multipart POST.</li><li>Read submitted values in PHP and put them back into the form.</li></ul>
<h2>Read the form markup</h2>
<p>The lab has text, email, password, telephone, URL, search, number, date, time, datetime-local, month, week, range, color, file, hidden, radio, and checkbox inputs. It also has single and multiple selects, a datalist, a textarea, and different buttons. Date-related controls may look different across browsers.</p>
<pre style="overflow-wrap:anywhere"><code>&lt;label for="full-name"&gt;Full name&lt;/label&gt;
&lt;input type="text" id="full-name" name="full_name"
       value="Ayesha Khan" required&gt;
&lt;textarea name="about"&gt;I am learning forms.&lt;/textarea&gt;</code></pre>
<ul>
<li><code>form</code> groups controls. <code>action</code> is the receiving URL. <code>method</code> chooses GET or POST. There is no form attribute called <code>type</code> that selects these formats.</li>
<li><code>label for</code> refers to a control's <code>id</code>. The <code>id</code> identifies the element in the page; <code>name</code> becomes the request parameter.</li>
<li><code>value</code> sets an input's initial value. A textarea's initial text goes between its opening and closing tags.</li>
<li><code>fieldset</code> and <code>legend</code> group and label related fields.</li>
<li><code>select</code> contains <code>option</code> elements; <code>selected</code> sets a default. <code>optgroup</code> labels a set of options. A <code>datalist</code> suggests values but still permits other text.</li>
<li><code>required</code>, <code>min</code>, <code>max</code>, and <code>maxlength</code> affect the browser form. They do not describe the database schema.</li>
</ul>
<h2>Compare three submissions</h2>
<pre style="overflow-wrap:anywhere"><code>&lt;form action="/labs/html-forms.php" method="post"
      enctype="application/x-www-form-urlencoded"&gt;
  &lt;!-- Controls go here. --&gt;
  &lt;button type="submit" formmethod="get"&gt;GET&lt;/button&gt;
  &lt;button type="submit" formmethod="post"
          formenctype="application/x-www-form-urlencoded"&gt;POST&lt;/button&gt;
  &lt;button type="submit" formmethod="post"
          formenctype="multipart/form-data"&gt;Multipart POST&lt;/button&gt;
&lt;/form&gt;</code></pre>
<p>A clicked button can override the form's method with <code>formmethod</code> and encoding with <code>formenctype</code>. That is why all three buttons work without custom JavaScript.</p>
<ol><li>Click Submit with GET. Look at the URL and Burp request line. Values appear in the query string, such as <code>full_name=Ayesha+Khan</code>. PHP reads them in <code>$_GET</code>.</li>
<li>Click Submit with POST. The URL stays at the form route; fields travel in an <code>application/x-www-form-urlencoded</code> body. PHP reads them in <code>$_POST</code>.</li>
<li>Choose a small text file and click Submit with multipart POST. Burp shows boundaries and separate parts. Text fields are in <code>$_POST</code>; uploaded file metadata is in <code>$_FILES</code>. GET and URL-encoded POST do not transmit the file contents.</li></ol>
<p>The page shows the request method, Content-Type, and all three arrays. No selected file is required; an empty multipart file input can show an upload error code of 4 (no file). The browser cannot prefill or restore a file selection. PHP's temporary upload is not saved permanently by this lab.</p>
<h2>Which controls are sent?</h2>
<p>Only checked checkboxes and the selected radio button are sent. Uncheck every interest and the interests field is absent. Names such as <code>interests[]</code> and <code>languages[]</code> become PHP arrays. A hidden input and a read-only input are sent; disabled controls and controls without a name are not. Only the clicked submit button contributes its name/value. Reset and type=button do not submit.</p>
<p>A password input only masks the display; inspect Burp to see its submitted value. Use the supplied practice value.</p>
<h2>Follow PHP reading the input</h2>
<pre style="overflow-wrap:anywhere"><code>$requestMethod = $_SERVER['REQUEST_METHOD'];
$submittedValues = $requestMethod === 'POST' ? $_POST : $_GET;
$fullName = $submittedValues['full_name'] ?? 'Ayesha Khan';</code></pre>
<p><code>$_SERVER</code>, <code>$_GET</code>, <code>$_POST</code>, and <code>$_FILES</code> are PHP superglobal arrays. The conditional expression chooses an array; <code>??</code> provides a sample when a key is missing. <code>&lt;?=$fullName?&gt;</code> prints the variable into the returned HTML. <code>print_r</code> displays array structure; <code>htmlspecialchars</code> in the diagnostic output makes that structure readable as text.</p>
<p>The browser receives HTML, not the PHP source. Use View source on the lab to study this single file. Shared includes only provide SVWA's layout and session setup.</p>
<h2>Try it yourself</h2>
<p>Change the name, uncheck HTML, and submit using all three buttons. Locate the same values in Burp and the PHP arrays. Explain why student_code is present but disabled_example is absent.</p>
</div>
<?php end_page(); ?>
