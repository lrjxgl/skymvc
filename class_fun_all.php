<?php 

/**
 *
 * A "custom" port of CodeIgniter's Security Library to use with Laravel for XSS prevention
 * Below is the licence info from CodeIgniter
 * - Mohit Mamoria, mohit.mamoria@gmail.com
 *
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.2.4 or newer
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the Open Software License version 3.0
 *
 * This source file is subject to the Open Software License (OSL 3.0) that is
 * bundled with this package in the files license.txt / license.rst.  It is
 * also available through the world wide web at this URL:
 * http://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to obtain it
 * through the world wide web, please send an email to
 * licensing@ellislab.com so we can send you a copy immediately.
 *
 * @package		CodeIgniter
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2013, EllisLab, Inc. (http://ellislab.com/)
 * @license		http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

class Xss {

	/**
	 * List of sanitize filename strings
	 *
	 * @var	array
	 */
	public $filename_bad_chars =	array(
		'../', '<!--', '-->', '<', '>',
		"'", '"', '&', '$', '#',
		'{', '}', '[', ']', '=',
		';', '?', '%20', '%22',
		'%3c',		// <
		'%253c',	// <
		'%3e',		// >
		'%0e',		// >
		'%28',		// (
		'%29',		// )
		'%2528',	// (
		'%26',		// &
		'%24',		// $
		'%3f',		// ?
		'%3b',		// ;
		'%3d'		// =
	);

	/**
	 * Character set
	 *
	 * Will be overriden by the constructor.
	 *
	 * @var	string
	 */
	public $charset = 'UTF-8';

	/**
	 * Whitelist of HTML tags that are allowed
	 *
	 * @var array
	 */
	protected $allowed_html_tags = array(
		'<p>',
		'<h1>',
		'<h2>',
		'<h3>',
		'<h4>',
		'<h5>',
		'<h6>',
		'<strong>',
		'<em>',
		'<sub>',
		'<sup>',
		'<small>',
		'<code>',
		'<pre>',
		'<strike>',
		'<table>',
		'<thead>',
		'<tbody>',
		'<tfoot>',
		'<tr>',
		'<th>',
		'<td>',
		'<ul>',
		'<ol>',
		'<li>',
		'<a>',
		'<img>',
		'<iframe>',
		'<br>',
		'<b>',
		'<u>',
		'<i>'
	);

	/**
	 * XSS Hash
	 *
	 * Random Hash for protecting URLs.
	 *
	 * @var	string
	 */
	protected $_xss_hash =	'';


	/**
	*
	* Charset
	*
	* @var string
	*/

	protected $_charset = 'UTF-8';

	/**
	 * List of never allowed strings
	 *
	 * @var	array
	 */
	protected $_never_allowed_str =	array(
		'document.cookie'	=> '[removed]',
		'document.write'	=> '[removed]',
		'.parentNode'		=> '[removed]',
		'.innerHTML'		=> '[removed]',
		'-moz-binding'		=> '[removed]',
		'<!--'				=> '&lt;!--',
		'-->'				=> '--&gt;',
		'<![CDATA['			=> '&lt;![CDATA[',
		'<comment>'			=> '&lt;comment&gt;'
	);

	/**
	 * List of never allowed regex replacements
	 *
	 * @var	array
	 */
	protected $_never_allowed_regex = array(
		'javascript\s*:',
		'(document|(document\.)?window)\.(location|on\w*)',
		'expression\s*(\(|&\#40;)', // CSS and IE
		'vbscript\s*:', // IE, surprise!
		'wscript\s*:', // IE
		'jscript\s*:', // IE
		'vbs\s*:', // IE
		'Redirect\s+30\d',
		"([\"'])?data\s*:[^\\1]*?base64[^\\1]*?,[^\\1]*?\\1?"
	);

	// --------------------------------------------------------------------

	/**
	 * XSS Clean
	 *
	 * Sanitizes data so that Cross Site Scripting Hacks can be
	 * prevented.  This method does a fair amount of work but
	 * it is extremely thorough, designed to prevent even the
	 * most obscure XSS attempts.  Nothing is ever 100% foolproof,
	 * of course, but I haven't been able to get anything passed
	 * the filter.
	 *
	 * Note: Should only be used to deal with data upon submission.
	 *	 It's not something that should be used for general
	 *	 runtime processing.
	 *
	 * @link	http://channel.bitflux.ch/wiki/XSS_Prevention
	 * 		Based in part on some code and ideas from Bitflux.
	 *
	 * @link	http://ha.ckers.org/xss.html
	 * 		To help develop this script I used this great list of
	 *		vulnerabilities along with a few other hacks I've
	 *		harvested from examining vulnerabilities in other programs.
	 *
	 * @param	string|string[]	$str		Input data
	 * @param 	bool		$is_image	Whether the input is an image
	 * @return	string
	 */
	public function clean($str, $extraAllowedTags = array(), $is_image = FALSE)
	{
		// Is the string an array?
		if (is_array($str))
		{
			while (list($key) = each($str))
			{
				$str[$key] = $this->clean($str[$key]);
			}

			return $str;
		}

		// Remove all the tags that aren't in the whitelist
		$str = $this->strip_tags($str, $extraAllowedTags);

		// Remove Invisible Characters
		$str = $this->remove_invisible_characters($str);

		/*
		 * URL Decode
		 *
		 * Just in case stuff like this is submitted:
		 *
		 * <a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a>
		 *
		 * Note: Use rawurldecode() so it does not remove plus signs
		 */
		do
		{
			$str = rawurldecode($str);
		}
		while (preg_match('/%[0-9a-f]{2,}/i', $str));

		/*
		 * Convert character entities to ASCII
		 *
		 * This permits our tests below to work reliably.
		 * We only convert entities that are within tags since
		 * these are the ones that will pose security problems.
		 */
		$str = preg_replace_callback("/[a-z]+=([\'\"]).*?\\1/si", array($this, '_convert_attribute'), $str);
		$str = preg_replace_callback('/<\w+.*/si', array($this, '_decode_entity'), $str);

		// Remove Invisible Characters Again!
		$str = $this->remove_invisible_characters($str);

		/*
		 * Convert all tabs to spaces
		 *
		 * This prevents strings like this: ja	vascript
		 * NOTE: we deal with spaces between characters later.
		 * NOTE: preg_replace was found to be amazingly slow here on
		 * large blocks of data, so we use str_replace.
		 */
		$str = str_replace("\t", ' ', $str);

		// Capture converted string for later comparison
		$converted_string = $str;

		// Remove Strings that are never allowed
		$str = $this->_do_never_allowed($str);

		/*
		 * Makes PHP tags safe
		 *
		 * Note: XML tags are inadvertently replaced too:
		 *
		 * <?xml
		 *
		 * But it doesn't seem to pose a problem.
		 */
		if ($is_image === TRUE)
		{
			// Images have a tendency to have the PHP short opening and
			// closing tags every so often so we skip those and only
			// do the long opening tags.
			$str = preg_replace('/<\?(php)/i', '&lt;?\\1', $str);
		}
		else
		{
			$str = str_replace(array('<?', '?'.'>'), array('&lt;?', '?&gt;'), $str);
		}

		/*
		 * Compact any exploded words
		 *
		 * This corrects words like:  j a v a s c r i p t
		 * These words are compacted back to their correct state.
		 */
		$words = array(
			'javascript', 'expression', 'vbscript', 'jscript', 'wscript',
			'vbs', 'script', 'base64', 'applet', 'alert', 'document',
			'write', 'cookie', 'window', 'confirm', 'prompt'
		);

		foreach ($words as $word)
		{
			$word = implode('\s*', str_split($word)).'\s*';

			// We only want to do this when it is followed by a non-word character
			// That way valid stuff like "dealer to" does not become "dealerto"
			$str = preg_replace_callback('#('.substr($word, 0, -3).')(\W)#is', array($this, '_compact_exploded_words'), $str);
		}

		/*
		 * Remove disallowed Javascript in links or img tags
		 * We used to do some version comparisons and use of stripos for PHP5,
		 * but it is dog slow compared to these simplified non-capturing
		 * preg_match(), especially if the pattern exists in the string
		 *
		 * Note: It was reported that not only space characters, but all in
		 * the following pattern can be parsed as separators between a tag name
		 * and its attributes: [\d\s"\'`;,\/\=\(\x00\x0B\x09\x0C]
		 * ... however, remove_invisible_characters() above already strips the
		 * hex-encoded ones, so we'll skip them below.
		 */
		do
		{
			$original = $str;

			if (preg_match('/<a/i', $str))
			{
				$str = preg_replace_callback('#<a[^a-z0-9>]+([^>]*?)(?:>|$)#si', array($this, '_js_link_removal'), $str);
			}

			if (preg_match('/<img/i', $str))
			{
				$str = preg_replace_callback('#<img[^a-z0-9]+([^>]*?)(?:\s?/?>|$)#si', array($this, '_js_img_removal'), $str);
			}

			if (preg_match('/script|xss/i', $str))
			{
				$str = preg_replace('#</*(?:script|xss).*?>#si', '[removed]', $str);
			}
		}
		while ($original !== $str);

		unset($original);

		// Remove evil attributes such as style, onclick and xmlns
		$str = $this->_remove_evil_attributes($str, $is_image);

		/*
		 * Sanitize naughty HTML elements
		 * (Not doing it, as we have stripped all the tags except those in white list)
		 *
		 * If a tag containing any of the words in the list
		 * below is found, the tag gets converted to entities.
		 *
		 * So this: <blink>
		 * Becomes: &lt;blink&gt;
		 */
		// $naughty = 'alert|prompt|confirm|applet|audio|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|button|select|isindex|layer|link|meta|keygen|object|plaintext|style|script|textarea|title|math|video|svg|xml|xss';	

		// $str = preg_replace_callback('#<(/*\s*)('.$naughty.')([^><]*)([><]*)#is', array($this, '_sanitize_naughty_html'), $str);

		/*
		 * Sanitize naughty scripting elements
		 *
		 * Similar to above, only instead of looking for
		 * tags it looks for PHP and JavaScript commands
		 * that are disallowed. Rather than removing the
		 * code, it simply converts the parenthesis to entities
		 * rendering the code un-executable.
		 *
		 * For example:	eval('some code')
		 * Becomes:	eval&#40;'some code'&#41;
		 */
		$str = preg_replace('#(alert|prompt|confirm|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si',
					'\\1\\2&#40;\\3&#41;',
					$str);

		// Final clean up
		// This adds a bit of extra precaution in case
		// something got through the above filters
		$str = $this->_do_never_allowed($str);

		/*
		 * Images are Handled in a Special Way
		 * - Essentially, we want to know that after all of the character
		 * conversion is done whether any unwanted, likely XSS, code was found.
		 * If not, we return TRUE, as the image is clean.
		 * However, if the string post-conversion does not matched the
		 * string post-removal of XSS, then it fails, as there was unwanted XSS
		 * code found and removed/changed during processing.
		 */
		if ($is_image === TRUE)
		{
			return ($str === $converted_string);
		}

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Strip Tags
	 *
	 * Strips all the tags that aren't allowed
	 *
	 * @see		Xss::$allowed_html_tags
	 * @return	string
	 */
	protected function strip_tags($str, $extraAllowedTags = array())
	{
		return strip_tags($str, implode('', array_merge($this->allowed_html_tags, $extraAllowedTags)));
	}

	// --------------------------------------------------------------------

	/**
	 * XSS Hash
	 *
	 * Generates the XSS hash if needed and returns it.
	 *
	 * @see		Xss::$_xss_hash
	 * @return	string	XSS hash
	 */
	public function xss_hash()
	{
		if ($this->_xss_hash === '')
		{
			$this->_xss_hash = md5(uniqid(mt_rand()));
		}

		return $this->_xss_hash;
	}

	// --------------------------------------------------------------------

	/**
	 * HTML Entities Decode
	 *
	 * A replacement for html_entity_decode()
	 *
	 * The reason we are not using html_entity_decode() by itself is because
	 * while it is not technically correct to leave out the semicolon
	 * at the end of an entity most browsers will still interpret the entity
	 * correctly. html_entity_decode() does not convert entities without
	 * semicolons, so we are left with our own little solution here. Bummer.
	 *
	 * @link	http://php.net/html-entity-decode
	 *
	 * @param	string	$str		Input
	 * @param	string	$charset	Character set
	 * @return	string
	 */
	public function entity_decode($str, $charset = NULL)
	{
		if (strpos($str, '&') === FALSE)
		{
			return $str;
		}

		static $_entities;

		isset($charset) OR $charset = $this->charset;
		$flag = phpversion() >= '5.4'
			? ENT_COMPAT | ENT_HTML5
			: ENT_COMPAT;

		do
		{
			$str_compare = $str;

			// Decode standard entities, avoiding false positives
			if ($c = preg_match_all('/&[a-z]{2,}(?![a-z;])/i', $str, $matches))
			{
				if ( ! isset($_entities))
				{
					$_entities = array_map('strtolower', get_html_translation_table(HTML_ENTITIES, $flag, $charset));

					// If we're not on PHP 5.4+, add the possibly dangerous HTML 5
					// entities to the array manually
					if ($flag === ENT_COMPAT)
					{
						$_entities[':'] = '&colon;';
						$_entities['('] = '&lpar;';
						$_entities[')'] = '&rpar';
						$_entities["\n"] = '&newline;';
						$_entities["\t"] = '&tab;';
					}
				}

				$replace = array();
				$matches = array_unique(array_map('strtolower', $matches[0]));
				for ($i = 0; $i < $c; $i++)
				{
					if (($char = array_search($matches[$i].';', $_entities, TRUE)) !== FALSE)
					{
						$replace[$matches[$i]] = $char;
					}
				}

				$str = str_ireplace(array_keys($replace), array_values($replace), $str);
			}

			// Decode numeric & UTF16 two byte entities
			// $str = html_entity_decode(
			// 	preg_replace('/(&#(?:x0*[0-9a-f]{2,5}(?![0-9a-f;]))|(?:0*\d{2,4}(?![0-9;])))/iS', '$1;', $str),
			// 	$flag,
			// 	$charset
			// );
		}
		while ($str_compare !== $str);
		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Sanitize Filename
	 *
	 * @param	string	$str		Input file name
	 * @param 	bool	$relative_path	Whether to preserve paths
	 * @return	string
	 */
	public function sanitize_filename($str, $relative_path = FALSE)
	{
		$bad = $this->filename_bad_chars;

		if ( ! $relative_path)
		{
			$bad[] = './';
			$bad[] = '/';
		}

		$str = $this->remove_invisible_characters($str, FALSE);

		do
		{
			$old = $str;
			$str = str_replace($bad, '', $str);
		}
		while ($old !== $str);

		return stripslashes($str);
	}

	// ----------------------------------------------------------------

	/**
	 * Strip Image Tags
	 *
	 * @param	string	$str
	 * @return	string
	 */
	public function strip_image_tags($str)
	{
		return preg_replace(array('#<img[\s/]+.*?src\s*=\s*["\'](.+?)["\'].*?\>#', '#<img[\s/]+.*?src\s*=\s*(.+?).*?\>#'), '\\1', $str);
	}

	// ----------------------------------------------------------------

	/**
	 * Compact Exploded Words
	 *
	 * Callback method for clean() to remove whitespace from
	 * things like 'j a v a s c r i p t'.
	 *
	 * @used-by	Xss::clean()
	 * @param	array	$matches
	 * @return	string
	 */
	protected function _compact_exploded_words($matches)
	{
		return preg_replace('/\s+/s', '', $matches[1]).$matches[2];
	}

	// --------------------------------------------------------------------

	/**
	 * Remove Evil HTML Attributes (like event handlers and style)
	 *
	 * It removes the evil attribute and either:
	 *
	 *  - Everything up until a space. For example, everything between the pipes:
	 *
	 *	<code>
	 *		<a |style=document.write('hello');alert('world');| class=link>
	 *	</code>
	 *
	 *  - Everything inside the quotes. For example, everything between the pipes:
	 *
	 *	<code>
	 *		<a |style="document.write('hello'); alert('world');"| class="link">
	 *	</code>
	 *
	 * @param	string	$str		The string to check
	 * @param	bool	$is_image	Whether the input is an image
	 * @return	string	The string with the evil attributes removed
	 */
	protected function _remove_evil_attributes($str, $is_image)
	{
		$evil_attributes = array('on\w*', 'xmlns', 'formaction', 'form', 'action', 'xlink:href');

		if ($is_image === TRUE)
		{
			/*
			 * Adobe Photoshop puts XML metadata into JFIF images,
			 * including namespacing, so we have to allow this for images.
			 */
			unset($evil_attributes[array_search('xmlns', $evil_attributes)]);
		}

		do {
			$count = 0;
			$attribs = array();

			// find occurrences of illegal attribute strings with quotes (042 and 047 are octal quotes)
			preg_match_all('/(?<!\w)('.implode('|', $evil_attributes).')\s*=\s*(\042|\047)([^\\2]*?)(\\2)/is', $str, $matches, PREG_SET_ORDER);

			foreach ($matches as $attr)
			{
				$attribs[] = preg_quote($attr[0], '/');
			}

			// find occurrences of illegal attribute strings without quotes
			preg_match_all('/(?<!\w)('.implode('|', $evil_attributes).')\s*=\s*([^\s>]*)/is', $str, $matches, PREG_SET_ORDER);

			foreach ($matches as $attr)
			{
				$attribs[] = preg_quote($attr[0], '/');
			}

			// replace illegal attribute strings that are inside an html tag
			if (count($attribs) > 0)
			{
				$str = preg_replace('/(<?)(\/?[^><]+?)([^A-Za-z<>\-])(.*?)('.implode('|', $attribs).')(.*?)([\s><]?)([><]*)/i', '$1$2 $4$6$7$8', $str, -1, $count);
			}
		}
		while ($count);

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Sanitize Naughty HTML
	 *
	 * Callback method for clean() to remove naughty HTML elements.
	 *
	 * @used-by	Xss::clean()
	 * @param	array	$matches
	 * @return	string
	 */
	protected function _sanitize_naughty_html($matches)
	{
		return '&lt;'.$matches[1].$matches[2].$matches[3] // encode opening brace
			// encode captured opening or closing brace to prevent recursive vectors:
			.str_replace(array('>', '<'), array('&gt;', '&lt;'), $matches[4]);
	}

	// --------------------------------------------------------------------

	/**
	 * JS Link Removal
	 *
	 * Callback method for clean() to sanitize links.
	 *
	 * This limits the PCRE backtracks, making it more performance friendly
	 * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
	 * PHP 5.2+ on link-heavy strings.
	 *
	 * @used-by	Xss::clean()
	 * @param	array	$match
	 * @return	string
	 */
	protected function _js_link_removal($match)
	{
		return str_replace($match[1],
					preg_replace('#href=.*?(?:(?:alert|prompt|confirm)(?:\(|&\#40;)|javascript:|livescript:|mocha:|charset=|window\.|document\.|\.cookie|<script|<xss|data\s*:)#si',
							'',
							$this->_filter_attributes(str_replace(array('<', '>'), '', $match[1]))
					),
					$match[0]);
	}

	// --------------------------------------------------------------------

	/**
	 * JS Image Removal
	 *
	 * Callback method for clean() to sanitize image tags.
	 *
	 * This limits the PCRE backtracks, making it more performance friendly
	 * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
	 * PHP 5.2+ on image tag heavy strings.
	 *
	 * @used-by	Xss::clean()
	 * @param	array	$match
	 * @return	string
	 */
	protected function _js_img_removal($match)
	{
		return str_replace($match[1],
					preg_replace('#src=.*?(?:(?:alert|prompt|confirm)(?:\(|&\#40;)|javascript:|livescript:|mocha:|charset=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si',
							'',
							$this->_filter_attributes(str_replace(array('<', '>'), '', $match[1]))
					),
					$match[0]);
	}

	// --------------------------------------------------------------------

	/**
	 * Attribute Conversion
	 *
	 * @used-by	Xss::clean()
	 * @param	array	$match
	 * @return	string
	 */
	protected function _convert_attribute($match)
	{
		return str_replace(array('>', '<', '\\'), array('&gt;', '&lt;', '\\\\'), $match[0]);
	}

	// --------------------------------------------------------------------

	/**
	 * Filter Attributes
	 *
	 * Filters tag attributes for consistency and safety.
	 *
	 * @used-by	Xss::_js_img_removal()
	 * @used-by	Xss::_js_link_removal()
	 * @param	string	$str
	 * @return	string
	 */
	protected function _filter_attributes($str)
	{
		$out = '';
		if (preg_match_all('#\s*[a-z\-]+\s*=\s*(\042|\047)([^\\1]*?)\\1#is', $str, $matches))
		{
			foreach ($matches[0] as $match)
			{
				$out .= preg_replace('#/\*.*?\*/#s', '', $match);
			}
		}

		return $out;
	}

	// --------------------------------------------------------------------

	/**
	 * HTML Entity Decode Callback
	 *
	 * @used-by	Xss::clean()
	 * @param	array	$match
	 * @return	string
	 */
	protected function _decode_entity($match)
	{
		// Protect GET variables in URLs
		// 901119URL5918AMP18930PROTECT8198
		$match = preg_replace('|\&([a-z\_0-9\-]+)\=([a-z\_0-9\-/]+)|i', $this->xss_hash().'\\1=\\2', $match[0]);

		// Decode, then un-protect URL GET vars
		return str_replace(
			$this->xss_hash(),
			'&',
			$this->entity_decode($match, $this->charset)
		);
	}

	// ----------------------------------------------------------------------

	/**
	 * Do Never Allowed
	 *
	 * @used-by	Xss::clean()
	 * @param 	string
	 * @return 	string
	 */
	protected function _do_never_allowed($str)
	{
		$str = str_replace(array_keys($this->_never_allowed_str), $this->_never_allowed_str, $str);

		foreach ($this->_never_allowed_regex as $regex)
		{
			$str = preg_replace('#'.$regex.'#is', '[removed]', $str);
		}

		return $str;
	}

	/**
	 * Remove Invisible Characters
	 *
	 * This prevents sandwiching null characters
	 * between ascii characters, like Java\0script.
	 *
	 * @param	string
	 * @param	bool
	 * @return	string
	 */
	function remove_invisible_characters($str, $url_encoded = TRUE)
	{
		$non_displayables = array();

		// every control character except newline (dec 10),
		// carriage return (dec 13) and horizontal tab (dec 09)
		if ($url_encoded)
		{
			$non_displayables[] = '/%0[0-8bcef]/';	// url encoded 00-08, 11, 12, 14, 15
			$non_displayables[] = '/%1[0-9a-f]/';	// url encoded 16-31
		}

		$non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127

		do
		{
			$str = preg_replace($non_displayables, '', $str, -1, $count);
		}
		while ($count);

		return $str;
	}	


}
?><?php
/**
*函数库核心文件
*/
/*全局变量定义*/
function G($var){
	return $GLOBALS[$var];
}
 

/*运营时间*/
function running_time(){
	return round(microtime(true)-B_TIME,4);
}
/*内存占用*/
function memory_use(){
	return intval(memory_get_usage()/1024);
}
/*输出测试数据*/
function skyTest(){
	 
	echo "服务器：".$_SERVER['SERVER_SOFTWARE']."<br>";
	 
	echo "php模式：".php_sapi_name()." <br>";
	echo "SQL执行时间：".G('query_time')."<br>";
	echo "程序运营时间：".running_time()."<br>";
	echo "程序内存使用：PID.".getmypid().",占用内存：".memory_use()."KB<br>";
	echo "SQL执行次数:".G('skysqlnum')."<br>";	
	echo "SQl执行语句：".G('skysqlrun')."<br>";
}
/**页面测试记录时间**/
function skymvc_test_page_auto(){
	if(isset($_GET['skymvc_test_page_auto'])){
		$t=microtime(true)-B_TIME;
		skyLog("test_page.txt","当前页面花费时间：".$t."秒");
	}
}

/**系统日志**/
function skyLog($file,$content){
	$file=ROOT_PATH."temp/log/".$file;
	umkdir(ROOT_PATH."temp/log/");
	if(file_exists($file)){
		if(filesize($file)>1024*1024*300){
			rename($file,ROOT_PATH."temp/log/".str_replace(".",date("Ymdhis").".",basename($file)));
		}
		clearstatcache() ;
	}
	
	$fp=fopen($file,"a+");
	
	fwrite($fp,"\r\n---".date("Y-m-d H:i:s")."--".$_SERVER['REQUEST_URI']."--\r\n".$content."\r\n");
	fclose($fp);	
}


/*
构造表格前缀 函数
*/
function table($tb)
{
	return TABLE_PRE.$tb;
}

/*
*字符串处理函数库
*/
function nrexplode($str){
	$str=str_replace("\r\n","\n",$str);
	return explode("\n",$str); 
}

function texplode($str){
	$t_d=explode(" ",$str);
	if($t_d){
		foreach($t_d as $v){
			if(!empty($v)){
				$data[]=$v;
			}
		}
	}
	return $data;
}

//获取小数点
function numdot($str){
	$s=explode(".",$str);
	if(isset($s[1])){
		return $s[1];
	}else{
		return $s[0];
	}
}
function sql($value){
	return newaddslashes($value);
}

function ustrip_tags($str){	
	$arr=array("&amp;","&nbsp;","&ldquo;","&rdquo;","&hellip;");
	$str=str_replace($arr,"",$str);
	$str=strip_tags($str);
	return $str;
}
function get($k,$format="",$len=0){
        if(isset($_GET[$k])){
				$_GET[$k]=get_format($_GET[$k],$format,$len);
				         
                return $_GET[$k];                
        }elseif($format=='i'){
			$_GET[$k]=0;
			return 0;
		}
}
function post($k,$format="",$len=0){
        if(isset($_POST[$k])){         
                $_POST[$k]=get_format($_POST[$k],$format,$len);
				return $_POST[$k];                
        }elseif($format=='i'){
			$_POST[$k]=0;
			return 0;
		}
}

function get_post($k,$format="",$len=0){
        if(isset($_GET[$k])){         
                return get_format($_GET[$k],$format,$len);                
        }
        if(isset($_POST[$k])){         
                return get_format($_POST[$k],$format,$len);                
        }
		if($format=='i'){
			return $_GET[$k]=0;
		}
}

function get_format($str,$format='',$len=0){
        if(!$format) return $str;

        if(is_array($str)){
                foreach($str as $k=>$v){
						$str[$k]=get_format($v,$format,$len);
                }
                return $str;
        }else{
                return str_format($str,$format,$len);
        }
         
}

function str_format($str,$format='',$len=0){
        if(!$format) return $str;
        $str=trim($str);
        $arr=str_split($format);
        foreach($arr as $html){
                switch($html){
					case "i":
							if($str>2147483647){
								$str=round($str,$len);
							}else{
								$str=intval($str);
							}
							break;
					case "f":
								$str=floatval($str);
							break;
					case "h":
							$str=htmlspecialchars($str);
							break;
					case "s":
							$str=strip_tags($str);
							break;
					case "x":
							$str=nRemoveXSS($str);
							break;
					case "r":
							$str=round($str,$len);
							break;
					case "a"://返回全部
							break;
                }
        }
        return $str;
}

function get_post_Html($arr){
	if(!empty($arr)){
		return is_array($arr) ? array_map('get_post_Html', $arr) : htmlspecialchars(trim($arr));
	}
}

function arrRemoveXss($arr){
	if(!empty($arr)){
		return is_array($arr) ? array_map('arrRemoveXss', $arr) : nRemoveXSS(trim($arr));
	}
}

function nRemoveXSS($str){
	if(!is_object($GLOBALS['xssClass'])){
		$GLOBALS['xssClass']=new Xss(); 
	}
	return $GLOBALS['xssClass']->clean($str);
}


/**
* 将数组元素格式化成类似 '1','2','3' 的字符串
* @return STRING 字串 否则为 NULL
*/
function _implode($array) {
	if(!empty($array)) {
		return "'".implode("','", is_array($array) ? $array : array($array))."'";
	} else {
		return '';
	}
}


/**
* 递归方式的对变量中的特殊字符进行转义
*
* @access  public
* @param   mix     $value
*
* @return  mix
*/
function addslashes_deep($value)
{
	if (empty($value))
	{
		return $value;
	}
	else
	{
		return is_array($value) ? array_map('addslashes_deep', $value) : addslashes(trim($value));
	}
}


function stripslashes_deep($value)
{
	if (empty($value))
	{
		return $value;
	}
	else
	{
		return is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes(trim($value));
	}
}
function newaddslashes($value){		 
		if(empty($value)){
			return $value;
		}else{
			if(is_array($value)){
				 return array_map('newaddslashes', $value);
			}else{				
				$value=stripslashes($value);
				$value=str_replace("\'","'",$value);  
				$value=str_replace('\"','"',$value);
				$value=addslashes(trim($value));				
				return $value;	
			}
		}	 
}



function ev($str){
	eval($str);
}

//截取字符串函数
function cutstr($string, $length, $dot = ' ...') {
	if(strlen($string) <= $length) {
		return $string;
	}
	$string = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array('&', '"', '<', '>'), $string);
	$strcut = '';
	$n = $tn = $noc = 0;
	while($n < strlen($string)) {	
		$t = ord($string[$n]);
		if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
			$tn = 1; $n++; $noc++;
		} elseif(194 <= $t && $t <= 223) {
			$tn = 2; $n += 2; $noc += 2;
		} elseif(224 <= $t && $t < 239) {
			$tn = 3; $n += 3; $noc += 2;
		} elseif(240 <= $t && $t <= 247) {
			$tn = 4; $n += 4; $noc += 2;
		} elseif(248 <= $t && $t <= 251) {
			$tn = 5; $n += 5; $noc += 2;
		} elseif($t == 252 || $t == 253) {
			$tn = 6; $n += 6; $noc += 2;
		} else {
			$n++;
		}	
		if($noc >= $length) {
			break;
		}	
	}
	if($noc > $length) {
		$n -= $tn;
	}
	$strcut = substr($string, 0, $n);		
	$strcut = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $strcut);
	return $strcut.$dot;
}


