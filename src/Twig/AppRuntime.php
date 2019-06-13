<?php

namespace App\Twig;

use App\Util\Functions;
use App\Util\Schema;
use App\Util\Seo;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\RuntimeExtensionInterface;
use Hracik\CreateImageFromText\CreateImageFromText;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
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
	 * @param $src
	 * @param $itemSettingName
	 * @return mixed
	 */
	public function displayImage($src, $itemSettingName)
	{
		$settings = $this->getItemSettings($itemSettingName);
		if (isset($settings['external_images']) && $settings['external_images'] === true && $src !== null) {
			return $src;
		}
		elseif (isset($settings['default_image_file'])) {
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
				throw new \Exception(sprintf('Array "app_ads_links" require to use one of keys: %s.', implode(', ', array_keys($links))));
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
	 * @return string|Markup
	 */
	public function histats()
	{
		if ($this->parameterBag->get('app_histats') !== null) {
			$script = sprintf('
			<!-- Histats.com  START  (aync)-->
	        <script type="text/javascript">var _Hasync = _Hasync || [];
	            _Hasync.push(["Histats.start", "1,{{ histats }},4,0,0,0,00010000"]);
	            _Hasync.push(["Histats.fasi", "1"]);
	            _Hasync.push(["Histats.track_hits", ""]);
	            (function () {
	                var hs = document.createElement("script");
	                hs.type ="text/javascript";
	                hs.async = true;
	                hs.src = ("//s10.histats.com/js15_as.js");
	                (document.getElementsByTagName("head")[0] || document.getElementsByTagName("body")[0]).appendChild(hs);
	            })();
	        </script>
	        <noscript>
	            <a href="/" target="_blank">
	                <img src="//sstatic1.histats.com/0.gif?{{ histats }}&101" alt="free webpage hit counter" border="0">
	            </a>
	        </noscript>
	        <!-- Histats.com  END  -->');
			return new Markup($script, 'UTF-8');
		}

		return '';
	}

	/**
	 * @return string|Markup
	 */
	public function cookieConsent()
	{
		if ($this->parameterBag->get('app_cookie_consent') === true) {
			$html = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cookieconsent2/3.1.1/cookieconsent.min.css" integrity="sha256-zQ0LblD/Af8vOppw18+2anxsuaz3pWYyVWi+bTvTH8Q=" crossorigin="anonymous" />
			<script src="https://cdnjs.cloudflare.com/ajax/libs/cookieconsent2/3.1.1/cookieconsent.min.js" integrity="sha256-5VhCqFam2Cn+yjw61zbBNrbHVJ6SRydPeKopYlngbiQ=" crossorigin="anonymous"></script>
			<script>window.addEventListener("load", function(){window.cookieconsent.initialise({"palette": {"popup": {"background": "#000"},"button": {"background": "#f1d600"}}})});</script>';
			return new Markup($html, 'UTF-8');
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
	 * @param bool   $forceParent
	 * @return string
	 */
	public function themeAsset(string $path, $forceParent = false) :string
	{
		$parentTheme = $this->parameterBag->get('app_parent_theme');
		//in case of use CDN, app_asset_base_url parameter is used with parent theme and asset version
		if (null !== $this->parameterBag->get('app_asset_base_url')) {
			return sprintf('%s/%s/%s/%s', $this->parameterBag->get('app_asset_base_url'), $parentTheme, $this->parameterBag->get('app_asset_version'), $path);
		};

		$theme = $this->parameterBag->get('app_theme');
		//get assetPath from child if exists, if not then from parent ..
		if ($parentTheme != $theme && $forceParent !== true) {
			$fromTheme = $theme;
		}
		else {
			$fromTheme = $parentTheme;
		}

		$assetPath = sprintf('%s/%s/%s/%s', $this->parameterBag->get('global_themes_public_dir'), $fromTheme, $this->parameterBag->get('app_asset_version'), $path);
		if ($fromTheme !== $parentTheme && !file_exists($this->parameterBag->get('kernel.project_dir').'/public'.$assetPath)) {
			return $this->themeAsset($path, true);
		}
		else {
			return $assetPath;
		}
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