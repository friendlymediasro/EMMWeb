<?php

namespace App\Util;


use Exception;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

trait ApiConnect
{

	/**
	 * @param array $parameters
	 * @return array
	 * @throws ClientExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws TransportExceptionInterface
	 * @throws Exception
	 */
	public function getAPIResponse(array $parameters): array
	{
		$httpClient = HttpClient::create([
			'headers' => ['X-AUTH-API' => $this->getParameter('app_auth_key')],
		]);

		$response = $httpClient->request('GET', 'https://admin.bunny-holding.com/api', ['query' => $parameters,]);
		$decodedResponse = $response->toArray();
		if (isset($decodedResponse['error'])) {
			throw new Exception(sprintf('API: returned error - %s', $decodedResponse['error']));
		}

		return $decodedResponse;
	}

	/**
	 * @param $APIParameters
	 * @return array
	 * @throws ClientExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws TransportExceptionInterface
	 */
	public function getParameters($APIParameters)
	{
		if (null !== $APIParameters) {
			return $this->getAPIResponse($APIParameters);
		}
		else {
			return [];
		}
	}
}