/**
@字符转换函数
*/
function iconvstr($from,$to,$str)
{
	if(empty($str))
	{
		return false;
	}
	if(is_array($str))
	{
		foreach($str as $key=>$val)
		{
		$string[$key]=iconvstr($from,$to,$val);
		}		
	}else
	{
		$string=iconv($from,$to,$str);
	}
	
	return $string;
}

//移除链接
function removelink($c)
{
	return preg_replace("/<a.*>(.*)<\/a>/iU","\\1",$c);
}

/*加密函数*/
function umd5($str)
{
	return md5(md5($str));	
}

function array_lastnum($arr){
	if(!is_array($arr)) return intval($arr);
	if($arr){
		$len=count($arr)-1;
		for($i=$len;$i>=0;$i--){
			if($arr[$i]>0){			 
				return $arr[$i];
			}
		}
	}else{
		return 0;
	}
}


function getImgs($content,$i=9999){
	preg_match_all("/<img[^>]*src[^=]*=[^\"']*[\"'](.*)[\"'][^>]*>/iUs",$content,$imgs);
	if($i!=9999){
		return $imgs[1][$i];
	}else{
		return $imgs[1];
	}
}

function replaceImgs($content){
	preg_match_all("/<img[^>]*src[^=]*=[^\"']*[\"'](.*)[\"'][^>]*>/iUs",$content,$imgs);
	if(is_array($imgs[1])){
		foreach($imgs[1] as $img){
			$content=str_replace($img,images_site($img),$content);
		}
	}
	return $content;
}

