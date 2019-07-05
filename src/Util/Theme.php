<?php

namespace EMMWeb\Util;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Yaml\Yaml;

class Theme
{
	public const CHILD_THEME_APPEND = '.child';
	public const THEMES_PUBLIC_DIR = '/themes';
	public const THEMES_SOURCE_DIR = '/themes';
	public const THEME_RESOURCES_PUBLIC_DIR = '/Resources/public';

	public static function getChildTheme(string $theme)
	{
		return $theme. self::CHILD_THEME_APPEND;
	}

	/**
	 * @param string $theme
	 * @return bool
	 */
	public static function isChildTheme(string $theme) : bool
	{
		if (substr($theme, -strlen(self::CHILD_THEME_APPEND)) === self::CHILD_THEME_APPEND) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * @param string $theme
	 * @return string
	 */
	public static function getParentTheme(string $theme) : string
	{
		if (self::isChildTheme($theme)) {
			$parentTheme = substr($theme, 0, -strlen(self::CHILD_THEME_APPEND));
			if (is_string($parentTheme)) {
				return $parentTheme;
			}
			return $theme;
		}
		else {
			return $theme;
		}
	}

	/**
	 * @param string $theme
	 * @param string $dir
	 * @return bool
	 */
	public static function doesChildThemeExists(string $theme, string $dir): bool
	{
		if (self::getParentTheme($theme) == $theme) {
			//this is parent theme, check if child theme exists
			return (bool) iterator_count(Finder::create()->in($dir)->depth(0)->directories()->name($theme.self::CHILD_THEME_APPEND));
		}
		return false;
	}

	/**
	 * @param ContainerBuilder $container
	 * @return array
	 */
	public static function loadThemeOptions(ContainerBuilder $container)
	{
		$filesystem = new Filesystem();
		$resolver = new OptionsResolver();
		$themeOptions = [];
		$resource = sprintf('%s/%s/Resources/config/config.yaml', $container->getParameter('themes_source_dir'), $container->getParameter('app_parent_theme'));
		if ($filesystem->exists($resource)) {
			//if null then set it empty array
			$themeConfig = Yaml::parseFile($resource) ?? [];
			$resolver->setDefaults($themeConfig);
			$resource = sprintf('%s/%s/Resources/config/options.yaml', $container->getParameter('themes_source_dir'), $container->getParameter('app_parent_theme'));
			if ($filesystem->exists($resource)) {
				//if null then set it empty array
				$themeOptions = Yaml::parseFile($resource) ?? [];
			}
		}

		return $resolver->resolve($themeOptions);
	}

}