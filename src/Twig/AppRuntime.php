<?php

namespace EMMWeb\Twig;

use EMMWeb\Util\Functions;
use EMMWeb\Util\Schema;
use EMMWeb\Util\Theme;
use Hracik\CreateAvatarFromText\CreateAvatarFromText;
use Hracik\CreateImageFromText\CreateImageFromText;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\RuntimeExtensionInterface;
use Twig\Markup;

class AppRuntime implements RuntimeExtensionInterface
{

	const DELIMITER_PLACEHOLDER = '|DELIMITER|';
	const ELLIPSIS_PLACEHOLDER = '|ELLIPSIS|';

	protected $environment;
	protected $parameterBag;
	protected $requestStack;
	protected $translator;
	protected $schema;
	protected $router;

	public function __construct(Environment $environment, ParameterBagInterface $parameterBag, RouterInterface $router, RequestStack $requestStack, Schema $schema, TranslatorInterface $translator)
	{
		$this->environment = $environment;
		$this->translator = $translator;
		$this->parameterBag = $parameterBag;
		$this->schema = $schema;
		$this->requestStack = $requestStack;
		$this->router = $router;
	}

	/**
	 * @param             $template
	 * @param array       $context
	 * @return string
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @throws SyntaxError
	 */
	public function renderFromString($template, array $context = [])
	{
		$template =	$this->environment->createTemplate((string) $template);
		$content = $this->environment->render($template, $context);

		return $content;
	}

	/**
	 * @param $route
	 * @return bool
	 */
	public function routeExists($route)
	{
		return null !== $this->router->getRouteCollection()->get($route);
	}

	/**
	 * @param null $email
	 * @return mixed
	 * @throws \Exception
	 */
	public function displayEmail($email = null)
	{
		if (null === $email) {
			$email = $this->parameterBag->get('app_email');
		}

		if ($this->parameterBag->get('app_email_obfuscate') !== false) {
			$html = CreateImageFromText::createImageFromText($email, 0, 10, 12, null, CreateImageFromText::RETURN_BASE64_IMG);
		}
		else {
			$html = sprintf('<a href="mailto:%s">%s</a>', $email, $email);
		}

		return new Markup($html, 'UTF-8');
	}

	/**
	 * @param $url
	 * @return string
	 */
	public function displayUrl($url)
	{
		if ($this->parameterBag->get('app_dereferer') !== false) {
			return "https://dereferer.me/?".urlencode($url);
		}
		else {
			return $url;
		}
	}

	/**
	 * @param $sources
	 * @param $itemSettings
	 * @return mixed
	 */
	public function displayImage($sources, $itemSettings)
	{
		if (isset($itemSettings['external_images']) && $itemSettings['external_images'] === true) {
			if (is_array($sources)) {
				foreach ($sources as $source) {
					if (is_string($source)) {
						return $source;
					}
				}
			}

			if (is_string($sources)) {
				return $sources;
			}
		}

		if (isset($itemSettings['default_image_file'])) {
			return $this->themeAsset('images/'.  $itemSettings['default_image_file']);
		}

		return '';
	}

	/**
	 * @param $item
	 * @param $niche
	 * @param $itemSettings
	 * @param $routeName
	 * @return mixed
	 * @throws \Exception
	 */
	public function displaySchemaOrgStructuredData($item, $niche, $itemSettings, $routeName)
	{
		if (isset($itemSettings['structured_data']) && true === $itemSettings['structured_data']) {
			$structuredData = $this->schema->getStructuredData($item, $niche, $routeName);
			if (false !== $structuredData) {
				$html = sprintf('<script type="application/ld+json">%s</script>', $structuredData);
                return new Markup($html, 'UTF-8');
			}
		}

		return '';
	}

