<?php

	namespace EMMWeb\DependencyInjection\Compiler;

	use EMMWeb\Util\Theme;
	use Exception;
	use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
	use Symfony\Component\DependencyInjection\ContainerBuilder;
	use Symfony\Component\Filesystem\Filesystem;
	use Symfony\Component\OptionsResolver\OptionsResolver;
	use Symfony\Component\Yaml\Yaml;

	class ParametersPass implements CompilerPassInterface
	{
		private const APP_REQUIRED_PARAMETERS = [
			'app_ads_links',
			'app_auth_key',
			'app_name',
			'app_email',
			'app_website_name',
			'app_website_url',
			'app_slogan',
			'app_theme',
		];
		private const APP_DEFAULT_PARAMETERS = [
			'app_locale' => 'en',
			'app_http_cache' => true, //must be false to NOT use HTTP cache
			'app_asset_version' => 'v1', //to load new assets when changed, avoid browser cache
			'app_asset_base_url' => null,
			'app_google_analytics' => null, //dynamically add Google Analytics script to theme calling {{ googleAnalytics() }}
			'app_histats' => null, //dynamically add Histats script to theme calling {{ histats() }}
			'app_cookie_consent' => false, //by default are NO cookies used at all, only third-party cookies are possible in app, add script/css from osano.com to theme calling {{ cookieConsent() }}
			'app_dereferer' => true, //must be false to NOT mask referer in external redirects
			'app_email_obfuscate' => true,  //must be false to NOT hide email address from scrapers by creating an image
			'app_excerpt' => [],
		];
		private const APP_DEFINED_PARAMETERS = [

		];
		private const ITEM_REQUIRED_PARAMETERS = [
			'title',
			'description',
		];
		private const ITEM_DEFAULT_PARAMETERS = [
			'external_images' => false,
			'title_options' => [],
			'description_options' => [],
		];
		private const ITEM_DEFINED_PARAMETERS = [
			'default_image_file',
			'structured_data',
		];
		private const TWIG_GLOBAL_PARAMETERS = [
			'app_locale', //used in <html lang="">
			'app_name',
			'app_email',
			'app_website_name',
			'app_website_url',
			'app_slogan',
			'app_google_analytics',
			'app_histats',
			'app_cookie_consent',
			'app_item_settings',
		];

		/**
		 * {@inheritdoc}
		 * @throws Exception
		 */
		public function process(ContainerBuilder $container)
		{
			$parameterBag = $container->getParameterBag();
			//check existsRequiredParameters
			$resolver = new OptionsResolver();
			$resolver->setRequired(self::APP_REQUIRED_PARAMETERS);
			$resolver->setDefaults(self::APP_DEFAULT_PARAMETERS);
			$resolver->setDefined(self::APP_DEFINED_PARAMETERS);
			$appParameters = array_filter($parameterBag->all(), function($p) {
				if (substr($p, 0, 4) == 'app_') {
					return true;
				} else {
					return false;
				}
			}, ARRAY_FILTER_USE_KEY);
			$resolver->resolve($appParameters);

			//creation of dynamic parameters
			//set parent theme parameter
			$container->setParameter('app_parent_theme', Theme::getParentTheme($parameterBag->get('app_theme')));
			//set item settings parameter
			$container->setParameter('app_item_settings', self::loadItemSettings($container));
			/** parameters validation*/
			if (false === ctype_alnum($container->getParameter('app_asset_version'))) {
				throw new Exception('app_asset_version can contains only alphanumeric.');
			}

			//register twig globals
			$twigDefinition =  $container->findDefinition('twig');
			foreach (self::TWIG_GLOBAL_PARAMETERS as $globalParameter) {
				$twigDefinition->addMethodCall('addGlobal', [$globalParameter, $parameterBag->get($globalParameter)]);
			}

			$twigDefinition->addMethodCall('addGlobal', ['app_theme_options', Theme::loadThemeOptions($container)]);
		}

		/**
		 * @param ContainerBuilder $container
		 * @return array
		 */
		private static function loadItemSettings(ContainerBuilder $container)
		{
			$settings = [];
			$filesystem = new Filesystem();
			$resource = sprintf('%s/config/app_items.yaml', $container->getParameter('kernel.project_dir'));
			if ($filesystem->exists($resource)) {
				$appItems = Yaml::parseFile($resource);
				foreach ($appItems as $itemName => $appItem)
				{
					$resolver = new OptionsResolver();
					$resolver->setRequired(self::ITEM_REQUIRED_PARAMETERS);
					$resolver->setDefaults(self::ITEM_DEFAULT_PARAMETERS);
					$resolver->setDefined(self::ITEM_DEFINED_PARAMETERS);
					$settings[$itemName] = $resolver->resolve($appItem['defaults']['settings'] ?? []);
				}
			}

			return $settings;
		}

	}