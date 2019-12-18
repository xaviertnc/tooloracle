<?php namespace OneFile;

/**
 * File Description
 *
 * @author C. Moller <xavier.tnc@gmail.com> - 07 Jun 2014
 *
 * Licensed under the MIT license. Please see LICENSE for more information.
 *
 * @update: C. Moller - 24 December 2016
 *
 * @update: C. Moller - 05 December 2017
 *   - Add Format::nbsp()
 *   - Add/correct function @param definitions
 *
 * @update: C. Moller - 18 December 2019
 *   - Move slug(), words, firstWord, camel, studly, snake, etc. to Slug.php
 *   - Re-arrange and rename methods
 */

class Format {

	/**
	 *
	 * @param mixed $value
	 * @param array $emptyValueVariants
	 * @param mixed $defaultEmptyValue
	 * @return mixed
	 */
	public static function defaultEmptyValue($value, $emptyValueVariants = array('', 'NULL'), $defaultEmptyValue = null)
	{
		return in_array($value, $emptyValueVariants) ? $defaultEmptyValue : $value;
	}

	/**
	 *
	 * @param mixed $value
	 * @param mixed $default
	 * @param integer $decimals
	 * @param string $seperator
	 * @return string
	 */
	public static function decimal($value, $default = null, $decimals = 0, $seperator = null)
	{
		if (is_null($value)) return $default;
		return is_numeric($value) ? number_format($value, $decimals, '.', $seperator) : $value;
	}

	/**
	 *
	 * @param mixed $value
	 * @param mixed $default
	 * @param integer $decimals
	 * @param string $symbol
	 * @param string $seperator
	 * @return string
	 */
	public static function currency($value, $default = null, $decimals = 0, $symbol = 'R', $seperator = null)
	{
		if (is_null($value)) return $default;
		return is_numeric($value) ? $symbol . number_format($value, $decimals, '.', $seperator) : $value;
	}

	/**
	 * E.g.  203948123 Bytes => "???.?? MB"
	 *
	 * @param integer $size in Bytes
	 * @return type
	 */
	public static function filesize($size = null)
	{
		if ($size < 1024)
			return $size . ' B';
		elseif ($size < 1048576)
			return round($size / 1024, 2) . ' KB';
		elseif ($size < 1073741824)
			return round($size / 1048576, 2) . ' MB';
		elseif ($size < 1099511627776)
			return round($size / 1073741824, 2) . ' GB';
		else
			return round($size / 1099511627776, 2) . ' TB';
	}

	/**
	 * Limit the number of characters in a string.
	 *
	 * @param  string  $value
	 * @param  int     $limit
	 * @param  string  $end
	 * @return string
	 */
	public static function limit($value, $limit = 100, $end = '...')
	{
		if (mb_strlen($value) <= $limit) return $value;

		return rtrim(mb_substr($value, 0, $limit, 'UTF-8')).$end;
	}

	/**
	 *
	 * @param string $text
	 * @return string
	 */
	public static function noWrap($text = null)
	{
		return str_replace(' ', '&nbsp;', $text);
	}

	public static function nl2br($text)
	{
		return str_replace(["\r", "\n"], ['', '<br>'], $text);
	}

	public static function html($text)
	{
		return htmlentities($text, ENT_QUOTES | ENT_IGNORE, 'UTF-8');
	}

}
