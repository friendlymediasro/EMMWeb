<?php

namespace EMMWeb\Twig;

use EMMWeb\Util\Functions;
use EMMWeb\Util\Schema;
use EMMWeb\Util\Seo;
use EMMWeb\Util\Theme;
use Hracik\CreateAvatarFromText\CreateAvatarFromText;
use Hracik\CreateImageFromText\CreateImageFromText;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\RuntimeExtensionInterface;
use Twig\Markup;

class AppRuntime implements RuntimeExtensionInterface
{

	protected $parameterBag;
	protected $requestStack;
	protected $translator;
	protected $schema;
	protected $seo;

	public function __construct(ParameterBagInterface $parameterBag, RequestStack $requestStack, Seo $seo, Schema $schema, TranslatorInterface $translator)
	{
		$this->translator = $translator;
		$this->parameterBag = $parameterBag;
		$this->schema = $schema;
		$this->seo = $seo;
		$this->requestStack = $requestStack;
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
	 * @param $itemSettingName
	 * @return mixed
	 */
	public function displayImage($sources, $itemSettingName)
	{
		$settings = $this->getItemSettings($itemSettingName);
		if (isset($settings['external_images']) && $settings['external_images'] === true) {
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

		if (isset($settings['default_image_file'])) {
			return $this->themeAsset('images/'.  $settings['default_image_file']);
		}

		return '';
	}

	/**
	 * @param $item
	 * @param $itemSettingName
	 * @return mixed
	 * @throws \Exception
	 */
	public function displaySchemaOrgStructuredData($item, $itemSettingName)
	{
		$settings = $this->getItemSettings($itemSettingName);
		if (isset($settings['structured_data'])) {
			$structuredData = $this->schema->getStructuredData($item, $settings['structured_data'], $itemSettingName);
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
	 * @param $name
	 * @return mixed
	 * @throws \Exception
	 */
	public function ads($key = false, $name = false)
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

    	return str_replace('$name',  $name !== false ? urlencode($name) : '', $link);
    }


	/**
	 * @param string $text
	 * @param array  $options
	 * @return string
	 */
	public function excerpt(string $text, array $options = []): string
	{
		$resolver = new OptionsResolver();
		$resolver->setDefaults([
			'trim_on_word' => true,
			'limit' => 24,
			'ellipses' => ' ..',
		]);
		$defaults = $resolver->resolve($this->parameterBag->get('app_excerpt'));
		$resolver->clear();
		$resolver->setDefaults($defaults);
		$options = $resolver->resolve($options);

		if ($options['trim_on_word'] === true) {
			return Functions::trimOnWord($text, $options['limit'], $options['ellipses']);
		}
		else {
			return Functions::trimOnChar($text, $options['limit'], $options['ellipses']);
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

	public function title($item, $itemSettingName)
	{
		$settings = $this->getItemSettings($itemSettingName);
		$options = $this->seo->resolveOptions($settings['title_options']);
		$title = $this->seo->makeMagic($item, $settings['title'], $options);
		if (true === $options['allowed_html']) {
			//unicode support
			return new Markup($title, 'UTF-8');
		}
		else {
			return $title;
		}
	}

	public function description($item, $itemSettingName)
	{
		$settings = $this->getItemSettings($itemSettingName);
		$options = $this->seo->resolveOptions($settings['description_options']);
		$description = $this->seo->makeMagic($item, $settings['description'], $options);
		if (true === $options['allowed_html']) {
			//unicode support
			return new Markup($description, 'UTF-8');
		}
		else {
			return $description;
		}
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

	/**
	 * @param $itemSettingName
	 * @return mixed
	 */
	private function getItemSettings($itemSettingName)
	{
		return $this->parameterBag->get('app_item_settings')[$itemSettingName];
	}
}