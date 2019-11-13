<?php

namespace EMMWeb;

use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache;

class CacheKernel extends HttpCache
{
	protected function getOptions()
	{
		return array(
			'stale_if_error' => null, //do no return cached response if error
		);
	}
}