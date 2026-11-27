<?php
// All form handling and markup for this exercise live in this file.
if (isset($_GET['source'])) {
    highlight_file(__FILE__);
    exit;
}
require __DIR__ . '/../includes/app.php';

$requestMethod = $_SERVER['REQUEST_METHOD'];
$submittedValues = $requestMethod === 'POST' ? $_POST : $_GET;
$wasSubmitted = isset($submittedValues['lesson']);
$fullName = $submittedValues['full_name'] ?? 'Ayesha Khan';
$email = $submittedValues['email'] ?? 'student@example.test';
$password = $submittedValues['password'] ?? 'practice123';
$phone = $submittedValues['phone'] ?? '03001234567';
$website = $submittedValues['website'] ?? 'https://example.com';
$keyword = $submittedValues['keyword'] ?? 'web security';
$age = $submittedValues['age'] ?? '21';
$birthDate = $submittedValues['birth_date'] ?? '2005-01-15';
$meetingTime = $submittedValues['meeting_time'] ?? '10:30';
$appointment = $submittedValues['appointment'] ?? '2026-11-27T10:30';
$studyMonth = $submittedValues['study_month'] ?? '2026-11';
$studyWeek = $submittedValues['study_week'] ?? '2026-W48';
$confidence = $submittedValues['confidence'] ?? '5';
$favoriteColor = $submittedValues['favorite_color'] ?? '#087f75';
$studyMode = $submittedValues['study_mode'] ?? 'online';
$interests = $wasSubmitted ? ($submittedValues['interests'] ?? []) : ['html', 'php'];
$country = $submittedValues['country'] ?? 'Pakistan';
$languages = $wasSubmitted ? ($submittedValues['languages'] ?? []) : ['English', 'Urdu'];
$browser = $submittedValues['browser'] ?? 'Chrome';
$about = $submittedValues['about'] ?? 'I am learning how browsers send form data.';

start_page('HTML Forms and PHP', 'Web Development Basics');
?>
<p class="lead">Inspect the controls, submit the sample values, and see what PHP receives.</p>
<p><a href="/labs/html-forms-guide.php">Read the student guide</a> &middot; <a href="?source=1">View this page's PHP
        source</a></p>
<p>The buttons below control this exercise's method and encoding. The global Submission Type selector does not change
    this form.</p>
<style>
    .teaching-form fieldset {
        margin: 20px 0;
        padding: 20px;
        border: 1px solid #cbdad6;
        border-radius: 8px
    }

    .teaching-form legend {
        font-weight: 600
    }

    .teaching-form input,
    .teaching-form select,
    .teaching-form textarea {
        margin-bottom: 14px
    }

    .teaching-form input[type=checkbox],
    .teaching-form input[type=radio] {
        width: auto;
        margin: 0 8px 0 0
    }

    .teaching-form input[type=color] {
        height: 48px
    }

    .teaching-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px
    }

    .teaching-result pre {
        overflow-wrap: anywhere
    }