	/**
	 * @param string $string
	 * @return Markup
	 */
	public function hracikAvatar(string $string): Markup
	{
		$svg = CreateAvatarFromText::getAvatar($string, ['size' => 64, 'text-case' => 'upper', 'text-modification' => 'pseudo', 'font-weight' => 'normal', 'color-scheme' => 'light']);
		return new Markup($svg, 'UTF-8');
	}
	/**
	 * @param $key
	 * @param $item
	 * @return mixed
	 * @throws \Exception
	 */
	public function ads(array $item, $key = false)
    {
	    $links = $this->parameterBag->get('app_ads_links');
        if (is_array($links)) {
		    //array with keys
            if ($key !== false && isset($links[$key])) {
		        $link = $links[$key];
	        }
			else {
				//key is not found or not specified
				throw new \Exception(sprintf('Array "app_ads_links" require to use one of keys: %s. Key "%s" have been called.', implode(', ', array_keys($links)), $key));
			}
	    }
        else {
            //just one link as string
		    $link = $links;
	    }

	    return $this->renderFromString($link, ['item' => $item]);
    }


	/**
	 * @param string $text
	 * @param array  $options
	 * @return string
	 * @throws \Exception
	 */
	public function excerpt(string $text, array $options = []): string
	{
		$resolver = new OptionsResolver();
		$resolver->setDefaults([
			'trim_on_word' => true,
			'limit' => 24,
			'ellipsis' => ' ..',
		]);
		$defaults = $resolver->resolve($this->parameterBag->get('app_excerpt'));
		$resolver->clear();
		$resolver->setDefaults($defaults);
		$options = $resolver->resolve($options);

		if ($options['trim_on_word'] === true) {
			return $this->trimOnWord($text, $options['limit'], $options['ellipsis']);
		}
		else {
			return $this->trimOnChar($text, $options['limit'], $options['ellipsis']);
		}
	}

	/**
	 * @param array  $routesToCheck
	 * @param string $class
	 * @return string
	 */
	public function activeNav(array $routesToCheck, string $class = ''): string
	{
		$currentRoute = $this->requestStack->getCurrentRequest()->get('_route');
		foreach ($routesToCheck as $routeToCheck) {
			if ($routeToCheck == $currentRoute) {
				return $class;
			}
		}

		return '';
	}

	/**
	 * @param int   $limit
	 * @param array $array
	 * @param null  $key
	 * @return string
	 * @throws \Exception
	 */
	public function arraySlice(int $limit, array $array, $key = null)
	{
		if ((count($array) !== count($array, COUNT_RECURSIVE)) && $key === null) {
			throw new \Exception('Key must be specified for multidimensional arrays.');
		}

		$limitedArray = array_slice($array, 0, $limit);
		if (null !== $key) {
			$limitedArray = array_column($limitedArray, $key);
			if ((count($limitedArray) !== count($limitedArray, COUNT_RECURSIVE)) && $key === null) {
				throw new \Exception('Array can not be multidimensional');
			}
		}

		return implode(', ', $limitedArray);
	}

	/**
	 * @param      $value
	 * @param bool $delimiter
	 * @return string
	 */
	public function delimiter($value, $delimiter = false)
	{
		if (false === $delimiter) {
			$delimiter = AppRuntime::DELIMITER_PLACEHOLDER;
		}

		return sprintf('%s%s', $value, $delimiter);
	}


	/**
	 * @param        $wordsLimit
	 * @param string $string
	 * @param bool   $ellipsis
	 * @return string
	 * @throws \Exception
	 */
	public function trimOnWord($wordsLimit, string $string, $ellipsis = false)
	{
		$string = Functions::cleanupStringFromHtml($string);
		$words = explode(' ', $string);
		if (count($words) <= $wordsLimit) {
			return $string;
		}

		$trimmedString = implode(' ', array_slice($words, 0, $wordsLimit));
		//add ellipsis (...)
		if (false === $ellipsis) {
			$ellipsis = AppRuntime::ELLIPSIS_PLACEHOLDER;
		}

		return $trimmedString . $ellipsis;
	}

	/**
	 * @param        $charsLimit
	 * @param string $string
	 * @param bool   $ellipsis
	 * @return string
	 * @throws \Exception
	 */
	public function trimOnChar($charsLimit, string $string, $ellipsis = false)
	{
		$string = Functions::cleanupStringFromHtml($string);
		if (strlen($string) <= $charsLimit) {
			return $string;
		}

		//find last space within length
		$lastSpace = strrpos(substr($string, 0, $charsLimit), ' ');
		$trimmedString = substr($string, 0, $lastSpace);
		//add ellipsis (...)
		if (false === $ellipsis) {
			$ellipsis = AppRuntime::ELLIPSIS_PLACEHOLDER;
		}

		return $trimmedString . $ellipsis;
	}

