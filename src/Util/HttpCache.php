<?php

namespace App\Util;

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait HttpCache
{

	/**
	 * @param Response $response
	 * @return bool
	 */
	public function isHit(Response $response)
	{
		return $response->getStatusCode() == 304;
	}


	/**
	 * @param Request $request
	 * @param string  $template
	 * @param array   $parameters
	 * @return Response
	 * @throws InvalidArgumentException
	 */
	public function renderCachedResponse(Request $request, string $template, array $parameters)
	{
		if ($this->isResponseCacheable()) {
			$ETag = $this->createETag($template, $parameters);
			$response = new Response();
			$response->setVary('Accept-Encoding');
			$response->setEtag($ETag, true);
			$response->setPublic(); // make sure the response is public/cacheable
			if ($response->isNotModified($request)) {
				return $response;
			}
		}

		return $this->renderCompressed($response ?? null, $template, $parameters);
	}


	/**
	 * @param string $template
	 * @param array  $parameters
	 * @return string
	 * @throws InvalidArgumentException
	 */
	private function createETag(string $template, array $parameters): string
	{
		$cache = new PhpArrayAdapter(
		// single file where values are cached
			$this->getParameter('kernel.cache_dir') . '/etag.cache',
			// a backup adapter, if you set values after warmup
			new FilesystemAdapter()
		);
		$item = $cache->getItem('etag-token');
		if (!$item->isHit()) {
			$cache->warmUp(['etag-token' => md5(microtime())]);
			$item = $cache->getItem('etag-token');
		}

		return md5($template . json_encode($parameters) . $item->get());
	}

	/**
	 * @return bool
	 */
	public function isResponseCacheable()
	{
		// || 'prod' !== $this->getParameter('kernel.environment')
		if (true === $this->getParameter('app_http_cache') && true === $this->getParameter('global_http_cache')) {
			return true;
		}
		else {
			return false;
		}
	}

}