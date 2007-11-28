<?php /*********************************************************************
 *
 *   Copyright : (C) 2006 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/lgpl.txt GNU/LGPL
 *
 *   This library is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Lesser General Public
 *   License as published by the Free Software Foundation; either
 *   version 3 of the License, or (at your option) any later version.
 *
 ***************************************************************************/


/*
 * Partial mbstring implementation in pure PHP
 *
 * Not implemented:

mb_check_encoding             - Check if the string is valid for the specified encoding
mb_convert_kana               - Convert "kana" one from another ("zen-kaku", "han-kaku" and more)
mb_convert_variables          - Convert character code in variable(s)
mb_decode_numericentity       - Decode HTML numeric string reference to character
mb_detect_encoding            - Detect character encoding
mb_detect_order               - Set/Get character encoding detection order
mb_encode_numericentity       - Encode character to HTML numeric string reference
mb_ereg_match                 - Regular expression match for multibyte string
mb_ereg_replace               - Replace regular expression with multibyte support
mb_ereg_search_getpos         - Returns start point for next regular expression match
mb_ereg_search_getregs        - Retrieve the result from the last multibyte regular expression match
mb_ereg_search_init           - Setup string and regular expression for multibyte regular expression match
mb_ereg_search_pos            - Return position and length of matched part of multibyte regular expression for predefined multibyte string
mb_ereg_search_regs           - Returns the matched part of multibyte regular expression
mb_ereg_search_setpos         - Set start point of next regular expression match
mb_ereg_search                - Multibyte regular expression match for predefined multibyte string
mb_ereg                       - Regular expression match with multibyte support
mb_eregi_replace              - Replace regular expression with multibyte support ignoring case
mb_eregi                      - Regular expression match ignoring case with multibyte support
mb_get_info                   - Get internal settings of mbstring
mb_http_input                 - Detect HTTP input character encoding
mb_http_output                - Set/Get HTTP output character encoding
mb_language                   - Set/Get current language
mb_list_encodings_alias_names - Returns an array of all supported alias encodings
mb_list_mime_names            - Returns an array or string of all supported mime names
mb_output_handler             - Callback function converts character encoding in output buffer
mb_preferred_mime_name        - Get MIME charset string
mb_regex_encoding             - Returns current encoding for multibyte regex as string
mb_regex_set_options          - Set/Get the default options for mbregex functions
mb_send_mail                  - Send encoded mail
mb_split                      - Split multibyte string using regular expression
mb_strcut                     - Get part of string
mb_strimwidth                 - Get truncated string with specified width
mb_strwidth                   - Return width of string


 * Implemented:

mb_convert_encoding     - Convert character encoding
mb_decode_mimeheader    - Decode string in MIME header field
mb_encode_mimeheader    - Encode string for MIME header XXX NATIVE IMPLEMENTATION IS REALLY BUGGED
mb_convert_case         - Perform case folding on a string
mb_internal_encoding    - Set/Get internal character encoding
mb_list_encodings       - Returns an array of all supported encodings
mb_strlen               - Get string length
mb_strpos               - Find position of first occurrence of string in a string
mb_strrpos              - Find position of last occurrence of a string in a string
mb_strtolower           - Make a string lowercase
mb_strtoupper           - Make a string uppercase
mb_substitute_character - Set/Get substitution character
mb_substr               - Get part of string

 */


define('MB_OVERLOAD_MAIL',   1);
define('MB_OVERLOAD_STRING', 2);
define('MB_OVERLOAD_REGEX',  4);
define('MB_CASE_UPPER', 0);
define('MB_CASE_LOWER', 1);
define('MB_CASE_TITLE', 2);


function mb_convert_encoding($str, $to_encoding, $from_encoding = INF) {return utf8_mbstring_500::convert_encoding($str, $to_encoding, $from_encoding);}
function mb_decode_mimeheader($str) {return utf8_mbstring_500::decode_mimeheader($str);}
function mb_encode_mimeheader($str, $charset = INF, $transfer_encoding = INF, $linefeed = INF, $indent = INF)
{
	return utf8_mbstring_500::encode_mimeheader($str, $charset, $transfer_encoding, $linefeed, $indent);
}

function mb_convert_case($str, $mode, $encoding = INF) {return utf8_mbstring_500::convert_case($str, $mode, $encoding);}
function mb_internal_encoding($encoding = INF)         {return utf8_mbstring_500::internal_encoding($encoding);}
function mb_list_encodings()                           {return utf8_mbstring_500::list_encodings();}

if (extension_loaded('xml'))
{
	function mb_strlen($str, $encoding = INF) {return utf8_mbstring_500::strlen ($str, $encoding);}
}
else
{
	function mb_strlen($str, $encoding = INF) {return utf8_mbstring_500::strlen2($str, $encoding);}
}

function mb_strpos ($haystack, $needle, $offset = 0, $encoding = INF)    {return utf8_mbstring_500::strpos ($haystack, $needle, $offset, $encoding);}
function mb_strrpos($haystack, $needle, $offset = 0, $encoding = INF)    {return utf8_mbstring_520::strrpos($haystack, $needle, $offset, $encoding);}
function mb_strtolower($str, $encoding = INF)                            {return utf8_mbstring_500::strtolower($str, $encoding);}
function mb_strtoupper($str, $encoding = INF)                            {return utf8_mbstring_500::strtoupper($str, $encoding);}
function mb_substitute_character($char = INF)                            {return utf8_mbstring_500::substitute_character($char);}
function mb_substr($str, $start, $length = PHP_INT_MAX, $encoding = INF) {return utf8_mbstring_500::substr($str, $start, $length, $encoding);}



