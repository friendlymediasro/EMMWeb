<?php

	namespace App\DependencyInjection\Compiler;

	use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
	use Symfony\Component\DependencyInjection\ContainerBuilder;

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
			$themesDir = $parameterBag->get('global_themes_dir');
			$parentTheme = $parameterBag->get('app_parent_theme');
			$templatesDir = sprintf('%s/%s/templates', $themesDir, $theme);
			if (is_dir($templatesDir)) {
				$twigFilesystemLoaderDefinition->addMethodCall('addPath', [$templatesDir,'theme']);
			}
			if ($theme != $parentTheme) {
				$templatesDir = sprintf('%s/%s/templates', $themesDir, $parentTheme);
				if (is_dir($templatesDir)) {
					$twigFilesystemLoaderDefinition->addMethodCall('addPath', [$templatesDir,'theme']);
				}
			};
		}
	}