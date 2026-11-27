<?php
return [
 'Web Development Basics'=>[
  ['html-forms','HTML Forms and PHP','Explore form controls and GET, POST, and multipart submissions.'],
  ['javascript-json','JavaScript and JSON','Use fetch and JSON to manage comments without page reloads.'],
  ['php-database','PHP and MySQL Queries','Follow form values into SELECT, INSERT, UPDATE, and DELETE.']],
 'HTTP Basics'=>[['trace-request','Trace a Request and Its Response','Trace a GET search in Burp Proxy and edit it in Repeater.'],['guestbook-methods','HTTP Methods','Use real guestbook actions to explore HTTP methods and comment IDs.'],['request-content-types','Request Content Types','Use Submission Type to compare URL-encoded, multipart, and JSON request bodies.'],['url-encoding','URL Encoding','Practice URL encoding in the reflected XSS lab.'],['session-management','Session Management','Follow the SVWASESSID cookie through login, profile editing, and logout.']],
 'XXE'=>[['xxe','XML order preview','Preview an order submitted as XML.']],
 'SSRF'=>[['ssrf','Stock checker','Check warehouse stock through a server-side request.']],
 'Broken Access Control'=>[['student-result','Broken Access Control – Student Result IDOR','Explore a university result portal. Login required.']],
 'SQL Injection'=>[
  ['union','Product search','Search the product catalog.'],
  ['numeric','Numeric lookup','Find a product by its ID.'],
  ['boolean','Availability check','Check whether a product is available.'],
  ['time','Stock notification','Request a stock notification.'],
  ['order','Product sorting','Choose how products are ordered.']],
 'Reflected XSS'=>[
  ['html','Search results','Search by keyword.'],
  ['attribute','Profile preview','Preview a display name.'],
  ['javascript','Welcome message','Personalize a welcome message.']],
 'DOM XSS'=>[
  ['innerhtml','Quick search','Display a query in the page.'],
  ['write','Message preview','Preview a message from the URL fragment.'],
  ['eval','Calculator','Evaluate an arithmetic expression.']],
 'Stored XSS'=>[['guestbook','Guestbook','Post, browse, edit, and delete comments on the shared board.']],
 'File Upload'=>[
  ['unrestricted','File drop','Upload a file to the shared folder.'],
  ['mime','Image upload','Upload a JPEG or PNG image.'],
  ['extension','Document upload','Upload a document attachment.']]
];
