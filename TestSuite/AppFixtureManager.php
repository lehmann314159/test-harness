<?php

App::uses( 'CakeFixtureManager', 'TestSuite/Fixture' );

class AppFixtureManager extends CakeFixtureManager {
    protected static $_instance = null;

    public function __construct($takeover = false) {
        if ( $takeover || !self::$_instance ) { 
            self::$_instance = $this;
        }   
    }   

    public static function getInstance($refresh = false) {
        if ( $refresh || is_null( self::$_instance ) ) { 
            self::$_instance = new AppFixtureManager();
        }   
        return self::$_instance;
    }   

    public static function loadFixture($name) {
        $instance = self::getInstance();
        $mapName = Inflector::underscore( $name );
        if ( !strpos( '.', $mapName ) ) { 
            $mapName = 'app.' . $mapName;
        }   
        if ( empty( $instance->_loaded[$mapName] ) ) { 
            $instance->_loadFixtures([$mapName]);
            $instance->loadSingle( $name );
        }   
    }   
}