/*保持中文格式 转化*/
function zh_json_encode($data){
		$data=array_urlencode($data);
		$data=json_encode($data);
		$data=array_urldecode($data);
		return $data;
}

function array_urlencode($data){
		if(is_array($data)){
			foreach($data as $k=>$v){
				$data[$k]=array_urlencode($v);
			}
			return $data;
		}else{
			return urlencode($data);
		}
}

function array_urldecode($data){
		if(is_array($data)){
			foreach($data as $k=>$v){
				$data[$k]=array_urldecode($v);
			}
			return $data;
		}else{
			return urldecode($data);
		}
}
/**
*@json 处理jsonp和json
**/
function sky_json_encode($data){
	if(get_post('jsonp')){
		return get_post('callback','h')."(".json_encode($data).")";
	}else{
		return json_encode($data);
	}
}

function sky_json_decode($data){
	return json_decode($data,true);
}
/**数组 字符串转换***/
function arr2str($arr){
	return urlencode(base64_encode(json_encode($arr)));
}
function str2arr($str){
	return json_decode(base64_decode(urldecode($str)));
}
/**
*@简易字符串转js
*/
function strToJs($content){
	$content=str_replace(array("\r","\n"),"",$content);
	$content=str_replace("'","\'",$content);
	return "document.write('$content');";
}
 

/**
* 获得用户的真实IP地址
*/
function ip(){
	return realip();
}
function realip() {
	$onlineip = '';
	if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
		$onlineip = getenv('HTTP_CLIENT_IP');
	} elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
		$onlineip = getenv('HTTP_X_FORWARDED_FOR');
	} elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
		$onlineip = getenv('REMOTE_ADDR');
	} elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
		$onlineip = $_SERVER['REMOTE_ADDR'];
	}
	return $onlineip;
}

/**
*
{"code":0,"data":{"ip":"210.75.225.254","country":"\u4e2d\u56fd","area":"\u534e\u5317",
"region":"\u5317\u4eac\u5e02","city":"\u5317\u4eac\u5e02","county":"","isp":"\u7535\u4fe1",
"country_id":"86","area_id":"100000","region_id":"110000","city_id":"110000",
"county_id":"-1","isp_id":"100017"}}
其中code的值的含义为，0：成功，1：失败。
*
*/
function ipCity($ip,$type=0){
	if($ip=="127.0.0.1") return false;
	$key="ip".$type."_".str_replace(".","_",$ip);
	if($data=cache()->get($key)) return $data;
	$c=file_get_contents("http://ip.taobao.com/service/getIpInfo.php?ip=".$ip);
	$d=json_decode($c,true);
	if($d['code']==0 && !empty($d['data']['city_id'])){
		if($type==0){
			cache()->set($key,$d['data'],3600000);
			return $d['data'];
		}else{
			$data=$d['data']['region'].$d['data']['city'].$d['data']['county'];
			cache()->set($key,$data,3600000);
			return $data;
		}
	}else{
		return false;
	}
}


/*获取远程内容*/
function curl_get_contents($url,$timeout=30,$referer="http://www.qq.com"){
	 $ch = curl_init();
	 curl_setopt($ch, CURLOPT_URL, $url);
	 curl_setopt($ch, CURLOPT_HEADER, 0);
	 curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	 curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	 curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
	 curl_setopt($ch, CURLOPT_REFERER,$referer); //伪造来路页面 防止被禁止
	 $content= curl_exec($ch);
	 curl_close($ch);
	 return $content;
} 

/*远程post*/
function curl_post($url, $data)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
    curl_setopt($ch, CURLOPT_POST, TRUE); 
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data); 
    curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    $ret = curl_exec($ch);

    curl_close($ch);
    return $ret;
}

function curl_post_json($url, $json)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
    curl_setopt($ch, CURLOPT_POST, TRUE); 
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json); 
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen($json))
);
    $ret = curl_exec($ch);

    curl_close($ch);
    return $ret;
}

/**
*判断是否来自搜索引擎
*/
function is_robot() { 
	$kw_spiders = 'Bot|Crawl|Spider|spider|Slurp|slurp|sohu-search|Lycos|lycos|robozilla|baidu|Baidu|google|Google|soso|Soso|YodaoBot|Sogou|sogou|Adsbot|Mediapartners|Msn|msn|scooter|FAST|ia_archiver|Ia_archiver'; 
	if(preg_match("/($kw_spiders)/i", $_SERVER['HTTP_USER_AGENT'],$matches)) 
	{ 
		return true;
	} else { 
		return false;
	}
}

/**
* 验证输入的邮件地址是否合法
*
* @access  public
* @param   string      $email      需要验证的邮件地址
*
* @return bool
*/
function is_email($user_email)
{
	$user_email=trim($user_email);
	$chars = "/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,6}\$/i";
	if (strpos($user_email, '@') !== false && strpos($user_email, '.') !== false)
	{
		if (preg_match($chars, $user_email))
		{
			return true;
		}
		else
		{
			return false;
		}
	}else
	{
		return false;
	}
}


/*判断是否手机号码*/
function is_tel($tel){
	if(preg_match("/1[34587]{1}\d{9}$/",$tel)){
		return true;
	}
	return false;
}


/*是否来自手机*/
function is_mobile(){ 
 
    // returns true if one of the specified mobile browsers is detected 
 
    $regex_match="/(nokia|iphone|android|motorola|^mot\-|softbank|foma|docomo|kddi|up\.browser|up\.link|"; 
    $regex_match.="htc|dopod|blazer|netfront|helio|hosin|huawei|novarra|CoolPad|webos|techfaith|palmsource|";
    $regex_match.="blackberry|alcatel|amoi|ktouch|nexian|samsung|^sam\-|s[cg]h|^lge|ericsson|philips|sagem|wellcom|bunjalloo|maui|";     
    $regex_match.="symbian|smartphone|midp|wap|phone|windows ce|iemobile|^spice|^bird|^zte\-|longcos|pantech|gionee|^sie\-|portalmmm|"; 
    $regex_match.="jig\s browser|hiptop|^ucweb|^benq|haier|^lct|opera\s*mobi|opera\*mini|320x320|240x320|176x220"; 
    $regex_match.=")/i";
	         
    return isset($_SERVER['HTTP_X_WAP_PROFILE']) or isset($_SERVER['HTTP_PROFILE']) or preg_match($regex_match, strtolower($_SERVER['HTTP_USER_AGENT'])); 
}
/****加解密*****/
if(!defined("JIAMI_MIYAO")){
	define("JIAMI_MIYAO","我要保密");
}
/*加密*/
function jiami($str,$miyao=''){
	$miyao=$miyao?$miyao:JIAMI_MIYAO;
	$mlen=strlen($miyao);
	$code=md5(md5($miyao));
	$step=array(
		1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17
	);
	$clen=strlen(base64_encode($miyao));
	$str=base64_encode(urlencode($str));
	 	  
	$slen=strlen($str);
	$nstr="";	
	for($i=0;$i<$slen;$i++){
		$nstr.=$str{$i};
		if(in_array($i,$step)){		
			$nstr.=$code{$i};
		} 
	}
	$nstr=$mlen.$nstr;
	 
	return $nstr;
}
/******解密******/
function jiemi($str,$miyao=''){
	$miyao=$miyao?$miyao:JIAMI_MIYAO;
	$mlen=strlen($miyao);
	//判断秘钥长度
	$mmi=substr($str,0,strlen($mlen));
	if($mmi!=$mlen){
		return false;
	}
	$str=substr($str,strlen($mlen));
 
	$code=md5(md5($miyao));
	$step=array(
		1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17
	);
	$bstep=array();
	$si=1;
	foreach($step as $s){
		$bstep[]=$s+$si;
		$si++;
	} 
	$clen=strlen(base64_encode($miyao));
	$slen=strlen($str);
	$nstr="";
 
	$ii=1;
	for($i=0;$i<$slen;$i++){
		if(in_array($i,$bstep)){
			 
			if($str{$i} != $code{$i-$ii}){
				return false;
			}
			 $ii++;
			continue;
		}else{
			$nstr.=$str{$i};
		}
	}
	$nstr=urldecode(base64_decode($nstr));
	
	return $nstr;
} 
/***获取一及域名***/
 function getBaseDomain($host){
		$com=array(
			".com.cn",
			".net.cn",
			".org.cn",
			".gov.cn",
			".com",
			".net",
			".cn",		
			".org",	
			".me",
			".co",
			".tv",
			".cc"	
		);
		$arr=$farr=explode(".",$host);
		$len=count($farr);
		//获取最后两个
		if($len>2){
			$dm2=".".$arr[$len-2].".".$arr[$len-1];
			$key=array_search($dm2,$com); 
			if($key!==false){
				 $cm=$dm2;
				
			}else{
				$cm=".".$arr[$len-1];
			}
			 
		}else{
			$cm=".".$arr[$len-1];
		}
		
		$s=str_replace($cm,"",$host);
		$arr=explode(".",$s);
		$s=array_pop($arr);
		return $s.$cm;
	}

/*剩余时间*/
function lefttime($endtime,$format="还剩#天#小时#分#秒",$ec=0){
	$t=$endtime-time();
	$a=explode("#",$format);
	if($t<=0){
		 $str="已结束"; 
	}elseif($t<60){
		$str= $a[0].$t.$a[4];
	}elseif($t<3600){
		$str= $a[0].intval($t/60).$a[3].($t-intval($t/60)*60).$a[4];
	}elseif($t<86400){//小时
		$str= $a[0].intval($t/3600).$a[2].($t-intval($t/3600)*3600).$a[3];
	}else{
		$str=$a[0].intval($t/86400).$a[1].intval(($t-intval($t/86400)*86400)/3600).$a[2];
	}
	if($ec){
		echo $str;
	}else{
		return $str;
	}
}

//格式化时间 多久前的
function timeago($dateline)
{
	$t=time()-intval($dateline);
	if($t<60)
	{
		return $t."秒前";
	}elseif($t<3600)
	{
		return ceil($t/60)."分前";
	}elseif($t<7200)
	{
		return "1小时".ceil(($t-3600)/60)."分前";
	}elseif($t<86400)//一天
	{
		return ceil($t/3600)."小时前";
	}elseif($t<604800)//一周
	{
		return ceil($t/86400)."天前";
	}else
	{
		return date("Y年m月d日",$dateline);
	}
}


/*计算开业时间函数*/
function opentype($starthour,$startminute,$endhour,$endminute)
{
	$opentype='doing';
	$h=intval(date("H"));
	$m=intval(date("i"));
	if($starthour>$endhour)
	{
		if(($endhour<$h && $starthour>$h) or (($endhour==$h && $endminute<$m) && ($starthour==$h && $startminute>$m)))
		{
			if(($starthour-$h)>(($starthour-$endhour)/2))
			{
				$opentype='done';
			}else
			{
				
				$opentype='will';
			}
		}elseif($endhour)
		{
			$opentype='doing';
		}
		
	}else
	{
		if($h<$starthour or ($h==$starthour && $m<$startminute))
		{
			$opentype='will';//未开时
		}elseif($h>$endhour or($h==$endhour && $m>$endminute))
		{
			$opentype='done';//一结束
		}else
		{
			$opentype='doing';
		}
	}
	return $opentype;
}


//星期几
function getweek($w="")
{
	if(date("N"))
	{
		return $w.date("N");
	}
	if(date("w")==0)
	{
		return $w."7";
	}else
	{
		return $w.intval(date("w"));
	}
}

function week_list($week=0){
	$data=array(
		'week1'=>'星期一',
		'week2'=>'星期二',
		'week3'=>'星期三',
		'week4'=>'星期四',
		'week5'=>'星期五',
		'week6'=>'星期六',
		'week7'=>'星期日',
	);
	if($week){
		return $data[$week];
	}else{
		return $data;
	}
}
 function echoweek($time){
	 $w=date("w",$time);
	 if($w==0) $w=7;
	 return week_list("week".$w);
	  
 }

