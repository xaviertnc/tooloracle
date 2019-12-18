<?php namespace OneFile;

/**
 * File Description
 *
 * @author C. Moller <xavier.tnc@gmail.com> - 18 December 2019
 *
 * Licensed under the MIT license. Please see LICENSE for more information.
 *
 */

class Slug {

	/**
	 * Limit the number of words in a string.
	 *
	 * @param  string  $value
	 * @param  int     $words
	 * @param  string  $end
	 * @return string
	 */
	public static function limitWords($value, $words = 100, $end = '...')
	{
		$matches = array();

		preg_match('/^\s*+(?:\S++\s*+){1,'.$words.'}/u', $value, $matches);

		if ( ! isset($matches[0])) return $value;

		if (strlen($value) == strlen($matches[0])) return $value;

		return rtrim($matches[0]).$end;
	}

	/**
	 * Returns the first word/part of a string before the delimiter char.
	 *
	 * @param  string  $value
	 * @param  string  $delimiter
	 * @return string
	 */
	public static function firstWord($value, $delimiter = ' ')
	{
		return strtok($value, $delimiter);
	}

	/**
	 * Convert the given string to title case.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public static function titleCase($value)
	{
		return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
	}

	/**
	 * Convert a string to snake case.
	 *
	 * @param  string  $value
	 * @param  string  $delimiter
	 * @return string
	 */
	public static function snakeCase($value, $delimiter = '_')
	{
		$replace = '$1'.$delimiter.'$2';

		return ctype_lower($value) ? $value : strtolower(preg_replace('/(.)([A-Z])/', $replace, $value));
	}

	/**
	 * Convert a value to studly caps case.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public static function studlyCase($value, $delimiter = '')
	{
		$value = ucwords(str_replace(array('-', '_'), ' ', $value));

		return ($delimiter == ' ') ? $value : str_replace(' ', $delimiter, $value);
	}

	/**
	 * Convert a value to camel case.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public static function camelCase($value)
	{
		return lcfirst(static::studly($value));
	}

	/**
	 * Generate a URL friendly "slug" from a given string.
	 *
	 * @param  string  $title
	 * @param  string  $separator
	 * @return string
	 */
	public static function make($title, $separator = '-')
	{

		$flip = ($separator == '-') ? '_' : '-';

		$patterns = array(
			'/['.preg_quote($flip).']+/u',					// Convert all dashes/underscores into separator
			'/[^'.preg_quote($separator).'\pL\pN\s]+/u',	// Remove all characters that are not the separator, letters, numbers, or whitespace.
			'/['.preg_quote($separator).'\s]+/u'			// Replace all separator characters and whitespace by a single separator
		);

		$replacements = array($flip, '', $separator);

		foreach ($patterns as $i => $pattern)
		{
			$title = preg_replace($pattern, $replacements[$i], $title);
		}

		return trim($title, $separator);
	}

}
