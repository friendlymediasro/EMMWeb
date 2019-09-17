<?php

namespace EMMWeb\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension
{

	/**
     * @return array
     */
    public function getFunctions()
    {
        return array(
	        new TwigFunction('renderSeo', [AppRuntime::class, 'renderSeo']),
	        //new TwigFunction('renderIfEverythingSet', [AppRuntime::class, 'renderIfEverythingSet']),
	        new TwigFunction('arraySlice', [AppRuntime::class, 'arraySlice']),
	        new TwigFunction('trimOnWord', [AppRuntime::class, 'trimOnWord']),
	        new TwigFunction('trimOnChar', [AppRuntime::class, 'trimOnChar']),

	        new TwigFunction('ads', [AppRuntime::class, 'ads']),
	        new TwigFunction('displayUrl', [AppRuntime::class, 'displayUrl']),
	        new TwigFunction('displayEmail', [AppRuntime::class, 'displayEmail']),
	        new TwigFunction('displayImage', [AppRuntime::class, 'displayImage']),
	        new TwigFunction('displaySchemaOrgStructuredData', [AppRuntime::class, 'displaySchemaOrgStructuredData']),
	        new TwigFunction('themeAsset', [AppRuntime::class, 'themeAsset']),
	        new TwigFunction('activeNav', [AppRuntime::class, 'activeNav']),
	        new TwigFunction('title', [AppRuntime::class, 'title']),
	        new TwigFunction('description', [AppRuntime::class, 'description']),
	        new TwigFunction('hracikAvatar', [AppRuntime::class, 'hracikAvatar']),
	        new TwigFunction('routeExists', [AppRuntime::class, 'routeExists']),
        );
    }

	/**
	 * @return array
	 */
	public function getFilters()
	{
		return [
			new TwigFilter('excerpt', [AppRuntime::class, 'excerpt']),
			new TwigFilter('themeTrans', [AppRuntime::class, 'themeTrans']),
			new TwigFilter('delimiter', [AppRuntime::class, 'delimiter']),
		];
	}

}