</style>
<form class="panel teaching-form" action="/labs/html-forms.php" method="post"
    enctype="application/x-www-form-urlencoded">
    <input type="hidden" name="lesson" value="html_forms">
    <fieldset>
        <legend>Text inputs</legend>
        <label for="full-name">Full name (text, required)</label>
        <input type="text" id="full-name" name="full_name" value="<?= $fullName ?>" required maxlength="80"
            placeholder="Your full name">
        <label for="email">Email</label><input type="email" id="email" name="email" value="<?= $email ?>">
        <label for="password">Practice password</label><input type="password" id="password" name="password"
            value="<?= $password ?>">
        <label for="phone">Telephone</label><input type="tel" id="phone" name="phone" value="<?= $phone ?>">
        <label for="website">Website URL</label><input type="url" id="website" name="website" value="<?= $website ?>">
        <label for="keyword">Search input</label><input type="search" id="keyword" name="keyword" value="<?= $keyword ?>">
        <label for="browser">Browser (datalist suggestions)</label><input id="browser" name="browser" list="browsers"
            value="<?= $browser ?>">
        <datalist id="browsers">
            <option value="Chrome">
            <option value="Firefox">
            <option value="Edge">
        </datalist>
    </fieldset>
    <fieldset>
        <legend>Numbers, dates, and color</legend>
        <label for="age">Age (number)</label><input type="number" id="age" name="age" value="<?= $age ?>" min="1"
            max="120">
        <label for="birth-date">Birth date</label><input type="date" id="birth-date" name="birth_date"
            value="<?= $birthDate ?>">
        <label for="meeting-time">Meeting time</label><input type="time" id="meeting-time" name="meeting_time"
            value="<?= $meetingTime ?>">
        <label for="appointment">Local date and time</label><input type="datetime-local" id="appointment"
            name="appointment" value="<?= $appointment ?>">
        <label for="study-month">Study month</label><input type="month" id="study-month" name="study_month"
            value="<?= $studyMonth ?>">
        <label for="study-week">Study week</label><input type="week" id="study-week" name="study_week"
            value="<?= $studyWeek ?>">
        <label for="confidence">Confidence (range, 0 to 10)</label><input type="range" id="confidence" name="confidence"
            value="<?= $confidence ?>" min="0" max="10">
        <label for="favorite-color">Favorite color</label><input type="color" id="favorite-color" name="favorite_color"
            value="<?= $favoriteColor ?>">
    </fieldset>
    <fieldset>
        <legend>Choices</legend>
        <p>Radio buttons: choose one study mode.</p>
        <label><input type="radio" name="study_mode" value="online" <?= $studyMode === 'online' ? 'checked' : '' ?>>Online</label>
        <label><input type="radio" name="study_mode" value="classroom" <?= $studyMode === 'classroom' ? 'checked' : '' ?>>Classroom</label>
        <p>Checkboxes: select any interests.</p>
        <label><input type="checkbox" name="interests[]" value="html" <?= in_array('html', $interests) ? 'checked' : '' ?>>HTML</label>
        <label><input type="checkbox" name="interests[]" value="javascript" <?= in_array('javascript', $interests) ? 'checked' : '' ?>>JavaScript</label>
        <label><input type="checkbox" name="interests[]" value="php" <?= in_array('php', $interests) ? 'checked' : '' ?>>PHP</label>
        <label for="country">Country (select)</label>
        <select id="country" name="country">
            <optgroup label="Practice locations">
                <?php foreach (['Pakistan', 'United Kingdom', 'United States'] as $option): ?>
                    <option value="<?= $option ?>" <?= $country === $option ? 'selected' : '' ?>><?= $option ?></option>
                <?php endforeach ?>
            </optgroup>
        </select>
        <label for="languages">Languages (multiple select; Ctrl/Cmd-click to choose several)</label>
        <select id="languages" name="languages[]" multiple
            size="3"><?php foreach (['English', 'Urdu', 'Arabic'] as $option): ?>
                <option value="<?= $option ?>" <?= in_array($option, $languages) ? 'selected' : '' ?>><?= $option ?></option>
            <?php endforeach ?>
        </select>
    </fieldset>
    <fieldset>
        <legend>Long text, files, and submission rules</legend>
        <label for="about">About you (textarea)</label><textarea id="about" name="about" rows="3"><?= $about ?></textarea>
        <label for="attachment">Optional file</label><input type="file" id="attachment" name="attachment">
        <p>A browser cannot prefill a file input. Choose a small local text file to see it in the multipart submission.
            This page displays upload metadata but does not save the file permanently.</p>
        <label for="student-code">Read-only value (sent)</label><input id="student-code" name="student_code"
            value="STUDENT-001" readonly>
        <label for="disabled-example">Disabled value (not sent)</label><input id="disabled-example"
            name="disabled_example" value="This stays in the browser" disabled>
        <label for="unnamed-example">Input with no name (not sent)</label><input id="unnamed-example"
            value="No name attribute">
        <p>The hidden input named <code>lesson</code> is also sent, although it has no visible control.</p>
    </fieldset>
    <div class="teaching-actions">
        <button type="submit" name="submit_format" value="get" formmethod="get">Submit with GET</button>
        <button type="submit" name="submit_format" value="post" formmethod="post"
            formenctype="application/x-www-form-urlencoded">Submit with POST</button>
        <button type="submit" name="submit_format" value="multipart" formmethod="post"
            formenctype="multipart/form-data">Submit with multipart POST</button>
        <button type="reset">Reset fields</button>
        <button type="button">Button with no action</button>
    </div>
</form>
<?php if ($wasSubmitted): ?>
    <section class="panel teaching-result" id="received-data">
        <h2>What PHP received</h2>
        <p>Request method: <strong><?= $requestMethod ?></strong></p>
        <p>Content-Type: <?= htmlspecialchars($_SERVER['CONTENT_TYPE'] ?? '(none)') ?></p>
        <h3>$_GET</h3>
        <pre><?= htmlspecialchars(print_r($_GET, true)) ?></pre>
        <h3>$_POST</h3>
        <pre><?= htmlspecialchars(print_r($_POST, true)) ?></pre>
        <h3>$_FILES</h3>
        <pre><?= htmlspecialchars(print_r($_FILES, true)) ?></pre>
        <p>The form above uses the submitted values. Reset restores the values in the current HTML response; <a
                href="/labs/html-forms.php">open a fresh form</a> to restore the original samples. File inputs must be
            selected again after submission.</p>
    </section>
<?php endif ?>
<?php end_page(); ?>