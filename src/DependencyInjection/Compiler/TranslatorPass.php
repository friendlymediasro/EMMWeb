<?php

	namespace App\DependencyInjection\Compiler;

	use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
	use Symfony\Component\DependencyInjection\ContainerBuilder;
	use Symfony\Component\Finder\Finder;

	class TranslatorPass implements CompilerPassInterface
	{
		/**
		 * {@inheritdoc}
		 */
		public function process(ContainerBuilder $container)
		{
			$translatorDefinition =  $container->findDefinition('translator.default');
			$parameterBag = $container->getParameterBag();
			$theme = $parameterBag->get('app_theme');
			$parentTheme = $parameterBag->get('app_parent_theme');
			$dirs = [];
			if ($theme != $parentTheme) {
				$translationsDir = sprintf('%s/%s/translations', $parameterBag->get('global_themes_dir'), $parentTheme);
				if (is_dir($translationsDir)) {
					$dirs[] = $translationsDir;
				}
			};
			$translationsDir = sprintf('%s/%s/translations', $parameterBag->get('global_themes_dir'), $theme);
			if (is_dir($translationsDir)) {
				$dirs[] = $translationsDir;
			}

			if ($dirs) {
				$files = [];
				$finder = Finder::create()
					->followLinks()
					->files()
					->filter(function (\SplFileInfo $file) {
						return 2 === substr_count($file->getBasename(), '.') && preg_match('/\.\w+$/', $file->getBasename());
					})
					->in($dirs)
					->sortByName()
				;

				foreach ($finder as $file) {
					list(, $locale) = explode('.', $file->getBasename(), 3);
					if (!isset($files[$locale])) {
						$files[$locale] = [];
					}

					$files[$locale][] = (string) $file;
				}

				$options = array_merge(
					$translatorDefinition->getArgument(4),
					['resource_files' => $files]
				);

				$translatorDefinition->replaceArgument(4, $options);
			}
		}
	}