
function show_help(message) {
  
  if (navigator.appName == 'Netscape') document.getElementById('help_menu').innerHTML=message;
  else document.all['help_menu'].innerHTML=message;
  
}

function help_rst() {
  
  if (navigator.appName == 'Netscape') document.getElementById('help_menu').innerHTML='&nbsp;';
  else document.all['help_menu'].innerHTML='&nbsp;';
  
}

if (navigator.appName == 'Microsoft Internet Explorer') document.write('<style>.menu a { display: inline;}</style>');
else document.write('<style>.menu a { display: block;} #content table { margin-left: 10px; margin-right: 10px; } </style>');
