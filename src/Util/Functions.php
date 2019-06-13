<?php

namespace App\Util;

class Functions
{

	/**
	 * @param $string
	 * @param $wordsLimit
	 * @param $ellipses
	 * @return string|string[]|null
	 */
	public static function trimOnWord($string, $wordsLimit, $ellipses)
	{
		$string = self::cleanupStringFromHtml($string);
		$words = explode(' ', $string);
		if (count($words) <= $wordsLimit) {
			return $string;
		}

		$trimmedString = implode(' ', array_slice($words, 0, $wordsLimit));
		//add ellipses (...)
		if (false !== $ellipses) {
			$trimmedString .= $ellipses;
		}

		return $trimmedString;
	}

	/**
	 * @param $string
	 * @param $charsLimit
	 * @param $ellipses
	 * @return bool|string|string[]|null
	 */
	public static function trimOnChar($string, $charsLimit, $ellipses)
	{
		$string = self::cleanupStringFromHtml($string);
		if (strlen($string) <= $charsLimit) {
			return $string;
		}

		//find last space within length
		$lastSpace = strrpos(substr($string, 0, $charsLimit), ' ');
		$trimmedString = substr($string, 0, $lastSpace);
		//add ellipses (...)
		if (false !== $ellipses) {
			$trimmedString .= $ellipses;
		}

		return $trimmedString;
	}

	/**
	 * @param string $search
	 * @param string $subject
	 * @param string $replace
	 * @return mixed|string
	 */
	public static function strReplaceFirstMatch(string $search, string $subject, string $replace)
	{
		//do the replace in string
		$pos = strpos($subject, $search);
		if ($pos !== false) {
			$subject = substr_replace($subject, $replace, $pos, strlen($search));
		}

		return $subject;
	}

	/**
	 * @param $string
	 * @return string|string[]|null
	 */
	private static function cleanupStringFromHtml($string)
	{
		//cleanup text from <br><br> and replace it by space
		if (strpos($string, '<br>') !== false) {
			$string = preg_replace('/(<br>)+/', ' ', $string);
		}
		//cleanup for other possible html tags, <a>,<i>,<strong> etc..
		$string = strip_tags($string);

		return $string;
	}
}