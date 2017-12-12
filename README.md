# Dumpscripts Plugin
## What is this for?
This is a system plugin for Joomla that allows you to dump unwanted scripts (including inline scripts) and unwanted css files in the HEAD. This plugin will
also allow you to re-order scripts/css files. The functionality applies to local and external scripts.
## Installation Requirements
* Joomla 3.1 or higher
* Not tested on Joomla 4
* Tested on PHP 5.x.x and up to PHP 7.0.25

## Usage examples
### Scripts
Separate scripts with comma. Dump an entire folder of scripts ( eg media/jui/js ) or specify a particular script (eg caption.js). If 2 scripts have the same name, then
use part of the path to distinguish them, otherwise only the first found will be discarded. For internal files, omit the domain name since Joomla hasn't processed that
when this script runs. Use an internal path like templates/some_tmpl/js/unwanted.min.js instead. For external files you can use all or just some of the path.

Example:  
``` media/jui/js, system/js/caption.js```

Ordering works the same way. Separate scripts with comma - the plugin will put whatever scripts you specify in order. Whatever scripts you don't specify will come in
after these in whatever order Joomla deems.

Example:  
```less-2.7.2.min.js, //ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js, templates/mytmpl/js/bootstrap.min.js```

### Inline Javascripts
Use the View > Source on the browser and copy/paste the inline javascript(s) that you want to remove. It's important to copy because the white-space determines how to remove it.
If there are more than 2, then separate scripts with a newline containing five hash symbols (#####).
This will only remove scripts that were added using addScriptDeclaration. Do NOT include the &lt;script&gt; tags.

Example:  
```
jQuery(window).on('load',  function() {
		new JCaption('img.caption');
});  
#####  
  jQuery(window).on('document',  ready() {
   if( typeof($(this).data("src")) != "undefined" && $(this).data("src") !== null) {
				window.location.href=$(this).data("src");
			}
		});
```

### CSS
This works the same as scripts

## License
Copyright (c) 2017 AP Clarke  
MIT license
