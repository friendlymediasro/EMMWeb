<?php

namespace App\Controller;

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class AppController extends AppAbstractController
{


	/**
	 * @param Request $request
	 * @param         $template
	 * @param null    $APIParameters
	 * @return Response
	 * @throws ClientExceptionInterface
	 * @throws InvalidArgumentException
	 * @throws RedirectionExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws TransportExceptionInterface
	 */
	public function page(Request $request, $template, $APIParameters = null)
	{
		$parameters = $this->getParameters($APIParameters);
		return $this->renderCachedResponse($request, $template, $parameters);

	}

	/** any item of any api source is using this function // can be book, author, movie, episode
	 * it is required to provide item as array, contains id and slug
	 * $_route decides what item we are getting from api and what template is going to be used
	 * $_route can't be changed quickly ONLY because it is used in template {{ path() ]}, {{ url() }} functions
	 * @param         $id
	 * @param         $slug
	 * @param         $template
	 * @param         $APIParameters
	 * @param Request $request
	 * @return RedirectResponse|Response
	 * @throws ClientExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws TransportExceptionInterface
	 * @throws InvalidArgumentException
	 */
	public function item(Request $request, $template, $APIParameters, $id, $slug)
	{
		//create etag also with current $slug because redirect has to happen first or error need to be displayed
		$APIParameters['item']['id'] = $id;
		$parameters = $this->getParameters($APIParameters);
		if ($id != $parameters['item']['id'] || $slug != $parameters['item']['slug']) {
			throw $this->createNotFoundException();
		}

		return $this->renderCachedResponse($request, $template, $parameters);
	}
}