?><?php
function showError($sql='')
{
     
	$array=debug_backtrace();
	unset($array[0]);
	echo $str='<style>
		.debug-list{background-color:rgb(240, 226, 126); padding:20px 40px; }
		.debug-list .item{margin-bottom:10px; display:block; border-bottom:1px solid #ccc; line-height:25px; padding:0px 5px;}
	</style>';
 	if($sql){
		$html="<div class='debug-list'><h1  style='color:red'>SQL错误提示</h1><div class='item'>系统：PHP " . PHP_VERSION . " (" . PHP_OS . ")</div><div class='item'>".$sql."</div>\n"; 
	}else{
		$html="<div class='debug-list'><h1  style='color:red'>错误提示</h1><div class='item'>系统：PHP " . PHP_VERSION . " (" . PHP_OS . ")</div>\n";
	}
	foreach($array as $row)
    {
       $html .="<div class='item'>".$row['file'].':'.$row['line'].'行,调用方法:'.$row['function']." </div>";
    }
	$html.='<div class="item">请联系管理员</div></div>';
	$html=str_replace(TABLEPRE,"",$html);
	 echo $html;
    /* Don't execute PHP internal error handler */
   return false;
}
function my_error_handler($errno=NULL, $errstr=NULL, $errfile=NULL, $errline=NULL ){
	if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting
        return;
    }
	
	echo $str='<style>
		.debug-list{background-color:rgb(240, 226, 126); padding:20px 40px; }
		.debug-list .item{margin-bottom:10px; display:block; border-bottom:1px solid #ccc; line-height:25px; padding:0px 5px;}
	</style>';
	
	$html="<div class='debug-list'><h1  style='color:red'>错误提示</h1><div class='item'>系统：PHP " . PHP_VERSION . " (" . PHP_OS . ")</div>\n";
    skyLog("error.txt","<div class='item'>错误类型：WARNING<br> 内容：$errstr  错误位置：$errfile 行：$errline </div>");
    switch ($errno) {
     
	
    case E_USER_WARNING:
       $html.="<div class='item'>错误类型：USER_WARNING<br> 内容：$errstr  错误位置：$errfile 行：$errline </div>\n";
        break;
	case E_USER_NOTICE:
        $html.="<div class='item'>错误类型：USER_NOTICE<br> 内容：$errstr  错误位置：$errfile 行：$errline </div>\n";
        break;
		
	case E_WARNING:
       $html.="<div class='item'>错误类型：WARNING<br> 内容：$errstr  错误位置：$errfile 行：$errline </div>\n";
        break;

   case E_NOTICE:
       $html.="<div class='item'>错误类型：NOTICE <br>内容：$errstr  错误位置：$errfile 行：$errline </div>\n";
        break; 

    default:
        $html.="<div class='item'>错误类型：未知<br> 内容：$errstr  错误位置：$errfile 行：$errline </div>\n";
        break;
    }
 
	$array=debug_backtrace();
	unset($array[0]);
 
 
	
	foreach($array as $row)
    {
       $html .="<div class='item'>".$row['file'].':'.$row['line'].'行,调用方法:'.$row['function']." </div>";
    }
	$html.='<div class="item">请联系管理员</div></div>';
	 echo $html;
	 exit();
}
set_error_handler("my_error_handler");

?><?php
if(!defined("ROOT_PATH")){
	define("ROOT_PATH",dirname(str_replace("\\", "/", dirname(__FILE__)))."/");
}

function getMime($url=""){
	$isremote=false;
	if(!file_exists($url)){
		$d=get_headers($url);
		if(empty($d)) return false;		
		if(!$d || preg_match("/200/i",$d[0])==false){
			if(preg_match("/Location:/i",$d[2])!=false){
				$url=trim(str_replace("Location:","",$d[2]));
			}else{
				return false;
			}
		}
		$isremote=true;		
	}
	if(function_exists("finfo_open") && !$isremote){
		$finfo    = finfo_open(FILEINFO_MIME);
		$mimetype = finfo_file($finfo, $url);
		finfo_close($finfo);
		return $mimetype;
	}
	
	if(function_exists("mime_content_type") && !$isremote){
		return mime_content_type($url);
	}
	
	$fh = @fopen($url, 'rb');
	$bin = fread($fh, 2); //读2字节
	fclose($fh);
	$data = unpack('C2chr', $bin);
	$strInfo = @unpack("C2chars", $bin);
	$typeCode = intval($strInfo['chars1'].$strInfo['chars2']);
	$fileType = '';
	switch ($typeCode) {
		case 7784: $fileType = 'audio/midi'; break;//midi
		case 8297: $fileType = 'application/x-rar-compressed'; break;//rar
		case 255216: $fileType = 'image/jpg'; break;//jpng
		case 7173: $fileType = 'image/gif'; break;//gif
		case 6677: $fileType = 'image/bmp'; break;//bmp
		case 13780: $fileType = 'image/png'; break;//png
		case 8075: $fileType="zip";break;
		case 6787: $fileType="application/x-shockwave-flash";break;//swf
		default: $fileType='unknown';
	}
	return $fileType;
}


function download($url, $filename){ 
        if(substr($url,0,5)=='http:' or substr($url,0,6)=='https:'){
			 $a=get_headers($url,1);			
			 if(!$a || preg_match("/200/i",$a[0])==false){
				if(isset($a['Location'])){				
					$url=$a['Location'];
					$a=get_headers($url,1);
				}
			}
			$filesize=$a['Content-Length'];
		}else{
        	$filesize = sprintf("%u", filesize($url)); 
		}
        if (!$filesize) 
        { 
       		 return; 
        }        
        header("Content-type:application/octet-stream\n"); //application/octet-stream 
        header("Content-type:unknown/unknown;"); 
        header("Content-disposition: attachment; filename=\"".$filename."\""); 
        header('Content-transfer-encoding: binary');         
        if ($range = getenv('HTTP_RANGE')) // 当有偏移量的时候，采用206的断点续传头 
        { 
        $range = explode('=', $range); 
        $range = $range[1]; 

        header("HTTP/1.1 206 Partial Content"); 
        header("Date: " . gmdate("D, d M Y H:i:s") . " GMT"); 
        header("Last-Modified: ".gmdate("D, d M Y H:i:s", filemtime($url))." GMT"); 
        header("Accept-Ranges: bytes"); 
        header("Content-Length:".($filesize - $range)); 
        header("Content-Range: bytes ".$range.($filesize-1)."/".$filesize); 
        header("Connection: close"."\n\n"); 
        } 
        else 
        { 
        header("Content-Length:".$filesize."\n\n"); 
        $range = 0; 
        }
       loadFile($url);               
    }
    
    function loadFile($filename, $retbytes = true) {
        $buffer = '';
        $cnt =0;        
        $handle = fopen($filename, 'rb');
        if ($handle === false) {
          return false;
        }
        while (!feof($handle)) {
          $buffer = fread($handle, 1024*1024);
          echo $buffer;
          ob_flush();
          flush();
          if ($retbytes) {
            $cnt += strlen($buffer);
          }
        }
        $status = fclose($handle);
        if ($retbytes && $status) {
          return $cnt; // return num. bytes delivered like readfile() does.
        }
        return $status;
   }

/**
@删除目录下的所有文件 保留当前目录
*/
function delfile($dir,$rmdir=0)
{
	$dir=str_replace("..","",$dir);
	$hd=opendir($dir);
	while(false !== ($f=readdir($hd)))
	{
		if($f!=".." && $f!=".")
		{
			if(is_dir($dir."/".$f)){
				delfile($dir."/".$f."/",$rmdir);
			}else
			{
				unlink($dir."/".$f);
			}
		}
	}
	 
	closedir($hd);
	if($rmdir)
	{
		rmdir($dir);
	}
}


/*创建文件夹*/
function umkdir($dir)
{
	$dir=str_replace(ROOT_PATH,"",$dir);
	$dir=str_replace("//","/",$dir);
	$dir=str_replace("..","",$dir);
	$arr=explode("/",$dir);
	foreach($arr as $key=>$val)
	{
		$d="";
		for($i=0;$i<=$key;$i++)
		{
			$d.=$arr[$i]."/";
		} 
		if(!file_exists(ROOT_PATH.$d))
		{ 
			mkdir(ROOT_PATH.$d,0755);
		}
	}
}
/*需要跟cls_upload.php 中的方法一样*/
function dirId($id){
		return (($id/1000000)%100)."/".(($id/10000)%100)."/".(($id/100)%100)."/".($id%100)."/".$id."/";
}

function img_remote($content,$w=400,$h=400){
	preg_match_all("/<img.*src=['\"](.*)['\"][^>]*>/iUs",$content,$arr);
	if(is_array($arr[1])){
		foreach($arr[1] as $k=>$v){
			$content = str_replace($v , "/get.php?width=".$w."&height=".$h."&url=".urlencode($v) , $content);
		}
	}
	$content=preg_replace("/<img.*src=['\"](.*)['\"][^>]*>/iUs","<img src=\"\\1\">",$content);
	return $content;
}

function IMAGES_SITE($imgurl){
	if(empty($imgurl)) return "";
	if(stripos($imgurl,"http://")===false && stripos($imgurl,"https://")===false ){
		return IMAGES_SITE.$imgurl;
	}else{
		return $imgurl;
	}
}

?><?php
/*
根据ip获取gps
{
    address: "CN|北京|北京|None|CHINANET|1|None",   #地址
    content:       #详细内容
    {
    address: "北京市",   #简要地址
    address_detail:      #详细地址信息
    {
    city: "北京市",        #城市
    city_code: 131,       #百度城市代码
    district: "",           #区县
    province: "北京市",   #省份
    street: "",            #街道
    street_number: ""    #门址
    },
    point:               #百度经纬度坐标值
    {
    x: "116.39564504",
    y: "39.92998578"
    }
    },
    status: 0     #返回状态码
} 
*/ 
function ip2latlng($ip=""){
	if(empty($ip)){
		$ip=ip();
		if($ip=='127.0.0.1' or $ip="192.168.1.1"){
			$ip='58.23.255.212';
		}
	}
	$c=file_get_contents("http://api.map.baidu.com/location/ip?ak=".BDMAPKEY."&coor=bd09ll&ip=".$ip);
	$d=json_decode($c,true);
	return $d['content'];
}

 

/*获取两个gps之间的距离*/
function distanceByLnglat($lng1,$lat1,$lng2,$lat2)
{
	if(!$lng1 or !$lng2) return 0;
	//echo "$lng1,$lat1,$lng2,$lat2  ";
     $radlat1 = Rad($lat1);
     $radlat2 = Rad($lat2);
     $a = $radlat1 - $radlat2;
     $b = Rad($lng1) - Rad($lng2);
     $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radlat1) * cos($radlat2) * pow(sin($b / 2), 2)));
    $s = $s * 6378137.0;// 取WGS84标准参考椭球中的地球长半径(单位:m)
    $s = round($s * 10000) / 10000;
	return intval($s);
}

function Rad($d)
{
    return $d * M_PI / 180.0;
}
/*gps转百度坐标*/
function gps2baidu($lat,$lng)
{
	 
	$c=file_get_contents("http://api.map.baidu.com/geoconv/v1/?coords=$lng,$lat&from=0&to=4");
	$arr=json_decode($c,true);
	if(!$arr['status'])
	{
		$lat=$arr['result'][0]['x'];
		$lng=$arr['result'][0]['y'];
	}
	 
	return array($lat,$lng);
}


function getgps($lats,$lngs)
{
	 
	$lat=$lats[0]+$lats[1]/60+$lats[2]/3600000;
	$lng=$lngs[0]+$lngs[1]/60+$lngs[2]/3600000;
	return array($lat,$lng);
}

/*测试*/
/*
$exif=exif_read_data("1.jpg");
list($lat,$lng)=getgps($exif['GPSLatitude'],$exif['GPSLongitude']);
$latlng=gps2baidu($lat,$lng);
print_r($latlng);
*/		
?><?php
/**
*url重写
*TYPE 
*	1 /m/a/b-1.html
*	2 index.php/m/a/b-1/c-2
*/
function R($url){
	if(!defined("REWRITE_ON") or !REWRITE_ON) return $url;
	return rewrite($url);
}
function rewrite($url){
	if(!defined("REWRITE_TYPE")){
		define("REWRITE_TYPE","");
	}
	if(empty($url)) return $url;
	$str="";
	$url=str_replace("&amp;","&",$url);
	$s=parse_url($url);
	$query=$s['query'];
	parse_str($query,$data);
	switch(REWRITE_TYPE){
		case "pathinfo":
				if(empty($data)) return $s['path'];
				$data['m']=isset($data['m'])?$data['m']:"index";
				$data['a']=isset($data['a'])?$data['a']:"default";
				$str=$s['path']."/".$data['m']."/".$data['a'];
				unset($data['m']);
				unset($data['a']);
				if(!empty($data)){
					foreach($data as $k=>$v){
						 $str.="/$k-$v";
					}
				}
				return $str;		
			break;
		case "rewrite":
				if(empty($data)) return "/index.html";
				$data['m']=isset($data['m'])?$data['m']:"index";
				$data['a']=isset($data['a'])?$data['a']:"default";
				$str="/".$data['m']."/".$data['a'];
				unset($data['m']);
				unset($data['a']);
				if(!empty($data)){
					$i=0;
					foreach($data as $k=>$v){
						if($i==0){
							$dot="/";
						}else{
							$dot="-";
						}
						 $str.=$dot."$k-$v";
						 $i++;
					}
				}
				$str.=".html";
				return $str;
			break;
		default:
				return $url;
			break;
		
	}
	
}
/**
*解析pathinfo 路径
*/
function url_get($url,$appindex="index.php|admin.php|module.php"){
	if(preg_match("/".$appindex."/i",$url)){
		$query=preg_replace("/.*(".$appindex.")/i","",$url);
		$basename=str_replace($query,"",$url);
		$para=explode("?",$query);
		$data=explode("/",$para[0]);
		unset($data[0]);
		if(isset($data[1])){
			$_GET['m']=$data[1];
			unset($data[1]);
		}
		
		if(isset($data[2])){
			$_GET['a']=$data[2];
			unset($data[2]);
		}
		if(!empty($data)){
			foreach($data as $v){
				$c=explode("-",$v);
				if(isset($c[1])){
					$_GET[$c[0]]=urldecode($c[1]);
				}
			}
		}
		if(isset($para[1])){
			parse_str($para[1],$t_a);
			if($t_a){
				foreach($t_a as $k=>$v){
					$_GET[$k]=$v;
				}
			}
		}

	}elseif($len=strpos($url,"module.php")!==false){
		$query=preg_replace("/.*module\.php/i","",$url);
		$basename=str_replace($query,"",$url);
		$data=explode("/",$query);
		unset($data[0]);
		if(isset($data[1])){
			$_GET['m']=$data[1];
			unset($data[1]);
		}
		
		if(isset($data[2])){
			$_GET['a']=$data[2];
			unset($data[2]);
		}
		if(!empty($data)){
			foreach($data as $v){
				$c=explode("-",$v);
				if(isset($c[1])){
					$_GET[$c[0]]=$c[1];
				}
			}
		}
	}
	 
}

?><?php
if(!defined("ROOT_PATH")){
	define("ROOT_PATH",dirname(str_replace("\\", "/", dirname(__FILE__)))."/");
}
/*
缓存类文件
*/
if(!defined("ROOT_PATH")){
	define("ROOT_PATH","");
}
class cache
{
	public $expire=3600;
	public $cache_dir; 
	public $mem=null;
	public $cache_type="file";
	public $mysql;	
	function __construct()
	{
		$this->init();
		$this->defaultType();
	}
	
	public function init(){
		$this->cache_dir=ROOT_PATH."temp/filecache";
	}
 
	public function keydir($key){
		$d=md5($key);
		return "/".$d{0}."/".$d[1]."/".$d[2]."/";
	}
	public function setType($cache_type){
		global $cacheconfig;
		if(!isset($cacheconfig[$cache_type])){
			return $this->defaultType();
		}
		$this->cache_type=$cache_type;
		return $this;
	}
	
	 
	
	public function defaultType(){
		global $cacheconfig;
		if($cacheconfig['memcache']){
			$this->cache_type="memcache";
		}elseif($cacheconfig['mysql']){
			$this->cache_type='mysql';
		}else{
			$this->cache_type="file";
		}
		
		
	}
	
	public function set($key,$val,$expire=3600)
	{
		
		 switch($this->cache_type){
			case "memcache":
					$this->mem_set($key,$val,$expire);
				break; 
			case "file":
			
					$this->file_set($key,$val,$expire);
					
				break;
			case "php":
					$this->php_set($key,$val,$expire);
				break;
			case "mysql":
					$this->mysql_set($key,$val,$expire);
				break;
		 }
	}
	
