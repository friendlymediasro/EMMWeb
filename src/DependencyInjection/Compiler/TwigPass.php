<?php

	namespace EMMWeb\DependencyInjection\Compiler;

	use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
	use Symfony\Component\DependencyInjection\ContainerBuilder;
	use Symfony\Component\Finder\Finder;

	class TwigPass implements CompilerPassInterface
	{
		/**
		 * {@inheritdoc}
		 */
		public function process(ContainerBuilder $container)
		{
			$twigFilesystemLoaderDefinition =  $container->findDefinition('twig.loader.native_filesystem');
			$parameterBag = $container->getParameterBag();
			$theme = $parameterBag->get('app_theme');
			$themesDir = $parameterBag->get('themes_source_dir');
			$parentTheme = $parameterBag->get('app_parent_theme');

			//first it will check child theme if exists or theme
			$templatesDir = sprintf('%s/%s/Resources/views', $themesDir, $theme);
			if (is_dir($templatesDir)) {
				$twigFilesystemLoaderDefinition->addMethodCall('addPath', [$templatesDir,'theme']);
			}
			//if parent theme exists then it will check templates there
			if ($theme != $parentTheme) {
				$templatesDir = sprintf('%s/%s/Resources/views', $themesDir, $parentTheme);
				if (is_dir($templatesDir)) {
					$twigFilesystemLoaderDefinition->addMethodCall('addPath', [$templatesDir,'theme']);
				}
			};

			//at last register global app views
			$twigFilesystemLoaderDefinition->addMethodCall('addPath', [$parameterBag->get('kernel.root_dir').'/Resources/views','theme']);
		}
	}