<?php

namespace EMMWeb\Util;

class Functions
{

	public static function getPublicDirectory($projectDir)
	{
		$defaultPublicDir = 'public';

		if (null === $projectDir) {
			return $defaultPublicDir;
		}

		$composerFilePath = $projectDir.'/composer.json';
		if (!file_exists($composerFilePath)) {
			return $defaultPublicDir;
		}

		$composerConfig = json_decode(file_get_contents($composerFilePath), true);

		if (isset($composerConfig['extra']['public-dir'])) {
			return $composerConfig['extra']['public-dir'];
		}

		return $defaultPublicDir;
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
	 * @param string $string
	 * @return string
	 * @throws \Exception
	 */
	public static function cleanupStringFromHtml(string $string)
	{
		//cleanup text from <br><br> and replace it by space
		if (strpos($string, '<br>') !== false) {
			$string = preg_replace('/(<br>)+/', ' ', $string);
			if (null === $string) {
				throw new \Exception('preg_replace error occured.');
			}
		}
		//cleanup for other possible html tags, <a>,<i>,<strong> etc..
		$string = strip_tags($string);

		return $string;
	}
}