	public function get($key){
		 switch($this->cache_type){
			 case "memcache":
			 			return $this->mem_get($key);
					break;
			 case "file":
						return $this->file_get($key);
					break;
			 case "php":
			 			return $this->php_get($key);
			 		break;
			case "mysql":
					
						return $this->mysql_get($key);	
					break;
		 }
	}
	

	
	/*
	@获取缓存内容
	*@file 文件名
	*/
	public function file_get($key)
	{
		$key=preg_replace("/[^\w]/","",$key);
		$dir=$this->cache_dir.$this->keydir($key);
		
		$file=$dir.$key.".txt";
		if(file_exists($file)){
			$data=json_decode(file_get_contents($file),true);
			if($data['expire']<time()){
				return false;
			}
			return $data['data'];
			
		}else{
			return false;
		}
		 
	}
	
	/**
	*设置缓存
	*/
	public function file_set($key,$val,$expire=3600){
		$key=preg_replace("/[^\w]/","",$key);
		$dir=$this->cache_dir.$this->keydir($key);
		$file=$dir.$key.".txt";
		$this->umkdir($dir);
		$data=array("expire"=>time()+$expire,"data"=>$val);
		file_put_contents($file,json_encode($data)); 
	}
	
	/**
	*@获取php缓存 可以直接include文件
	*/
	public function php_get($key)
	{
		$key=preg_replace("/[^\w]/","",$key);
		$dir=$this->cache_dir.$this->keydir($key);
		$file=$dir.$key.".php";
		if(file_exists($file)){
			@include($file);
			return $$key;
		}else{
			return false;
		}

	}
	 
	
	 
	/**
	*@写入php缓存 一般用作配置缓存 永久有效
	*/
	public function php_set($key,$val,$expire=3600)
	{
		$key=preg_replace("/[^\w]/","",$key);
		$dir=$this->cache_dir.$this->keydir($key);
		$file=$dir.$key.".php";
		$content='<?php'." \r\n".'$'.$key.'='.var_export($val,true).";\r\n?>";
		file_put_contents($file,$content);
	}
	
	
	/*
	*mem缓存
	*/
	
	 
	
	public function mem_set($k,$v,$expire=0){
		if(function_exists("cache_mem_set")){
			cache_mem_set($k,$v,$expire);
		}else{
			$this->file_set($k,$v,$expire);
		}
	}
	
	public function mem_get($k){
		if(function_exists("cache_mem_get")){
			return cache_mem_get($k);
		}else{
			return $this->file_get($k);
		}
	}
	
	/**
	*mysql 缓存
	*/
	
 
		
	public function mysql_set($k,$v,$expire=3600){
		
		if(function_exists("cache_mysql_set")){
			cache_mysql_set($k,$v,$expire);
		}else{
			$this->file_set($k,$v,$expire);
		}
		
	}
	
	public function mysql_get($k){
		if(function_exists("cache_mysql_get")){
			return cache_mysql_get($k);
		}else{
			return $this->file_get($k);
		}
		 
	}
	
	
	/**
	@删除目录缓存
	*/
	public function clear($type=0)
	{
		$this->delFile($this->cache_dir,$type);
	}
	
	/**
	@删除目录下的所有文件 保留当前目录
	*/
	function delfile($dir,$rmdir=0)
	{
		$hd=opendir($dir);
		while(($f=readdir($hd))!==false)
		{
			if($f!=".." and $f!=".")
			{
				if(is_file($dir."/".$f)){
					unlink($dir."/".$f);
				}else
				{
					self::delfile($dir."/".$f."/",$rmdir);
				}
			}
		}
		
		closedir($hd);
		if($rmdir)
		{
			rmdir($dir);
		}
	}
	
	
	/*创建文件夹*/
	function umkdir($dir)
	{
		$dir=str_replace(ROOT_PATH,"",$dir);
		$arr=explode("/",$dir);
		foreach($arr as $key=>$val)
		{			
			$d="";
			for($i=0;$i<=$key;$i++)
			{
				$d.=$arr[$i]."/";
			}
			if(!file_exists(ROOT_PATH.$d))
			{ 
				mkdir(ROOT_PATH.$d,0755);
			} 
		}
		
	}

	

		
}

?><?php

class mysql
{
	public $db;
	public	$dbconfig=array();//配置
	public	$charset="utf8";//编码
	public	$testmodel=false;//测试模式 开启后将会输出错误的sql语句 
	public $base;
	public $query=NULL;//最近操作的
	public $sql;
	public $stime;
	/**
	*mysql初始化 
	*/
	 public function __construct($data=array("charset"=>"utf8")){
		 
		 if(!defined("TABLE_PRE")){
			 define("TABLE_PRE","");
		 }
		 $this->charset=$data['charset'];
		 if(defined("TESTMODEL") && TESTMODEL==true){
			 $this->testmodel=true;
		 }
		  
		 
	 }
	 
	  
	 /**
	  * 设置参数
	  * $config=array(
	  * 	"master"=>array("localhost:","root","password","database"),//主数据库 必须的 以下从数据库非必须
	  * 	"slave"=>array(
	  * 				array("slave1","root","password","database"),
	  * 				array("slave2","root","password","database"),
	  * 			),
	  * )
	  */
	 public function set($config){
		 $this->dbconfig=$config;
	 }
	 /*
	  * 连接mysql
	  * */
	 public function connect($config=array()){
		if(!empty($config)){
			$master=$config;
		}else{
			$master=$this->dbconfig['master'];
		}
		$arr=explode(":",$master['host']);
		$host=$arr[0];
		if(isset($arr[1])){
			$port=$arr[1];
		}else{
			$port=3306;
		}
	 	$dsn = "mysql:host=".$master['host'].";port={$port};dbname=".$master['database']."";
		
		try {
			 $this->db = new PDO($dsn, $master['user'], $master['pwd']);
			 $rs=$this->db->prepare("SET sql_mode=''");
			 $rs->execute();
			  $rs=$this->db->prepare("SET NAMES ".$this->charset);
			 $rs->execute();
		} catch ( PDOException $e ) {
			echo  'Connection failed: '  .  $e -> getMessage ();
		}
	 }
	 /**
	  * 执行sql语句
	  */
	 public function query($sql){
		try{   
			if($this->testmodel){
				
				$GLOBALS['skysqlrun'] .="<br>".$sql;
				$GLOBALS['skysqlnum'] ++;
			}
			$this->sql=$sql;
			if(!$this->db){
				$this->connect();
			}
			$this->stime=microtime(true);
			$this->query=$rs = $this->db->prepare($sql);
			$rs->execute();
			 
			
			if($this->testmodel){
				$GLOBALS['query_time']+=microtime(true)-$st;
			}
			if($this->errno() >0 ){
				$e=$this->error();
				if(TESTMODEL){
					showError("sql错误：".$sql." ".$e[2]);
					exit;
				}else{
					showError("sql错误");
					exit;
				}
			};
			return $rs;
		}catch(PDOException $e){  
			if($e->errorInfo[0] == 70100 || $e->errorInfo[0] == 2006){  
				$this->connect();
				return $this->query($sql);  
			}else exit($e->errorInfo[2]);  
		}
	 }
	 
	 public function slowLog(){
		 if(SQL_SLOW_LOG==1){
			$qt=(microtime(true)-$this->stime);
			if(!defined("SQL_SLOW_TIME")){
				 define("SQL_SLOW_TIME",0.7);
			}
			if($qt>SQL_SLOW_TIME){
				skylog("sqlslow.txt","执行时间:".$qt."秒  ".$this->sql); 
			}
		}
	 }

	 /**
	  * 返回结果集中的数目
	  */
	public function num_rows(){
		return $this->query-> columnCount ();
	}
	
	/**
	 * 将结果集解析成数组
	 */
	public function fetch_array($result_type=PDO::FETCH_ASSOC){
		$this->query-> setFetchMode ( $result_type );
		return $this->query->fetchAll();	
	}
	
	/**
	 * 从结果集中取一行
	 */
	public function fetch_row($result_type=PDO::FETCH_ASSOC){
		return $this->query->fetch($result_type);	
	}
	
	
	/**
	 * 取得结果集中字段的数目
	 */
	public function num_fields(){
		return $this->query->columnCount ();
	}
	
	/*
	 * 插入数据
	 * */
	public function insert($table,$data){
		$fields=$this->compile_array($data);
		$this->query("INSERT INTO ".TABLE_PRE.$table." SET $fields ", $this->db);
		return $this->insert_id();
	}
	/**
	 * 更新数据
	 */
	public function update($table,$data,$w=""){
		$fields=$this->compile_array($data);
		$where=$w?" WHERE ".$this->compile_array($w," AND"):"";
		$this->query("UPDATE ".TABLE_PRE.$table." SET {$fields} {$where} ", $this->db);
		return $this->affected_rows();
	}
	/**
	 * 删除数据
	 */
	public function delete($table,$w=''){
		$where=$w?" WHERE ".$this->compile_array($w," AND "):"";
		$this->query("DELETE FROM ".TABLE_PRE."$table {$where} ");
		return $this->affected_rows( $this->db);		
	} 
	
	/**
	 * 获取全部数据
	 *array("table","where"=>array(),"order","start","pagesize","fields")
	*/
	public function SELECT($table,$data=array(),&$rscount=false){		
		$start=isset($data['start'])?$data['start']:0;
		$pagesize=isset($data['limit'])?$data['limit']:1000000;
		$fields=isset($data['fields'])?$data['fields']:" * ";
		$where=(!isset($data['where']) or empty($data['where']))?"":" where ".$this->compile_array($data['where']," AND ");
		$order=isset($data['order']) && !empty($data['order'])?" ORDER BY ".$data['order']:"";
		if($rscount){
			$rscount=$this->getCount($table,isset($data['where'])?$data['where']:'');
		}
		return $this->getAll("SELECT {$fields} FROM ".TABLE_PRE."{$table}  $where {$order} LIMIT $start,$pagesize ");
		
	}
	/**
	 * 获取一个数据
	 *array("table","where","order","start","pagesize","fields")
	*/
	public function selectOne($table,$data=array()){
		$start=isset($data['start'])?$data['start']:0;
		$pagesize=isset($data['limit'])?$data['limit']:1;
		$fields=isset($data['fields'])?$data['fields']:" * ";
		$where=(!isset($data['where']) or empty($data['where']))?"":" where ".$this->compile_array($data['where']," AND ");
		$order=isset($data['order']) && !empty($data['order'])?" ORDER BY ".$data['order']:"";
		return $this->getOne("SELECT {$fields} FROM ".TABLE_PRE."{$table}  {$where} {$order} LIMIT 1 ");
	}
	/**
	 * 获取一行数据
	 *array("table","where","order","start","pagesize","fields")
	*/
	public function selectRow($table,$data=array()){
		$start=isset($data['start'])?$data['start']:0;
		$pagesize=isset($data['limit'])?$data['limit']:1;
		$fields=isset($data['fields'])?$data['fields']:" * ";
		$where=(!isset($data['where']) or empty($data['where']))?"":" where ".$this->compile_array($data['where']," AND ");
		$order=isset($data['order']) && !empty($data['order'])?" ORDER BY ".$data['order']:"";
		return $this->getRow("SELECT {$fields} FROM ".TABLE_PRE."{$table}  {$where} {$order} LIMIT 1 ");
	}
	/**
	 * 获取一列数据
	 *array("table","where","order","start","pagesize","fields")
	*/	
	public function selectCols($table,$data=array(),&$rscount=false){
		$start=isset($data['start'])?$data['start']:0;
		$pagesize=isset($data['limit'])?$data['limit']:1000000;
		$fields=isset($data['fields'])?$data['fields']:" * ";
		$where=(!isset($data['where']) or empty($data['where']))?"":" where ".$this->compile_array($data['where']," AND ");
		$order=isset($data['order']) && !empty($data['order'])?" ORDER BY ".$data['order']:"";
		if($rscount){
			$rscount=$this->getCount($table,isset($data['where'])?$data['where']:'');
		}
		return $this->getCols("SELECT {$fields} FROM ".TABLE_PRE."{$table}  {$where} {$order} LIMIT $start,$pagesize ");
	}
	
	
	
	/**
	 * 获取统计数据
	 */	
	public function getCount($table,$w=array()){
		
		$where=empty($w)?"":" where ".$this->compile_array($w," AND ");
		return $this->getOne("SELECT COUNT(1) FROM ".TABLE_PRE.$table." $where ");
	}
	/**
	 * 获取全部数据
	 */
	public function getAll($sql){
		$res=$this->query($sql);
		if($res!==false)
		{
			$res-> setFetchMode ( PDO :: FETCH_ASSOC );
			$arr=$res->fetchAll();
			$this->slowLog();
			return $arr;
		}else
		 {
			return false;
			
		}
	}

	/**
	 * 获取一个字段
	 */
	public function getOne($sql){
		$res=$this->query($sql);
		if($res !==false){
			$rs=$res->fetch();
			$this->slowLog();
			if($rs!==false){
				return $rs[0];
			}else{
				return '';
			}
		}
		else {
			return false;
		}
		
	}
		
	/*获取一行*/
	 public function getRow($sql){
        $res = $this->query($sql);
        if ($res !== false){
			$res-> setFetchMode ( PDO :: FETCH_ASSOC );
			$arr=$res->fetch();
			$this->slowLog();
            return $arr;
        }else{
            return false;
        }
    }
    /*获取一列*/
    public function getCols($sql)
	{
		$res=$this->query($sql);
		if($res!==false){
			$res-> setFetchMode ( PDO :: FETCH_NUM );

			$data=$res->fetchAll();
			$arr=array();
			if(!empty($data)){
				foreach($data as $v){
					$arr[]=$v[0];
				}
			}
			$this->slowLog();
			return $arr;
		}else{
			return false;
		}
	}
	
	/**
	 * 获取影响的行数
	 */
	public function affected_rows(){
	 
		return  $this->query -> rowCount ();
	}
	/*
	 * 最新插入的函数
	 * */
	public function insert_id( ){
        return $this->db->lastInsertId();
    }
	
	/*复制表*/
	public function copy_table($table,$newtable){
		
		$rs=$this->getRow("show create table ".TABLE_PRE."$table ");
		 
		$sql=preg_replace("/CREATE TABLE/i","CREATE TABLE IF NOT EXISTS",$rs['Create Table'],1);
		$sql=str_replace(TABLE_PRE.$table,TABLE_PRE.$newtable,$sql);
		$this->query($sql);
		return true;
	}
	
    /*
     * 获取错误信息
     * */
    public function error($link=null){
        return $this->query->errorInfo();
    }
	/*
	 * 获取错误代号
	 * */
    public function errno($link=null){
        return $this->query->errorCode();
    }
	
	
	 /*
	  * 判断是否查询语句
	  * */
	 public function isselect($sql){ 		
		preg_match("/^[ ]*(select).*/i",$sql,$a);
		if($a)
		{
			return true;
		}else{
			return false;
		}
 
	 }
	/*字符串转义*/ 
	public function newaddslashes($value){
		if(empty($value)){
			return $value;
		}else{
			if(is_array($value)){
				 return array_map('newaddslashes', $value);
			}else{				
				$value=stripslashes($value);
				$value=str_replace("\'","'",$value);  
				$value=str_replace('\"','"',$value);
				$value=addslashes(trim($value));				
				return $value;	
			}
		}	 
	}
	
