<?php
/*
                 RavenCore Hosting Control Panel
               Copyright (C) 2005  Corey Henderson

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

 //
 // sometime later I'll use the native php gettext functions....
 // and create the .po files with the GNU unix command:
 // xgettext -k_ -kN_ -o messages.pot file.php
 //


$locale_dir = 'locales/';

// include all of our installed language pack's info

$locales = array();

// first we scan the locales directory
$d = dir($locale_dir);

// fill an array with the data
while (false !== ($entry = $d->read())) $locales_sorted[] = $entry;

// sort the data
sort($locales_sorted);

// walk down the array and pick out what exists
foreach ($locales_sorted as $entry) {
  
	// if this element is a directory, and not an implied "." or ".."
	if (is_dir($locale_dir . $entry) and !ereg('^\.',$entry)) {

		// search for the "info.php" file. It contains the data we need to know about the language
		if (file_exists($locale_dir . $entry . '/info.php')) {
			// it does an "array_push" on the $locales array, with the language pack header info
			include $locale_dir . $entry . '/info.php';
		}

	}

}



function __($string) {
	global $trans, $current_locale;

	// Default - means english
	// No translation needed
	if (empty($current_locale) || $current_locale === 'en_US' ) {
		$current_locale = 'en_US';
		return $string;
	}
	
	// Locale is the same as locale in $trans array
	if (isset($trans[$current_locale]))
		return ($trans[$current_locale][$string]) ?  $trans[$current_locale][$string] : $string;

	return $string;
}

/**
 * e_( string )
 *
 * Prints out translated string
 */
function e_($string) {
	echo __($string, $locale);
}


/**
 * locale_change( string )
 *
 * Changes current locale
 */
function locale_change($locale) {
	global $current_locale, $last_locale, $locale_dir;
	
	if ($locale === $current_locale)
		return true;
		
	$file = $locale_dir . $locale . '/data.php';

	if( file_exists($file) && is_readable($file)) {
		// We can include the file
		include $file;

		// Checking if file contains valid info
		if (isset($trans) && is_array($trans) && isset($trans[$locale])) {
			$last_locale = $current_locale; // saving last locale
			$current_locale = $locale; 		// setting current locale 
			$GLOBALS['trans'] = $trans;		// setting translation array
		}
	}
	
	return false;
}

/**
 * 		See: locale_change( string )
 */
function locale_set($locale) {
	return locale_change( $locale );
}


function locale_getcharset() {
	global $locales, $current_locale;

	if (isset($locales[$current_locale]) && isset($locales[$current_locale]['charset']))
		return $locales[$current_locale]['charset'];
	
	return 'iso-8859-1';
}

/**
 * locale_previous()
 *
 * Changes current locale to the last used one
 */
function locale_previous() {
	global $last_locale;
	
	return locale_change($last_locale);
}


/**
 *	Original code by Francois PLANQUE - {@link http://fplanque.net/}
 *
 *	locale_extract( string )
 *	
 *	Generates a _global.php from messages.po file
 *	_global.php contains an array; keys are english sentence, values are there translation
 *
 *	@return true / false
 */
function locale_extract($file, $outdir) {
	if ( !file_exists($file) )	
		return false;
		
	if ( !is_file($file) )
		return false;
	
	if ( !is_readable($file) )
		return false;
		
	if ( !is_writable($outdir) )
		return false;
		
	// Get PO file for that locale:
	$lines = file( $file);
	$lines[] = '';	// Adds a blank line at the end in order to ensure complete handling of the file
	$all = 0;
	$fuzzy=0;
	$untranslated=0;
	$translated=0;
	$status='-';
	$matches = array();
	$sources = array();
	$loc_vars = array();
	$ttrans = array();
	foreach ($lines as $line) {
		// echo 'LINE:', $line, '<br />';
		if (trim($line) == '') {
			// Blank line, go back to base status:
			if ($status == 't') {
				// ** End of a translation **:
				if ($msgstr == '') {
					$untranslated++;
					// echo 'untranslated: ', $msgid, '<br />';
				} else {
					$translated++;

					// Inspect where the string is used
					$sources = array_unique( $sources );
					// echo '<p>sources: ', implode( ', ', $sources ), '</p>';

					foreach( $sources as $source ) {
						if ( !isset( $loc_vars[$source]  ) ) $loc_vars[$source] = 1;
						else $loc_vars[$source] ++;
					}

					// Save the string
					// $ttrans[] = "\n\t'".str_replace( "'", "\'", str_replace( '\"', '"', $msgid ))."' => '".str_replace( "'", "\'", str_replace( '\"', '"', $msgstr ))."',";
					// $ttrans[] = "\n\t\"$msgid\" => \"$msgstr\",";
					$ttrans[] = "\n\t'".str_replace( "'", "\'", str_replace( '\"', '"', $msgid ))."' => \"".str_replace( '$', '\$', $msgstr)."\",";

				}
			}

			$status = '-';
			$msgid = '';
			$msgstr = '';
			$sources = array();
		} elseif (($status=='-') && preg_match( '#^msgid "(.*)"#', $line, $matches)) {
			// Encountered an original text
			$status = 'o';
			$msgid = $matches[1];
			// echo 'original: "', $msgid, '"<br />';
			$all++;
		} elseif (($status=='o') && preg_match( '#^msgstr "(.*)"#', $line, $matches)) {
			// Encountered a translated text
			$status = 't';
			$msgstr = $matches[1];
			// echo 'translated: "', $msgstr, '"<br />';
		} elseif (preg_match( '#^"(.*)"#', $line, $matches)) {
			// Encountered a followup line
			if ($status=='o')
				$msgid .= $matches[1];
			elseif ($status=='t')
				$msgstr .= $matches[1];
		} elseif (($status=='-') && preg_match( '@^#:(.*)@', $line, $matches)) {
			// Encountered a source code location comment
			// echo $matches[0],'<br />';
			$sourcefiles = preg_replace( '@\\\\@', '/', $matches[1] );
			// $c = preg_match_all( '@ ../../../([^:]*):@', $sourcefiles, $matches);
			$c = preg_match_all( '@ ../../../([^/:]*)@', $sourcefiles, $matches);
			for( $i = 0; $i < $c; $i++ )
			{
				$sources[] = $matches[1][$i];
			}
			// echo '<br />';
		} elseif (strpos($line,'#, fuzzy') === 0)
			$fuzzy++;
	}
	
	// Writing _global.php file
	$outfile = $outdir . ( preg_match('#[\\\|/]$#', $outdir) ? '_global.php' : '/_global.php' );
	$fp = fopen( $outfile, 'w+' );
	fwrite( $fp, "<?php\n" );
	fwrite( $fp, "/*\n" );
	fwrite( $fp, " * Global lang file\n" );
	fwrite( $fp, " * This file was generated automatically from messages.po\n" );
	fwrite( $fp, " */\n" );
	fwrite( $fp, "\n\$trans['".$locales[$locale]['messages']."'] = array(" );

	// echo '<pre>';
	foreach( $ttrans as $line ) {
		// echo htmlspecialchars( $line );
		fwrite( $fp, $line );
	}

	// echo '</pre>';
	fwrite( $fp, "\n);\n?>" );
	fclose( $fp );
	
	return true;
}

?>
