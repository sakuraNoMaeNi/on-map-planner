<?php
namespace omp;

/*
	setup each and every dependancy, using the enviorement settings and/or custom manipulations
	*/
class Config
{
	/**
		* load enviorement configuration
		* @return $settings
		*/
	protected static $configuration;
	public static function loadConfiguration()
	{
		if( empty(self::$configuration) )
		{
			if( file_exists( PRIVATE_PATH . '/configuration.json' )
				&& $fileContents = file_get_contents( PRIVATE_PATH . '/configuration.json' )
			) {
				if( $configuration = json_decode($fileContents, true) )
				{
					self::$configuration = $configuration;
				}
				else
				{
					throw new Exception( 'Missing enviorement configuration, create the private/configuration.json file.');
				}
			}
		}
		return self::$configuration;
	}

	/**
		* get all or specific settings from the currently loaded configuration
		* @param string name of the first key
		* @param string name of the second key
		* @param string name of the…
		* @return requested setting
		*/
	public static function getSettings()
	{
		if( empty(self::$configuration) )
		{
			self::loadSettings();
		}
		$args = func_get_args();
		if( !empty($args) )
		{
			$settings = [];
			foreach( $args as $requestedSetting )
			{
				if( isset(self::$configuration[$requestedSetting]) )
				{
					$settings[] = self::$configuration[$requestedSetting];
				}
				else
				{
					trigger_error( 'Requesting unknown setting', E_WARNING  );
				}
			}
			return $settings;
		}
		return self::$configuration;
	}

	/**
		* Get Brucore database (MySql) adapter.
		*
		* @param string $database Database name.
		* @param string $country For country specific databases. | Defaults to country constant.
		*
		* @static
		* @access public
		* @return \brucore\database\BrucoreMySql
		*/
	protected static $mySqlConnection;
	public static function getMySql()
	{
		if( empty(self::$mySqlConnection) )
		{
			list( $host, $port, $user, $password, $databaseName ) = self::getSetting('database.mysql.host', 'database.mysql.port', 'database.mysql.user', 'database.mysql.password', 'database.mysql.databaseName');
			if( !($mysql = new \brucore\database\BrucoreMySql( array_shift($masterServers) ) ) )
			{
				trigger_error('Could not load database', E_WARNING);
				return false;
			}
		}
		return self::$mySqlConnection;
	}
}