	public function compile_array($data,$d=","){
		
		$dot=$fields="";
		$i=0;
		if(is_array($data)){ 
			foreach($data as $k=>$v){
				if($i>0) $dot=$d;
				if(preg_match("/[<|>]/",$k)){
					$fields.="$dot {$k}'".$this->newaddslashes($v)."' ";
				}else{
					$fields.="$dot $k='".$this->newaddslashes($v)."' ";
				}
				$i++;	
			}
			return $fields;
		}else{
			return $data;
		}
	}
	
	public function __destruct(){
		 $this->close();
	}
	
	public function close(){
		//$this->db=NULL;
	}
	/*生成md5缓存的key*/
	public function md5key($sql){
		$sql=strtolower($sql);
		$key=md5($sql);
		preg_match("/from (.*) [where]?/is",$sql,$data);
		$table=trim($data[1]);
		return "sql".$table."_".$key;
	}
	 

}	

?><?php
class model{
	public $base;
	public $db;
	public $table;
	public $table_all=NULL;
	public $tmpTable=false;  
	function __construct(&$base)
	{
		$this->base=$base;
		$this->db=$base->db;		
	}
	
	public function setDb($table=NULL){
		$this->db=setDb($table?$table:$this->table);
		if($this->db){
			$this->db->base=$this->base;
		}
	}
	
	public function setTable($table,$object_id=0,$table_num=10){
		$this->setDb($table);
		if($object_id){
			$this->tmpTable=$table."_".($object_id%$table_num);
		}else{
			$this->tmpTable=$table;
		}
		return $this;
	}
	public function clearTable(){
		$this->tmpTable=false;
	}
	/*加载模型*/
	public function loadModel($model,$base=NULL){
		$this->base->loadModel($model,$base);		
		if(is_array($model)){
			foreach($model as $m){
				$this->$m=$this->base->$m;
			}
		}else{
			$this->$model=$this->base->$model;
		}
	}
	/**
	*执行sql语句
	*/
	public function query($sql){
		 
		return $this->db->query($sql);
	}
	
	public function begin(){
		 
		return $this->db->query("BEGIN");
	}
	
	public function commit(){
		  
		return $this->db->query("COMMIT");
	}
	
	public function rollback(){
		 
		return $this->db->query("rollback");
	}
	
	/**
	 * 将结果集解析成数组
	 */
	public function fetch_array($result_type=MYSQL_ASSOC){
		return $this->db->fetch_array($result_type);	
	}
	
	/**
	 * 从结果集中取一行
	 */
	public function fetch_row($result_type=PDO::FETCH_ASSOC){
		return $this->db->fetch_row($result_type);	
	}
	
	/**
	*获取全部数据
	*/
	public function getAll($sql,$cache=0,$expire=60){
		return $this->db->getAll($sql,$cache,$expire);
	}
	/**
	*获取一个字段数据
	*/
	public function getOne($sql,$cache=0,$expire=60){
		return $this->db->getOne($sql,$cache,$expire);
	}
	/**
	*获取一列数据
	*/
	public function getCols($sql,$cache=0,$expire=60){
		return $this->db->getCOls($sql,$cache,$expire);
	}
	/**
	*获取一行数据
	*/
	public function getRow($sql,$cache=0,$expire=60){
		return $this->db->getRow($sql,$cache,$expire);
	}
	/**
	*获取全部数据
	* data array("table","where"=>array(),"order","start","pagesize","fields")
	*/
	public function select($option=array(),&$rscount=false,$cache=0,$expire=60){
		/*
		if(!isset($option['where'])){
			 
			exit('请确认sql select where条件'.$this->table);
		}
		*/
		$data=$this->db->select($this->tmpTable?$this->tmpTable:($this->table_all?$this->table_all:$this->table),$option,$rscount,$cache,$expire);
		$this->clearTable();
		return $data;
	}
	/**
	*获取一个字段
	*data array("table","where"=>array(),"order","start","pagesize","fields")
	*/
	public function selectOne($data=array(),$cache=0,$expire=60){
		$data=$this->db->selectOne($this->tmpTable?$this->tmpTable:($this->table_all?$this->table_all:$this->table),$data,$cache,$expire);
		$this->clearTable();
		return $data;
	}
	/**
	*获取一行信息
	*data array("table","where"=>array(),"order","start","pagesize","fields")
	*/
	public function selectRow($data=array(),$cache=0,$expire=60){
		if(!is_array($data)){
			$t=$data;
			$data=array();
			$data['where']=$t;
		}
		$data=$this->db->selectRow($this->tmpTable?$this->tmpTable:($this->table_all?$this->table_all:$this->table),$data,$cache,$expire);
		$this->clearTable();
		return $data;
	}
	/**
	*获取一列信息
	*data array("table","where"=>array(),"order","start","pagesize","fields")
	*/
	public function selectCols($data=array(),&$rscount=false,$cache=0,$expire=60){
		$data=$this->db->selectCols($this->tmpTable?$this->tmpTable:($this->table_all?$this->table_all:$this->table),$data,$rscount,$cache,$expire);
		$this->clearTable();
		return $data;
	}
	/*
	*获取记录总数
	*data array("table","where"=>array(),"order","start","pagesize","fields")
	*/
	public function getCount($w=array(),$cache=0,$expire=60){
		$data=$this->db->getCount($this->tmpTable?$this->tmpTable:($this->table_all?$this->table_all:$this->table),$w,$cache,$expire);
		$this->clearTable();
		return $data;
	}
	/**
	*插入数据
	*data array("title"=>'aaa');
	*/
	public function insert($data){
		$data=$this->db->insert($this->tmpTable?$this->tmpTable:($this->table_all?$this->table_all:$this->table),$data);
		$this->clearTable();
		return $data;
	}
	
	/**
	 * 批量插入数据***
	**/
	public function insertMore($data){
		$table=$this->tmpTable?$this->tmpTable:($this->table_all?$this->table_all:$this->table);
		
		if(!empty($data)){
			$i=0;
			$fields="";
			foreach($data[0] as $k=>$v){
				if($i>0){
					$fields.=",";
				}
				$fields.=$k;
				$i++;
			}
			$values="";
			foreach($data as $k=>$v){
				if($k>0){
					$values.=",";
				}
				$values.="("._implode($v).") ";
			}
		}
		$sql=" insert into ".TABLE_PRE.$table." ($fields) values $values ;";
		$this->db->query($sql);
		$this->clearTable();
		return $data;
	}
	/**
	*更新数据
	*data array("title"=>'aaa');
	*where array("id"=>1)
	*/
	public function update($data,$where=""){
		if(empty($where)){
			exit("UPDate 必须 含条件");
		}
		if(empty($data)) return false;
		$data=$this->db->update($this->tmpTable?$this->tmpTable:($this->table_all?$this->table_all:$this->table),$data,$where);
		$this->clearTable();
		return $data;
	}
	/**
	/*修改数字类型
	*/
	public function changenum($fields,$num,$w){
		if(empty($w)){
			exit("UPDate 必须 含条件");
		}
		$data=$this->db->query("UPDATE ".TABLE_PRE.($this->tmpTable?$this->tmpTable:($this->table_all?$this->table_all:$this->table))." SET $fields=$fields+{$num} WHERE $w ");
		$this->clearTable();
		return $data;
	}
	/**
	*删除数据
	*where array("id"=>1)
	*/
	public function delete($where=""){
		if(empty($where)){
			exit("Delete 必须 含条件");
		}
		$data=$this->db->delete($this->tmpTable?$this->tmpTable:($this->table_all?$this->table_all:$this->table),$where);
		$this->clearTable();
		return $data;
	}
	/*最近插入的id*/
	public function insert_id(){
		return $this->db->insert_id();
	}
	
	public function getFields(){
		$table=$this->tmpTable?$this->tmpTable:($this->table_all?$this->table_all:$this->table);
		return $this->db->getAll("show columns from ".TABLE_PRE.$table."");
	}
	public function postData(){
		$table=$this->tmpTable?$this->tmpTable:($this->table_all?$this->table_all:$this->table);
		$fields=$this->getFields();
		if($fields){
			foreach($fields as $k=>$v){
				//if($k==0) continue;
				if(isset($_POST[$v['Field']])){
					$format="h";
					$len=0;
					if(preg_match("/int/i",$v['Type'])){
						$format="i";
					}elseif(preg_match("/char/i",$v['Type'])){
						$format="h";
					}elseif(preg_match("/text/i",$v['Type']) or preg_match("/blog/i",$v['Type']) ){
						$format="x";
					}elseif(preg_match("/decimal/i",$v['Type'])){
						$format="r";
						$len=7;
					}
					if($v['Field']=='starttime' or $v['Field']=='endtime'){
						$data[$v['Field']]=strtotime(post($v['Field']));
					}elseif($v['Field']=='dateline'){
						$data[$v['Field']]=time();
					}else{				
						$data[$v['Field']]=post($v['Field'],$format,$len);
					}
				}
			}
		}
		return $data;
	}
	/*关闭数据库*/
	public function close(){
		//$this->db->close();
	}
	
	
	
}

?><?php
class session{

	public $db;
	function __construct()
	{
		session_module_name('user'); 		
	 
		session_set_save_handler( 
            array(&$this, 'open'), 
            array(&$this, 'close'), 
            array(&$this, 'read'), 
            array(&$this, 'write'), 
            array(&$this, 'destroy'), 
            array(&$this, 'gc')                                                             
        ); 
		session_start();
	}
	function open($save_path, $session_name)
	{
		return true;
	}
	
	function close()
	{
		return true;
	}
	
	function read($id)
	{	 
		return sess_read($id); 
		return false;
	}
	
	function write($id, $sess_data)
	{
		sess_write($id,$sess_data);
		return true;
	}
	
	function destroy($id)
	{
		sess_destroy($id);
		return false;
	}
	
	function gc($maxlifetime)
	{
		sess_gc($maxlifetime);
		return true;
	}

	
}

?><?php

if(!defined("ROOT_PATH")){
	define("ROOT_PATH",dirname(str_replace("\\", "/", dirname(__FILE__)))."/");
}
if(!defined("OB_GZIP")){
	define("OB_GZIP",false);
}
class Smarty
{
    public $template_dir   = '';//模版文件夹
    public $cache_dir      = '';//缓存文件夹
    public $compile_dir    = '';//编译文件夹
	public $html_dir		='';//生成静态文件夹
    public $cache_lifetime = 3600; // 缓存更新时间, 默认 3600 秒
    public $direct_output  = false;//直接输出
    public $caching        = false;//是否开启缓存
    public $template       = array();//模版
    public $force_compile  = false;//强制编译 
	public $rewrite_on	=false;//是否开启伪静态
	public $rewriterule=array();//伪静态规则
    public $_var           = array();//临时变量
    public $charset			="utf-8";//设置编码 
    public $_foreach       = array();//循环
    public $_current_file  = '';//当前文件
    public $_expires       = 0;//过期时间
    public $_errorlevel    = 0;//设置错误提醒级别
    public $_nowtime       = null;//当前时间戳
    public $_checkfile     = true;//检查文件
    public $_foreachmark   = '';//循环标记
    public $_seterror      = 0;//是否错误	
    public $_temp_key      = array();  // 临时存放 foreach 里 key 的数组
    public $_temp_val      = array();  // 临时存放 foreach 里 item 的数组
	public $htm_lfile="";
	public $_vars;
	public $dir;

   public function __construct()
    {
        $this->Smarty();
    }

    public function Smarty()
    {
        $this->_errorlevel = error_reporting();
        $this->_nowtime    = time();      
    }
	/**
		注册变量 有ajax直接返回数据	
	*/
	public function goAssign($tpl_var, $value = ''){
		
		if(get('ajax')){
			skymvc_test_page_auto();
			C()->goALL("success",0,$tpl_var);
		}else{
			$this->assign($tpl_var,$value);
		}
	}

    /**
     * 注册变量
     *
     * @access  public
     * @param   mix      $tpl_var
     * @param   mix      $value
     *
     * @return  void
     */
    public function assign($tpl_var, $value = '')
    {
		 
        if (is_array($tpl_var))
        {
            foreach ($tpl_var AS $key => $val)
            {
                if ($key != '')
                {
                    $this->_var[$key] = $val;
                }
            }
        }
        else
        {
            if ($tpl_var != '')
            {
                $this->_var[$tpl_var] = $value;
            }
        }
    }

    /**
     * 显示页面函数
     *
     * @access  public
     * @param   string      $filename
     * @param   sting      $cache_id
     *
     * @return  void
     */
    public function display($filename, $cache_id = '')
    {
		$_GET=get_post_Html($_GET); 
        
		$this->_seterror++;
        error_reporting(E_ALL ^ E_NOTICE);
		
        $this->_checkfile = false;
        $out = $this->fetch($filename, $cache_id);		
        $this->_seterror--;
		
		if(OB_GZIP){
			if(extension_loaded('zlib')){
				@ob_start("ob_gzhandler");
			}
		}else{
			ob_start();
		}
		
		 
		if(function_exists("shouQuanTpl")){
				$out=shouQuanTpl($out);
		}
		if($this->html_file){
			error_reporting(E_ALL ^ E_NOTICE);
			$this->umkdir(dirname($this->html_file));		
			file_put_contents($this->html_file,$out);
		}
		if(defined("CHECKBADWORD") && CHECKBADWORD==1){
			require(ROOT_PATH."config/badword.php");
			$out=preg_replace("/".$badword."/iUs","****",$out);
		}
		echo $out;
		ob_end_flush();
		
		if(false!==method_exists(C(),"sexit")){
			C()->sexit();
		}
		exit;
    }
	
	/**
	*生成html
	*/
	public function html($htmlfile,$expire=3600){
		$file=$this->html_dir."/".$htmlfile;
		$filestat = @stat($filename);
		if(file_exists($file) && !isset($_GET['forceHtml']) && $filestat['mtime']>time()-$expire){
			return false;
		}else{
			$this->html_file=$file;
		}
	}

    /**
     * 处理模板文件
     *
     * @access  public
     * @param   string      $filename
     * @param   sting      $cache_id
     *
     * @return  sring
     */
	
	public function fetchhtml($str){
		return $out = $this->_eval($this->fetch_str($str)); 
	}
	 
