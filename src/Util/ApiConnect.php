<?php

namespace EMMWeb\Util;

use Exception;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

trait ApiConnect
{

	/**
	 * @param array  $parameters
	 * @param string $host
	 * @return array
	 * @throws ClientExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws TransportExceptionInterface
	 * @throws Exception
	 */
	public function getAPIResponse(array $parameters, string $host): array
	{
		$httpClient = HttpClient::create([
			'headers' => [
				'X-AUTH-API' => $this->getParameter('app_auth_key'),
				'Referer' => $host,
			],
		]);

		$response = $httpClient->request('GET', 'https://affwebgen.com/api', ['query' => $parameters,]);
		$decodedResponse = $response->toArray();
		if (isset($decodedResponse['error'])) {
			throw new Exception(sprintf('API: returned error - %s', $decodedResponse['error']));
		}

		return $decodedResponse;
	}

	/**
	 * @param         $APIParameters
	 * @param Request $request
	 * @return array
	 * @throws ClientExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws TransportExceptionInterface
	 */
	public function getParameters($APIParameters, Request $request)
	{
		if (null !== $APIParameters) {
			return $this->getAPIResponse($APIParameters, $request->getHttpHost());
		}
		else {
			return [];
		}
	}
}