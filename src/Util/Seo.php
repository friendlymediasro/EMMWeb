<?php

namespace App\Util;


use Symfony\Component\OptionsResolver\OptionsResolver;

class Seo
{

	const VAR_APPEND_DELIMITER = '#';
	const VAR_SEPARATOR = '.';
	const DELIMITER_PLACEHOLDER = '|DELIMITER|';

	public function resolveOptions($options)
	{
		$resolver = new OptionsResolver();
		$resolver->setDefaults([
			'delimiter' => '.',
			'ellipsis' => false,
			'remove_delimiter_if_last' => false,
			'trim_on_word' => true,
			'allowed_html' => true,
		]);
		$resolver->setAllowedTypes('delimiter', ['string']);
		$resolver->setAllowedTypes('ellipsis', ['boolean', 'string']);
		$resolver->setAllowedTypes('remove_delimiter_if_last', 'boolean');
		$resolver->setAllowedTypes('trim_on_word', 'boolean');
		$resolver->setAllowedTypes('allowed_html', ['boolean']);

		return $resolver->resolve($options);
	}

	/**
	 * @param $item
	 * @param $content
	 * @param $options
	 * @return bool|mixed|string
	 */
	public function makeMagic($item, $content, $options)
	{
		//first replace blocks
		//if any variable in block is null/false/empty skip all block
		preg_match_all('/{% (.*?) %}/', $content, $matches);
		if ($matches !== false && !empty($matches)) {
			$stringBlocks = $matches[0];
			$blocks = $matches[1];
			foreach ($blocks as $order => $block) {
				$blockContent = $this->replaceVariables($item, $block, $options, true);
				$content = Functions::strReplaceFirstMatch($stringBlocks[$order], $content, $blockContent);
			}
		}

		//second replace variables
		$content = $this->replaceVariables($item, $content, $options);
		$content = trim($content);
		if (true === $options['remove_delimiter_if_last'] && substr($content, -strlen(self::DELIMITER_PLACEHOLDER)) === self::DELIMITER_PLACEHOLDER) {
			//remove delimiter if it is last group of characters
			$content = substr($content, 0, strrpos($content,self::DELIMITER_PLACEHOLDER));
		}

		//todo possible RTL bug, book 10,000 as example, russian chars are good, 10021 armenian lang is ok ..
		$content = str_replace(self::DELIMITER_PLACEHOLDER, $options['delimiter'], $content);

		//cleanup from unused/empty variables and spaces they could add
		$content = trim(preg_replace('/\s+/', ' ', $content));

		return $content;
	}

	/**
	 * @param      $item
	 * @param      $content
	 * @param      $options
	 * @param bool $strict
	 * @return mixed|string
	 */
	private function replaceVariables($item, $content, $options, $strict = false)
	{
		preg_match_all('/{{ (.*?) }}/', $content, $matches);
		if ($matches !== false && !empty($matches)) {
			$stringVariables = $matches[0];
			$variables = $matches[1];
			foreach ($variables as $order => $variable) {
				$limit = null;
				$replace = '';
				$appendDelimiter = false;
				$parts = [];

				//if parts[1] is set we are looking for array, if not then it is just single value
				if (strpos($variable, self::VAR_SEPARATOR) !== false) {
					$parts = explode(self::VAR_SEPARATOR, $variable);
				}
				else {
					$parts[0] = $variable;
				}

				//check if we are going to append delimiter or not
				if (substr($parts[0], 0, strlen(self::VAR_APPEND_DELIMITER)) == self::VAR_APPEND_DELIMITER) {
					$appendDelimiter = true;
					$parts[0] = substr($parts[0], strlen(self::VAR_APPEND_DELIMITER));
				}

				//check for limit on parts[0]
				//if isset parts[1] it means limit is on number of array elements otherwise it is limit of number of characters
				//limitation => can't limit number of characters on array element, i.e. rating, author name, genres
				preg_match('/\[(\d+)\]/', $parts[0], $matches);
				if ($matches !== false && !empty($matches)) {
					//store limit
					$limit = $matches[1];
					//remove limit from variable
					$parts[0] = Functions::strReplaceFirstMatch($matches[0], $parts[0], '');
				}

				//check if we know or have this variable on item
				if (isset($item[$parts[0]]) && !empty($item[$parts[0]])) {
					//we expect here only array or string
					if (is_array($item[$parts[0]])) {
						if (isset($limit)) {
							$array = array_slice($item[$parts[0]],0, $limit);
							//filter out if key does not exists or if is it empty/null
							$array = array_filter($array, function ($a) use ($parts) {
								return isset($a[$parts[1]]) && !empty($a[$parts[1]]);
							});
							$replace = implode(', ', array_map(
								function($a) use ($parts) {
									return $a[$parts[1]];
								},
								$array
							));
						}
						else {
							if (isset($item[$parts[0]][$parts[1]])) {
								$replace = $item[$parts[0]][$parts[1]];
							}
						}
					}
					elseif (is_string($item[$parts[0]])) {
						if (isset($limit)) {
							if ($options['trim_on_word'] === true) {
								$replace = Functions::trimOnWord($item[$parts[0]], $limit, $options['ellipsis']);
							}
							else {
								$replace = Functions::trimOnChar($item[$parts[0]], $limit, $options['ellipsis']);
							}
						}
						else {
							$replace = $item[$parts[0]];
						}
					}
				}

				if (true === $strict && empty($replace)) {
					//i.e. false, '', 0, [], null
					//strict is true in case of block, block is empty if any variable replace in it is empty
					return '';
				}

				if (true === $appendDelimiter && !empty($replace)) {
					$replace = sprintf('%s%s', $replace, self::DELIMITER_PLACEHOLDER);
				}
				//do the replace in string
				$content = Functions::strReplaceFirstMatch($stringVariables[$order], $content, (string) $replace);
			}
		}

		return $content;
	}

}