    public function fetch($filename, $cache_id = '',$dir="")
    {
		//$dir=!empty($dir)?$this->template_dir."/".$dir:$this->template_dir;
		$dir=str_replace("..","",$dir);
		$dir=!empty($dir)?$dir:$this->template_dir;
        if (!$this->_seterror)
        {
            error_reporting(E_ALL ^ E_NOTICE);
        }
        $this->_seterror++;
		if(is_object(C())){
			$this->assign("config",C()->config_item());
		}
        if (strncmp($filename,'str:', 4) == 0)
        {
            $out = $this->_eval($this->fetch_str(substr($filename, 4)));
        }
        else
        {
            if ($this->_checkfile)
            {
                if (!file_exists($filename))
                {
                    $filename = $dir . '/' . $filename;
                }
            }
            else
            {
                $filename = $dir . '/' . $filename;
            }

            if ($this->direct_output)
            {
                $this->_current_file = $filename;
				if(file_exists($filename)){
                	$out = $this->_eval($this->fetch_str(file_get_contents($filename)));
				}
            }
            else
            {
                if ($cache_id && $this->caching)
                {
                    $out = $this->template_out;
                }
                else
                {
                    if (!in_array($filename, $this->template))
                    {
                        $this->template[] = $filename;
                    }

                    $out = $this->make_compiled($filename);

                    if ($cache_id)
                    {
                     	$cachename = str_replace(array("/",":"),"_",$filename) . '_' . $cache_id ;
						
                        $data = serialize(array('template' => $this->template, 'expires' => $this->_nowtime + $this->cache_lifetime, 'maketime' => $this->_nowtime));
                        $out = str_replace("\r", '', $out);

                        while (strpos($out, "\n\n") !== false)
                        {
                            $out = str_replace("\n\n", "\n", $out);
                        }

                        $hash_dir = $this->cache_dir . '/' . substr(md5($cachename), 0, 1);
                        if (!is_dir($hash_dir))
                        {
                            mkdir($hash_dir);
                        }
                        if (file_put_contents($hash_dir . '/' . $cachename . '.php', '<?php exit;?>' . $data . $out, LOCK_EX) === false)
                        {
                            trigger_error('can\'t write:' . $hash_dir . '/' . $cachename . '.php');
                        }
                        $this->template = array();
                    }
                }
            }
        }

        $this->_seterror--;
        if (!$this->_seterror)
        {
            error_reporting($this->_errorlevel);
        }
		skymvc_test_page_auto();
        return $out; // 返回html数据
    }

    /**
     * 编译模板函数
     *
     * @access  public
     * @param   string      $filename
     *
     * @return  sring        编译后文件地址
     */
    public function make_compiled($filename)
    {
	 	$dir= $this->compile_dir."/".str_replace(ROOT_PATH,"",dirname($filename));
		umkdir($dir);
        $name =$dir."/".basename($filename) . '.php';
        if ($this->_expires)
        {
            $expires = $this->_expires - $this->cache_lifetime;
        }
        else
        {
            $filestat = @stat($name);
            $expires  = $filestat['mtime'];
        }

        $filestat = @stat($filename);

        if ($filestat['mtime'] <= $expires && !$this->force_compile)
        {
            if (file_exists($name))
            {
                $source = $this->_require($name);
                if ($source == '')
                {
                    $expires = 0;
                }
            }
            else
            {
                $source = '';
                $expires = 0;
            }
        }

        if ($this->force_compile || $filestat['mtime'] > $expires)
        {
            $this->_current_file = $filename;
			if(!file_exists($filename)){
            	echo "模板".$filename."不存在";
            	return false;
            }
            $source = $this->fetch_str(file_get_contents($filename));
			
            if (file_put_contents($name, $source, LOCK_EX) === false)
            {
                trigger_error('can\'t write:' . $name);
            }

            $source = $this->_eval($source);
        }

        return $source;
    }

    /**
     * 处理字符串函数
     *
     * @access  public
     * @param   string     $source
     *
     * @return  sring
     */
    public function fetch_str($source)
    {
       //5.2
	   return preg_replace_callback("/{([^\}\{\n]*)}/", array($this, 'select'), $source);
		//5.3
       // return preg_replace_callback("/{([^\}\{\n]*)}/", "self::select", $source);
    }

