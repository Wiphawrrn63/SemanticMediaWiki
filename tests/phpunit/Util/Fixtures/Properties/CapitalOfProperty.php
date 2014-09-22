<?php

namespace SMW\Tests\Util\Fixtures\Properties;

use SMW\SemanticData;
use SMW\DIProperty;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class CapitalOfProperty extends FixtureProperty {

	/**
	 * @since 2.1
	 */
	public function __construct() {
		$this->property = DIProperty::newFromUserLabel( 'Capital of' );
		$this->property->setPropertyTypeId( '_wpg' );
	}

	/**
	 * @since 2.1
	 *
	 * @return SemanticData
	 */
	public function getDependencies() {

		$semanticData = parent::getDependencies();

		$locatedInProperty = new LocatedInProperty();

		$semanticData->addPropertyObjectValue(
			new DIProperty( '_SUBP' ),
			$locatedInProperty->getProperty()->getDiWikiPage()
		);

		return $semanticData;
	}

}
