<?php

namespace EMMWeb\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use EMMWeb\Util\ApiConnect;
use EMMWeb\Util\HttpCache;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use WyriHaximus\HtmlCompress\Factory;

/**
 * Provides common features needed in controllers.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
abstract class AppAbstractController extends AbstractController
{

	use HttpCache;
	use ApiConnect;

	/**
	 * Symfony has getParameter function in AbstractController but not hasParameter function
	 * @param string $name
	 * @return bool
	 */
	protected function hasParameter(string $name)
	{
		if (!$this->container->has('parameter_bag')) {
			throw new ServiceNotFoundException('parameter_bag', null, null, [], sprintf('The "%s::getParameter()" method is missing a parameter bag to work properly. Did you forget to register your controller as a service subscriber? This can be fixed either by using autoconfiguration or by manually wiring a "parameter_bag" in the service locator passed to the controller.', \get_class($this)));
		}

		return $this->container->get('parameter_bag')->has($name);
	}


	/**
	 * @param Response|null $response
	 * @param string        $template
	 * @param array         $parameters
	 * @return Response
	 */
	protected function renderCompressed(?Response $response, string $template, array $parameters): Response
	{
		$response = $this->render(sprintf('@theme/%s', $template), $parameters, $response);
		return $this->compressContent($response);
	}


	/**
	 * @param Response $response
	 * @return Response
	 */
	protected function compressContent(Response $response): Response
	{
		$parser = Factory::construct();
		$compressedHtml = $parser->compress($response->getContent());
		//remove html comments
		$compressedHtml = preg_replace('/<!--(.|\s)*?-->/', '', $compressedHtml);
		$response->setContent($compressedHtml);

		return $response;
	}
}
