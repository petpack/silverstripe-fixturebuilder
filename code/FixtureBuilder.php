<?

class FixtureBuilder extends Controller {
	
	/**
	 * Tables which we won't dump data from by default 
	 * @var Array <table> => true
	 */
	private $exclude_tables = Array(
		//'Account' => true,
		//'ActivityLogItem' => true,
	);
	
	/**
	 * Array of fields to be ignored
	 * format: tablename.fieldname
	 * case-insensitive
	 * @var Array
	 */
	private $ignore_fields = Array(
		'account.migrationnotes',
		'client.migrationnotes',
		'file.migrationnotes',
		'member.migrationnotes',
		'newsletter.migrationnotes',
		'newsletter_article.migrationnotes',
		'newsletter_article_image.migrationnotes',
		'newsletter_edition.migrationnotes',
		'pet.migrationnotes',
		'practice.migrationnotes',
		'treatment.migrationnotes',
	);
	
	/**
	 * computed 'ignored fields' array
	 * @var Array
	 */
	private $_ignore = Array();
	
	/**
	 * for the sake of both execution speed and development time,
	 * 	we'll maintain our own database connection and use php's
	 * 	mysql functions rather than the silverstripe DB abstraction
	 * @var null|Resource
	 */
	private $conn = null;
	
	function __construct() {
		
		if (!Director::isDev())
			die("DEV ONLY!");
		
		parent::__construct();
		
		//connect to the DB:
		global $database;	//this comes from pet_pack's _config.php
		$user = SS_DATABASE_USERNAME;
		$pass = SS_DATABASE_PASSWORD;
		$host = SS_DATABASE_SERVER;
		$this->connect($host, $user, $pass, $database);
		
		//Yes, this is a bit nasty:
		set_time_limit(600);
		//...and this is even nastier:
		ini_set('memory_limit','512M');
	}
	
	private function message($message) {
		if (Director::is_cli()) {
			$message = strip_tags($message);
		}
		echo "<p>" . $message . "</p>";
	}
	
	function index() {
		return $this->renderWith('FixtureBuilder');
	}
	
	/**
	 * Establish a DB connection and switch to the desired database
	 */
	private function connect($host,$user,$pass,$db) {
		// Open database connection
		$this->conn = mysqli_connect($host, $user, $pass);
		if (!$this->conn) {
			die("Could not connect to DB [{$user}@{$host}] :: " . mysqli_error() . "\n");
		}
		
		// Select the database
		if (!mysqli_select_db($db))
			die("Count not connect to the '{$db}' database!");
		
		return true;
	}
	
	/**
	 * Return a dataobjectset containing all tables in the database 
	 * @return SS_Boolean|DataObjectSet
	 */
	function list_tables() {
		if (!$this->conn) return false;
		
		$result = mysqli_query('SHOW TABLES', $this->conn);
		
		$ret = new DataObjectSet();
		if ($result)
			while ($row = mysqli_fetch_row($result)) {
				$table = $row[0];
				$o = new DataObject;
				$o->table = $table;
				$o->selected = !isset($this->exclude_tables[$table]);
				$o->records = $this->count_records($table);
				$o->small = ($o->records < 200);
				$ret->push($o);
			}
		
		return $ret;
	}
	
	function count_records($table) {
		$ret = 0;
		$result = mysqli_query("SELECT COUNT(*) FROM $table",$this->conn);
		if ($result) {
			$row = mysqli_fetch_row($result);
			$ret = $row[0];	
		}
		return $ret;
	}
	
	/**
	 * returns ignored fields as text, separated by linefeeds, suitable for
	 * 	putting in a textarea
	 * @return string
	 */
	function ignored_fields_val() {
		$ret = "";
		foreach($this->ignore_fields as $field ) {
			$ret .= $field . "\n";
		}
		return $ret;
	}
	
	function build() {
		$tables = Array();
		$fields = Array();
		$all = Array();
		
		$params = explode('&',$_SERVER['QUERY_STRING']);
		
		foreach ($params as $param) {
			list($k,$v) = explode('=',$param);
			if ($k == 'table')
				$tables[] = $v;
			if ($k == 'all')
				$all[$v] = true;
		}
		
		$ignore = explode("\n",$_REQUEST['ignore_fields']);
		foreach ($ignore as $k => $v)
			if (trim($v)) {
				list($table,$field) = explode('.',trim($v));
				$table = strtolower($table);
				$field = strtolower($field);
				
				if (!isset($fields[$table]))
					$fields[$table] = Array();
				$fields[$table][$field] = true;
			}

		$this->_ignore = $fields;

		$yaml = "#FixtureBuilder dump\n#Generated at " . date('d-M-Y H:i:s') . "\n\n";
		foreach ($tables as $table) {
			$all = isset($all[$table]);
			$yaml .= $this->table_to_yaml($table,$all);
		}
		
		header('Content-Type: text/plain');
		header('Content-Disposition: attachment; filename="fixtures.yml"');
		
		echo $yaml;
		
	}
	
	private function table_to_yaml($table,$all = true) {
		$sql = "SELECT * from $table";
		$result = mysqli_query($sql, $this->conn);
		
		$yaml = '';
		
		if ($result) {
			//table header:
			$yaml = "\n$table:\n";
			//iterate over records: 
			while ($row = mysqli_fetch_assoc($result)) {
				if (isset($row['ClassName']) && isset($row['ID']))
					$identifier = $row['ClassName'] . $row['ID'] . ":";
				else
					$identifier = "-";
				
				$yaml .= "\t$identifier\n";
				
				//iterate over fields:
				foreach ($row as $key => $value) {
					
					//ignored fields:	
					if (isset($this->_ignore[strtolower($table)]) && 
						isset($this->_ignore[strtolower($table)][strtolower($key)]))
							continue;

					// Do have any newlines or line feeds?
					$literalFlag = (strpos($value, "\r") !== FALSE || 
							strpos($value, "\n") !== FALSE) ? "| " : "";
				
					// Output the key/value pair
					$yaml .= "\t\t{$key}: {$literalFlag}{$value}\n";
				} //column
				
				if (!$all) break;	//only dump first record
				
			} //row
		}
		
		return $yaml;
	}
	
}

?>