class utf8_mbstring_500
{
	static function convert_encoding($s, $to_encoding, $from_encoding = INF)
	{
		return iconv(INF !== $from_encoding ? $from_encoding : 'UTF-8', $to_encoding . '//IGNORE', $s);
	}

	static function decode_mimeheader($s)
	{
		return iconv_mime_decode($s);
	}

	static function encode_mimeheader($s, $charset = INF, $transfer_encoding = INF, $linefeed = INF, $indent = INF)
	{
		trigger_error('mb_encode_mimeheader() is bugged. Please use iconv_mime_encode() instead.');
	}


	static function convert_case($s, $mode, $encoding = INF)
	{
		if ('' === $s) return '';

		switch ($mode)
		{
		case MB_CASE_TITLE:
			self::$encoding = $encoding;
			return preg_replace_callback('/\b\p{Ll}/u', array(__CLASS__, 'title_case_callback'), $s);

		case MB_CASE_UPPER:
			static $upper;
			isset($upper) || $upper = self::loadCaseTable(1);
			$map =& $upper;
			break;

		case MB_CASE_LOWER:
		default:
			static $lower;
			isset($lower) || $lower = self::loadCaseTable(0);
			$map =& $lower;
		}

		static $utf_len_mask = array("\xC0" => 2, "\xD0" => 2, "\xE0" => 3, "\xF0" => 4);

		$i = 0;
		$len = strlen($s);

		while ($i < $len)
		{
			$utf_len = $s[$i] < "\x80" ? 1 : $utf_len_mask[$s[$i] & "\xF0"];
			$utf_chr = substr($s, $i, $utf_len);
			$i += $utf_len;

			if (isset($map[$utf_chr]))
			{
				$utf_chr = $map[$utf_chr];
				$new_len = strlen($utf_chr);

				if ($new_len == $utf_len)
				{
					$new_len = $i;
					do $s[--$new_len] = $utf_chr[--$utf_len];
					while ($utf_len);
				}
				else
				{
					$s = substr_replace($s, $utf_chr, $i, $utf_len);
					$len += $new_len - $utf_len;
					$i   += $new_len - $utf_len;
				}
			}
		}

		return $s;
	}

	static function internal_encoding($encoding = INF)
	{
		return INF !== $encoding ? 'UTF-8' === strtoupper($encoding) : 'UTF-8';
	}

	static function list_encodings()
	{
		return array('UTF-8');
	}

	static function strlen($s, $encoding = INF)
	{
		return strlen(utf8_decode($s));
	}

	static function strlen2($s, $encoding = INF)
	{
		// Quickest alternative if utf8_decode() is not available:
		preg_replace('/./us', '', $s, -1, $s);
		return $s;
	}

	static function strpos($haystack, $needle, $offset = 0, $encoding = INF)
	{
		if ($offset = (int) $offset) $haystack = self::substr($haystack, $offset);
		$pos = strpos($haystack, $needle);
		return false === $pos ? false : ($offset + ($pos ? self::strlen(substr($haystack, 0, $pos)) : 0));
	}

	static function strrpos($haystack, $needle, $encoding = INF)
	{
		$needle = self::substr($needle, 0, 1, $encoding);
		$pos = strpos(strrev($haystack), strrev($needle));
		return false === $pos ? false : self::strlen($pos ? substr($haystack, 0, -$pos) : $haystack, $encoding);
	}

	static function strtolower($s, $encoding = INF)
	{
		return self::convert_case($s, MB_CASE_LOWER, $encoding);
	}

	static function strtoupper($s, $encoding = INF)
	{
		return self::convert_case($s, MB_CASE_UPPER, $encoding);
	}

	static function substitute_character($c = INF)
	{
		return INF !== $c ? false : 'none';
	}

	static function substr($s, $start, $length = PHP_INT_MAX, $encoding = INF)
	{
		$slen = self::strlen($s);
		$start = (int) $start;

		if (0 > $start) $start += $slen;
		if (0 > $start) $start = 0;
		if ($start >= $slen) return '';

		$rx = $slen - $start;

		else if (0 > $length) $length += $rx;
		if (0 >= $length) return '';

		if ($length > $slen - $start) $length = $rx;

		$rx = '/^' . ($start ? self::preg_offset($start) : '') . '(' . self::preg_offset($length) . ')/u';

		return preg_match($rx, $s, $s) ? $s[1] : '';
	}

	protected static function preg_offset($offset)
	{
		$rx = array();
		$offset = (int) $offset;

		while ($offset > 65535)
		{
			$rx[] = '.{65535}';
			$offset -= 65535;
		}

		return implode('', $rx) . '.{' . $offset . '}';
	}

	protected static function loadCaseTable($upper)
	{
		return unserialize(file_get_contents(
			$upper
				? resolvePath('data/utf8/upperCase.ser')
				: resolvePath('data/utf8/lowerCase.ser')
		));
	}

	protected static $encoding;
	protected static function title_case_callback($s)
	{
		$s = self::convert_case($s[0], MB_CASE_UPPER, self::$encoding);

		$len = strlen($s);
		for ($i = 1; $i < $len && $s[$i] < "\x80"; ++$i) $s[$i] = strtolower($s[$i]);

		return $s;
	}
}