    /**
     * 判断是否缓存
     *
     * @access  public
     * @param   string     $filename
     * @param   sting      $cache_id
     *
     * @return  bool
     */
    public function is_cached($filename, $cache_id = '')
    {
       $cachename = str_replace(array("/",":"),"_", $this->template_dir . '/' .$filename) . '_' . $cache_id;
	  
        if ($this->caching == true && $this->direct_output == false)
        {
			
            $hash_dir = $this->cache_dir . '/' . substr(md5($cachename), 0, 1);
			 
            if ($data = @file_get_contents($hash_dir . '/' . $cachename . '.php'))
            {
                $data = substr($data, 13);
                $pos  = strpos($data, '<');
                $paradata = substr($data, 0, $pos);
                $para     = @unserialize($paradata);
                if ($para === false || $this->_nowtime > $para['expires'])
                {
                    $this->caching = false;

                    return false;
                }
                $this->_expires = $para['expires'];

                $this->template_out = substr($data, $pos);

                foreach ($para['template'] AS $val)
                {
                    $stat = @stat($val);
                    if ($para['maketime'] < $stat['mtime'])
                    {
                        $this->caching = false;

                        return false;
                    }
                }
            }
            else
            {
                $this->caching = false;

                return false;
            }

            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * 处理{}标签
     *
     * @access  public
     * @param   string      $tag
     *
     * @return  sring
     */
    public function select($tag)
    {
		if(is_array($tag)) $tag=$tag[1];
        $tag = stripslashes(trim($tag));
        if (empty($tag))
        {
            return '{}';
        }elseif(substr($tag,0,3)=='get'){
		/*
		*解析模版获取model数据
		*{get model=index module=book data=k fun=list($k,$v,$s) }
		*/	
			@preg_match("/module=(.*) /iU",$tag,$module);	 
			@preg_match("/model=(.*) /iU",$tag,$m);			
			@preg_match("/data=(.*) /iU",$tag,$k);
			@preg_match("/fun=([^}]*)/i",$tag,$fun);
			$fun=$fun[1];
			//解析fun变量 $a['x']['y'] => $this_var['a']['x']['y'];
			preg_match_all('/(\$[\w\.\[\]]+)/i',$fun,$b);
			
			foreach($b[1] as $v){
				if($v=="\$rscount") continue;
				$fun=str_replace($v,$this->parse_var($v),$fun);
			}
			
			//$fun=preg_replace("/\\$([\w_]+)([\[\w_\]]*)/i","{\$this->_var[\\1]\\2}",$fun);
			//解析数组 {$a}[][][];
			if(isset($module[1])){
				return '<?php C()->loadModulemodel("'.$module[1].'","'.$m[1].'");$this->assign("'.$k[1].'",$GLOBALS["'.$m[1].'Model"]->'.$fun.'); ?>';
			}else{
				return '<?php $this->assign("'.$k[1].'",M("'.$m[1].'")->'.$fun.'); ?>';
				/*
				return '<?php C()->loadmodel("'.$m[1].'");$this->assign("'.$k[1].'",$GLOBALS["'.$m[1].'Model"]->'.$fun.'); ?>';
				*/
			}
		}elseif(substr($tag,0,3)=='api'){
			/**
			*解析模板获取control数据
			*/
			@preg_match("/module=(.*) /iU",$tag,$module);	 
			@preg_match("/control=(.*) /iU",$tag,$m);			
			@preg_match("/data=(.*) /iU",$tag,$k);
			@preg_match("/fun=([^}]*)/i",$tag,$fun);
			$fun=$fun[1];
			//解析fun变量 $a['x']['y'] => $this_var['a']['x']['y'];
			preg_match_all('/(\$[\w\.\[\]]+)/i',$fun,$b);
			foreach($b[1] as $v){
				if($v=="\$rscount") continue;
				$fun=str_replace($v,$this->parse_var($v),$fun);
			}
			//$fun=preg_replace("/\\$([\w_]+)([\[\w_\]]*)/i","{\$this->_var[\\1]\\2}",$fun);
			//解析数组 {$a}[][][];
			if(isset($module[1])){
				return '<?php $this->assign("'.$k[1].'",CC("'.$module[1].'","'.$m[1].'")->'.$fun.'); ?>';
			}else{ 
				return '<?php $this->assign("'.$k[1].'",C("'.$m[1].'")->'.$fun.'); ?>';
			}
			
		}elseif(substr($tag,0,1)=='R'){//伪静态处理
			$tag=str_replace("'",'"',$tag);
			preg_match("/R\((.*)\)/i",$tag,$a);
			$a=$a[1];
			
			preg_match_all('/(\$[\w\.\[\]]+)/i',$a,$b);
			foreach($b[1] as $v){
				$tag=str_replace($v,$this->parse_var($v),$tag);
			}
			return '<?php echo '.$tag.';?>';
		}elseif ($tag{0} == '*' && substr($tag, -1) == '*') // 注释部分
        {
            return '';
        }
        elseif ($tag{0} == '$') // 变量
        {
            return '<?php echo ' . $this->get_val(substr($tag, 1)) . '; ?>';
        }elseif(substr($tag,0,3)=='php')
		{
			$tag=str_replace('$smarty','$this->_var',$tag);
 			return "<?$tag;?>";
					 
		}elseif(substr($tag,0,5)=='const' )
		{
			return '<?php echo ' . substr($tag, 6) . '; ?>';
		}elseif(substr($tag,0,4)=='math'){//计算函数
			return $this->_math($tag);
		}elseif ($tag{0} == '/') // 结束 tag
        {
            switch (substr($tag, 1))
            {
                case 'if':
                    return '<?php endif; ?>';
                    break;

                case 'foreach':
                    if ($this->_foreachmark == 'foreachelse')
                    {
                        $output = '<?php endif; unset($_from); ?>';
                    }
                    else
                    {
                        array_pop($this->_patchstack);
                        $output = '<?php endforeach; endif; unset($_from); ?>';
                    }
                    $output .= "<?php \$this->pop_vars();; ?>";

                    return $output;
                    break;

                case 'literal':
                    return '';
                    break;

                default:
                    return '{'. $tag .'}';
                    break;
            }
        }
        else
        {
			$t_a=explode(' ', $tag);
            $tag_sel = array_shift($t_a);
            switch ($tag_sel)
            {
                case 'if':

                    return $this->_compile_if_tag(substr($tag, 3));
                    break;

                case 'else':

                    return '<?php else: ?>';
                    break;

                case 'elseif':

                    return $this->_compile_if_tag(substr($tag, 7), true);
                    break;

                case 'foreachelse':
                    $this->_foreachmark = 'foreachelse';

                    return '<?php endforeach; else: ?>';
                    break;

                case 'foreach':
                    $this->_foreachmark = 'foreach';
                    if(!isset($this->_patchstack))
                    {
                        $this->_patchstack = array();
                    }
                    return $this->_compile_foreach_start(substr($tag, 8));
                    break;

                case 'assign':
                    $t = $this->get_para(substr($tag, 7),0);
					 
					//=号bug处理
					$t['value']=str_replace("[eq]","=",$t['value']); 
                    if ($t['value']{0} == '$' or substr(trim($t['value']),0,6)=='array(')
                    {
                        /* 如果传进来的值是变量，就不用用引号 */
                        $tmp = '$this->assign(\'' . $t['var'] . '\',' . $t['value'] . ');';
                    }
                    else
                    {
                        $tmp = '$this->assign(\'' . $t['var'] . '\',\'' . addcslashes($t['value'], "'") . '\');';
                    }
                    // $tmp = $this->assign($t['var'], $t['value']);

                    return '<?php ' . $tmp . ' ?>';
                    break;

                case 'include':
                    $t = $this->get_para(substr($tag, 8), 0);
	
					/*  shop/()left.html */
					$t['file']=str_replace('$',"",$t['file']);
					preg_match_all("/\((.*)\)/iUs",$t['file'],$t_a);
					if(isset($t_a[1])){
						foreach($t_a[1] as $t_k=>$t_a_v){
							$t['file']=str_replace($t_a[0][$t_k],"'.\"".$this->parse_var($t_a_v)."\".'",$t['file']);
						}
					}
					 
					if(isset($t[dir])){
						if(preg_match("/this->_var/i",$t[dir])){
							return '<?php echo $this->fetch(' . "'$t[file]'" . ',0,'.$t[dir].'); ?>';
						}else{
							return '<?php echo $this->fetch(' . "'$t[file]'" . ',0,'."'$t[dir]'".'); ?>';
						}
						
					}else{
                    	return '<?php echo $this->fetch(' . "'$t[file]'" . '); ?>';
					}
                    break;
       
                case 'literal':
                    return '';
                    break;

                case 'cycle' :
                    $t = $this->get_para(substr($tag, 6), 0);

                    return '<?php echo $this->cycle(' . $this->make_array($t) . '); ?>';
                    break;
                default:
                    return '{' . $tag . '}';
                    break;
            }
        }
    }

    /**
     * 处理smarty标签中的变量标签
     *
     * @access  public
     * @param   string     $val
     *
     * @return  bool
     */
	public function var_replace($d){
		return ".".str_replace("$","\$",$d[1]);
	}
    public function get_val($val)
    {
        if (strrpos($val, '[') !== false && strpos($val,'.')!==false)
        {
            //php5.2
			$val = preg_replace_callback("/\[([^\[\]]*)\]/is", array($this, 'var_replace'), $val);
			//php5.3
			//$val = preg_replace_callback("/\[([^\[\]]*)\]/is", "self::var_replace", $val);
        }
		
		 
		
        if (strrpos($val, '|') !== false)
        {
            $moddb = explode('|', $val);
            $val = array_shift($moddb);
        }

        if (empty($val))
        {
            return '';
        }		
	
        if (strpos($val, '.$') !== false)
        {
			
            $all = explode('.$', $val);

            foreach ($all AS $key => $val)
            {
                $all[$key] = $key == 0 ? $this->make_var($val) : '['. $this->make_var($val) . ']';
            }
			 
            $p = implode('', $all);
			 
        }
        else
        {
			/*加减乘除*/
			if(empty($moddb)){				 
				if(preg_match("/[\+\-\*\/%]/i",$val,$a)){
					preg_match("/\w+/i",$val,$c);
					$p=$this->make_var($c[0]);
					$p=str_replace($c[0],$p,$val);
				}else{
					$p = $this->make_var($val);
				}
			}else{
            	$p = $this->make_var($val);
			}
        }

        if (!empty($moddb))
        {
            foreach ($moddb AS $key => $mod)
            {
                $s = explode(':', $mod);
                switch ($s[0])
                {
                    case 'escape':
                        $s[1] = trim($s[1], '"');
                        if ($s[1] == 'html')
                        {
                            $p = 'htmlspecialchars(' . $p . ')';
                        }
                        elseif ($s[1] == 'url')
                        {
                            $p = 'urlencode(' . $p . ')';
                        }
                        elseif ($s[1] == 'decode_url')
                        {
                            $p = 'urldecode(' . $p . ')';
                        }
                        elseif ($s[1] == 'quotes')
                        {
                            $p = 'addslashes(' . $p . ')';
                        }
                        else
                        {
                            $p = 'htmlspecialchars(' . $p . ')';
                        }
                        break;

                    case 'nl2br':
                        $p = 'nl2br(' . $p . ')';
                        break;

                    case 'default':
                        $s[1] = $s[1]{0} == '$' ?  $this->get_val(substr($s[1], 1)) : "'$s[1]'";
                        $p = 'empty(' . $p . ') ? ' . $s[1] . ' : ' . $p;
                        break;

                    case 'cutstr':
                        $p = '$this->cutstr(' . $p . ",$s[1],'$s[2]')";
                        break;

                    case 'strip_tags':
                        $p = 'strip_tags(' . $p . ')';
                        break;
					case 'date':
						$s=str_replace("date:","",$mod);
						$s=str_replace("@",":",$s);//兼容以前的
						$p='date("' . $s . '",'. $p .')';   
						break;
					case '+':
						$p = $p .'+'. $s[1];	
						break;
					case '-':
						$p = $p .'-'. $s[1];	
						break;
					case '*':
						$p = $p .'*'. $s[1];	
						break;
					case '/':
						$p = $p .'/'. $s[1];	
						break;
					
                    default:
                        if(function_exists($s[0])){
							if(isset($s[1])){
								$pa=str_replace($s[0].":","",$mod);
								$p=$s[0].'('.$p.',"'.$pa.'")';
							}else{
								$p=$s[0].'('.$p.')';
							}
						}
					
                        break;
                }
            }
        }
		 
        return $p;
    }
	/**
	*解析变量
	*/
	public function parse_var($str){
		$str=str_replace('$',"",$str);
		$a=explode(".",$str);
		$data='$this->_var';
		foreach($a as $v){
			if(strpos($v,"[")!==false){
				$b=explode("[",$v);
				foreach($b as $bb){
					$bb=str_replace("]","",$bb);
					if(preg_match("/\d+/",$bb)){
						$data.='['.$bb.']';
					}else{
						$data.='["'.$bb.'"]';
					}
				}
			}else{
				$data.='["'.$v.'"]';		
			}
		}
		return '".'. $data .'."';
	}

    /**
     * 处理去掉$的字符串
     *
     * @access  public
     * @param   string     $val
     *
     * @return  bool
     */
   public  function make_var($val)
    {
        if (strrpos($val, '.') === false)
        {
            if (isset($this->_var[$val]) && isset($this->_patchstack[$val]))
            {
                $val = $this->_patchstack[$val];
            }
			if(substr_count($val,"[")>0){
				$p=preg_replace('/\$(\w+)/i',"\$this->_var['\\1']","$".$val);
			}else{
            	$p = '$this->_var[\'' . $val . '\']';
			}
        }
        else
        {
            $t = explode('.', $val);
            $_var_name = array_shift($t);
            if (isset($this->_var[$_var_name]) && isset($this->_patchstack[$_var_name]))
            {
                $_var_name = $this->_patchstack[$_var_name];
            }
            if ($_var_name == 'smarty')
            {
                 $p = $this->_compile_smarty_ref($t);
            }
            else
            {
                $p = '$this->_var[\'' . $_var_name . '\']';
            }
            foreach ($t AS $val)
            {
                $p.= '[\'' . $val . '\']';
            }
        }

        return $p;
    }

    /**
     * 处理insert外部函数/需要include运行的函数的调用数据
     *
     * @access  public
     * @param   string     $val
     * @param   int         $type
     *
     * @return  array
     */
    public function get_para($val, $type = 1) // 处理insert外部函数/需要include运行的函数的调用数据
    {
        $pa = $this->str_trim($val);
        foreach ($pa AS $value)
        {
            if (strrpos($value, '='))
            {
                list($a, $b) = explode('=', str_replace(array(' ', '"', "'", '&quot;'), '', $value));
                if ($b{0} == '$')
                {
                    if ($type)
                    {
                        eval('$para[\'' . $a . '\']=' . $this->get_val(substr($b, 1)) . ';');
                    }
                    else
                    {
                        $para[$a] = $this->get_val(substr($b, 1));
                    }
                }
                else
                {
                    $para[$a] = $b;
                }
            }
        }

        return $para;
    }

    /**
     * 判断变量是否被注册并返回值
     *
     * @access  public
     * @param   string     $name
     *
     * @return  mix
     */
    public function &get_template_vars($name = null)
    {
        if (empty($name))
        {
            return $this->_var;
        }
        elseif (!empty($this->_var[$name]))
        {
            return $this->_var[$name];
        }
        else
        {
            $_tmp = null;

            return $_tmp;
        }
    }

    /**
     * 处理if标签
     *
     * @access  public
     * @param   string     $tag_args
     * @param   bool       $elseif
     *
     * @return  string
     */
    public function _compile_if_tag($tag_args, $elseif = false)
    {
        preg_match_all('/\-?\d+[\.\d]+|\'[^\'|\s]*\'|"[^"|\s]*"|[\$\w\.]+|!==|===|==|!=|<>|<<|>>|<=|>=|&&|\|\||\(|\)|,|\!|\^|=|&|<|>|~|\||\%|\+|\-|\/|\*|\@|\S/', $tag_args, $match);

        $tokens = $match[0];
        // make sure we have balanced parenthesis
        $token_count = array_count_values($tokens);
        if (!empty($token_count['(']) && $token_count['('] != $token_count[')'])
        {
            // $this->_syntax_error('unbalanced parenthesis in if statement', E_USER_ERROR, __FILE__, __LINE__);
        }

        for ($i = 0, $count = count($tokens); $i < $count; $i++)
        {
            $token = &$tokens[$i];
            switch (strtolower($token))
            {
                case 'eq':
                    $token = '==';
                    break;

                case 'ne':
                case 'neq':
                    $token = '!=';
                    break;

                case 'lt':
                    $token = '<';
                    break;

                case 'le':
                case 'lte':
                    $token = '<=';
                    break;

                case 'gt':
                    $token = '>';
                    break;

                case 'ge':
                case 'gte':
                    $token = '>=';
                    break;

                case 'and':
                    $token = '&&';
                    break;

                case 'or':
                    $token = '||';
                    break;

                case 'not':
                    $token = '!';
                    break;

                case 'mod':
                    $token = '%';
                    break;
				
                default:
                    if ($token[0] == '$')
                    {
                        $token = $this->get_val(substr($token, 1));
                    }
                    break;
            }
        }

        if ($elseif)
        {
            return '<?php elseif (' . implode(' ', $tokens) . '): ?>';
        }
        else
        {
            return '<?php if (' . implode(' ', $tokens) . '): ?>';
        }
    }

    /**
     * 处理foreach标签
     *
     * @access  public
     * @param   string     $tag_args
     *
     * @return  string
     */
   public  function _compile_foreach_start($tag_args)
    {
        $attrs = $this->get_para($tag_args, 0);
        $arg_list = array();
        $from = $attrs['from'];
        /*
		if(isset($this->_var[$attrs['item']]) && !isset($this->_patchstack[$attrs['item']]))
        {
            $this->_patchstack[$attrs['item']] = $attrs['item'] . '_' . str_replace(array(' ', '.'), '_', microtime());
            $attrs['item'] = $this->_patchstack[$attrs['item']];
        }
        else
        {
            $this->_patchstack[$attrs['item']] = $attrs['item'];
        }
		*/
		$this->_patchstack[$attrs['item']] = $attrs['item'];
        $item = $this->get_val($attrs['item']);

        if (!empty($attrs['key']))
        {
            $key = $attrs['key'];
            $key_part = $this->get_val($key).' => ';
        }
        else
        {
            $key = null;
            $key_part = '';
        }

        if (!empty($attrs['name']))
        {
            $name = $attrs['name'];
        }
        else
        {
            $name = null;
        }

        $output = '<?php ';
      //  $output .= "\$_from = $from; if (!is_array(\$_from) && !is_object(\$_from)) { settype(\$_from, 'array'); }; \$this->push_vars('$attrs[key]', '$attrs[item]');";
		
		$output .= "\$_from = $from; if (!is_array(\$_from) && !is_object(\$_from)) { \$_from=array();}; \$this->push_vars('$attrs[key]', '$attrs[item]');";

        if (!empty($name))
        {
            $foreach_props = "\$this->_foreach['$name']";
            $output .= "{$foreach_props} = array('total' => count(\$_from), 'iteration' => 0);\n";
            $output .= "if ({$foreach_props}['total'] > 0):\n";
            $output .= "    foreach (\$_from AS $key_part$item):\n";
            $output .= "        {$foreach_props}['iteration']++;\n";
        }
        else
        {
            $output .= "if (count(\$_from)):\n";
            $output .= "    foreach (\$_from AS $key_part$item):\n";
        }
        return $output . '?>';
    }

    /**
     * 将 foreach 的 key, item 放入临时数组
     *
     * @param  mixed    $key
     * @param  mixed    $val
     *
     * @return  void
     */
    public function push_vars($key, $val)
    {
        if (!empty($key))
        {
            array_push($this->_temp_key, "\$this->_vars['$key']='" .$this->_vars[$key] . "';");
        }
        if (!empty($val))
        {
            array_push($this->_temp_val, "\$this->_vars['$val']='" .$this->_vars[$val] ."';");
        }
    }

    /**
     * 弹出临时数组的最后一个
     *
     * @return  void
     */
    public function pop_vars()
    {
        $key = array_pop($this->_temp_key);
        $val = array_pop($this->_temp_val);

        if (!empty($key))
        {
            eval($key);
        }
    }

    /**
     * 处理smarty开头的预定义变量
     *
     * @access  public
     * @param   array   $indexes
     *
     * @return  string
     */
   public  function _compile_smarty_ref(&$indexes)
    {
        /* Extract the reference name. */
        $_ref = $indexes[0];

        switch ($_ref)
        {
            case 'now':
                $compiled_ref = 'time()';
                break;

            case 'foreach':
                array_shift($indexes);
                $_var = $indexes[0];
                $_propname = $indexes[1];
                switch ($_propname)
                {
                    case 'index':
                        array_shift($indexes);
                        $compiled_ref = "(\$this->_foreach['$_var']['iteration'] - 1)";
                        break;

                    case 'first':
                        array_shift($indexes);
                        $compiled_ref = "(\$this->_foreach['$_var']['iteration'] <= 1)";
                        break;

                    case 'last':
                        array_shift($indexes);
                        $compiled_ref = "(\$this->_foreach['$_var']['iteration'] == \$this->_foreach['$_var']['total'])";
                        break;

                    case 'show':
                        array_shift($indexes);
                        $compiled_ref = "(\$this->_foreach['$_var']['total'] > 0)";
                        break;

                    default:
                        $compiled_ref = "\$this->_foreach['$_var']";
                        break;
                }
                break;

            case 'get':
                $compiled_ref = '$_GET';
                break;

            case 'post':
                $compiled_ref = '$_POST';
                break;

            case 'cookies':
                $compiled_ref = '$_COOKIE';
                break;

            case 'env':
                $compiled_ref = '$_G';
                break;

            case 'server':
                $compiled_ref = '$_SERVER';
                break;

            case 'request':
                $compiled_ref = '$_REQUEST';
                break;

            case 'session':
                $compiled_ref = '$_SESSION';
                break;		 
            default:

                break;
        }
        array_shift($indexes);

        return $compiled_ref;
    }

 

    public function str_trim($str)
    {
        /* 处理'a=b c=d k = f '类字符串，返回数组 */
        while (strpos($str, '= ') != 0)
        {
            $str = str_replace('= ', '=', $str);
        }
        while (strpos($str, ' =') != 0)
        {
            $str = str_replace(' =', '=', $str);
        }

        return explode(' ', trim($str));
    }

    public function _eval($content)
    {
        ob_start();
        eval('?' . '>' . trim($content));
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

   public  function _require($filename)
    {
        ob_start();
        include $filename;
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

   

   public function cycle($arr)
    {
        static $k, $old;

        $value = explode(',', $arr['values']);
        if ($old != $value)
        {
            $old = $value;
            $k = 0;
        }
        else
        {
            $k++;
            if (!isset($old[$k]))
            {
                $k = 0;
            }
        }

        echo $old[$k];
    }

   public function make_array($arr)
    {
        $out = '';
        foreach ($arr AS $key => $val)
        {
            if ($val{0} == '$')
            {
                $out .= $out ? ",'$key'=>$val" : "array('$key'=>$val";
            }
            else
            {
                $out .= $out ? ",'$key'=>'$val'" : "array('$key'=>'$val'";
            }
        }

        return $out . ')';
    }
	
	function cutstr($string, $length, $dot = ' ...') {
		if(strlen($string) <= $length) {
			return $string;
		}
	
		$pre = chr(1);
		$end = chr(1);
		$string = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array($pre.'&'.$end, $pre.'"'.$end, $pre.'<'.$end, $pre.'>'.$end), $string);
	
		$strcut = '';
		if(strtolower($this->charset) == 'utf-8') {
	
			$n = $tn = $noc = 0;
			while($n < strlen($string)) {
	
				$t = ord($string[$n]);
				if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
					$tn = 1; $n++; $noc++;
				} elseif(194 <= $t && $t <= 223) {
					$tn = 2; $n += 2; $noc += 2;
				} elseif(224 <= $t && $t <= 239) {
					$tn = 3; $n += 3; $noc += 2;
				} elseif(240 <= $t && $t <= 247) {
					$tn = 4; $n += 4; $noc += 2;
				} elseif(248 <= $t && $t <= 251) {
					$tn = 5; $n += 5; $noc += 2;
				} elseif($t == 252 || $t == 253) {
					$tn = 6; $n += 6; $noc += 2;
				} else {
					$n++;
				}
	
				if($noc >= $length) {
					break;
				}
	
			}
			if($noc > $length) {
				$n -= $tn;
			}
	
			$strcut = substr($string, 0, $n);
	
		} else {
			for($i = 0; $i < $length; $i++) {
				$strcut .= ord($string[$i]) > 127 ? $string[$i].$string[++$i] : $string[$i];
			}
		}
	
		$strcut = str_replace(array($pre.'&'.$end, $pre.'"'.$end, $pre.'<'.$end, $pre.'>'.$end), array('&amp;', '&quot;', '&lt;', '&gt;'), $strcut);
	
		$pos = strrpos($strcut, chr(1));
		if($pos !== false) {
			$strcut = substr($strcut,0,$pos);
		}
		return $strcut.$dot;
	}
	
	/*创建文件夹*/
	public function umkdir($dir)
	{
		if(empty($dir)) return false;
		$dir=str_replace(ROOT_PATH,"",$dir);
		$arr=explode("/",$dir);
		
		foreach($arr as $key=>$val)
		{
			$d="";
			for($i=0;$i<=$key;$i++)
			{
				if($arr[$i]!=""){				
					$d.=$arr[$i]."/";
				}
			} 
			if(!file_exists(ROOT_PATH.$d))
			{ 
				mkdir(ROOT_PATH.$d,0755);
			}
		}
	}
	/**
	*数学计算
	*/
	public function _math($str){
		preg_match("/equation=\"(.*)\"/iU",$str,$a);
		$eq=$a[1];
		preg_match("/format=\"(.*)\"/iU",$str,$f);
		$format="";
		if(isset($f[1])){
			$format=$f[1];
		}
		preg_match_all("/(\w+=\"[^\"]+\")/iUs",$str,$b);
		
		$temp=array();
		if(isset($b[1])){			 
			foreach($b[1] as $v){
				$e=explode("=",$v);
				$e[1]=str_replace('"','',$e[1]);
				if(defined($e[1])){
					$temp[$e[0]]=$e[1];
				}else{
					$temp[$e[0]]=is_numeric($e[1])?$e[1]:$this->get_val(substr($e[1],1));
				}
			}
			$eq=str_replace('"',"",$eq);
			$ss= preg_replace("/(\w+)/iUs",'$temp[\\1]',$eq);						  
			 eval("\$x= \"$ss\";");
			$res=$format?'<?php echo sprintf("'.$format.'",'.$x.') ?>':'<?php echo '.$x.'?>';
			return $res;			 
		}
	}
	/*url重写*/	
	public function rewriteurl($content)
	{
		if($this->rewriterule){
		foreach($this->rewriterule as $s)
		{
			$content=preg_replace($s[0],$s[1],$content);
		}
		}
		
		return $content;
	}
	
	
}


?>