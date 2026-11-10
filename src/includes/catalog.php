<?php
return [
 'SSRF'=>[['ssrf','Stock checker','Check warehouse stock through a server-side request.']],
 'Broken Access Control'=>[['student-result','Broken Access Control – Student Result IDOR','Explore a university result portal. Login required.']],
 'SQL Injection'=>[
  ['numeric','Numeric lookup','Find a product by its ID.'],
  ['union','Product search','Search the product catalog.'],
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
 'Stored XSS'=>[['guestbook','Guestbook','Leave a message on the shared board.']],
 'File Upload'=>[
  ['unrestricted','File drop','Upload a file to the shared folder.'],
  ['mime','Image upload','Upload a JPEG or PNG image.'],
  ['extension','Document upload','Upload a document attachment.']]
];
