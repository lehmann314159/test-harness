<?php

/**
 * Harness is a library for testing that allows you to interact with fixture
 * data in a read-write fashion.
 *
 * Harness::createFromFixture($inClassName, $inData, $newId) lets you partially
 * specify a model's adata, and utilize fixture data to supplement.
 *
 * Harness::createFromNamedFixture($inClassName, $inColumnName, $inValue) is
 * a generic wrapper for find.  We might want to investigate allowing data
 * injection on this method as well, but I wanted to keep the initial rollout
 * crisp.
 */

App::uses( 'AppFixtureManager', 'TestSuite' );

class Harness {
    protected static $_fixtures = []; 
    protected static $_models = []; 

/**
 * Resets the internal state between tests
 *
 * @return [type] [description]
 */
    public static function clear() {
        self::$_fixtures = []; 
        self::$_models = []; 
    }   

/**
 * Create New
 *
 * Used to dynamically instantiate a model, populate it using the supplied data,
 * saving it, and handing it back to the user.
 *
 * @param string $inClassName - Model class name to create
 * @param array $inData - The data from which to create said model instance.
 * @return array
 **/
    public static function createNew($inClassName, array $inData) {
        $aClassName = $inClassName;
        self::load( $aClassName );
        self::$_models[$aClassName]->create();
        if (array_key_exists('id', $inData)) {
            unset($inData['id']);
        }   

        if (! ($clay = self::$_models[$aClassName]->save($inData))) {
            $errorText = "\nThere were errors creating the new entry...\n";
            foreach (self::$_models[$aClassName]->validationErrors as $columnName => $errorList) {
            $errorText = "\nThere were errors creating the new entry...\n";
            foreach (self::$_models[$aClassName]->validationErrors as $columnName => $errorList) {
                $errorText .= "$columnName:\n";
                foreach ($errorList as $errorDesc) {
                    $errorText .= "\t$errorDesc\n";
                }
            }
            print $errorText;
            return false;
        }

        $clay[$aClassName]['id'] = self::$_models[$aClassName]->id;
        return $clay;
    }

/**
 * loadIndexedFixture
 *
 * Given a fixture name and an optional offset, acquire the data from said
 * fixture and return.
 *
 * @param string $inClassName - Name of the model/fixture to instantiate
 * @param integer $index - Offset of the fixture of interest, defaults to 0.
 * @return array
 **/
    public static function loadIndexedFixture($inClassName, $index = 0) {
        self::load( $inClassName );
        $proto = self::$_fixtures[$inClassName]->records[$index];
        return [$inClassName => $proto];
    }

/**
 * extendFromFixture
 *
 * Given a fixture and a set of data, optionally overwrite portions of the base fixture
 * with the new data (and potentially new id) and hand it back.
 *
 * @param string $inClassName - Name of the model/fixture to create
 * @param array $inData - The data members of interest for overwriting.
 * @param string $newId - UUID string of the ID under which to create information, making it easy to find later.
 * @return array
 **/
    public static function extendFromFixture($inClassName, array $inData, $newId = true) {
        self::load( $inClassName );
        $proto = array_replace(self::$_fixtures[$inClassName]->records[0], $inData);

        if ($newId) {
            self::$_models[$inClassName]->create();
            if (array_key_exists('id', $proto)) {
                unset($proto['id']);
            }
        }

        if (! ($clay = self::$_models[$inClassName]->save($proto))) {
            $errorText = "\nThere were errors creating the new entry...\n";
            foreach (self::$_models[$inClassName]->validationErrors as $columnName => $errorList) {
                $errorText .= "$columnName:\n";
                foreach ($errorList as $errorDesc) {
                    $errorText .= "\t$errorDesc\n";
                }
            }
            print $errorText;
            return false;
        }

        $clay[$inClassName]['id'] = self::$_models[$inClassName]->id;
        return $clay;
    }

/**
 * loadFixtureByField
 *
 * Do a flexible find-by of known fixture data using a field = value lookup.
 *
 * @param string $inClassName - Name of the model/fixture of interest
 * @param string $inColumnName - Name of the column on which to do the lookup.
 * @param string $inValue - Value to match in doing the lookup.
 * @return mixed - array on success, false on failure.
 **/
    public static function loadFixtureByField($inClassName, $inColumnName, $inValue) {
        self::load( $inClassName );
        $functionName = 'findBy' . Inflector::camelize($inColumnName);
        return self::$_models[$inClassName]->$functionName($inValue);
    }

/**
 * loadFixture
 *
 * This is the most flexible way of finding a matching fixture.
 *
 * @param string $inClassName - Name of the class/fixture of interest
 * @param array $inOptions - list of query conditions
 *
 * @return array
 */
    public static function loadFixture($inClassName, $inOptions) {
        self::load($inClassName);
        return self::$_models[$inClassName]->find('first', $inOptions);
    }

/**
 * load
 *
 * Load a fixture into the local fixture cache.  Returns success bool.
 *
 * @param string $inClassName - The model/fixture name of interest.
 * @return boolean
 **/
    public static function load($inClassName) {
        if ( empty( self::$_models[$inClassName] ) ) {
            self::$_models[$inClassName] = ClassRegistry::init( $inClassName );
        }

        AppFixtureManager::loadFixture( $inClassName ); // Make sure this exists on the stack & db
        if ( empty( self::$_fixtures[$inClassName] ) ) {
            $fix = $inClassName . 'Fixture';
            self::$_fixtures[$inClassName] = new $fix;
        }
        return !empty( self::$_models[$inClassName] ) && !empty( self::$_fixtures[$inClassName] );
    }
}
