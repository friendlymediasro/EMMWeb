<?php

namespace EMMWeb\Util;

use Exception;
use Symfony\Component\Routing\RouterInterface;

class Schema
{

	const NICHE_MOVIE_TYPES = [
		1 => 'Movie',
		2 => 'Episode',
		3 => 'TVSeries',
	];

	protected $router;

	public function __construct(RouterInterface $router)
	{
		$this->router = $router;
	}

	/**
	 * @param string $niche
	 * @return bool
	 */
	private function supportedNiche(string $niche): bool
	{
		if (in_array($niche, ['Book', 'Movie',])) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * @param        $item
	 * @param string $niche
	 * @param string $routeName
	 * @return false|string
	 * @throws Exception
	 */
	public function getStructuredData($item, string $niche, string $routeName)
	{
		if (false === $this->supportedNiche($niche)) {
			return false;
		}

		$url = $this->router->generate($routeName, ['id' => $item['id'], 'slug' => $item['slug']], $this->router::ABSOLUTE_URL);
		$structuredData = call_user_func(array(__NAMESPACE__ .'\Schema', sprintf('schema%s', $niche)), $item, $url);

		$json = json_encode($structuredData);
		if (false === $json) {
			throw new Exception(sprintf('Invalid structured data JSON - item "%s", niche "%s".', $item['id'], $niche));
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

		$rating = $this->aggregateRating($item);
		if (null !== $rating) {
			$workExample = array_merge($workExample, $rating);
		}

		$structuredData["workExample"][] = $workExample;

		return $structuredData;
	}

	/**
	 * https://schema.org/Movie
	 * https://schema.org/TVSeries
	 * https://developers.google.com/search/docs/data-types/movie
	 *
	 * @param array  $item
	 * @param string $url
	 * @return array
	 */
	private function schemaMovie(array $item, string $url): array
	{
		if (!empty($item['category'])) {
			if (in_array($item['category'], ['TV Episode'])) {
				$type = Schema::NICHE_MOVIE_TYPES[2];
			}
			elseif (in_array($item['category'], ['TV Series', 'TV Mini-Series'])) {
				$type = Schema::NICHE_MOVIE_TYPES[3];
			}
			elseif (in_array($item['category'], ['Movie', 'TV Short'])) {
				$type = Schema::NICHE_MOVIE_TYPES[1];
			}
		}

		if (!isset($type)) {
			return [];
		}

		$structuredData = [
			"@context" => "https://schema.org",
			"@type" => $type,
			"url" => $url,
		];

		if (!empty($item['name'])) {
			$structuredData['name'] = $item['name'];
		}

		if (!empty($item['description'])) {
			$structuredData['description'] = $item['description'];
		}

		if (!empty($item['cast'])) {
			foreach ($item['cast'] as $person) {
				$role = null;
				if ($person['role'] == 'star') {
					$role = 'actor';
				}
				elseif ($person['role'] == 'director') {
					$role = 'director';
				}
				elseif (in_array($person['role'], ['creator', 'writer'])) {
					$role = 'creator';
				}

				if (null !== $role) {
					$structuredData[$role][] = [
						"@type" => "Person",
						'name' => $person['person']['name'],
					];
				}

				/* http://prntscr.com/p7oqjy Himself/Herself issue?
				 * if (null !== $person['characterName']) {
					$structuredData['character'][] = [
						"@type" => "Person",
						'name' => $person['characterName'],
					];
				}*/
			}
		}

		if (!empty($item['companies'])) {
			foreach ($item['companies'] as $company) {
				$structuredData['productionCompany'][] = [
					"@type" => "Organization",
					'name' => $company['name'],
				];
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

		if (!empty($item['contentRating'])) {
			$structuredData["contentRating"] = $item['contentRating'];
		}

		$rating = $this->aggregateRating($item);
		if (null !== $rating) {
			$structuredData = array_merge($structuredData, $rating);
		}

		if ($type == Schema::NICHE_MOVIE_TYPES[1]) {
			if (!empty($item['duration'])) {
				$structuredData["duration"] = sprintf('PT%dM', $item['duration']);
			}
		}
		elseif ($type == Schema::NICHE_MOVIE_TYPES[3]) {
			if (!empty($item['listSeasonsWithEpisodes'])) {
				$structuredData['numberOfSeasons'] = count($item['listSeasonsWithEpisodes']);
				$structuredData['numberOfEpisodes'] = array_sum(array_map("count", $item['listSeasonsWithEpisodes']));
			}
		}
		elseif ($type == Schema::NICHE_MOVIE_TYPES[2]) {
			if (!empty($item['episodeParent'])) {
				$structuredData['partOfSeries'] = [
					"@type" => 'TVSeries',
					'name' => $item['episodeParent']['name'],
				];
			}

			if (!empty($item['episode'])) {
				$structuredData['episodeNumber'] = $item['episode'];
			}

			if (!empty($item['season'])) {
				$structuredData['partOfSeason'] = [
					'@type' => "TVSeason",
					'seasonNumber' => $item['season'],
				];
			}
		}

		return $structuredData;
	}

	/**
	 * @param $item
	 * @return array|null
	 */
	private function aggregateRating($item)
	{
		if (!empty($item['rating']['weight']) && !empty($item['rating']['value'])) {
			return ["aggregateRating" => [
				"@type" => "AggregateRating",
				"ratingCount" => $item['rating']['weight'],
				"bestRating" => $item['rating']['scale'],
				"ratingValue" => $item['rating']['value'],
			]];
		}

		return null;
	}
}