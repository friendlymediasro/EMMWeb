<?php

namespace App\Twig;

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
        	new TwigFunction('ads', [AppRuntime::class, 'ads']),
	        new TwigFunction('displayUrl', [AppRuntime::class, 'displayUrl']),
	        new TwigFunction('displayEmail', [AppRuntime::class, 'displayEmail']),
	        new TwigFunction('displayImage', [AppRuntime::class, 'displayImage']),
	        new TwigFunction('displaySchemaOrgStructuredData', [AppRuntime::class, 'displaySchemaOrgStructuredData']),
	        new TwigFunction('themeAsset', [AppRuntime::class, 'themeAsset']),
	        new TwigFunction('activeNav', [AppRuntime::class, 'activeNav']),
	        new TwigFunction('title', [AppRuntime::class, 'title']),
	        new TwigFunction('description', [AppRuntime::class, 'description']),
	        new TwigFunction('googleAnalytics', [AppRuntime::class, 'googleAnalytics']),
	        new TwigFunction('histats', [AppRuntime::class, 'histats']),
	        new TwigFunction('cookieConsent', [AppRuntime::class, 'cookieConsent']),
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
		];
	}

}