	public function renderIfEverythingSet($item, $stringTemplate, $variables)
	{
		//todo not usable
		/*foreach ($variables as $variable) {
			if (empty($variable)) {
				//i.e. false, '', 0, [], null
				//strict is true in case of block, block is empty if any variable replace in it is empty
				return '';
			}
		}

		return $this->renderFromString($stringTemplate, ['item' => $item]);
		*/
	}

	/**
	 * @param $item
	 * @param $stringTemplate
	 * @param $options
	 * @return Markup
	 * @throws \Throwable
	 */
	public function renderSeo($item, $stringTemplate, $options)
	{
		$content = $this->renderFromString($stringTemplate, ['item' => $item]);

		//replace delimiters, remove whitespace
		$resolver = new OptionsResolver();
		$resolver->setDefaults([
			'delimiter' => '.',
			'ellipsis' => ' ..',
			'remove_delimiter_if_last' => false,
		]);
		$resolver->setAllowedTypes('delimiter', 'string');
		$resolver->setAllowedTypes('ellipsis', 'string');
		$resolver->setAllowedTypes('remove_delimiter_if_last', 'boolean');
		$options = $resolver->resolve($options);

		$content = trim($content);
		if (true === $options['remove_delimiter_if_last'] && substr($content, -strlen(AppRuntime::DELIMITER_PLACEHOLDER)) === AppRuntime::DELIMITER_PLACEHOLDER) {
			//remove delimiter if it is last group of characters
			$content = substr($content, 0, strrpos($content,AppRuntime::DELIMITER_PLACEHOLDER));
		}

		//todo possible RTL bug, book 10,000 as example, russian chars are good, 10021 armenian lang is ok ..
		$content = str_replace([AppRuntime::DELIMITER_PLACEHOLDER, AppRuntime::ELLIPSIS_PLACEHOLDER], [$options['delimiter'], $options['ellipsis']], $content);
		//cleanup from unused/empty variables and spaces they could add
		$content = trim(preg_replace('/\s+/', ' ', $content));

		return new Markup($content, 'UTF-8'); //unicode support
	}

	/**
	 * @return string|Markup
	 */
	public function googleAnalytics()
	{
		if ($this->parameterBag->get('app_google_analytics') !== null) {
			$script = sprintf('<link rel="dns-prefetch" href="https://www.google-analytics.com">
			<!-- Global site tag (gtag.js) - Google Analytics -->
		    <script async src="https://www.googletagmanager.com/gtag/js?id=%s"></script>
		    <script>window.dataLayer = window.dataLayer || [];function gtag(){dataLayer.push(arguments);}gtag("js", new Date());gtag("config", "%s");</script>', $this->parameterBag->get('app_google_analytics'), $this->parameterBag->get('app_google_analytics'));

			return new Markup($script, 'UTF-8');
		}

		return '';
	}

	/**
	 * @param       $message
	 * @param array $arguments
	 * @param null  $domain
	 * @return string
	 */
	public function themeTrans($message, $arguments = [], $domain = null)
	{
		if (null === $domain) {
			$domain = 'messages';
		}

		$translated = $this->translator->trans($message, $arguments, $domain);
		$needle = '.html';
		if (substr($message, -strlen($needle)) === $needle) {
			//translation can contain HTML chars, have to be returned "raw"
			return new Markup($translated, 'UTF-8');
		}
		else {
			return $translated;
		}
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public function themeAsset(string $path) :string
	{
		$parentTheme = $this->parameterBag->get('app_parent_theme');
		//in case of use CDN, app_asset_base_url parameter is used with parent theme and asset version
		if (null !== $this->parameterBag->get('app_asset_base_url')) {
			return sprintf('%s/%s/%s/%s', $this->parameterBag->get('app_asset_base_url'), $parentTheme, $this->parameterBag->get('app_asset_version'), $path);
		};

		return sprintf('%s/%s/%s/%s', Theme::THEMES_PUBLIC_DIR, $parentTheme, $this->parameterBag->get('app_asset_version'), $path);
	}
}