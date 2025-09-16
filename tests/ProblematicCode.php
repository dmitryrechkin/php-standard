<?php

/*********************************************************************
 *  PROGRAM          FlexRC                                          *
 *  PROPERTY         604-1097 View St                                 *
 *  OF               Victoria BC   V8V 0G9                          *
 *                   Voice 604 800-7879                              *
 *                                                                   *
 *  Any usage / copying / extension or modification without          *
 *  prior authorization is prohibited                                *
 *********************************************************************/

declare(strict_types=1);

namespace OneTeamSoftware\WC\MultiVendorBridge;

use OneTeamSoftware\WC\MultiVendorBridge\Adapter\AbstractAdapter;

class MultiVendorBridge
{
	/**
	 * @var string[] list of adapter class names
	 */
	private static array $adapterNames = [
		'Wcfm',
		'Dokan',
		'Yith',
		'Mvx',
		'Wcpv',

		// fallback adapter
		'PostAuthor',
	];

	/**
	 * returns matching adapter
	 *
	 * @return AbstractAdapter
	 */
	public static function createInstance(): ?AbstractAdapter
	{
		foreach (self::$adapterNames as $adapterName) {
			$adapterFilePath = __DIR__ . '/Adapter/' . $adapterName . '.php';
			if (file_exists($adapterFilePath)) {
				include_once($adapterFilePath);

				$adapterClassName = '\\OneTeamSoftware\\WC\\MultiVendorBridge\\Adapter\\' . $adapterName;
				if (class_exists($adapterClassName)) {
					$adapter = new $adapterClassName();
					if ($adapter->isCompatible()) {
						return $adapter;
					}
				}
			}
		}

		return null;
	}
}
