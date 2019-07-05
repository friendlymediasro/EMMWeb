<?php

namespace EMMWeb\Util;

use Exception;
use Symfony\Component\Routing\RouterInterface;

class Schema
{

	protected $router;

	public function __construct(RouterInterface $router)
	{
		$this->router = $router;
	}

	/**
	 * @param string $type
	 * @return bool
	 */
	public function supportedType(string $type): bool
	{
		if (in_array($type, ['book', 'video'])) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * @param        $item
	 * @param string $type
	 * @param string $routeName
	 * @return false|string
	 * @throws Exception
	 */
	public function getStructuredData($item, string $type, string $routeName)
	{
		if (false === $this->supportedType($type)) {
			return false;
		}

		$url = $this->router->generate($routeName, ['id' => $item['id'], 'slug' => $item['slug']], $this->router::ABSOLUTE_URL);
		$structuredData = call_user_func(array(__NAMESPACE__ .'\Schema', sprintf('schema%s', ucfirst($type))), $item, $url);

		$json = json_encode($structuredData);
		if (false === $json) {
			throw new Exception(sprintf('Invalid structured data JSON - item "%s", type "%s".', $item['id'], $type));
		}

		return $json;
	}

	/**
	 * Get structured data with following rules below
	 * https://schema.org/Book
	 * https://developers.google.com/search/docs/data-types/book
	 *
	 * @param array $item
	 * @param string $url
	 * @return array
	 */
	private function schemaBook(array $item, string $url): array
	{
		if (!empty($item['format']) && isset($item['format']['name']) && $item['format']['name'] == 'AudioBook') {
			$type = 'Audiobook';
		}
		else {
			$type = 'Book';
		}
		$structuredData = [
			"@context" => "https://schema.org",
			"@type" => $type,
			"url" => $url,
		];

		if (!empty($item['name'])) {
			$structuredData["name"] = $item['name'];
		}

		if (!empty($item['persons'])) {
			foreach ($item['persons'] as $author) {
				if (isset($author['name'])) {
					$author = [
						"@type" => "Person",
						"name" => $author['name'],
					];
					$structuredData["author"][] = $author;
				}
			}
		}

		if (!empty($item['characters'])) {
			foreach ($item['characters'] as $character) {
				if (isset($character['name'])) {
					$character = [
						"@type" => "Person",
						"name" => $character['name'],
					];
					$structuredData["character"][] = $character;
				}
			}
		}

		if (!empty($item['awards'])) {
			foreach ($item['awards'] as $award) {
				if (isset($award['name'])) {
					$structuredData["awards"][] = $award['name'];
				}
			}
		}

		if (!empty($item['genres'])) {
			foreach ($item['genres'] as $genre) {
				if (isset($genre['name'])) {
					$structuredData["genre"][] = $genre['name'];
				}
			}
		}

		//todo can be added to $workExample but this source have it in wrong formats, it is not easy to set..
		//"datePublished" => "", //	2019-05-25 https://en.wikipedia.org/wiki/ISO_8601
		//"inLanguage" => "", //en / es
		$workExample = [
			"@type" => $type,
			"url" => $url,
		];
		if (!empty($item['format']) && isset($item['format']['name']) && in_array($item['format']['name'], ['Paperback', 'Hardcover', 'EBook', 'AudioBook'])) {
			$workExample["bookFormat"] = sprintf('https://schema.org/%s', $item['format']['name']);
		}
		if (!empty($item['pages'])) {
			$workExample["numberOfPages"] = $item['pages'];
		}
		if (!empty($item['isbn10']) || !empty($item['isbn13'])) {
			$workExample["isbn"] = $item['isbn13'] ?? $item['isbn10'];
		}

		if (!empty($item['rating']['weight']) && !empty($item['rating']['value'])) {
			$workExample["aggregateRating"] = [
				"@type" => "AggregateRating",
				"ratingCount" => $item['rating']['weight'],
				"bestRating" => $item['rating']['scale'],
				"ratingValue" => $item['rating']['value'],
			];
		}

		$structuredData["workExample"][] = $workExample;

		return $structuredData;
	}


	private function schemaVideo(array $item, string $url): array
	{
		//todo use for movie/tv show/episode .. type, rating, author, actor, episode, containsSeason ..
		$structuredData = [
			"@context" => "https://schema.org",
			"url" => $url,
		];

		if (!empty($item['name'])) {
			$structuredData["name"] = $item['name'];
		}

		return $structuredData;
	}
}